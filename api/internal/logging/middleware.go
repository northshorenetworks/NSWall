// Package logging provides HTTP middleware for request logging
package logging

import (
	"net/http"
	"time"
)

// responseWriter wraps http.ResponseWriter to capture status code
type responseWriter struct {
	http.ResponseWriter
	statusCode int
	size       int
}

func (rw *responseWriter) WriteHeader(code int) {
	rw.statusCode = code
	rw.ResponseWriter.WriteHeader(code)
}

func (rw *responseWriter) Write(b []byte) (int, error) {
	size, err := rw.ResponseWriter.Write(b)
	rw.size += size
	return size, err
}

// RequestLogger returns middleware that logs HTTP requests
func RequestLogger(logger *Logger) func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			start := time.Now()

			// Create wrapped response writer
			wrapped := &responseWriter{
				ResponseWriter: w,
				statusCode:     http.StatusOK,
			}

			// Get or generate request ID
			requestID := r.Header.Get("X-Request-ID")
			if requestID == "" {
				requestID = generateRequestID()
			}

			// Create request-scoped logger
			reqLogger := logger.WithFields(map[string]interface{}{
				"request_id": requestID,
				"method":     r.Method,
				"path":       r.URL.Path,
				"client_ip":  getClientIP(r),
				"user_agent": r.UserAgent(),
			})

			// Add logger to context
			ctx := WithContext(r.Context(), reqLogger)
			r = r.WithContext(ctx)

			// Process request
			next.ServeHTTP(wrapped, r)

			// Calculate duration
			duration := time.Since(start)

			// Log the request
			reqLogger.WithFields(map[string]interface{}{
				"status_code": wrapped.statusCode,
				"duration_ms": float64(duration.Microseconds()) / 1000,
				"size":        wrapped.size,
			}).logRequest(wrapped.statusCode, r.Method, r.URL.Path, duration)
		})
	}
}

func (l *Logger) logRequest(status int, method, path string, duration time.Duration) {
	level := LevelInfo
	if status >= 500 {
		level = LevelError
	} else if status >= 400 {
		level = LevelWarn
	}

	msg := "HTTP Request"
	l.log(level, msg, nil)
}

// getClientIP extracts the real client IP from the request
func getClientIP(r *http.Request) string {
	// Check X-Forwarded-For header
	if xff := r.Header.Get("X-Forwarded-For"); xff != "" {
		return xff
	}
	// Check X-Real-IP header
	if xri := r.Header.Get("X-Real-IP"); xri != "" {
		return xri
	}
	return r.RemoteAddr
}

// generateRequestID creates a unique request ID
func generateRequestID() string {
	return time.Now().Format("20060102150405.000000")
}

// AccessLog is a simpler access log format (like nginx)
func AccessLog(logger *Logger) func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			start := time.Now()

			wrapped := &responseWriter{
				ResponseWriter: w,
				statusCode:     http.StatusOK,
			}

			next.ServeHTTP(wrapped, r)

			duration := time.Since(start)

			// Combined log format
			logger.Infof("%s %s %s %d %d %dms",
				getClientIP(r),
				r.Method,
				r.URL.Path,
				wrapped.statusCode,
				wrapped.size,
				duration.Milliseconds(),
			)
		})
	}
}

// RecoveryLogger logs panics
func RecoveryLogger(logger *Logger) func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			defer func() {
				if err := recover(); err != nil {
					logger.WithRequest(r).WithField("panic", true).Errorf("Panic recovered: %v", err)
					http.Error(w, "Internal Server Error", http.StatusInternalServerError)
				}
			}()
			next.ServeHTTP(w, r)
		})
	}
}
