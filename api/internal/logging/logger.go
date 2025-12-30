// Package logging provides structured logging for NSWall API
package logging

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"log/syslog"
	"net/http"
	"os"
	"runtime"
	"sync"
	"time"
)

// Level represents log severity
type Level int

const (
	LevelDebug Level = iota
	LevelInfo
	LevelWarn
	LevelError
	LevelFatal
)

func (l Level) String() string {
	switch l {
	case LevelDebug:
		return "DEBUG"
	case LevelInfo:
		return "INFO"
	case LevelWarn:
		return "WARN"
	case LevelError:
		return "ERROR"
	case LevelFatal:
		return "FATAL"
	default:
		return "UNKNOWN"
	}
}

// LogEntry represents a structured log entry
type LogEntry struct {
	Timestamp   time.Time              `json:"timestamp"`
	Level       string                 `json:"level"`
	Message     string                 `json:"message"`
	Component   string                 `json:"component,omitempty"`
	RequestID   string                 `json:"request_id,omitempty"`
	UserID      string                 `json:"user_id,omitempty"`
	ClientIP    string                 `json:"client_ip,omitempty"`
	Method      string                 `json:"method,omitempty"`
	Path        string                 `json:"path,omitempty"`
	StatusCode  int                    `json:"status_code,omitempty"`
	Duration    float64                `json:"duration_ms,omitempty"`
	Error       string                 `json:"error,omitempty"`
	Stack       string                 `json:"stack,omitempty"`
	Fields      map[string]interface{} `json:"fields,omitempty"`
}

// Logger provides structured logging
type Logger struct {
	mu        sync.Mutex
	output    io.Writer
	syslogW   *syslog.Writer
	level     Level
	component string
	format    string // "json" or "text"
	fields    map[string]interface{}
}

// Config holds logger configuration
type Config struct {
	Level      string `json:"level"`      // debug, info, warn, error
	Format     string `json:"format"`     // json or text
	Output     string `json:"output"`     // stdout, stderr, file path
	SyslogAddr string `json:"syslog"`     // syslog address (e.g., "localhost:514")
	Component  string `json:"component"`  // component name
}

var (
	defaultLogger *Logger
	once          sync.Once
)

// Init initializes the default logger
func Init(cfg Config) error {
	var err error
	once.Do(func() {
		defaultLogger, err = NewLogger(cfg)
	})
	return err
}

// Default returns the default logger
func Default() *Logger {
	if defaultLogger == nil {
		defaultLogger, _ = NewLogger(Config{
			Level:  "info",
			Format: "json",
			Output: "stdout",
		})
	}
	return defaultLogger
}

// NewLogger creates a new logger instance
func NewLogger(cfg Config) (*Logger, error) {
	l := &Logger{
		format:    cfg.Format,
		component: cfg.Component,
		fields:    make(map[string]interface{}),
	}

	// Parse level
	switch cfg.Level {
	case "debug":
		l.level = LevelDebug
	case "info":
		l.level = LevelInfo
	case "warn":
		l.level = LevelWarn
	case "error":
		l.level = LevelError
	default:
		l.level = LevelInfo
	}

	// Set output
	switch cfg.Output {
	case "stdout", "":
		l.output = os.Stdout
	case "stderr":
		l.output = os.Stderr
	default:
		f, err := os.OpenFile(cfg.Output, os.O_APPEND|os.O_CREATE|os.O_WRONLY, 0644)
		if err != nil {
			return nil, fmt.Errorf("failed to open log file: %w", err)
		}
		l.output = f
	}

	// Setup syslog if configured
	if cfg.SyslogAddr != "" {
		w, err := syslog.Dial("udp", cfg.SyslogAddr, syslog.LOG_INFO|syslog.LOG_DAEMON, "nswall-api")
		if err != nil {
			return nil, fmt.Errorf("failed to connect to syslog: %w", err)
		}
		l.syslogW = w
	}

	return l, nil
}

// WithComponent creates a new logger with a component name
func (l *Logger) WithComponent(name string) *Logger {
	return &Logger{
		output:    l.output,
		syslogW:   l.syslogW,
		level:     l.level,
		component: name,
		format:    l.format,
		fields:    copyFields(l.fields),
	}
}

// WithField adds a field to the logger
func (l *Logger) WithField(key string, value interface{}) *Logger {
	newFields := copyFields(l.fields)
	newFields[key] = value
	return &Logger{
		output:    l.output,
		syslogW:   l.syslogW,
		level:     l.level,
		component: l.component,
		format:    l.format,
		fields:    newFields,
	}
}

// WithFields adds multiple fields to the logger
func (l *Logger) WithFields(fields map[string]interface{}) *Logger {
	newFields := copyFields(l.fields)
	for k, v := range fields {
		newFields[k] = v
	}
	return &Logger{
		output:    l.output,
		syslogW:   l.syslogW,
		level:     l.level,
		component: l.component,
		format:    l.format,
		fields:    newFields,
	}
}

// WithRequest creates a logger with request context
func (l *Logger) WithRequest(r *http.Request) *Logger {
	fields := copyFields(l.fields)
	fields["method"] = r.Method
	fields["path"] = r.URL.Path
	fields["client_ip"] = r.RemoteAddr
	if reqID := r.Header.Get("X-Request-ID"); reqID != "" {
		fields["request_id"] = reqID
	}
	if userID := r.Context().Value("user_id"); userID != nil {
		fields["user_id"] = userID
	}
	return &Logger{
		output:    l.output,
		syslogW:   l.syslogW,
		level:     l.level,
		component: l.component,
		format:    l.format,
		fields:    fields,
	}
}

func copyFields(src map[string]interface{}) map[string]interface{} {
	dst := make(map[string]interface{}, len(src))
	for k, v := range src {
		dst[k] = v
	}
	return dst
}

// log writes a log entry
func (l *Logger) log(level Level, msg string, err error) {
	if level < l.level {
		return
	}

	entry := LogEntry{
		Timestamp: time.Now().UTC(),
		Level:     level.String(),
		Message:   msg,
		Component: l.component,
		Fields:    l.fields,
	}

	// Extract fields
	if reqID, ok := l.fields["request_id"].(string); ok {
		entry.RequestID = reqID
	}
	if userID, ok := l.fields["user_id"].(string); ok {
		entry.UserID = userID
	}
	if clientIP, ok := l.fields["client_ip"].(string); ok {
		entry.ClientIP = clientIP
	}
	if method, ok := l.fields["method"].(string); ok {
		entry.Method = method
	}
	if path, ok := l.fields["path"].(string); ok {
		entry.Path = path
	}
	if statusCode, ok := l.fields["status_code"].(int); ok {
		entry.StatusCode = statusCode
	}
	if duration, ok := l.fields["duration_ms"].(float64); ok {
		entry.Duration = duration
	}

	if err != nil {
		entry.Error = err.Error()
		if level >= LevelError {
			entry.Stack = getStack()
		}
	}

	l.write(entry)
}

func (l *Logger) write(entry LogEntry) {
	l.mu.Lock()
	defer l.mu.Unlock()

	var output string
	if l.format == "json" {
		data, _ := json.Marshal(entry)
		output = string(data)
	} else {
		// Text format
		output = fmt.Sprintf("%s [%s] %s",
			entry.Timestamp.Format("2006-01-02 15:04:05"),
			entry.Level,
			entry.Message)
		if entry.Component != "" {
			output = fmt.Sprintf("%s [%s] [%s] %s",
				entry.Timestamp.Format("2006-01-02 15:04:05"),
				entry.Level,
				entry.Component,
				entry.Message)
		}
		if entry.Error != "" {
			output += fmt.Sprintf(" error=%s", entry.Error)
		}
	}

	fmt.Fprintln(l.output, output)

	// Also write to syslog if configured
	if l.syslogW != nil {
		switch entry.Level {
		case "DEBUG":
			l.syslogW.Debug(entry.Message)
		case "INFO":
			l.syslogW.Info(entry.Message)
		case "WARN":
			l.syslogW.Warning(entry.Message)
		case "ERROR":
			l.syslogW.Err(entry.Message)
		case "FATAL":
			l.syslogW.Crit(entry.Message)
		}
	}
}

func getStack() string {
	buf := make([]byte, 4096)
	n := runtime.Stack(buf, false)
	return string(buf[:n])
}

// Debug logs a debug message
func (l *Logger) Debug(msg string) {
	l.log(LevelDebug, msg, nil)
}

// Debugf logs a formatted debug message
func (l *Logger) Debugf(format string, args ...interface{}) {
	l.log(LevelDebug, fmt.Sprintf(format, args...), nil)
}

// Info logs an info message
func (l *Logger) Info(msg string) {
	l.log(LevelInfo, msg, nil)
}

// Infof logs a formatted info message
func (l *Logger) Infof(format string, args ...interface{}) {
	l.log(LevelInfo, fmt.Sprintf(format, args...), nil)
}

// Warn logs a warning message
func (l *Logger) Warn(msg string) {
	l.log(LevelWarn, msg, nil)
}

// Warnf logs a formatted warning message
func (l *Logger) Warnf(format string, args ...interface{}) {
	l.log(LevelWarn, fmt.Sprintf(format, args...), nil)
}

// Error logs an error message
func (l *Logger) Error(msg string, err error) {
	l.log(LevelError, msg, err)
}

// Errorf logs a formatted error message
func (l *Logger) Errorf(format string, args ...interface{}) {
	l.log(LevelError, fmt.Sprintf(format, args...), nil)
}

// Fatal logs a fatal message and exits
func (l *Logger) Fatal(msg string, err error) {
	l.log(LevelFatal, msg, err)
	os.Exit(1)
}

// Package-level convenience functions
func Debug(msg string)                           { Default().Debug(msg) }
func Debugf(format string, args ...interface{})  { Default().Debugf(format, args...) }
func Info(msg string)                            { Default().Info(msg) }
func Infof(format string, args ...interface{})   { Default().Infof(format, args...) }
func Warn(msg string)                            { Default().Warn(msg) }
func Warnf(format string, args ...interface{})   { Default().Warnf(format, args...) }
func Error(msg string, err error)                { Default().Error(msg, err) }
func Errorf(format string, args ...interface{})  { Default().Errorf(format, args...) }
func Fatal(msg string, err error)                { Default().Fatal(msg, err) }
func WithField(k string, v interface{}) *Logger  { return Default().WithField(k, v) }
func WithFields(f map[string]interface{}) *Logger { return Default().WithFields(f) }
func WithComponent(name string) *Logger          { return Default().WithComponent(name) }
func WithRequest(r *http.Request) *Logger        { return Default().WithRequest(r) }

// ContextKey for context values
type ContextKey string

const (
	LoggerKey   ContextKey = "logger"
	RequestIDKey ContextKey = "request_id"
	UserIDKey    ContextKey = "user_id"
)

// FromContext retrieves logger from context
func FromContext(ctx context.Context) *Logger {
	if l, ok := ctx.Value(LoggerKey).(*Logger); ok {
		return l
	}
	return Default()
}

// WithContext adds logger to context
func WithContext(ctx context.Context, l *Logger) context.Context {
	return context.WithValue(ctx, LoggerKey, l)
}
