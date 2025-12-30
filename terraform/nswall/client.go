package nswall

import (
	"bytes"
	"crypto/tls"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"time"
)

// apiRequest makes an HTTP request to the NSWall API
func apiRequest(client *Client, method, endpoint string, body interface{}) (interface{}, error) {
	url := fmt.Sprintf("http://%s:%d%s", client.Host, client.Port, endpoint)

	var reqBody io.Reader
	if body != nil {
		jsonBody, err := json.Marshal(body)
		if err != nil {
			return nil, fmt.Errorf("failed to marshal request: %w", err)
		}
		reqBody = bytes.NewBuffer(jsonBody)
	}

	req, err := http.NewRequest(method, url, reqBody)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-API-Key", client.APIKey)

	httpClient := &http.Client{
		Timeout: 30 * time.Second,
	}

	if client.Insecure {
		httpClient.Transport = &http.Transport{
			TLSClientConfig: &tls.Config{InsecureSkipVerify: true},
		}
	}

	resp, err := httpClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("request failed: %w", err)
	}
	defer resp.Body.Close()

	respBody, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("failed to read response: %w", err)
	}

	var result map[string]interface{}
	if err := json.Unmarshal(respBody, &result); err != nil {
		return nil, fmt.Errorf("failed to parse response: %w", err)
	}

	if success, ok := result["success"].(bool); !ok || !success {
		if errMsg, ok := result["error"].(string); ok {
			return nil, fmt.Errorf("API error: %s", errMsg)
		}
		return nil, fmt.Errorf("API request failed")
	}

	return result["data"], nil
}
