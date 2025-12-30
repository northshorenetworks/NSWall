package services

import (
	"bufio"
	"encoding/json"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"regexp"
	"strconv"
	"strings"
	"sync"
)

// HTTPDConfig represents httpd configuration
type HTTPDConfig struct {
	Enabled   bool           `json:"enabled"`
	Chroot    string         `json:"chroot"` // default /var/www
	Logdir    string         `json:"logdir"`
	Prefork   int            `json:"prefork"`
	Servers   []HTTPDServer  `json:"servers"`
	Types     []HTTPDType    `json:"types"`
}

// HTTPDServer represents an httpd server block
type HTTPDServer struct {
	Name        string         `json:"name"`
	Alias       []string       `json:"alias"`
	ListenOn    string         `json:"listen_on"`
	Port        int            `json:"port"`
	TLS         *HTTPDTLSConfig `json:"tls,omitempty"`
	Root        string         `json:"root"`
	Directory   *HTTPDDirectory `json:"directory,omitempty"`
	Location    []HTTPDLocation `json:"location"`
	Log         HTTPDLog        `json:"log"`
	Connection  HTTPDConnection `json:"connection"`
	Gzip        bool           `json:"gzip"`
	DefaultType string         `json:"default_type"`
	BlockList   []string       `json:"block_list"` // blocked patterns
	Errdocs     bool           `json:"errdocs"`
}

// HTTPDTLSConfig represents TLS configuration
type HTTPDTLSConfig struct {
	Certificate string   `json:"certificate"`
	Key         string   `json:"key"`
	OCSP        string   `json:"ocsp,omitempty"`
	Ciphers     string   `json:"ciphers,omitempty"`
	DHE         string   `json:"dhe,omitempty"`
	ECDHE       string   `json:"ecdhe,omitempty"`
	Protocols   string   `json:"protocols,omitempty"`
	Ticket      bool     `json:"ticket"`
}

// HTTPDDirectory represents directory listing config
type HTTPDDirectory struct {
	Auto    bool   `json:"auto"`
	Index   string `json:"index"`
	NoIndex bool   `json:"no_index"`
}

// HTTPDLocation represents a location block
type HTTPDLocation struct {
	Path         string         `json:"path"`
	Match        string         `json:"match,omitempty"` // regex match
	Root         string         `json:"root,omitempty"`
	Directory    *HTTPDDirectory `json:"directory,omitempty"`
	Block        bool           `json:"block"`
	Pass         string         `json:"pass,omitempty"` // reverse proxy
	FastCGI      *HTTPDFastCGI  `json:"fastcgi,omitempty"`
	Strip        int            `json:"strip,omitempty"`
	Authenticate string         `json:"authenticate,omitempty"` // auth realm
	Hsts         bool           `json:"hsts"`
	HstsSubdom   bool           `json:"hsts_subdomains"`
	RequestRewrite  []HTTPDRewrite `json:"request_rewrite,omitempty"`
	ResponseHeader  []HTTPDHeader  `json:"response_header,omitempty"`
}

// HTTPDFastCGI represents FastCGI configuration
type HTTPDFastCGI struct {
	Socket   string            `json:"socket"`
	Param    map[string]string `json:"param,omitempty"`
	Strip    int               `json:"strip,omitempty"`
}

// HTTPDRewrite represents URL rewrite rules
type HTTPDRewrite struct {
	Match   string `json:"match"`
	Replace string `json:"replace"`
}

// HTTPDHeader represents custom headers
type HTTPDHeader struct {
	Name   string `json:"name"`
	Value  string `json:"value"`
	Remove bool   `json:"remove,omitempty"`
}

// HTTPDLog represents logging configuration
type HTTPDLog struct {
	Access  string `json:"access"`
	Error   string `json:"error"`
	Style   string `json:"style"` // combined, common, connection, forwarded
	NoLog   bool   `json:"no_log"`
}

// HTTPDConnection represents connection settings
type HTTPDConnection struct {
	MaxRequests   int `json:"max_requests"`
	MaxRequestBody int64 `json:"max_request_body"`
	Timeout       int `json:"timeout"` // seconds
}

// HTTPDType represents a media type definition
type HTTPDType struct {
	Extension string `json:"extension"`
	MediaType string `json:"media_type"`
}

// HTTPDStatus represents httpd operational status
type HTTPDStatus struct {
	Running     bool           `json:"running"`
	PID         int            `json:"pid"`
	Connections int            `json:"connections"`
	Requests    int64          `json:"requests"`
	Servers     []HTTPDServerStatus `json:"servers"`
}

// HTTPDServerStatus represents status for a server
type HTTPDServerStatus struct {
	Name     string `json:"name"`
	Port     int    `json:"port"`
	TLS      bool   `json:"tls"`
	Requests int64  `json:"requests"`
}

// HTTPDService manages OpenBSD httpd web server
type HTTPDService struct {
	configPath string
	config     HTTPDConfig
	mu         sync.RWMutex
}

// Default media types
var DefaultHTTPDTypes = []HTTPDType{
	{Extension: ".html", MediaType: "text/html"},
	{Extension: ".htm", MediaType: "text/html"},
	{Extension: ".css", MediaType: "text/css"},
	{Extension: ".js", MediaType: "application/javascript"},
	{Extension: ".json", MediaType: "application/json"},
	{Extension: ".xml", MediaType: "application/xml"},
	{Extension: ".txt", MediaType: "text/plain"},
	{Extension: ".png", MediaType: "image/png"},
	{Extension: ".jpg", MediaType: "image/jpeg"},
	{Extension: ".jpeg", MediaType: "image/jpeg"},
	{Extension: ".gif", MediaType: "image/gif"},
	{Extension: ".svg", MediaType: "image/svg+xml"},
	{Extension: ".ico", MediaType: "image/x-icon"},
	{Extension: ".woff", MediaType: "font/woff"},
	{Extension: ".woff2", MediaType: "font/woff2"},
	{Extension: ".pdf", MediaType: "application/pdf"},
	{Extension: ".zip", MediaType: "application/zip"},
}

// NewHTTPDService creates a new httpd service
func NewHTTPDService(configPath string) *HTTPDService {
	svc := &HTTPDService{
		configPath: configPath,
		config: HTTPDConfig{
			Enabled: false,
			Chroot:  "/var/www",
			Logdir:  "logs",
			Prefork: 3,
			Types:   DefaultHTTPDTypes,
		},
	}

	os.MkdirAll(configPath, 0700)
	svc.loadConfig()

	return svc
}

func (s *HTTPDService) loadConfig() {
	configFile := filepath.Join(s.configPath, "httpd.json")
	data, err := os.ReadFile(configFile)
	if err != nil {
		return
	}
	json.Unmarshal(data, &s.config)
}

func (s *HTTPDService) saveConfig() error {
	configFile := filepath.Join(s.configPath, "httpd.json")
	data, err := json.MarshalIndent(s.config, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(configFile, data, 0600)
}

// GetConfig returns the httpd configuration
func (s *HTTPDService) GetConfig() HTTPDConfig {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.config
}

// UpdateConfig updates the httpd configuration
func (s *HTTPDService) UpdateConfig(config HTTPDConfig) error {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.config = config
	return s.saveConfig()
}

// GetStatus returns httpd operational status
func (s *HTTPDService) GetStatus() (*HTTPDStatus, error) {
	status := &HTTPDStatus{}

	// Check if running
	output, err := exec.Command("rcctl", "check", "httpd").CombinedOutput()
	status.Running = err == nil && strings.Contains(string(output), "ok")

	if !status.Running {
		return status, nil
	}

	// Get PID
	pidBytes, _ := os.ReadFile("/var/run/httpd.pid")
	status.PID, _ = strconv.Atoi(strings.TrimSpace(string(pidBytes)))

	// Get server status from config
	s.mu.RLock()
	for _, srv := range s.config.Servers {
		serverStatus := HTTPDServerStatus{
			Name: srv.Name,
			Port: srv.Port,
			TLS:  srv.TLS != nil,
		}
		status.Servers = append(status.Servers, serverStatus)
	}
	s.mu.RUnlock()

	return status, nil
}

// AddServer adds a new server block
func (s *HTTPDService) AddServer(server HTTPDServer) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	// Check for duplicate
	for _, srv := range s.config.Servers {
		if srv.Name == server.Name && srv.Port == server.Port {
			return fmt.Errorf("server already exists: %s:%d", server.Name, server.Port)
		}
	}

	// Set defaults
	if server.Port == 0 {
		if server.TLS != nil {
			server.Port = 443
		} else {
			server.Port = 80
		}
	}
	if server.Root == "" {
		server.Root = "/htdocs"
	}
	if server.Log.Style == "" {
		server.Log.Style = "combined"
	}
	if server.Connection.Timeout == 0 {
		server.Connection.Timeout = 600
	}
	if server.Connection.MaxRequestBody == 0 {
		server.Connection.MaxRequestBody = 1048576 // 1MB
	}

	s.config.Servers = append(s.config.Servers, server)
	return s.saveConfig()
}

// RemoveServer removes a server block
func (s *HTTPDService) RemoveServer(name string, port int) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, srv := range s.config.Servers {
		if srv.Name == name && srv.Port == port {
			s.config.Servers = append(s.config.Servers[:i], s.config.Servers[i+1:]...)
			return s.saveConfig()
		}
	}
	return fmt.Errorf("server not found: %s:%d", name, port)
}

// UpdateServer updates a server block
func (s *HTTPDService) UpdateServer(name string, port int, server HTTPDServer) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, srv := range s.config.Servers {
		if srv.Name == name && srv.Port == port {
			s.config.Servers[i] = server
			return s.saveConfig()
		}
	}
	return fmt.Errorf("server not found: %s:%d", name, port)
}

// AddLocation adds a location to a server
func (s *HTTPDService) AddLocation(serverName string, serverPort int, location HTTPDLocation) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, srv := range s.config.Servers {
		if srv.Name == serverName && srv.Port == serverPort {
			// Check for duplicate
			for _, loc := range srv.Location {
				if loc.Path == location.Path {
					return fmt.Errorf("location already exists: %s", location.Path)
				}
			}
			s.config.Servers[i].Location = append(s.config.Servers[i].Location, location)
			return s.saveConfig()
		}
	}
	return fmt.Errorf("server not found: %s:%d", serverName, serverPort)
}

// RemoveLocation removes a location from a server
func (s *HTTPDService) RemoveLocation(serverName string, serverPort int, path string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, srv := range s.config.Servers {
		if srv.Name == serverName && srv.Port == serverPort {
			for j, loc := range srv.Location {
				if loc.Path == path {
					s.config.Servers[i].Location = append(
						s.config.Servers[i].Location[:j],
						s.config.Servers[i].Location[j+1:]...,
					)
					return s.saveConfig()
				}
			}
			return fmt.Errorf("location not found: %s", path)
		}
	}
	return fmt.Errorf("server not found: %s:%d", serverName, serverPort)
}

// GenerateConfig generates /etc/httpd.conf
func (s *HTTPDService) GenerateConfig() error {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var buf strings.Builder

	buf.WriteString(`#
# NSWall HTTPd Configuration
# Generated by NSWall - Do not edit manually
#

`)

	// Global options
	if s.config.Chroot != "/var/www" {
		buf.WriteString(fmt.Sprintf("chroot \"%s\"\n", s.config.Chroot))
	}
	if s.config.Logdir != "" && s.config.Logdir != "logs" {
		buf.WriteString(fmt.Sprintf("logdir \"%s\"\n", s.config.Logdir))
	}
	if s.config.Prefork > 0 && s.config.Prefork != 3 {
		buf.WriteString(fmt.Sprintf("prefork %d\n", s.config.Prefork))
	}
	buf.WriteString("\n")

	// Media types
	if len(s.config.Types) > 0 {
		buf.WriteString("types {\n")
		for _, t := range s.config.Types {
			buf.WriteString(fmt.Sprintf("\t%s\t%s\n", t.MediaType, t.Extension))
		}
		buf.WriteString("}\n\n")
	}

	// Server blocks
	for _, srv := range s.config.Servers {
		buf.WriteString(s.generateServerBlock(srv))
		buf.WriteString("\n")
	}

	return os.WriteFile("/etc/httpd.conf", []byte(buf.String()), 0644)
}

func (s *HTTPDService) generateServerBlock(srv HTTPDServer) string {
	var buf strings.Builder

	buf.WriteString(fmt.Sprintf("server \"%s\" {\n", srv.Name))

	// Aliases
	for _, alias := range srv.Alias {
		buf.WriteString(fmt.Sprintf("\talias \"%s\"\n", alias))
	}

	// Listen
	listen := srv.ListenOn
	if listen == "" {
		listen = "*"
	}
	if srv.TLS != nil {
		buf.WriteString(fmt.Sprintf("\tlisten on %s tls port %d\n", listen, srv.Port))
	} else {
		buf.WriteString(fmt.Sprintf("\tlisten on %s port %d\n", listen, srv.Port))
	}

	// TLS config
	if srv.TLS != nil {
		buf.WriteString(fmt.Sprintf("\ttls certificate \"%s\"\n", srv.TLS.Certificate))
		buf.WriteString(fmt.Sprintf("\ttls key \"%s\"\n", srv.TLS.Key))
		if srv.TLS.OCSP != "" {
			buf.WriteString(fmt.Sprintf("\ttls ocsp \"%s\"\n", srv.TLS.OCSP))
		}
		if srv.TLS.Ciphers != "" {
			buf.WriteString(fmt.Sprintf("\ttls ciphers \"%s\"\n", srv.TLS.Ciphers))
		}
		if srv.TLS.Protocols != "" {
			buf.WriteString(fmt.Sprintf("\ttls protocols \"%s\"\n", srv.TLS.Protocols))
		}
		if srv.TLS.Ticket {
			buf.WriteString("\ttls ticket lifetime default\n")
		}
	}

	// Root
	if srv.Root != "" {
		buf.WriteString(fmt.Sprintf("\troot \"%s\"\n", srv.Root))
	}

	// Directory options
	if srv.Directory != nil {
		if srv.Directory.Auto {
			buf.WriteString("\tdirectory auto index\n")
		}
		if srv.Directory.Index != "" {
			buf.WriteString(fmt.Sprintf("\tdirectory index \"%s\"\n", srv.Directory.Index))
		}
		if srv.Directory.NoIndex {
			buf.WriteString("\tdirectory no index\n")
		}
	}

	// Gzip
	if srv.Gzip {
		buf.WriteString("\tgzip-static\n")
	}

	// Error documents
	if srv.Errdocs {
		buf.WriteString("\terrdocs\n")
	}

	// Default type
	if srv.DefaultType != "" {
		buf.WriteString(fmt.Sprintf("\tdefault type \"%s\"\n", srv.DefaultType))
	}

	// Block patterns
	for _, pattern := range srv.BlockList {
		buf.WriteString(fmt.Sprintf("\tblock drop \"%s\"\n", pattern))
	}

	// Connection settings
	if srv.Connection.MaxRequests > 0 {
		buf.WriteString(fmt.Sprintf("\tconnection max requests %d\n", srv.Connection.MaxRequests))
	}
	if srv.Connection.MaxRequestBody > 0 {
		buf.WriteString(fmt.Sprintf("\tconnection max request body %d\n", srv.Connection.MaxRequestBody))
	}
	if srv.Connection.Timeout > 0 && srv.Connection.Timeout != 600 {
		buf.WriteString(fmt.Sprintf("\tconnection timeout %d\n", srv.Connection.Timeout))
	}

	// Logging
	if srv.Log.NoLog {
		buf.WriteString("\tno log\n")
	} else {
		if srv.Log.Access != "" {
			buf.WriteString(fmt.Sprintf("\tlog access \"%s\"\n", srv.Log.Access))
		}
		if srv.Log.Error != "" {
			buf.WriteString(fmt.Sprintf("\tlog error \"%s\"\n", srv.Log.Error))
		}
		if srv.Log.Style != "" && srv.Log.Style != "combined" {
			buf.WriteString(fmt.Sprintf("\tlog style %s\n", srv.Log.Style))
		}
	}

	// Locations
	for _, loc := range srv.Location {
		buf.WriteString(s.generateLocationBlock(loc))
	}

	buf.WriteString("}\n")
	return buf.String()
}

func (s *HTTPDService) generateLocationBlock(loc HTTPDLocation) string {
	var buf strings.Builder

	if loc.Match != "" {
		buf.WriteString(fmt.Sprintf("\n\tlocation match \"%s\" {\n", loc.Match))
	} else {
		buf.WriteString(fmt.Sprintf("\n\tlocation \"%s\" {\n", loc.Path))
	}

	if loc.Block {
		buf.WriteString("\t\tblock\n")
	} else {
		// Root override
		if loc.Root != "" {
			buf.WriteString(fmt.Sprintf("\t\troot \"%s\"\n", loc.Root))
		}

		// Directory options
		if loc.Directory != nil {
			if loc.Directory.Auto {
				buf.WriteString("\t\tdirectory auto index\n")
			}
			if loc.Directory.NoIndex {
				buf.WriteString("\t\tdirectory no index\n")
			}
		}

		// Reverse proxy
		if loc.Pass != "" {
			buf.WriteString(fmt.Sprintf("\t\tpass\n"))
		}

		// FastCGI
		if loc.FastCGI != nil {
			buf.WriteString(fmt.Sprintf("\t\tfastcgi socket \"%s\"\n", loc.FastCGI.Socket))
			for k, v := range loc.FastCGI.Param {
				buf.WriteString(fmt.Sprintf("\t\tfastcgi param %s \"%s\"\n", k, v))
			}
			if loc.FastCGI.Strip > 0 {
				buf.WriteString(fmt.Sprintf("\t\tfastcgi strip %d\n", loc.FastCGI.Strip))
			}
		}

		// Strip path components
		if loc.Strip > 0 {
			buf.WriteString(fmt.Sprintf("\t\tstrip %d\n", loc.Strip))
		}

		// Authentication
		if loc.Authenticate != "" {
			buf.WriteString(fmt.Sprintf("\t\tauthenticate \"%s\" with \"/htpasswd\"\n", loc.Authenticate))
		}

		// HSTS
		if loc.Hsts {
			if loc.HstsSubdom {
				buf.WriteString("\t\thsts subdomains\n")
			} else {
				buf.WriteString("\t\thsts\n")
			}
		}

		// Request rewrites
		for _, rw := range loc.RequestRewrite {
			buf.WriteString(fmt.Sprintf("\t\trequest rewrite \"%s\" \"%s\"\n", rw.Match, rw.Replace))
		}

		// Response headers
		for _, hdr := range loc.ResponseHeader {
			if hdr.Remove {
				buf.WriteString(fmt.Sprintf("\t\tresponse header remove \"%s\"\n", hdr.Name))
			} else {
				buf.WriteString(fmt.Sprintf("\t\tresponse header set \"%s\" \"%s\"\n", hdr.Name, hdr.Value))
			}
		}
	}

	buf.WriteString("\t}\n")
	return buf.String()
}

// Enable enables the httpd service
func (s *HTTPDService) Enable() error {
	s.mu.Lock()
	s.config.Enabled = true
	s.saveConfig()
	s.mu.Unlock()

	if err := s.GenerateConfig(); err != nil {
		return err
	}

	exec.Command("rcctl", "enable", "httpd").Run()
	return exec.Command("rcctl", "start", "httpd").Run()
}

// Disable disables the httpd service
func (s *HTTPDService) Disable() error {
	s.mu.Lock()
	s.config.Enabled = false
	s.saveConfig()
	s.mu.Unlock()

	exec.Command("rcctl", "stop", "httpd").Run()
	return exec.Command("rcctl", "disable", "httpd").Run()
}

// Reload reloads httpd configuration
func (s *HTTPDService) Reload() error {
	if err := s.GenerateConfig(); err != nil {
		return err
	}

	// Validate config
	output, err := exec.Command("httpd", "-n").CombinedOutput()
	if err != nil {
		return fmt.Errorf("config validation failed: %s", string(output))
	}

	return exec.Command("rcctl", "reload", "httpd").Run()
}

// ValidateConfig validates the httpd configuration
func (s *HTTPDService) ValidateConfig() error {
	if err := s.GenerateConfig(); err != nil {
		return err
	}

	output, err := exec.Command("httpd", "-n").CombinedOutput()
	if err != nil {
		return fmt.Errorf("config validation failed: %s", string(output))
	}

	return nil
}

// GetAccessLogs returns recent access log entries
func (s *HTTPDService) GetAccessLogs(serverName string, lines int) ([]map[string]interface{}, error) {
	s.mu.RLock()
	logPath := ""
	for _, srv := range s.config.Servers {
		if srv.Name == serverName && srv.Log.Access != "" {
			logPath = filepath.Join(s.config.Chroot, s.config.Logdir, srv.Log.Access)
			break
		}
	}
	s.mu.RUnlock()

	if logPath == "" {
		logPath = filepath.Join(s.config.Chroot, s.config.Logdir, "access.log")
	}

	file, err := os.Open(logPath)
	if err != nil {
		return nil, err
	}
	defer file.Close()

	var entries []map[string]interface{}
	scanner := bufio.NewScanner(file)
	var allLines []string

	for scanner.Scan() {
		allLines = append(allLines, scanner.Text())
	}

	// Get last N lines
	start := 0
	if len(allLines) > lines {
		start = len(allLines) - lines
	}

	// Parse combined log format
	logRegex := regexp.MustCompile(`^(\S+) \S+ \S+ \[([^\]]+)\] "([^"]*)" (\d+) (\d+|-) "([^"]*)" "([^"]*)"`)

	for _, line := range allLines[start:] {
		matches := logRegex.FindStringSubmatch(line)
		if matches != nil {
			size, _ := strconv.ParseInt(matches[5], 10, 64)
			status, _ := strconv.Atoi(matches[4])
			entries = append(entries, map[string]interface{}{
				"ip":         matches[1],
				"timestamp":  matches[2],
				"request":    matches[3],
				"status":     status,
				"size":       size,
				"referer":    matches[6],
				"user_agent": matches[7],
			})
		}
	}

	return entries, nil
}

// CreateHTPasswd creates or updates an htpasswd file
func (s *HTTPDService) CreateHTPasswd(realm string, username string, password string) error {
	htpasswdPath := filepath.Join(s.config.Chroot, "htpasswd")

	// Use htpasswd utility
	cmd := exec.Command("htpasswd", "-b", htpasswdPath, username, password)
	if _, err := os.Stat(htpasswdPath); os.IsNotExist(err) {
		cmd = exec.Command("htpasswd", "-bc", htpasswdPath, username, password)
	}

	return cmd.Run()
}

// GetServerTemplates returns common server configuration templates
func (s *HTTPDService) GetServerTemplates() []map[string]interface{} {
	return []map[string]interface{}{
		{
			"name":        "static",
			"description": "Static file server",
			"config": HTTPDServer{
				Name:   "example.com",
				Port:   80,
				Root:   "/htdocs",
				Gzip:   true,
				Directory: &HTTPDDirectory{
					Index: "index.html",
				},
				Log: HTTPDLog{Style: "combined"},
			},
		},
		{
			"name":        "static_tls",
			"description": "Static file server with TLS",
			"config": HTTPDServer{
				Name: "example.com",
				Port: 443,
				Root: "/htdocs",
				Gzip: true,
				TLS: &HTTPDTLSConfig{
					Certificate: "/etc/ssl/example.com.crt",
					Key:         "/etc/ssl/private/example.com.key",
					Protocols:   "TLSv1.2 TLSv1.3",
				},
				Directory: &HTTPDDirectory{
					Index: "index.html",
				},
				Location: []HTTPDLocation{
					{
						Path: "/",
						Hsts: true,
					},
				},
				Log: HTTPDLog{Style: "combined"},
			},
		},
		{
			"name":        "php_fcgi",
			"description": "PHP FastCGI server",
			"config": HTTPDServer{
				Name: "example.com",
				Port: 80,
				Root: "/htdocs",
				Location: []HTTPDLocation{
					{
						Match: "/.*.php$",
						FastCGI: &HTTPDFastCGI{
							Socket: "/run/php-fpm.sock",
						},
					},
				},
				Directory: &HTTPDDirectory{
					Index: "index.php",
				},
				Log: HTTPDLog{Style: "combined"},
			},
		},
		{
			"name":        "redirect_https",
			"description": "HTTP to HTTPS redirect",
			"config": HTTPDServer{
				Name: "example.com",
				Port: 80,
				Root: "/htdocs",
				Location: []HTTPDLocation{
					{
						Path: "/",
						Block: false,
						RequestRewrite: []HTTPDRewrite{
							{
								Match:   "(.*)",
								Replace: "https://$HTTP_HOST$1",
							},
						},
					},
				},
			},
		},
	}
}
