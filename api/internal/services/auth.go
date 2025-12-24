package services

import (
	"context"
	"crypto/rand"
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
	"sync"
	"time"

	"github.com/northshorenetworks/nswall/api/internal/models"
)

// Role definitions
const (
	RoleAdmin    = "admin"
	RoleOperator = "operator"
	RoleViewer   = "viewer"
)

// AuthService handles authentication and authorization
type AuthService struct {
	dataDir     string
	users       map[string]*userRecord
	sessions    map[string]*models.Session
	apiKeys     map[string]*apiKeyRecord
	mu          sync.RWMutex
	sessionTTL  time.Duration
}

type userRecord struct {
	Username     string   `json:"username"`
	PasswordHash string   `json:"password_hash"`
	Role         string   `json:"role"`
	Enabled      bool     `json:"enabled"`
	APIKeys      []string `json:"api_keys"`
	CreatedAt    time.Time `json:"created_at"`
	LastLogin    time.Time `json:"last_login,omitempty"`
}

type apiKeyRecord struct {
	Key       string    `json:"key"`
	Name      string    `json:"name"`
	Username  string    `json:"username"`
	Role      string    `json:"role"`
	CreatedAt time.Time `json:"created_at"`
	ExpiresAt time.Time `json:"expires_at,omitempty"`
}

// NewAuthService creates a new AuthService
func NewAuthService(dataDir string) *AuthService {
	a := &AuthService{
		dataDir:    dataDir,
		users:      make(map[string]*userRecord),
		sessions:   make(map[string]*models.Session),
		apiKeys:    make(map[string]*apiKeyRecord),
		sessionTTL: 24 * time.Hour,
	}

	// Load users and API keys
	a.load()

	// Create default admin if no users exist
	if len(a.users) == 0 {
		a.createDefaultAdmin()
	}

	// Start session cleanup
	go a.cleanupSessions()

	return a
}

// Login authenticates a user and creates a session
func (a *AuthService) Login(ctx context.Context, username, password string) (*models.Session, error) {
	a.mu.Lock()
	defer a.mu.Unlock()

	user, ok := a.users[username]
	if !ok {
		return nil, fmt.Errorf("invalid credentials")
	}

	if !user.Enabled {
		return nil, fmt.Errorf("user disabled")
	}

	// Verify password
	hash := hashPassword(password)
	if hash != user.PasswordHash {
		return nil, fmt.Errorf("invalid credentials")
	}

	// Create session
	token := generateToken()
	session := &models.Session{
		Token:     token,
		Username:  username,
		Role:      user.Role,
		CreatedAt: time.Now(),
		ExpiresAt: time.Now().Add(a.sessionTTL),
	}

	a.sessions[token] = session
	user.LastLogin = time.Now()
	a.save()

	return session, nil
}

// Logout invalidates a session
func (a *AuthService) Logout(ctx context.Context, token string) error {
	a.mu.Lock()
	defer a.mu.Unlock()

	delete(a.sessions, token)
	return nil
}

// ValidateSession checks if a session is valid
func (a *AuthService) ValidateSession(token string) (*models.Session, error) {
	a.mu.RLock()
	defer a.mu.RUnlock()

	session, ok := a.sessions[token]
	if !ok {
		return nil, fmt.Errorf("invalid session")
	}

	if time.Now().After(session.ExpiresAt) {
		return nil, fmt.Errorf("session expired")
	}

	return session, nil
}

// ValidateAPIKey checks if an API key is valid
func (a *AuthService) ValidateAPIKey(key string) (*apiKeyRecord, error) {
	a.mu.RLock()
	defer a.mu.RUnlock()

	record, ok := a.apiKeys[key]
	if !ok {
		return nil, fmt.Errorf("invalid API key")
	}

	if !record.ExpiresAt.IsZero() && time.Now().After(record.ExpiresAt) {
		return nil, fmt.Errorf("API key expired")
	}

	// Check if user is still enabled
	user, ok := a.users[record.Username]
	if !ok || !user.Enabled {
		return nil, fmt.Errorf("user disabled")
	}

	return record, nil
}

// CheckPermission checks if a role has permission for an action
func (a *AuthService) CheckPermission(role, action string) bool {
	permissions := map[string][]string{
		RoleAdmin: {
			"read", "write", "delete", "admin",
			"config:read", "config:write",
			"firewall:read", "firewall:write",
			"services:read", "services:write",
			"users:read", "users:write",
		},
		RoleOperator: {
			"read", "write",
			"config:read", "config:write",
			"firewall:read", "firewall:write",
			"services:read", "services:write",
		},
		RoleViewer: {
			"read",
			"config:read",
			"firewall:read",
			"services:read",
		},
	}

	perms, ok := permissions[role]
	if !ok {
		return false
	}

	for _, p := range perms {
		if p == action {
			return true
		}
	}

	return false
}

// CreateUser creates a new user
func (a *AuthService) CreateUser(ctx context.Context, username, password, role string) error {
	a.mu.Lock()
	defer a.mu.Unlock()

	if _, exists := a.users[username]; exists {
		return fmt.Errorf("user already exists")
	}

	if role != RoleAdmin && role != RoleOperator && role != RoleViewer {
		return fmt.Errorf("invalid role")
	}

	a.users[username] = &userRecord{
		Username:     username,
		PasswordHash: hashPassword(password),
		Role:         role,
		Enabled:      true,
		CreatedAt:    time.Now(),
	}

	return a.save()
}

// DeleteUser deletes a user
func (a *AuthService) DeleteUser(ctx context.Context, username string) error {
	a.mu.Lock()
	defer a.mu.Unlock()

	if _, exists := a.users[username]; !exists {
		return fmt.Errorf("user not found")
	}

	// Delete user's API keys
	for key, record := range a.apiKeys {
		if record.Username == username {
			delete(a.apiKeys, key)
		}
	}

	// Delete user's sessions
	for token, session := range a.sessions {
		if session.Username == username {
			delete(a.sessions, token)
		}
	}

	delete(a.users, username)
	return a.save()
}

// UpdateUser updates a user
func (a *AuthService) UpdateUser(ctx context.Context, username string, updates map[string]interface{}) error {
	a.mu.Lock()
	defer a.mu.Unlock()

	user, exists := a.users[username]
	if !exists {
		return fmt.Errorf("user not found")
	}

	if password, ok := updates["password"].(string); ok && password != "" {
		user.PasswordHash = hashPassword(password)
	}

	if role, ok := updates["role"].(string); ok && role != "" {
		if role != RoleAdmin && role != RoleOperator && role != RoleViewer {
			return fmt.Errorf("invalid role")
		}
		user.Role = role
	}

	if enabled, ok := updates["enabled"].(bool); ok {
		user.Enabled = enabled
	}

	return a.save()
}

// GetUsers returns all users
func (a *AuthService) GetUsers(ctx context.Context) ([]models.User, error) {
	a.mu.RLock()
	defer a.mu.RUnlock()

	var users []models.User
	for _, u := range a.users {
		user := models.User{
			Username: u.Username,
			Role:     u.Role,
			Enabled:  u.Enabled,
		}
		for _, key := range u.APIKeys {
			// Mask API key
			if len(key) > 8 {
				user.APIKeys = append(user.APIKeys, key[:4]+"****"+key[len(key)-4:])
			}
		}
		users = append(users, user)
	}

	return users, nil
}

// CreateAPIKey creates a new API key for a user
func (a *AuthService) CreateAPIKey(ctx context.Context, username, name string, expiry time.Duration) (string, error) {
	a.mu.Lock()
	defer a.mu.Unlock()

	user, exists := a.users[username]
	if !exists {
		return "", fmt.Errorf("user not found")
	}

	key := generateAPIKey()
	record := &apiKeyRecord{
		Key:       key,
		Name:      name,
		Username:  username,
		Role:      user.Role,
		CreatedAt: time.Now(),
	}

	if expiry > 0 {
		record.ExpiresAt = time.Now().Add(expiry)
	}

	a.apiKeys[key] = record
	user.APIKeys = append(user.APIKeys, key)

	a.save()
	return key, nil
}

// DeleteAPIKey deletes an API key
func (a *AuthService) DeleteAPIKey(ctx context.Context, key string) error {
	a.mu.Lock()
	defer a.mu.Unlock()

	record, exists := a.apiKeys[key]
	if !exists {
		return fmt.Errorf("API key not found")
	}

	// Remove from user
	if user, ok := a.users[record.Username]; ok {
		var newKeys []string
		for _, k := range user.APIKeys {
			if k != key {
				newKeys = append(newKeys, k)
			}
		}
		user.APIKeys = newKeys
	}

	delete(a.apiKeys, key)
	return a.save()
}

// Helper functions

func (a *AuthService) createDefaultAdmin() {
	a.users["admin"] = &userRecord{
		Username:     "admin",
		PasswordHash: hashPassword("admin"),
		Role:         RoleAdmin,
		Enabled:      true,
		CreatedAt:    time.Now(),
	}
	a.save()
}

func (a *AuthService) load() {
	usersFile := filepath.Join(a.dataDir, "users.json")
	if data, err := os.ReadFile(usersFile); err == nil {
		json.Unmarshal(data, &a.users)
	}

	keysFile := filepath.Join(a.dataDir, "apikeys.json")
	if data, err := os.ReadFile(keysFile); err == nil {
		json.Unmarshal(data, &a.apiKeys)
	}
}

func (a *AuthService) save() error {
	if err := os.MkdirAll(a.dataDir, 0700); err != nil {
		return err
	}

	usersFile := filepath.Join(a.dataDir, "users.json")
	data, _ := json.MarshalIndent(a.users, "", "  ")
	if err := os.WriteFile(usersFile, data, 0600); err != nil {
		return err
	}

	keysFile := filepath.Join(a.dataDir, "apikeys.json")
	data, _ = json.MarshalIndent(a.apiKeys, "", "  ")
	return os.WriteFile(keysFile, data, 0600)
}

func (a *AuthService) cleanupSessions() {
	ticker := time.NewTicker(15 * time.Minute)
	for range ticker.C {
		a.mu.Lock()
		now := time.Now()
		for token, session := range a.sessions {
			if now.After(session.ExpiresAt) {
				delete(a.sessions, token)
			}
		}
		a.mu.Unlock()
	}
}

func hashPassword(password string) string {
	hash := sha256.Sum256([]byte(password))
	return hex.EncodeToString(hash[:])
}

func generateToken() string {
	b := make([]byte, 32)
	rand.Read(b)
	return hex.EncodeToString(b)
}

func generateAPIKey() string {
	b := make([]byte, 24)
	rand.Read(b)
	return "nswall_" + hex.EncodeToString(b)
}
