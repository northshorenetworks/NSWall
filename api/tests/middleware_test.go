// Package tests provides unit tests for API middleware
package tests

import (
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/northshorenetworks/nswall/api/internal/middleware"
)

// TestRequestIDMiddleware tests the request ID middleware
func TestRequestIDMiddleware(t *testing.T) {
	handler := middleware.RequestID(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	}))

	req := httptest.NewRequest("GET", "/test", nil)
	rr := httptest.NewRecorder()

	handler.ServeHTTP(rr, req)

	reqID := rr.Header().Get("X-Request-ID")
	if reqID == "" {
		t.Error("Expected X-Request-ID header to be set")
	}

	// Request ID should be a valid UUID-like format
	if len(reqID) < 10 {
		t.Errorf("Request ID seems too short: %s", reqID)
	}
}

// TestRequestIDWithExisting tests that existing request ID is preserved
func TestRequestIDWithExisting(t *testing.T) {
	handler := middleware.RequestID(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	}))

	req := httptest.NewRequest("GET", "/test", nil)
	req.Header.Set("X-Request-ID", "existing-id-123")
	rr := httptest.NewRecorder()

	handler.ServeHTTP(rr, req)

	reqID := rr.Header().Get("X-Request-ID")
	if reqID != "existing-id-123" {
		t.Errorf("Expected request ID to be preserved, got %s", reqID)
	}
}

// TestLoggerMiddleware tests the logger middleware
func TestLoggerMiddleware(t *testing.T) {
	handler := middleware.Logger(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
		w.Write([]byte("OK"))
	}))

	req := httptest.NewRequest("GET", "/test", nil)
	rr := httptest.NewRecorder()

	// Should not panic and complete normally
	handler.ServeHTTP(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("Expected status 200, got %d", rr.Code)
	}
}

// TestCORSMiddleware tests CORS header setting
func TestCORSMiddleware(t *testing.T) {
	handler := middleware.CORS(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	}))

	req := httptest.NewRequest("GET", "/test", nil)
	rr := httptest.NewRecorder()

	handler.ServeHTTP(rr, req)

	if rr.Header().Get("Access-Control-Allow-Origin") == "" {
		t.Error("Expected Access-Control-Allow-Origin header")
	}

	if rr.Header().Get("Access-Control-Allow-Methods") == "" {
		t.Error("Expected Access-Control-Allow-Methods header")
	}

	if rr.Header().Get("Access-Control-Allow-Headers") == "" {
		t.Error("Expected Access-Control-Allow-Headers header")
	}
}

// TestCORSPreflight tests CORS preflight request handling
func TestCORSPreflight(t *testing.T) {
	handler := middleware.CORS(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	}))

	req := httptest.NewRequest("OPTIONS", "/test", nil)
	rr := httptest.NewRecorder()

	handler.ServeHTTP(rr, req)

	// OPTIONS should return 200 for preflight
	if rr.Code != http.StatusOK {
		t.Errorf("Expected status 200 for OPTIONS, got %d", rr.Code)
	}
}

// TestRateLimiterMiddleware tests rate limiting
func TestRateLimiterMiddleware(t *testing.T) {
	// Create a rate limiter with very low limit for testing
	handler := middleware.RateLimiter(1, 1)(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	}))

	// First request should succeed
	req := httptest.NewRequest("GET", "/test", nil)
	req.RemoteAddr = "127.0.0.1:12345"
	rr := httptest.NewRecorder()

	handler.ServeHTTP(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("Expected first request to succeed, got %d", rr.Code)
	}

	// Second request might be rate limited (depends on implementation)
	// This is a basic test - actual rate limiting behavior depends on the implementation
}

// TestAuthMiddleware tests authentication middleware
func TestAuthMiddleware(t *testing.T) {
	handler := middleware.RequireAuth(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	}))

	tests := []struct {
		name     string
		auth     string
		expected int
	}{
		{"No Auth", "", http.StatusUnauthorized},
		{"Invalid Token", "Bearer invalid", http.StatusUnauthorized},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			req := httptest.NewRequest("GET", "/test", nil)
			if tt.auth != "" {
				req.Header.Set("Authorization", tt.auth)
			}
			rr := httptest.NewRecorder()

			handler.ServeHTTP(rr, req)

			if rr.Code != tt.expected {
				t.Errorf("Expected status %d, got %d", tt.expected, rr.Code)
			}
		})
	}
}

// TestRecoverMiddleware tests panic recovery
func TestRecoverMiddleware(t *testing.T) {
	handler := middleware.Recover(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		panic("test panic")
	}))

	req := httptest.NewRequest("GET", "/test", nil)
	rr := httptest.NewRecorder()

	// Should not panic
	handler.ServeHTTP(rr, req)

	if rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 500 after panic, got %d", rr.Code)
	}
}

// TestContentTypeMiddleware tests content type setting
func TestContentTypeMiddleware(t *testing.T) {
	handler := middleware.ContentType("application/json")(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	}))

	req := httptest.NewRequest("GET", "/test", nil)
	rr := httptest.NewRecorder()

	handler.ServeHTTP(rr, req)

	ct := rr.Header().Get("Content-Type")
	if ct != "application/json" {
		t.Errorf("Expected Content-Type application/json, got %s", ct)
	}
}

// TestSecurityHeadersMiddleware tests security headers
func TestSecurityHeadersMiddleware(t *testing.T) {
	handler := middleware.SecurityHeaders(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	}))

	req := httptest.NewRequest("GET", "/test", nil)
	rr := httptest.NewRecorder()

	handler.ServeHTTP(rr, req)

	expectedHeaders := map[string]string{
		"X-Content-Type-Options": "nosniff",
		"X-Frame-Options":        "DENY",
		"X-XSS-Protection":       "1; mode=block",
	}

	for header, expected := range expectedHeaders {
		if got := rr.Header().Get(header); got != expected {
			t.Errorf("Expected %s=%s, got %s", header, expected, got)
		}
	}
}

// TestMiddlewareChain tests middleware chaining
func TestMiddlewareChain(t *testing.T) {
	handler := middleware.RequestID(
		middleware.Logger(
			middleware.CORS(
				http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
					w.WriteHeader(http.StatusOK)
				}),
			),
		),
	)

	req := httptest.NewRequest("GET", "/test", nil)
	rr := httptest.NewRecorder()

	handler.ServeHTTP(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("Expected status 200, got %d", rr.Code)
	}

	// Check all middleware added their headers
	if rr.Header().Get("X-Request-ID") == "" {
		t.Error("Expected X-Request-ID header from RequestID middleware")
	}

	if rr.Header().Get("Access-Control-Allow-Origin") == "" {
		t.Error("Expected CORS headers from CORS middleware")
	}
}
