// Package store provides persistent storage for controller state
package store

import (
	"encoding/json"
	"os"
	"path/filepath"
	"sync"
	"time"

	"github.com/northshorenetworks/nswall/controller/internal/models"
)

// AgentStore manages agent state
type AgentStore struct {
	dataDir   string
	agents    map[string]*models.AgentStatus
	groups    map[string]*models.Group
	templates map[string]*models.ConfigTemplate
	mu        sync.RWMutex
}

// NewAgentStore creates a new agent store
func NewAgentStore(dataDir string) *AgentStore {
	s := &AgentStore{
		dataDir:   dataDir,
		agents:    make(map[string]*models.AgentStatus),
		groups:    make(map[string]*models.Group),
		templates: make(map[string]*models.ConfigTemplate),
	}

	// Load persisted state
	s.load()

	// Start background cleanup
	go s.cleanupLoop()

	return s
}

// UpdateAgent updates or creates an agent
func (s *AgentStore) UpdateAgent(status *models.AgentStatus) {
	s.mu.Lock()
	defer s.mu.Unlock()

	status.LastSeen = time.Now()
	status.Status = "online"

	// Preserve existing groups/tags if not provided
	if existing, ok := s.agents[status.ID]; ok {
		if len(status.Groups) == 0 {
			status.Groups = existing.Groups
		}
		if status.Tags == nil {
			status.Tags = existing.Tags
		}
	}

	s.agents[status.ID] = status
}

// GetAgent returns an agent by ID
func (s *AgentStore) GetAgent(id string) *models.AgentStatus {
	s.mu.RLock()
	defer s.mu.RUnlock()

	agent := s.agents[id]
	if agent != nil {
		// Update status based on last seen
		a := *agent
		if time.Since(a.LastSeen) > 2*time.Minute {
			a.Status = "offline"
		} else if time.Since(a.LastSeen) > 1*time.Minute {
			a.Status = "degraded"
		}
		return &a
	}
	return nil
}

// GetAgents returns all agents
func (s *AgentStore) GetAgents() []*models.AgentStatus {
	s.mu.RLock()
	defer s.mu.RUnlock()

	agents := make([]*models.AgentStatus, 0, len(s.agents))
	for _, agent := range s.agents {
		a := *agent
		if time.Since(a.LastSeen) > 2*time.Minute {
			a.Status = "offline"
		} else if time.Since(a.LastSeen) > 1*time.Minute {
			a.Status = "degraded"
		}
		agents = append(agents, &a)
	}
	return agents
}

// GetAgentsByGroup returns agents in a group
func (s *AgentStore) GetAgentsByGroup(groupName string) []*models.AgentStatus {
	s.mu.RLock()
	defer s.mu.RUnlock()

	group := s.groups[groupName]
	if group == nil {
		return nil
	}

	agents := make([]*models.AgentStatus, 0)
	for _, agent := range s.agents {
		// Check explicit IDs
		for _, id := range group.AgentIDs {
			if agent.ID == id {
				agents = append(agents, agent)
				continue
			}
		}
		// Check tag matching
		if group.Tags != nil && agent.Tags != nil {
			match := true
			for k, v := range group.Tags {
				if agent.Tags[k] != v {
					match = false
					break
				}
			}
			if match {
				agents = append(agents, agent)
			}
		}
	}
	return agents
}

// DeleteAgent removes an agent
func (s *AgentStore) DeleteAgent(id string) {
	s.mu.Lock()
	defer s.mu.Unlock()
	delete(s.agents, id)
}

// GetSummary returns fleet summary statistics
func (s *AgentStore) GetSummary() *models.FleetSummary {
	s.mu.RLock()
	defer s.mu.RUnlock()

	summary := &models.FleetSummary{}
	var totalCPU, totalMem float64

	for _, agent := range s.agents {
		summary.TotalAgents++

		if time.Since(agent.LastSeen) > 2*time.Minute {
			summary.OfflineAgents++
		} else if time.Since(agent.LastSeen) > 1*time.Minute {
			summary.DegradedAgents++
		} else {
			summary.OnlineAgents++
		}

		summary.TotalPFStates += agent.PFStates
		totalCPU += agent.CPULoad
		totalMem += agent.MemoryPercent
	}

	if summary.TotalAgents > 0 {
		summary.AvgCPULoad = totalCPU / float64(summary.TotalAgents)
		summary.AvgMemory = totalMem / float64(summary.TotalAgents)
	}

	return summary
}

// Group operations

func (s *AgentStore) GetGroup(name string) *models.Group {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.groups[name]
}

func (s *AgentStore) GetGroups() []*models.Group {
	s.mu.RLock()
	defer s.mu.RUnlock()

	groups := make([]*models.Group, 0, len(s.groups))
	for _, g := range s.groups {
		groups = append(groups, g)
	}
	return groups
}

func (s *AgentStore) SaveGroup(group *models.Group) {
	s.mu.Lock()
	defer s.mu.Unlock()

	if group.CreatedAt.IsZero() {
		group.CreatedAt = time.Now()
	}
	group.UpdatedAt = time.Now()
	s.groups[group.Name] = group
	s.persist()
}

func (s *AgentStore) DeleteGroup(name string) {
	s.mu.Lock()
	defer s.mu.Unlock()
	delete(s.groups, name)
	s.persist()
}

// Template operations

func (s *AgentStore) GetTemplate(name string) *models.ConfigTemplate {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.templates[name]
}

func (s *AgentStore) GetTemplates() []*models.ConfigTemplate {
	s.mu.RLock()
	defer s.mu.RUnlock()

	templates := make([]*models.ConfigTemplate, 0, len(s.templates))
	for _, t := range s.templates {
		templates = append(templates, t)
	}
	return templates
}

func (s *AgentStore) SaveTemplate(template *models.ConfigTemplate) {
	s.mu.Lock()
	defer s.mu.Unlock()

	if template.CreatedAt.IsZero() {
		template.CreatedAt = time.Now()
	}
	template.UpdatedAt = time.Now()
	s.templates[template.Name] = template
	s.persist()
}

func (s *AgentStore) DeleteTemplate(name string) {
	s.mu.Lock()
	defer s.mu.Unlock()
	delete(s.templates, name)
	s.persist()
}

// Persistence

func (s *AgentStore) persist() {
	data := struct {
		Groups    map[string]*models.Group          `json:"groups"`
		Templates map[string]*models.ConfigTemplate `json:"templates"`
	}{
		Groups:    s.groups,
		Templates: s.templates,
	}

	bytes, _ := json.MarshalIndent(data, "", "  ")
	os.WriteFile(filepath.Join(s.dataDir, "store.json"), bytes, 0600)
}

func (s *AgentStore) load() {
	bytes, err := os.ReadFile(filepath.Join(s.dataDir, "store.json"))
	if err != nil {
		return
	}

	var data struct {
		Groups    map[string]*models.Group          `json:"groups"`
		Templates map[string]*models.ConfigTemplate `json:"templates"`
	}

	if json.Unmarshal(bytes, &data) == nil {
		if data.Groups != nil {
			s.groups = data.Groups
		}
		if data.Templates != nil {
			s.templates = data.Templates
		}
	}
}

// cleanupLoop removes stale agents
func (s *AgentStore) cleanupLoop() {
	ticker := time.NewTicker(5 * time.Minute)
	defer ticker.Stop()

	for range ticker.C {
		s.mu.Lock()
		for id, agent := range s.agents {
			// Remove agents not seen in 24 hours
			if time.Since(agent.LastSeen) > 24*time.Hour {
				delete(s.agents, id)
			}
		}
		s.mu.Unlock()
	}
}
