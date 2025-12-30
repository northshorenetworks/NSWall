// Package nats provides NATS server and client functionality
package nats

import (
	"fmt"
	"path/filepath"
	"time"

	"github.com/nats-io/nats-server/v2/server"
)

// Server wraps the embedded NATS server
type Server struct {
	ns *server.Server
}

// StartServer starts an embedded NATS server with JetStream
func StartServer(addr, clusterURL, dataDir string) (*Server, error) {
	opts := &server.Options{
		Host:           "0.0.0.0",
		Port:           4222,
		NoLog:          false,
		NoSigs:         true,
		MaxControlLine: 4096,
		MaxPayload:     1024 * 1024, // 1MB max message

		// JetStream for message persistence
		JetStream: true,
		StoreDir:  filepath.Join(dataDir, "jetstream"),
	}

	// Parse address
	if addr != "" {
		var host string
		var port int
		_, err := fmt.Sscanf(addr, "%s:%d", &host, &port)
		if err == nil {
			opts.Host = host
			opts.Port = port
		}
	}

	// Cluster configuration for HA
	if clusterURL != "" {
		opts.Cluster = server.ClusterOpts{
			Host: opts.Host,
			Port: opts.Port + 1000, // Cluster port = client port + 1000
		}
		opts.Routes = server.RoutesFromStr(clusterURL)
	}

	ns, err := server.NewServer(opts)
	if err != nil {
		return nil, fmt.Errorf("failed to create NATS server: %w", err)
	}

	// Start server in background
	go ns.Start()

	// Wait for server to be ready
	if !ns.ReadyForConnections(10 * time.Second) {
		return nil, fmt.Errorf("NATS server failed to start")
	}

	return &Server{ns: ns}, nil
}

// ClientURL returns the URL clients should use to connect
func (s *Server) ClientURL() string {
	return s.ns.ClientURL()
}

// Shutdown stops the NATS server
func (s *Server) Shutdown() {
	s.ns.Shutdown()
	s.ns.WaitForShutdown()
}
