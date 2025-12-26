// Package handlers provides HTTP handlers for the NSWall API
package handlers

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"strings"
	"sync"
	"time"
)

// AIProvider represents supported AI providers
type AIProvider string

const (
	ProviderAnthropic AIProvider = "anthropic"
	ProviderOpenAI    AIProvider = "openai"
	ProviderOllama    AIProvider = "ollama"
	ProviderAzure     AIProvider = "azure"
	ProviderGoogle    AIProvider = "google"
	ProviderCustom    AIProvider = "custom"
)

// AIConfig holds the AI configuration
type AIConfig struct {
	Provider    AIProvider `json:"provider"`
	Model       string     `json:"model"`
	APIKey      string     `json:"api_key,omitempty"`
	APIEndpoint string     `json:"api_endpoint,omitempty"`
	MaxTokens   int        `json:"max_tokens"`
	Temperature float64    `json:"temperature"`
}

// AIAnalyzeRequest represents an AI analysis request
type AIAnalyzeRequest struct {
	Query   string                   `json:"query"`
	Context string                   `json:"context"`
	Type    string                   `json:"type"` // logs, tcpdump, netstat, firewall, sysinfo
	History []map[string]interface{} `json:"history"`
}

// AIAnalyzeResponse represents an AI analysis response
type AIAnalyzeResponse struct {
	Response string `json:"response"`
	Model    string `json:"model"`
	Provider string `json:"provider"`
	Tokens   int    `json:"tokens,omitempty"`
}

// ModelInfo represents available model information
type ModelInfo struct {
	ID          string `json:"id"`
	Name        string `json:"name"`
	Description string `json:"description,omitempty"`
	MaxTokens   int    `json:"max_tokens,omitempty"`
}

// ProviderInfo represents AI provider information
type ProviderInfo struct {
	ID           string      `json:"id"`
	Name         string      `json:"name"`
	Description  string      `json:"description"`
	RequiresKey  bool        `json:"requires_key"`
	DefaultURL   string      `json:"default_url"`
	Models       []ModelInfo `json:"models"`
	Configurable bool        `json:"configurable"`
}

// AIHandler handles AI-related endpoints
type AIHandler struct {
	mu     sync.RWMutex
	config AIConfig
}

// NewAIHandler creates a new AI handler
func NewAIHandler() *AIHandler {
	h := &AIHandler{}
	h.loadConfig()
	return h
}

func (h *AIHandler) loadConfig() {
	h.config = AIConfig{
		Provider:    AIProvider(getEnvDefault("AI_PROVIDER", "anthropic")),
		Model:       getEnvDefault("AI_MODEL", "claude-3-haiku-20240307"),
		APIKey:      getEnvDefault("AI_API_KEY", os.Getenv("ANTHROPIC_API_KEY")),
		APIEndpoint: getEnvDefault("AI_API_URL", ""),
		MaxTokens:   4096,
		Temperature: 0.7,
	}

	// Provider-specific defaults
	if h.config.APIKey == "" {
		switch h.config.Provider {
		case ProviderAnthropic:
			h.config.APIKey = os.Getenv("ANTHROPIC_API_KEY")
		case ProviderOpenAI:
			h.config.APIKey = os.Getenv("OPENAI_API_KEY")
		case ProviderAzure:
			h.config.APIKey = os.Getenv("AZURE_OPENAI_KEY")
		case ProviderGoogle:
			h.config.APIKey = os.Getenv("GOOGLE_AI_KEY")
		}
	}
}

func getEnvDefault(key, defaultVal string) string {
	if val := os.Getenv(key); val != "" {
		return val
	}
	return defaultVal
}

// GetProviders returns list of supported AI providers
func (h *AIHandler) GetProviders(w http.ResponseWriter, r *http.Request) {
	providers := []ProviderInfo{
		{
			ID:          "anthropic",
			Name:        "Anthropic Claude",
			Description: "Claude AI models from Anthropic",
			RequiresKey: true,
			DefaultURL:  "https://api.anthropic.com/v1/messages",
			Models: []ModelInfo{
				{ID: "claude-opus-4-20250514", Name: "Claude Opus 4", Description: "Most capable, best for complex analysis", MaxTokens: 32000},
				{ID: "claude-sonnet-4-20250514", Name: "Claude Sonnet 4", Description: "Balanced performance and speed", MaxTokens: 16000},
				{ID: "claude-3-5-sonnet-20241022", Name: "Claude 3.5 Sonnet", Description: "Fast and capable", MaxTokens: 8192},
				{ID: "claude-3-haiku-20240307", Name: "Claude 3 Haiku", Description: "Fastest, good for quick analysis", MaxTokens: 4096},
			},
			Configurable: true,
		},
		{
			ID:          "openai",
			Name:        "OpenAI",
			Description: "GPT models from OpenAI",
			RequiresKey: true,
			DefaultURL:  "https://api.openai.com/v1/chat/completions",
			Models: []ModelInfo{
				{ID: "gpt-4o", Name: "GPT-4o", Description: "Most capable GPT-4 model", MaxTokens: 16384},
				{ID: "gpt-4o-mini", Name: "GPT-4o Mini", Description: "Smaller, faster GPT-4", MaxTokens: 16384},
				{ID: "gpt-4-turbo", Name: "GPT-4 Turbo", Description: "Fast GPT-4 with large context", MaxTokens: 4096},
				{ID: "gpt-3.5-turbo", Name: "GPT-3.5 Turbo", Description: "Fast and economical", MaxTokens: 4096},
			},
			Configurable: true,
		},
		{
			ID:          "ollama",
			Name:        "Ollama (Local)",
			Description: "Run models locally with Ollama",
			RequiresKey: false,
			DefaultURL:  "http://localhost:11434/api/chat",
			Models: []ModelInfo{
				{ID: "llama3.2", Name: "Llama 3.2", Description: "Meta's Llama 3.2 model"},
				{ID: "mistral", Name: "Mistral 7B", Description: "Fast and capable"},
				{ID: "codellama", Name: "Code Llama", Description: "Optimized for code"},
				{ID: "mixtral", Name: "Mixtral 8x7B", Description: "Mixture of experts model"},
				{ID: "deepseek-coder", Name: "DeepSeek Coder", Description: "Coding specialist"},
				{ID: "qwen2.5", Name: "Qwen 2.5", Description: "Alibaba's Qwen model"},
			},
			Configurable: true,
		},
		{
			ID:          "azure",
			Name:        "Azure OpenAI",
			Description: "OpenAI models hosted on Azure",
			RequiresKey: true,
			DefaultURL:  "",
			Models: []ModelInfo{
				{ID: "gpt-4o", Name: "GPT-4o", Description: "GPT-4o on Azure"},
				{ID: "gpt-4", Name: "GPT-4", Description: "GPT-4 on Azure"},
			},
			Configurable: true,
		},
		{
			ID:          "google",
			Name:        "Google AI (Gemini)",
			Description: "Gemini models from Google",
			RequiresKey: true,
			DefaultURL:  "https://generativelanguage.googleapis.com/v1beta/models",
			Models: []ModelInfo{
				{ID: "gemini-1.5-pro", Name: "Gemini 1.5 Pro", Description: "Most capable Gemini"},
				{ID: "gemini-1.5-flash", Name: "Gemini 1.5 Flash", Description: "Fast Gemini model"},
				{ID: "gemini-2.0-flash-exp", Name: "Gemini 2.0 Flash", Description: "Latest experimental"},
			},
			Configurable: true,
		},
		{
			ID:          "custom",
			Name:        "Custom Endpoint",
			Description: "Any OpenAI-compatible API endpoint",
			RequiresKey: false,
			DefaultURL:  "",
			Models:      []ModelInfo{},
			Configurable: true,
		},
	}

	jsonResponse(w, map[string]interface{}{
		"success": true,
		"data":    providers,
	})
}

// GetConfig returns current AI configuration (without exposing API key)
func (h *AIHandler) GetConfig(w http.ResponseWriter, r *http.Request) {
	h.mu.RLock()
	defer h.mu.RUnlock()

	config := map[string]interface{}{
		"provider":     h.config.Provider,
		"model":        h.config.Model,
		"api_endpoint": h.config.APIEndpoint,
		"max_tokens":   h.config.MaxTokens,
		"temperature":  h.config.Temperature,
		"has_api_key":  h.config.APIKey != "",
	}

	jsonResponse(w, map[string]interface{}{
		"success": true,
		"data":    config,
	})
}

// UpdateConfig updates AI configuration
func (h *AIHandler) UpdateConfig(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Provider    string  `json:"provider"`
		Model       string  `json:"model"`
		APIKey      string  `json:"api_key,omitempty"`
		APIEndpoint string  `json:"api_endpoint,omitempty"`
		MaxTokens   int     `json:"max_tokens,omitempty"`
		Temperature float64 `json:"temperature,omitempty"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonError(w, "Invalid request body", http.StatusBadRequest)
		return
	}

	h.mu.Lock()
	defer h.mu.Unlock()

	if req.Provider != "" {
		h.config.Provider = AIProvider(req.Provider)
	}
	if req.Model != "" {
		h.config.Model = req.Model
	}
	if req.APIKey != "" {
		h.config.APIKey = req.APIKey
	}
	if req.APIEndpoint != "" {
		h.config.APIEndpoint = req.APIEndpoint
	}
	if req.MaxTokens > 0 {
		h.config.MaxTokens = req.MaxTokens
	}
	if req.Temperature > 0 {
		h.config.Temperature = req.Temperature
	}

	jsonResponse(w, map[string]interface{}{
		"success": true,
		"message": "AI configuration updated",
	})
}

// TestConnection tests the AI connection
func (h *AIHandler) TestConnection(w http.ResponseWriter, r *http.Request) {
	h.mu.RLock()
	config := h.config
	h.mu.RUnlock()

	messages := []map[string]interface{}{
		{"role": "user", "content": "Say 'Connection successful' and nothing else."},
	}

	response, err := h.callAIWithConfig(messages, config)
	if err != nil {
		jsonResponse(w, map[string]interface{}{
			"success": false,
			"error":   err.Error(),
		})
		return
	}

	jsonResponse(w, map[string]interface{}{
		"success":  true,
		"message":  "Connection successful",
		"response": response,
		"model":    config.Model,
		"provider": config.Provider,
	})
}

// Analyze handles POST /api/v1/ai/analyze
func (h *AIHandler) Analyze(w http.ResponseWriter, r *http.Request) {
	var req AIAnalyzeRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonError(w, "Invalid request body", http.StatusBadRequest)
		return
	}

	if req.Query == "" {
		jsonError(w, "Query is required", http.StatusBadRequest)
		return
	}

	h.mu.RLock()
	config := h.config
	h.mu.RUnlock()

	// Build system prompt based on context type
	systemPrompt := h.buildSystemPrompt(req.Type)

	// Build messages with context
	messages := h.buildMessages(req, systemPrompt)

	// Call AI API
	response, err := h.callAIWithConfig(messages, config)
	if err != nil {
		jsonError(w, fmt.Sprintf("AI analysis failed: %v", err), http.StatusInternalServerError)
		return
	}

	jsonResponse(w, map[string]interface{}{
		"success": true,
		"data": AIAnalyzeResponse{
			Response: response,
			Model:    config.Model,
			Provider: string(config.Provider),
		},
	})
}

func (h *AIHandler) buildSystemPrompt(contextType string) string {
	base := `You are an expert network security analyst for NSWall, an OpenBSD-based firewall system.
You analyze network traffic, logs, and security events to identify threats, anomalies, and provide recommendations.

Key expertise:
- OpenBSD pf (packet filter) firewall rules and logs
- Network traffic analysis (TCP/IP, protocols, common attacks)
- Security incident detection (port scans, DDoS, intrusion attempts)
- System log analysis (authentication, services, errors)
- CARP/pfsync high availability
- VPN tunnels (IPsec, WireGuard)

When analyzing data:
1. Identify any security concerns or anomalies
2. Explain what the traffic/logs indicate
3. Provide actionable recommendations
4. Use clear, concise language
5. Highlight critical issues prominently

Format your response with:
- Clear section headers using ##
- Bullet points for lists
- **Bold** for important terms
- Code blocks for IPs, ports, commands`

	switch contextType {
	case "tcpdump":
		return base + `

You are analyzing packet capture (tcpdump) data. Look for:
- Unusual traffic patterns
- Port scans or reconnaissance
- Suspicious connections
- Protocol anomalies
- Potential data exfiltration
- Malformed packets`

	case "logs":
		return base + `

You are analyzing system logs. Look for:
- Failed authentication attempts
- Privilege escalation
- Service errors or crashes
- Configuration changes
- Suspicious user activity
- System anomalies`

	case "firewall":
		return base + `

You are analyzing firewall (pf) logs. Look for:
- Blocked attack attempts
- Rule effectiveness
- Potential rule gaps
- DDoS indicators
- Scanning activity
- Geographic anomalies`

	case "netstat":
		return base + `

You are analyzing network connection data. Look for:
- Suspicious outbound connections
- Unexpected listening services
- Connection state anomalies
- Potential backdoors
- Resource exhaustion`

	case "sysinfo":
		return base + `

You are analyzing system information. Look for:
- Resource constraints
- Performance issues
- Configuration problems
- Security hardening opportunities`

	default:
		return base
	}
}

func (h *AIHandler) buildMessages(req AIAnalyzeRequest, systemPrompt string) []map[string]interface{} {
	messages := []map[string]interface{}{}

	// Add system message for some providers
	h.mu.RLock()
	provider := h.config.Provider
	h.mu.RUnlock()

	if provider != ProviderAnthropic {
		messages = append(messages, map[string]interface{}{
			"role":    "system",
			"content": systemPrompt,
		})
	}

	// Add conversation history (limited)
	for _, msg := range req.History {
		if len(messages) >= 10 {
			break
		}
		messages = append(messages, msg)
	}

	// Build the user message with context
	userContent := req.Query
	if req.Context != "" {
		// Truncate context if too large
		context := req.Context
		if len(context) > 50000 {
			context = context[:50000] + "\n... (truncated)"
		}
		userContent = fmt.Sprintf("Data to analyze:\n```\n%s\n```\n\nQuestion: %s", context, req.Query)
	}

	messages = append(messages, map[string]interface{}{
		"role":    "user",
		"content": userContent,
	})

	return messages
}

func (h *AIHandler) callAIWithConfig(messages []map[string]interface{}, config AIConfig) (string, error) {
	if config.APIKey == "" && config.Provider != ProviderOllama && config.Provider != ProviderCustom {
		return h.generateOfflineResponse()
	}

	switch config.Provider {
	case ProviderAnthropic:
		return h.callAnthropic(messages, config)
	case ProviderOpenAI, ProviderAzure:
		return h.callOpenAI(messages, config)
	case ProviderOllama:
		return h.callOllama(messages, config)
	case ProviderGoogle:
		return h.callGoogle(messages, config)
	case ProviderCustom:
		return h.callOpenAI(messages, config) // Assume OpenAI-compatible
	default:
		return h.callAnthropic(messages, config)
	}
}

func (h *AIHandler) callAnthropic(messages []map[string]interface{}, config AIConfig) (string, error) {
	url := config.APIEndpoint
	if url == "" {
		url = "https://api.anthropic.com/v1/messages"
	}

	// Extract system prompt from messages
	var systemPrompt string
	var cleanMessages []map[string]interface{}
	for _, msg := range messages {
		if msg["role"] == "system" {
			systemPrompt = msg["content"].(string)
		} else {
			cleanMessages = append(cleanMessages, msg)
		}
	}

	requestBody := map[string]interface{}{
		"model":      config.Model,
		"max_tokens": config.MaxTokens,
		"messages":   cleanMessages,
	}
	if systemPrompt != "" {
		requestBody["system"] = systemPrompt
	}

	jsonData, err := json.Marshal(requestBody)
	if err != nil {
		return "", err
	}

	req, err := http.NewRequest("POST", url, bytes.NewBuffer(jsonData))
	if err != nil {
		return "", err
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("x-api-key", config.APIKey)
	req.Header.Set("anthropic-version", "2023-06-01")

	client := &http.Client{Timeout: 120 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return "", err
	}

	if resp.StatusCode != http.StatusOK {
		return "", fmt.Errorf("API error (%d): %s", resp.StatusCode, string(body))
	}

	var result struct {
		Content []struct {
			Text string `json:"text"`
		} `json:"content"`
	}
	if err := json.Unmarshal(body, &result); err != nil {
		return "", err
	}

	if len(result.Content) > 0 {
		return result.Content[0].Text, nil
	}
	return "", fmt.Errorf("no response content")
}

func (h *AIHandler) callOpenAI(messages []map[string]interface{}, config AIConfig) (string, error) {
	url := config.APIEndpoint
	if url == "" {
		url = "https://api.openai.com/v1/chat/completions"
	}

	requestBody := map[string]interface{}{
		"model":       config.Model,
		"max_tokens":  config.MaxTokens,
		"messages":    messages,
		"temperature": config.Temperature,
	}

	jsonData, err := json.Marshal(requestBody)
	if err != nil {
		return "", err
	}

	req, err := http.NewRequest("POST", url, bytes.NewBuffer(jsonData))
	if err != nil {
		return "", err
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+config.APIKey)

	// Azure-specific headers
	if config.Provider == ProviderAzure {
		req.Header.Set("api-key", config.APIKey)
	}

	client := &http.Client{Timeout: 120 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return "", err
	}

	if resp.StatusCode != http.StatusOK {
		return "", fmt.Errorf("API error (%d): %s", resp.StatusCode, string(body))
	}

	var result struct {
		Choices []struct {
			Message struct {
				Content string `json:"content"`
			} `json:"message"`
		} `json:"choices"`
	}
	if err := json.Unmarshal(body, &result); err != nil {
		return "", err
	}

	if len(result.Choices) > 0 {
		return result.Choices[0].Message.Content, nil
	}
	return "", fmt.Errorf("no response content")
}

func (h *AIHandler) callOllama(messages []map[string]interface{}, config AIConfig) (string, error) {
	url := config.APIEndpoint
	if url == "" {
		url = "http://localhost:11434/api/chat"
	}

	requestBody := map[string]interface{}{
		"model":    config.Model,
		"messages": messages,
		"stream":   false,
	}

	jsonData, err := json.Marshal(requestBody)
	if err != nil {
		return "", err
	}

	req, err := http.NewRequest("POST", url, bytes.NewBuffer(jsonData))
	if err != nil {
		return "", err
	}

	req.Header.Set("Content-Type", "application/json")

	client := &http.Client{Timeout: 300 * time.Second} // Longer timeout for local models
	resp, err := client.Do(req)
	if err != nil {
		return "", fmt.Errorf("Ollama connection failed. Is Ollama running? Error: %v", err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return "", err
	}

	if resp.StatusCode != http.StatusOK {
		return "", fmt.Errorf("Ollama error (%d): %s", resp.StatusCode, string(body))
	}

	var result struct {
		Message struct {
			Content string `json:"content"`
		} `json:"message"`
	}
	if err := json.Unmarshal(body, &result); err != nil {
		return "", err
	}

	return result.Message.Content, nil
}

func (h *AIHandler) callGoogle(messages []map[string]interface{}, config AIConfig) (string, error) {
	url := fmt.Sprintf("https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s",
		config.Model, config.APIKey)

	// Convert messages to Google format
	var contents []map[string]interface{}
	for _, msg := range messages {
		role := msg["role"].(string)
		if role == "assistant" {
			role = "model"
		}
		contents = append(contents, map[string]interface{}{
			"role": role,
			"parts": []map[string]interface{}{
				{"text": msg["content"]},
			},
		})
	}

	requestBody := map[string]interface{}{
		"contents": contents,
		"generationConfig": map[string]interface{}{
			"maxOutputTokens": config.MaxTokens,
			"temperature":     config.Temperature,
		},
	}

	jsonData, err := json.Marshal(requestBody)
	if err != nil {
		return "", err
	}

	req, err := http.NewRequest("POST", url, bytes.NewBuffer(jsonData))
	if err != nil {
		return "", err
	}

	req.Header.Set("Content-Type", "application/json")

	client := &http.Client{Timeout: 120 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return "", err
	}

	if resp.StatusCode != http.StatusOK {
		return "", fmt.Errorf("Google AI error (%d): %s", resp.StatusCode, string(body))
	}

	var result struct {
		Candidates []struct {
			Content struct {
				Parts []struct {
					Text string `json:"text"`
				} `json:"parts"`
			} `json:"content"`
		} `json:"candidates"`
	}
	if err := json.Unmarshal(body, &result); err != nil {
		return "", err
	}

	if len(result.Candidates) > 0 && len(result.Candidates[0].Content.Parts) > 0 {
		return result.Candidates[0].Content.Parts[0].Text, nil
	}
	return "", fmt.Errorf("no response content")
}

func (h *AIHandler) generateOfflineResponse() (string, error) {
	return `## AI Analysis Not Configured

No AI provider is configured. Configure AI in Settings > AI Analysis or set environment variables.

### Supported Providers

**Cloud Providers:**
- **Anthropic Claude** - Set \`ANTHROPIC_API_KEY\`
- **OpenAI GPT** - Set \`OPENAI_API_KEY\`
- **Google Gemini** - Set \`GOOGLE_AI_KEY\`
- **Azure OpenAI** - Set \`AZURE_OPENAI_KEY\` and \`AI_API_URL\`

**Local Providers:**
- **Ollama** - Install Ollama and run models locally (no API key needed)
  - Install: \`curl -fsSL https://ollama.com/install.sh | sh\`
  - Pull a model: \`ollama pull llama3.2\`

### Environment Variables

\`\`\`bash
# Provider selection
export AI_PROVIDER=anthropic  # anthropic, openai, ollama, google, azure, custom

# API keys (based on provider)
export ANTHROPIC_API_KEY=sk-ant-...
export OPENAI_API_KEY=sk-...
export GOOGLE_AI_KEY=...

# Model selection (optional)
export AI_MODEL=claude-3-haiku-20240307

# Custom endpoint (for Ollama, Azure, self-hosted)
export AI_API_URL=http://localhost:11434/api/chat
\`\`\`

Restart NSWall API after configuration.`, nil
}

// GetOllamaModels fetches available models from Ollama
func (h *AIHandler) GetOllamaModels(w http.ResponseWriter, r *http.Request) {
	endpoint := r.URL.Query().Get("endpoint")
	if endpoint == "" {
		endpoint = "http://localhost:11434"
	}

	resp, err := http.Get(endpoint + "/api/tags")
	if err != nil {
		jsonError(w, fmt.Sprintf("Failed to connect to Ollama: %v", err), http.StatusBadRequest)
		return
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		jsonError(w, "Failed to read response", http.StatusInternalServerError)
		return
	}

	var result struct {
		Models []struct {
			Name       string `json:"name"`
			ModifiedAt string `json:"modified_at"`
			Size       int64  `json:"size"`
		} `json:"models"`
	}
	if err := json.Unmarshal(body, &result); err != nil {
		jsonError(w, "Failed to parse response", http.StatusInternalServerError)
		return
	}

	models := make([]ModelInfo, len(result.Models))
	for i, m := range result.Models {
		// Clean model name (remove :latest suffix)
		name := strings.TrimSuffix(m.Name, ":latest")
		models[i] = ModelInfo{
			ID:   m.Name,
			Name: name,
		}
	}

	jsonResponse(w, map[string]interface{}{
		"success": true,
		"data":    models,
	})
}

// Helper functions
func jsonResponse(w http.ResponseWriter, data interface{}) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(data)
}

func jsonError(w http.ResponseWriter, message string, status int) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": false,
		"error":   message,
	})
}
