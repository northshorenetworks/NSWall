// Package middleware provides HTTP middleware for the NSWall API
package middleware

import (
	"context"
	"encoding/json"
	"net/http"
	"strings"
	"time"

	"github.com/northshorenetworks/nswall/api/internal/models"
	"github.com/northshorenetworks/nswall/api/internal/services"
)

type contextKey string

const (
	UserContextKey contextKey = "user"
	RoleContextKey contextKey = "role"
)

// JSONContentType sets JSON content type
func JSONContentType(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/json")
		next.ServeHTTP(w, r)
	})
}

// CORS adds CORS headers
func CORS(allowedOrigins []string) func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			origin := r.Header.Get("Origin")

			// Check if origin is allowed
			allowed := len(allowedOrigins) == 0 // Allow all if no origins specified
			for _, o := range allowedOrigins {
				if o == "*" || o == origin {
					allowed = true
					break
				}
			}

			if allowed && origin != "" {
				w.Header().Set("Access-Control-Allow-Origin", origin)
				w.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS")
				w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization, X-API-Key")
				w.Header().Set("Access-Control-Allow-Credentials", "true")
				w.Header().Set("Access-Control-Max-Age", "86400")
			}

			if r.Method == "OPTIONS" {
				w.WriteHeader(http.StatusOK)
				return
			}

			next.ServeHTTP(w, r)
		})
	}
}

// RateLimiter provides simple rate limiting
type RateLimiter struct {
	requests map[string][]time.Time
	limit    int
	window   time.Duration
}

// NewRateLimiter creates a new rate limiter
func NewRateLimiter(limit int, window time.Duration) *RateLimiter {
	rl := &RateLimiter{
		requests: make(map[string][]time.Time),
		limit:    limit,
		window:   window,
	}

	// Cleanup old entries periodically
	go func() {
		ticker := time.NewTicker(time.Minute)
		for range ticker.C {
			rl.cleanup()
		}
	}()

	return rl
}

func (rl *RateLimiter) cleanup() {
	now := time.Now()
	for ip, times := range rl.requests {
		var valid []time.Time
		for _, t := range times {
			if now.Sub(t) < rl.window {
				valid = append(valid, t)
			}
		}
		if len(valid) == 0 {
			delete(rl.requests, ip)
		} else {
			rl.requests[ip] = valid
		}
	}
}

// Middleware returns the rate limiting middleware
func (rl *RateLimiter) Middleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		ip := getClientIP(r)
		now := time.Now()

		// Clean old requests for this IP
		var valid []time.Time
		for _, t := range rl.requests[ip] {
			if now.Sub(t) < rl.window {
				valid = append(valid, t)
			}
		}

		if len(valid) >= rl.limit {
			writeError(w, http.StatusTooManyRequests, "Rate limit exceeded")
			return
		}

		rl.requests[ip] = append(valid, now)
		next.ServeHTTP(w, r)
	})
}

// APIKeyAuth validates API key authentication
func APIKeyAuth(auth *services.AuthService, staticKey string) func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			// Get API key from header or query
			key := r.Header.Get("X-API-Key")
			if key == "" {
				key = r.URL.Query().Get("api_key")
			}

			// Check static API key if configured
			if staticKey != "" && key == staticKey {
				ctx := context.WithValue(r.Context(), UserContextKey, "api")
				ctx = context.WithValue(ctx, RoleContextKey, services.RoleAdmin)
				next.ServeHTTP(w, r.WithContext(ctx))
				return
			}

			// Check dynamic API key
			if key != "" {
				record, err := auth.ValidateAPIKey(key)
				if err == nil {
					ctx := context.WithValue(r.Context(), UserContextKey, record.Username)
					ctx = context.WithValue(ctx, RoleContextKey, record.Role)
					next.ServeHTTP(w, r.WithContext(ctx))
					return
				}
			}

			// Check Bearer token
			authHeader := r.Header.Get("Authorization")
			if strings.HasPrefix(authHeader, "Bearer ") {
				token := strings.TrimPrefix(authHeader, "Bearer ")
				session, err := auth.ValidateSession(token)
				if err == nil {
					ctx := context.WithValue(r.Context(), UserContextKey, session.Username)
					ctx = context.WithValue(ctx, RoleContextKey, session.Role)
					next.ServeHTTP(w, r.WithContext(ctx))
					return
				}
			}

			writeError(w, http.StatusUnauthorized, "Invalid or missing authentication")
		})
	}
}

// RequireRole checks if the user has the required role
func RequireRole(auth *services.AuthService, action string) func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			role, ok := r.Context().Value(RoleContextKey).(string)
			if !ok {
				writeError(w, http.StatusForbidden, "Access denied")
				return
			}

			if !auth.CheckPermission(role, action) {
				writeError(w, http.StatusForbidden, "Insufficient permissions")
				return
			}

			next.ServeHTTP(w, r)
		})
	}
}

// OptionalAuth allows unauthenticated access but populates context if authenticated
func OptionalAuth(auth *services.AuthService, staticKey string) func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			ctx := r.Context()

			// Try to authenticate
			key := r.Header.Get("X-API-Key")
			if key == "" {
				key = r.URL.Query().Get("api_key")
			}

			if staticKey != "" && key == staticKey {
				ctx = context.WithValue(ctx, UserContextKey, "api")
				ctx = context.WithValue(ctx, RoleContextKey, services.RoleAdmin)
			} else if key != "" {
				if record, err := auth.ValidateAPIKey(key); err == nil {
					ctx = context.WithValue(ctx, UserContextKey, record.Username)
					ctx = context.WithValue(ctx, RoleContextKey, record.Role)
				}
			} else if authHeader := r.Header.Get("Authorization"); strings.HasPrefix(authHeader, "Bearer ") {
				token := strings.TrimPrefix(authHeader, "Bearer ")
				if session, err := auth.ValidateSession(token); err == nil {
					ctx = context.WithValue(ctx, UserContextKey, session.Username)
					ctx = context.WithValue(ctx, RoleContextKey, session.Role)
				}
			}

			next.ServeHTTP(w, r.WithContext(ctx))
		})
	}
}

// RequestLogger logs requests with timing
func RequestLogger(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		start := time.Now()

		// Wrap response writer to capture status
		wrapped := &responseWriter{ResponseWriter: w, status: http.StatusOK}

		next.ServeHTTP(wrapped, r)

		duration := time.Since(start)

		// Log format: timestamp method path status duration
		// This will be captured by the standard logger middleware
		_ = duration // TODO: integrate with structured logging
	})
}

type responseWriter struct {
	http.ResponseWriter
	status int
}

func (w *responseWriter) WriteHeader(status int) {
	w.status = status
	w.ResponseWriter.WriteHeader(status)
}

// Helper functions

func getClientIP(r *http.Request) string {
	// Check X-Forwarded-For header
	if xff := r.Header.Get("X-Forwarded-For"); xff != "" {
		ips := strings.Split(xff, ",")
		if len(ips) > 0 {
			return strings.TrimSpace(ips[0])
		}
	}

	// Check X-Real-IP header
	if xri := r.Header.Get("X-Real-IP"); xri != "" {
		return xri
	}

	// Fall back to RemoteAddr
	ip := r.RemoteAddr
	if idx := strings.LastIndex(ip, ":"); idx != -1 {
		ip = ip[:idx]
	}
	return ip
}

func writeError(w http.ResponseWriter, status int, message string) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	resp := models.Response{Success: false, Error: message}
	json.NewEncoder(w).Encode(resp)
}
