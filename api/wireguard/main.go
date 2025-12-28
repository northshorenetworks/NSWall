// NSWall WireGuard API Server
// Copyright (c) 2024 North Shore Networks
//
// This package provides a REST API for managing WireGuard VPN interfaces
// on OpenBSD-based NSWall systems.

package main

import (
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"os"
	"os/exec"
	"regexp"
	"strings"
)

const (
	defaultPort     = ":8080"
	ifconfigPath    = "/sbin/ifconfig"
	opensslPath     = "/usr/bin/openssl"
	maxKeyLength    = 44
	maxInterfaceLen = 16
)

// Response structures
type APIResponse struct {
	Status  string      `json:"status"`
	Message string      `json:"message,omitempty"`
	Data    interface{} `json:"data,omitempty"`
	Error   string      `json:"error,omitempty"`
}

type Interface struct {
	Name       string   `json:"name"`
	PublicKey  string   `json:"public_key,omitempty"`
	ListenPort int      `json:"listen_port,omitempty"`
	Addresses  []string `json:"addresses,omitempty"`
	Peers      []Peer   `json:"peers,omitempty"`
}

type Peer struct {
	PublicKey          string   `json:"public_key"`
	Endpoint           string   `json:"endpoint,omitempty"`
	AllowedIPs         []string `json:"allowed_ips,omitempty"`
	PersistentKeepalive int     `json:"persistent_keepalive,omitempty"`
	LatestHandshake    string   `json:"latest_handshake,omitempty"`
	TransferRx         int64    `json:"transfer_rx,omitempty"`
	TransferTx         int64    `json:"transfer_tx,omitempty"`
}

type CreateInterfaceRequest struct {
	Name       string `json:"name"`
	PrivateKey string `json:"private_key,omitempty"`
	ListenPort int    `json:"listen_port,omitempty"`
	Address    string `json:"address,omitempty"`
}

type AddPeerRequest struct {
	PublicKey           string   `json:"public_key"`
	Endpoint            string   `json:"endpoint,omitempty"`
	AllowedIPs          []string `json:"allowed_ips,omitempty"`
	PersistentKeepalive int      `json:"persistent_keepalive,omitempty"`
	PresharedKey        string   `json:"preshared_key,omitempty"`
}

type SetKeyRequest struct {
	PrivateKey string `json:"private_key"`
}

type SetPortRequest struct {
	Port int `json:"port"`
}

// Validation helpers
var interfaceRegex = regexp.MustCompile(`^wg[0-9]{1,3}$`)
var base64Regex = regexp.MustCompile(`^[A-Za-z0-9+/]{43}=$`)

func validateInterfaceName(name string) bool {
	return interfaceRegex.MatchString(name)
}

func validateBase64Key(key string) bool {
	return len(key) == maxKeyLength && base64Regex.MatchString(key)
}

// Execute shell commands
func runCommand(name string, args ...string) (string, error) {
	cmd := exec.Command(name, args...)
	output, err := cmd.CombinedOutput()
	return string(output), err
}

// API Handlers

// GET /api/v1/wireguard/interfaces
func listInterfaces(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		sendError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	output, err := runCommand(ifconfigPath)
	if err != nil {
		sendError(w, http.StatusInternalServerError, "Failed to list interfaces")
		return
	}

	var interfaces []Interface
	lines := strings.Split(output, "\n")
	for _, line := range lines {
		if strings.HasPrefix(line, "wg") && strings.Contains(line, ":") {
			name := strings.Split(line, ":")[0]
			iface := parseInterface(name)
			if iface != nil {
				interfaces = append(interfaces, *iface)
			}
		}
	}

	sendSuccess(w, interfaces)
}

// GET /api/v1/wireguard/interfaces/{name}
func getInterface(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		sendError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	name := strings.TrimPrefix(r.URL.Path, "/api/v1/wireguard/interfaces/")
	if !validateInterfaceName(name) {
		sendError(w, http.StatusBadRequest, "Invalid interface name")
		return
	}

	iface := parseInterface(name)
	if iface == nil {
		sendError(w, http.StatusNotFound, "Interface not found")
		return
	}

	sendSuccess(w, iface)
}

// POST /api/v1/wireguard/interfaces
func createInterface(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		sendError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	var req CreateInterfaceRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		sendError(w, http.StatusBadRequest, "Invalid request body")
		return
	}

	if !validateInterfaceName(req.Name) {
		sendError(w, http.StatusBadRequest, "Invalid interface name")
		return
	}

	// Create interface
	_, err := runCommand(ifconfigPath, req.Name, "create")
	if err != nil {
		sendError(w, http.StatusInternalServerError, "Failed to create interface")
		return
	}

	// Set private key if provided
	if req.PrivateKey != "" {
		if !validateBase64Key(req.PrivateKey) {
			sendError(w, http.StatusBadRequest, "Invalid private key format")
			return
		}
		_, err := runCommand(ifconfigPath, req.Name, "wgkey", req.PrivateKey)
		if err != nil {
			sendError(w, http.StatusInternalServerError, "Failed to set private key")
			return
		}
	}

	// Set listen port if provided
	if req.ListenPort > 0 {
		_, err := runCommand(ifconfigPath, req.Name, "wgport", fmt.Sprintf("%d", req.ListenPort))
		if err != nil {
			sendError(w, http.StatusInternalServerError, "Failed to set listen port")
			return
		}
	}

	// Set address if provided
	if req.Address != "" {
		_, err := runCommand(ifconfigPath, req.Name, "inet", req.Address)
		if err != nil {
			sendError(w, http.StatusInternalServerError, "Failed to set address")
			return
		}
	}

	// Bring up the interface
	runCommand(ifconfigPath, req.Name, "up")

	sendSuccessMessage(w, fmt.Sprintf("Interface %s created", req.Name))
}

// DELETE /api/v1/wireguard/interfaces/{name}
func deleteInterface(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodDelete {
		sendError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	name := strings.TrimPrefix(r.URL.Path, "/api/v1/wireguard/interfaces/")
	if !validateInterfaceName(name) {
		sendError(w, http.StatusBadRequest, "Invalid interface name")
		return
	}

	_, err := runCommand(ifconfigPath, name, "destroy")
	if err != nil {
		sendError(w, http.StatusInternalServerError, "Failed to destroy interface")
		return
	}

	sendSuccessMessage(w, fmt.Sprintf("Interface %s destroyed", name))
}

// POST /api/v1/wireguard/interfaces/{name}/peers
func addPeer(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		sendError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	path := r.URL.Path
	parts := strings.Split(path, "/")
	if len(parts) < 6 {
		sendError(w, http.StatusBadRequest, "Invalid path")
		return
	}
	name := parts[5]

	if !validateInterfaceName(name) {
		sendError(w, http.StatusBadRequest, "Invalid interface name")
		return
	}

	var req AddPeerRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		sendError(w, http.StatusBadRequest, "Invalid request body")
		return
	}

	if !validateBase64Key(req.PublicKey) {
		sendError(w, http.StatusBadRequest, "Invalid public key format")
		return
	}

	// Add peer
	_, err := runCommand(ifconfigPath, name, "wgpeer", req.PublicKey)
	if err != nil {
		sendError(w, http.StatusInternalServerError, "Failed to add peer")
		return
	}

	// Set endpoint if provided
	if req.Endpoint != "" {
		_, err := runCommand(ifconfigPath, name, "wgpeer", req.PublicKey, "wgendpoint", req.Endpoint)
		if err != nil {
			sendError(w, http.StatusInternalServerError, "Failed to set endpoint")
			return
		}
	}

	// Add allowed IPs
	for _, aip := range req.AllowedIPs {
		_, err := runCommand(ifconfigPath, name, "wgpeer", req.PublicKey, "wgaip", aip)
		if err != nil {
			log.Printf("Warning: Failed to add allowed IP %s: %v", aip, err)
		}
	}

	// Set persistent keepalive if provided
	if req.PersistentKeepalive > 0 {
		_, err := runCommand(ifconfigPath, name, "wgpeer", req.PublicKey, "wgpka", fmt.Sprintf("%d", req.PersistentKeepalive))
		if err != nil {
			log.Printf("Warning: Failed to set keepalive: %v", err)
		}
	}

	// Set preshared key if provided
	if req.PresharedKey != "" {
		if validateBase64Key(req.PresharedKey) {
			_, err := runCommand(ifconfigPath, name, "wgpeer", req.PublicKey, "wgpsk", req.PresharedKey)
			if err != nil {
				log.Printf("Warning: Failed to set preshared key: %v", err)
			}
		}
	}

	sendSuccessMessage(w, fmt.Sprintf("Peer %s added to %s", req.PublicKey[:8]+"...", name))
}

// DELETE /api/v1/wireguard/interfaces/{name}/peers/{pubkey}
func deletePeer(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodDelete {
		sendError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	path := r.URL.Path
	parts := strings.Split(path, "/")
	if len(parts) < 8 {
		sendError(w, http.StatusBadRequest, "Invalid path")
		return
	}
	name := parts[5]
	pubkey := parts[7]

	if !validateInterfaceName(name) {
		sendError(w, http.StatusBadRequest, "Invalid interface name")
		return
	}

	if !validateBase64Key(pubkey) {
		sendError(w, http.StatusBadRequest, "Invalid public key format")
		return
	}

	_, err := runCommand(ifconfigPath, name, "-wgpeer", pubkey)
	if err != nil {
		sendError(w, http.StatusInternalServerError, "Failed to remove peer")
		return
	}

	sendSuccessMessage(w, "Peer removed")
}

// POST /api/v1/wireguard/genkey
func generateKey(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		sendError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	output, err := runCommand(opensslPath, "rand", "-base64", "32")
	if err != nil {
		sendError(w, http.StatusInternalServerError, "Failed to generate key")
		return
	}

	key := strings.TrimSpace(output)
	sendSuccess(w, map[string]string{"private_key": key})
}

// POST /api/v1/wireguard/genpsk
func generatePSK(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		sendError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	output, err := runCommand(opensslPath, "rand", "-base64", "32")
	if err != nil {
		sendError(w, http.StatusInternalServerError, "Failed to generate preshared key")
		return
	}

	psk := strings.TrimSpace(output)
	sendSuccess(w, map[string]string{"preshared_key": psk})
}

// POST /api/v1/wireguard/pubkey
func derivePublicKey(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		sendError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	var req SetKeyRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		sendError(w, http.StatusBadRequest, "Invalid request body")
		return
	}

	if !validateBase64Key(req.PrivateKey) {
		sendError(w, http.StatusBadRequest, "Invalid private key format")
		return
	}

	// Create temporary interface to derive public key
	tmpIface := "wg99"
	runCommand(ifconfigPath, tmpIface, "destroy") // Clean up if exists
	_, err := runCommand(ifconfigPath, tmpIface, "create")
	if err != nil {
		sendError(w, http.StatusInternalServerError, "Failed to create temp interface")
		return
	}
	defer runCommand(ifconfigPath, tmpIface, "destroy")

	_, err = runCommand(ifconfigPath, tmpIface, "wgkey", req.PrivateKey)
	if err != nil {
		sendError(w, http.StatusInternalServerError, "Failed to set key")
		return
	}

	output, err := runCommand(ifconfigPath, tmpIface)
	if err != nil {
		sendError(w, http.StatusInternalServerError, "Failed to get public key")
		return
	}

	// Parse public key from output
	for _, line := range strings.Split(output, "\n") {
		if strings.Contains(line, "wgpubkey") {
			parts := strings.Fields(line)
			for i, p := range parts {
				if p == "wgpubkey" && i+1 < len(parts) {
					sendSuccess(w, map[string]string{"public_key": parts[i+1]})
					return
				}
			}
		}
	}

	sendError(w, http.StatusInternalServerError, "Could not derive public key")
}

// PUT /api/v1/wireguard/interfaces/{name}/key
func setInterfaceKey(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPut {
		sendError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	path := r.URL.Path
	parts := strings.Split(path, "/")
	if len(parts) < 6 {
		sendError(w, http.StatusBadRequest, "Invalid path")
		return
	}
	name := parts[5]

	if !validateInterfaceName(name) {
		sendError(w, http.StatusBadRequest, "Invalid interface name")
		return
	}

	var req SetKeyRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		sendError(w, http.StatusBadRequest, "Invalid request body")
		return
	}

	if !validateBase64Key(req.PrivateKey) {
		sendError(w, http.StatusBadRequest, "Invalid private key format")
		return
	}

	_, err := runCommand(ifconfigPath, name, "wgkey", req.PrivateKey)
	if err != nil {
		sendError(w, http.StatusInternalServerError, "Failed to set private key")
		return
	}

	sendSuccessMessage(w, "Private key set")
}

// PUT /api/v1/wireguard/interfaces/{name}/port
func setInterfacePort(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPut {
		sendError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	path := r.URL.Path
	parts := strings.Split(path, "/")
	if len(parts) < 6 {
		sendError(w, http.StatusBadRequest, "Invalid path")
		return
	}
	name := parts[5]

	if !validateInterfaceName(name) {
		sendError(w, http.StatusBadRequest, "Invalid interface name")
		return
	}

	var req SetPortRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		sendError(w, http.StatusBadRequest, "Invalid request body")
		return
	}

	if req.Port < 1 || req.Port > 65535 {
		sendError(w, http.StatusBadRequest, "Invalid port number")
		return
	}

	_, err := runCommand(ifconfigPath, name, "wgport", fmt.Sprintf("%d", req.Port))
	if err != nil {
		sendError(w, http.StatusInternalServerError, "Failed to set port")
		return
	}

	sendSuccessMessage(w, fmt.Sprintf("Listen port set to %d", req.Port))
}

// Helper to parse interface configuration
func parseInterface(name string) *Interface {
	output, err := runCommand(ifconfigPath, name)
	if err != nil {
		return nil
	}

	iface := &Interface{
		Name:      name,
		Addresses: []string{},
		Peers:     []Peer{},
	}

	var currentPeer *Peer
	lines := strings.Split(output, "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)
		fields := strings.Fields(line)

		for i, field := range fields {
			switch field {
			case "wgpubkey":
				if i+1 < len(fields) {
					iface.PublicKey = fields[i+1]
				}
			case "wgport":
				if i+1 < len(fields) {
					fmt.Sscanf(fields[i+1], "%d", &iface.ListenPort)
				}
			case "wgpeer":
				if i+1 < len(fields) {
					if currentPeer != nil {
						iface.Peers = append(iface.Peers, *currentPeer)
					}
					currentPeer = &Peer{
						PublicKey:  fields[i+1],
						AllowedIPs: []string{},
					}
				}
			case "wgendpoint":
				if currentPeer != nil && i+1 < len(fields) {
					currentPeer.Endpoint = fields[i+1]
				}
			case "wgaip":
				if currentPeer != nil && i+1 < len(fields) {
					aips := strings.Split(fields[i+1], ",")
					currentPeer.AllowedIPs = append(currentPeer.AllowedIPs, aips...)
				}
			case "wgpka":
				if currentPeer != nil && i+1 < len(fields) {
					fmt.Sscanf(fields[i+1], "%d", &currentPeer.PersistentKeepalive)
				}
			}
		}

		// Parse inet address
		if strings.HasPrefix(line, "inet ") {
			addrParts := strings.Fields(line)
			if len(addrParts) >= 2 {
				iface.Addresses = append(iface.Addresses, addrParts[1])
			}
		}
	}

	if currentPeer != nil {
		iface.Peers = append(iface.Peers, *currentPeer)
	}

	return iface
}

// Response helpers
func sendSuccess(w http.ResponseWriter, data interface{}) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(APIResponse{
		Status: "success",
		Data:   data,
	})
}

func sendSuccessMessage(w http.ResponseWriter, message string) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(APIResponse{
		Status:  "success",
		Message: message,
	})
}

func sendError(w http.ResponseWriter, status int, message string) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	json.NewEncoder(w).Encode(APIResponse{
		Status: "error",
		Error:  message,
	})
}

// Router
func router(w http.ResponseWriter, r *http.Request) {
	log.Printf("%s %s", r.Method, r.URL.Path)

	// CORS headers
	w.Header().Set("Access-Control-Allow-Origin", "*")
	w.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS")
	w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization")

	if r.Method == "OPTIONS" {
		w.WriteHeader(http.StatusOK)
		return
	}

	path := r.URL.Path

	switch {
	case path == "/api/v1/wireguard/interfaces" && r.Method == http.MethodGet:
		listInterfaces(w, r)
	case path == "/api/v1/wireguard/interfaces" && r.Method == http.MethodPost:
		createInterface(w, r)
	case strings.HasPrefix(path, "/api/v1/wireguard/interfaces/") && strings.HasSuffix(path, "/peers") && r.Method == http.MethodPost:
		addPeer(w, r)
	case strings.HasPrefix(path, "/api/v1/wireguard/interfaces/") && strings.Contains(path, "/peers/") && r.Method == http.MethodDelete:
		deletePeer(w, r)
	case strings.HasPrefix(path, "/api/v1/wireguard/interfaces/") && strings.HasSuffix(path, "/key") && r.Method == http.MethodPut:
		setInterfaceKey(w, r)
	case strings.HasPrefix(path, "/api/v1/wireguard/interfaces/") && strings.HasSuffix(path, "/port") && r.Method == http.MethodPut:
		setInterfacePort(w, r)
	case strings.HasPrefix(path, "/api/v1/wireguard/interfaces/") && r.Method == http.MethodGet:
		getInterface(w, r)
	case strings.HasPrefix(path, "/api/v1/wireguard/interfaces/") && r.Method == http.MethodDelete:
		deleteInterface(w, r)
	case path == "/api/v1/wireguard/genkey":
		generateKey(w, r)
	case path == "/api/v1/wireguard/genpsk":
		generatePSK(w, r)
	case path == "/api/v1/wireguard/pubkey":
		derivePublicKey(w, r)
	case path == "/api/v1/wireguard/health" || path == "/health":
		sendSuccess(w, map[string]string{"status": "healthy"})
	default:
		sendError(w, http.StatusNotFound, "Endpoint not found")
	}
}

func main() {
	port := os.Getenv("WG_API_PORT")
	if port == "" {
		port = defaultPort
	} else if !strings.HasPrefix(port, ":") {
		port = ":" + port
	}

	log.Printf("NSWall WireGuard API starting on %s", port)
	log.Printf("Endpoints:")
	log.Printf("  GET    /api/v1/wireguard/interfaces              - List all WireGuard interfaces")
	log.Printf("  GET    /api/v1/wireguard/interfaces/{name}       - Get interface details")
	log.Printf("  POST   /api/v1/wireguard/interfaces              - Create new interface")
	log.Printf("  DELETE /api/v1/wireguard/interfaces/{name}       - Destroy interface")
	log.Printf("  POST   /api/v1/wireguard/interfaces/{name}/peers - Add peer to interface")
	log.Printf("  DELETE /api/v1/wireguard/interfaces/{name}/peers/{pubkey} - Remove peer")
	log.Printf("  PUT    /api/v1/wireguard/interfaces/{name}/key   - Set private key")
	log.Printf("  PUT    /api/v1/wireguard/interfaces/{name}/port  - Set listen port")
	log.Printf("  POST   /api/v1/wireguard/genkey                  - Generate private key")
	log.Printf("  POST   /api/v1/wireguard/genpsk                  - Generate preshared key")
	log.Printf("  POST   /api/v1/wireguard/pubkey                  - Derive public key")
	log.Printf("  GET    /health                                   - Health check")

	http.HandleFunc("/", router)
	log.Fatal(http.ListenAndServe(port, nil))
}
