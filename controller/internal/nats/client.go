package nats

import (
	"encoding/json"
	"fmt"
	"log"
	"sync"
	"time"

	"github.com/nats-io/nats.go"
	"github.com/northshorenetworks/nswall/controller/internal/models"
	"github.com/northshorenetworks/nswall/controller/internal/store"
)

// Subject patterns for NATS messaging
const (
	SubjectAgentStatus    = "nswall.agent.*.status"      // Agent publishes status
	SubjectAgentStatusFmt = "nswall.agent.%s.status"     // Format for specific agent
	SubjectAgentConfig    = "nswall.agent.%s.config"     // Push config to agent
	SubjectAgentCommand   = "nswall.agent.%s.command"    // Send command to agent
	SubjectBroadcastConfig = "nswall.broadcast.config"   // Config to all agents
	SubjectBroadcastCmd    = "nswall.broadcast.command"  // Command to all agents
	SubjectEvents          = "nswall.events"             // Event stream
)

// Client handles NATS messaging for the controller
type Client struct {
	nc         *nats.Conn
	js         nats.JetStreamContext
	store      *store.AgentStore
	subs       []*nats.Subscription
	eventsCh   chan models.Event
	mu         sync.RWMutex
	eventSubs  []chan models.Event
}

// NewClient creates a new NATS client
func NewClient(url string, agentStore *store.AgentStore) (*Client, error) {
	nc, err := nats.Connect(url,
		nats.ReconnectWait(time.Second),
		nats.MaxReconnects(-1), // Unlimited reconnects
		nats.DisconnectErrHandler(func(nc *nats.Conn, err error) {
			log.Printf("NATS disconnected: %v", err)
		}),
		nats.ReconnectHandler(func(nc *nats.Conn) {
			log.Printf("NATS reconnected to %s", nc.ConnectedUrl())
		}),
	)
	if err != nil {
		return nil, fmt.Errorf("failed to connect to NATS: %w", err)
	}

	// Get JetStream context
	js, err := nc.JetStream()
	if err != nil {
		nc.Close()
		return nil, fmt.Errorf("failed to get JetStream context: %w", err)
	}

	// Create streams for persistence
	_, err = js.AddStream(&nats.StreamConfig{
		Name:     "NSWALL_EVENTS",
		Subjects: []string{SubjectEvents},
		Storage:  nats.FileStorage,
		MaxAge:   7 * 24 * time.Hour, // Keep events for 7 days
		MaxBytes: 100 * 1024 * 1024,  // 100MB max
	})
	if err != nil && err != nats.ErrStreamNameAlreadyInUse {
		log.Printf("Warning: failed to create events stream: %v", err)
	}

	return &Client{
		nc:        nc,
		js:        js,
		store:     agentStore,
		eventsCh:  make(chan models.Event, 1000),
		eventSubs: make([]chan models.Event, 0),
	}, nil
}

// Close closes the NATS connection
func (c *Client) Close() {
	for _, sub := range c.subs {
		sub.Unsubscribe()
	}
	c.nc.Close()
}

// SubscribeToAgents subscribes to agent status messages
func (c *Client) SubscribeToAgents() error {
	// Subscribe to agent status updates
	sub, err := c.nc.Subscribe(SubjectAgentStatus, func(msg *nats.Msg) {
		var status models.AgentStatus
		if err := json.Unmarshal(msg.Data, &status); err != nil {
			log.Printf("Failed to parse agent status: %v", err)
			return
		}

		// Update agent in store
		c.store.UpdateAgent(&status)

		// Publish event
		c.publishEvent(models.Event{
			Type:      "agent_status",
			AgentID:   status.ID,
			Timestamp: time.Now(),
			Data:      status,
		})

		// Acknowledge if requested
		if msg.Reply != "" {
			msg.Respond([]byte(`{"ack":true}`))
		}
	})
	if err != nil {
		return fmt.Errorf("failed to subscribe to agent status: %w", err)
	}
	c.subs = append(c.subs, sub)

	log.Printf("Subscribed to agent status updates")
	return nil
}

// SendConfig sends configuration to a specific agent
func (c *Client) SendConfig(agentID string, config *models.AgentConfig) error {
	data, err := json.Marshal(config)
	if err != nil {
		return err
	}

	subject := fmt.Sprintf(SubjectAgentConfig, agentID)
	resp, err := c.nc.Request(subject, data, 30*time.Second)
	if err != nil {
		return fmt.Errorf("failed to send config: %w", err)
	}

	var result struct {
		Success bool   `json:"success"`
		Error   string `json:"error,omitempty"`
	}
	if err := json.Unmarshal(resp.Data, &result); err != nil {
		return err
	}

	if !result.Success {
		return fmt.Errorf("agent rejected config: %s", result.Error)
	}

	c.publishEvent(models.Event{
		Type:      "config_pushed",
		AgentID:   agentID,
		Timestamp: time.Now(),
		Data:      map[string]interface{}{"config_hash": config.Hash},
	})

	return nil
}

// SendCommand sends a command to a specific agent
func (c *Client) SendCommand(agentID string, cmd *models.Command) (*models.CommandResult, error) {
	data, err := json.Marshal(cmd)
	if err != nil {
		return nil, err
	}

	subject := fmt.Sprintf(SubjectAgentCommand, agentID)
	timeout := 30 * time.Second
	if cmd.Timeout > 0 {
		timeout = time.Duration(cmd.Timeout) * time.Second
	}

	resp, err := c.nc.Request(subject, data, timeout)
	if err != nil {
		return nil, fmt.Errorf("command failed: %w", err)
	}

	var result models.CommandResult
	if err := json.Unmarshal(resp.Data, &result); err != nil {
		return nil, err
	}

	c.publishEvent(models.Event{
		Type:      "command_executed",
		AgentID:   agentID,
		Timestamp: time.Now(),
		Data: map[string]interface{}{
			"command": cmd.Command,
			"success": result.Success,
		},
	})

	return &result, nil
}

// BroadcastConfig sends configuration to all agents
func (c *Client) BroadcastConfig(config *models.AgentConfig) error {
	data, err := json.Marshal(config)
	if err != nil {
		return err
	}

	if err := c.nc.Publish(SubjectBroadcastConfig, data); err != nil {
		return fmt.Errorf("failed to broadcast config: %w", err)
	}

	c.publishEvent(models.Event{
		Type:      "config_broadcast",
		Timestamp: time.Now(),
		Data:      map[string]interface{}{"config_hash": config.Hash},
	})

	return nil
}

// BroadcastCommand sends a command to all agents
func (c *Client) BroadcastCommand(cmd *models.Command) error {
	data, err := json.Marshal(cmd)
	if err != nil {
		return err
	}

	if err := c.nc.Publish(SubjectBroadcastCmd, data); err != nil {
		return fmt.Errorf("failed to broadcast command: %w", err)
	}

	c.publishEvent(models.Event{
		Type:      "command_broadcast",
		Timestamp: time.Now(),
		Data:      map[string]interface{}{"command": cmd.Command},
	})

	return nil
}

// publishEvent publishes an event to JetStream and notifies subscribers
func (c *Client) publishEvent(event models.Event) {
	data, _ := json.Marshal(event)

	// Publish to JetStream for persistence
	c.js.Publish(SubjectEvents, data)

	// Notify local subscribers
	c.mu.RLock()
	for _, ch := range c.eventSubs {
		select {
		case ch <- event:
		default:
			// Skip if channel is full
		}
	}
	c.mu.RUnlock()
}

// SubscribeEvents returns a channel for receiving events
func (c *Client) SubscribeEvents() chan models.Event {
	ch := make(chan models.Event, 100)
	c.mu.Lock()
	c.eventSubs = append(c.eventSubs, ch)
	c.mu.Unlock()
	return ch
}

// UnsubscribeEvents removes an event subscription
func (c *Client) UnsubscribeEvents(ch chan models.Event) {
	c.mu.Lock()
	defer c.mu.Unlock()
	for i, sub := range c.eventSubs {
		if sub == ch {
			c.eventSubs = append(c.eventSubs[:i], c.eventSubs[i+1:]...)
			close(ch)
			return
		}
	}
}

// GetRecentEvents retrieves recent events from JetStream
func (c *Client) GetRecentEvents(limit int) ([]models.Event, error) {
	sub, err := c.js.PullSubscribe(SubjectEvents, "controller-events",
		nats.StartTime(time.Now().Add(-24*time.Hour)),
	)
	if err != nil {
		return nil, err
	}
	defer sub.Unsubscribe()

	msgs, err := sub.Fetch(limit, nats.MaxWait(2*time.Second))
	if err != nil && err != nats.ErrTimeout {
		return nil, err
	}

	events := make([]models.Event, 0, len(msgs))
	for _, msg := range msgs {
		var event models.Event
		if err := json.Unmarshal(msg.Data, &event); err == nil {
			events = append(events, event)
		}
		msg.Ack()
	}

	return events, nil
}
