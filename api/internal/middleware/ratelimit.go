package middleware

import (
	"encoding/json"
	"net/http"
	"sync"
	"time"
)

// RateLimiter provides rate limiting for API endpoints
type RateLimiter struct {
	requests map[string]*clientRequests
	mu       sync.RWMutex
	config   RateLimitConfig
}

// RateLimitConfig configures rate limiting behavior
type RateLimitConfig struct {
	// General API rate limit
	RequestsPerMinute int `json:"requests_per_minute"`

	// Login endpoint rate limit (stricter)
	LoginAttemptsPerMinute int `json:"login_attempts_per_minute"`

	// Burst allowance
	BurstSize int `json:"burst_size"`

	// Whitelist IPs (no rate limit)
	WhitelistIPs []string `json:"whitelist_ips"`

	// Enable rate limiting
	Enabled bool `json:"enabled"`
}

type clientRequests struct {
	times       []time.Time
	loginTimes  []time.Time
	blocked     bool
	blockedUntil time.Time
}

// NewRateLimiter creates a new rate limiter
func NewRateLimiter(config RateLimitConfig) *RateLimiter {
	if config.RequestsPerMinute == 0 {
		config.RequestsPerMinute = 100
	}
	if config.LoginAttemptsPerMinute == 0 {
		config.LoginAttemptsPerMinute = 5
	}
	if config.BurstSize == 0 {
		config.BurstSize = 20
	}

	rl := &RateLimiter{
		requests: make(map[string]*clientRequests),
		config:   config,
	}

	// Start cleanup goroutine
	go rl.cleanup()

	return rl
}

// cleanup removes old entries periodically
func (rl *RateLimiter) cleanup() {
	ticker := time.NewTicker(5 * time.Minute)
	for range ticker.C {
		rl.mu.Lock()
		now := time.Now()
		cutoff := now.Add(-2 * time.Minute)

		for ip, client := range rl.requests {
			// Remove old request times
			newTimes := make([]time.Time, 0)
			for _, t := range client.times {
				if t.After(cutoff) {
					newTimes = append(newTimes, t)
				}
			}
			client.times = newTimes

			newLoginTimes := make([]time.Time, 0)
			for _, t := range client.loginTimes {
				if t.After(cutoff) {
					newLoginTimes = append(newLoginTimes, t)
				}
			}
			client.loginTimes = newLoginTimes

			// Remove unblocked entries with no recent requests
			if len(client.times) == 0 && len(client.loginTimes) == 0 && !client.blocked {
				delete(rl.requests, ip)
			}

			// Unblock if time has passed
			if client.blocked && now.After(client.blockedUntil) {
				client.blocked = false
			}
		}
		rl.mu.Unlock()
	}
}

// Middleware returns an HTTP middleware for rate limiting
func (rl *RateLimiter) Middleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if !rl.config.Enabled {
			next.ServeHTTP(w, r)
			return
		}

		ip := getClientIP(r)

		// Check whitelist
		if rl.isWhitelisted(ip) {
			next.ServeHTTP(w, r)
			return
		}

		// Check if currently blocked
		if rl.isBlocked(ip) {
			rl.respondTooManyRequests(w, ip)
			return
		}

		// Determine if this is a login request
		isLogin := r.URL.Path == "/api/v1/auth/login" && r.Method == "POST"

		// Check rate limit
		if !rl.allow(ip, isLogin) {
			rl.block(ip, 5*time.Minute)
			rl.respondTooManyRequests(w, ip)
			return
		}

		// Add rate limit headers
		remaining, resetTime := rl.getStatus(ip, isLogin)
		w.Header().Set("X-RateLimit-Limit", itoa(rl.config.RequestsPerMinute))
		w.Header().Set("X-RateLimit-Remaining", itoa(remaining))
		w.Header().Set("X-RateLimit-Reset", itoa(int(resetTime.Unix())))

		next.ServeHTTP(w, r)
	})
}

func (rl *RateLimiter) isWhitelisted(ip string) bool {
	for _, whitelisted := range rl.config.WhitelistIPs {
		if whitelisted == ip {
			return true
		}
	}
	return false
}

func (rl *RateLimiter) isBlocked(ip string) bool {
	rl.mu.RLock()
	defer rl.mu.RUnlock()

	client, exists := rl.requests[ip]
	if !exists {
		return false
	}

	return client.blocked && time.Now().Before(client.blockedUntil)
}

func (rl *RateLimiter) block(ip string, duration time.Duration) {
	rl.mu.Lock()
	defer rl.mu.Unlock()

	client, exists := rl.requests[ip]
	if !exists {
		client = &clientRequests{}
		rl.requests[ip] = client
	}

	client.blocked = true
	client.blockedUntil = time.Now().Add(duration)
}

func (rl *RateLimiter) allow(ip string, isLogin bool) bool {
	rl.mu.Lock()
	defer rl.mu.Unlock()

	now := time.Now()
	cutoff := now.Add(-time.Minute)

	client, exists := rl.requests[ip]
	if !exists {
		client = &clientRequests{
			times:      make([]time.Time, 0),
			loginTimes: make([]time.Time, 0),
		}
		rl.requests[ip] = client
	}

	// Clean old entries
	newTimes := make([]time.Time, 0)
	for _, t := range client.times {
		if t.After(cutoff) {
			newTimes = append(newTimes, t)
		}
	}
	client.times = newTimes

	if isLogin {
		newLoginTimes := make([]time.Time, 0)
		for _, t := range client.loginTimes {
			if t.After(cutoff) {
				newLoginTimes = append(newLoginTimes, t)
			}
		}
		client.loginTimes = newLoginTimes

		// Check login rate limit
		if len(client.loginTimes) >= rl.config.LoginAttemptsPerMinute {
			return false
		}
		client.loginTimes = append(client.loginTimes, now)
	}

	// Check general rate limit
	if len(client.times) >= rl.config.RequestsPerMinute+rl.config.BurstSize {
		return false
	}

	client.times = append(client.times, now)
	return true
}

func (rl *RateLimiter) getStatus(ip string, isLogin bool) (remaining int, resetTime time.Time) {
	rl.mu.RLock()
	defer rl.mu.RUnlock()

	client, exists := rl.requests[ip]
	if !exists {
		return rl.config.RequestsPerMinute, time.Now().Add(time.Minute)
	}

	limit := rl.config.RequestsPerMinute
	count := len(client.times)

	if isLogin {
		limit = rl.config.LoginAttemptsPerMinute
		count = len(client.loginTimes)
	}

	remaining = limit - count
	if remaining < 0 {
		remaining = 0
	}

	// Find earliest request time
	if len(client.times) > 0 {
		resetTime = client.times[0].Add(time.Minute)
	} else {
		resetTime = time.Now().Add(time.Minute)
	}

	return remaining, resetTime
}

func (rl *RateLimiter) respondTooManyRequests(w http.ResponseWriter, ip string) {
	rl.mu.RLock()
	client := rl.requests[ip]
	retryAfter := 60
	if client != nil && client.blocked {
		retryAfter = int(time.Until(client.blockedUntil).Seconds())
		if retryAfter < 0 {
			retryAfter = 60
		}
	}
	rl.mu.RUnlock()

	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Retry-After", itoa(retryAfter))
	w.WriteHeader(http.StatusTooManyRequests)

	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": false,
		"error":   "Rate limit exceeded. Please try again later.",
		"retry_after": retryAfter,
	})
}

func getClientIP(r *http.Request) string {
	// Check X-Forwarded-For header
	xff := r.Header.Get("X-Forwarded-For")
	if xff != "" {
		// Take the first IP
		for i := 0; i < len(xff); i++ {
			if xff[i] == ',' {
				return xff[:i]
			}
		}
		return xff
	}

	// Check X-Real-IP header
	xri := r.Header.Get("X-Real-IP")
	if xri != "" {
		return xri
	}

	// Fall back to RemoteAddr
	ip := r.RemoteAddr
	// Remove port
	for i := len(ip) - 1; i >= 0; i-- {
		if ip[i] == ':' {
			return ip[:i]
		}
	}
	return ip
}

func itoa(n int) string {
	if n == 0 {
		return "0"
	}

	negative := n < 0
	if negative {
		n = -n
	}

	var buf [20]byte
	i := len(buf)
	for n > 0 {
		i--
		buf[i] = byte(n%10) + '0'
		n /= 10
	}

	if negative {
		i--
		buf[i] = '-'
	}

	return string(buf[i:])
}

// GetConfig returns the current rate limit configuration
func (rl *RateLimiter) GetConfig() RateLimitConfig {
	return rl.config
}

// UpdateConfig updates the rate limit configuration
func (rl *RateLimiter) UpdateConfig(config RateLimitConfig) {
	rl.mu.Lock()
	defer rl.mu.Unlock()
	rl.config = config
}

// GetStats returns rate limiting statistics
func (rl *RateLimiter) GetStats() map[string]interface{} {
	rl.mu.RLock()
	defer rl.mu.RUnlock()

	blocked := 0
	active := len(rl.requests)

	for _, client := range rl.requests {
		if client.blocked && time.Now().Before(client.blockedUntil) {
			blocked++
		}
	}

	return map[string]interface{}{
		"active_clients":  active,
		"blocked_clients": blocked,
		"config":          rl.config,
	}
}

// UnblockIP manually unblocks an IP address
func (rl *RateLimiter) UnblockIP(ip string) {
	rl.mu.Lock()
	defer rl.mu.Unlock()

	if client, exists := rl.requests[ip]; exists {
		client.blocked = false
		client.blockedUntil = time.Time{}
	}
}
