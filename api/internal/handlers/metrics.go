package handlers

import (
	"net/http"

	"github.com/northshorenetworks/nswall/api/internal/services"
)

// MetricsHandler handles Prometheus metrics
type MetricsHandler struct {
	metrics *services.MetricsService
}

// NewMetricsHandler creates a new MetricsHandler
func NewMetricsHandler(nshPath string) *MetricsHandler {
	return &MetricsHandler{
		metrics: services.NewMetricsService(nshPath),
	}
}

// GetMetrics returns Prometheus metrics
func (m *MetricsHandler) GetMetrics(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "text/plain; version=0.0.4; charset=utf-8")
	w.Write([]byte(m.metrics.GetMetrics()))
}

// Middleware returns a middleware that counts requests
func (m *MetricsHandler) Middleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		m.metrics.IncrementRequests()
		next.ServeHTTP(w, r)
	})
}
