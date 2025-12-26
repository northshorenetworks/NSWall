// Package main generates a static site from the metrics database for GitHub Pages
//
// Exports:
//   - metrics.json: Current test results with trends
//   - runs.json: Test run history with pass/fail status
//   - tests.json: Test library catalog
//   - alerts.json: Recent alerts
package main

import (
	"database/sql"
	"encoding/json"
	"flag"
	"fmt"
	"log"
	"os"
	"path/filepath"
	"strings"
	"time"

	_ "github.com/mattn/go-sqlite3"
)

var (
	dbPath    = flag.String("db", "metrics.db", "Path to metrics database")
	outputDir = flag.String("output", "../../docs/metrics", "Output directory for generated files")
	days      = flag.Int("days", 30, "Number of days of history to include")
)

// Data structures for JSON export

type DashboardData struct {
	GeneratedAt string        `json:"generated_at"`
	Summary     Summary       `json:"summary"`
	Tests       []TestSummary `json:"tests"`
	Alerts      []Alert       `json:"alerts"`
}

type Summary struct {
	Total    int `json:"total"`
	Passed   int `json:"passed"`
	Warnings int `json:"warnings"`
	Failures int `json:"failures"`
}

type TestSummary struct {
	TestID        string         `json:"test_id"`
	TestName      string         `json:"test_name"`
	Category      string         `json:"category"`
	TestType      string         `json:"test_type"`
	Unit          string         `json:"unit"`
	Critical      bool           `json:"critical"`
	Status        string         `json:"status"`
	CurrentValue  *float64       `json:"current_value"`
	BaselineMean  *float64       `json:"baseline_mean"`
	BaselineStdDev *float64      `json:"baseline_std_dev"`
	DeviationPct  *float64       `json:"deviation_pct"`
	Trend         string         `json:"trend"`
	History       []HistoryPoint `json:"history"`
}

type HistoryPoint struct {
	Timestamp string  `json:"timestamp"`
	Value     float64 `json:"value"`
	Status    string  `json:"status"`
	CommitSHA string  `json:"commit_sha,omitempty"`
}

type Alert struct {
	Timestamp  string  `json:"timestamp"`
	TestID     string  `json:"test_id"`
	Severity   string  `json:"severity"`
	Message    string  `json:"message"`
	Value      float64 `json:"value"`
	Baseline   float64 `json:"baseline"`
	Deviation  float64 `json:"deviation"`
}

type TestRun struct {
	RunID        string    `json:"run_id"`
	Timestamp    time.Time `json:"timestamp"`
	Branch       string    `json:"branch"`
	CommitSHA    string    `json:"commit_sha"`
	Status       string    `json:"status"`
	TotalTests   int       `json:"total_tests"`
	Passed       int       `json:"passed"`
	Warnings     int       `json:"warnings"`
	Failures     int       `json:"failures"`
	Duration     float64   `json:"duration_secs"`
}

type TestLibraryEntry struct {
	TestID      string `json:"test_id"`
	TestName    string `json:"test_name"`
	Category    string `json:"category"`
	TestType    string `json:"test_type"`
	Description string `json:"description"`
	Unit        string `json:"unit"`
	Critical    bool   `json:"critical"`
	Enabled     bool   `json:"enabled"`
	Scale       *int   `json:"scale,omitempty"`
}

type RunsData struct {
	GeneratedAt string    `json:"generated_at"`
	Runs        []TestRun `json:"runs"`
}

type TestLibraryData struct {
	GeneratedAt string             `json:"generated_at"`
	Total       int                `json:"total"`
	Categories  map[string]int     `json:"categories"`
	Tests       []TestLibraryEntry `json:"tests"`
}

// TestHistory contains complete historical data for a single test
type TestHistory struct {
	TestID     string         `json:"test_id"`
	TestName   string         `json:"test_name"`
	Category   string         `json:"category"`
	Unit       string         `json:"unit"`
	Baseline   BaselineStats  `json:"baseline"`
	History    []HistoryPoint `json:"history"`
	Statistics TestStats      `json:"statistics"`
}

type BaselineStats struct {
	Mean   float64 `json:"mean"`
	StdDev float64 `json:"std_dev"`
	Min    float64 `json:"min"`
	Max    float64 `json:"max"`
	P50    float64 `json:"p50"`
	P95    float64 `json:"p95"`
	P99    float64 `json:"p99"`
}

type TestStats struct {
	TotalRuns   int     `json:"total_runs"`
	PassRate    float64 `json:"pass_rate"`
	WarningRate float64 `json:"warning_rate"`
	FailRate    float64 `json:"fail_rate"`
	AllTimeMin  float64 `json:"all_time_min"`
	AllTimeMax  float64 `json:"all_time_max"`
	AllTimeMean float64 `json:"all_time_mean"`
}

func main() {
	flag.Parse()

	db, err := sql.Open("sqlite3", *dbPath)
	if err != nil {
		log.Fatalf("Failed to open database: %v", err)
	}
	defer db.Close()

	// Create output directories
	if err := os.MkdirAll(*outputDir, 0755); err != nil {
		log.Fatalf("Failed to create output directory: %v", err)
	}
	historyDir := filepath.Join(*outputDir, "history")
	if err := os.MkdirAll(historyDir, 0755); err != nil {
		log.Fatalf("Failed to create history directory: %v", err)
	}

	// Generate all data files
	generateMetricsJSON(db)
	generateRunsJSON(db)
	generateTestLibraryJSON(db)
	generateAlertsJSON(db)
	generateTestHistoryFiles(db, historyDir)

	log.Printf("Site generated in %s", *outputDir)
}

// generateTestHistoryFiles creates individual JSON files with complete history for each test
func generateTestHistoryFiles(db *sql.DB, historyDir string) {
	rows, err := db.Query(`SELECT test_id, test_name, category, expected_unit FROM test_library`)
	if err != nil {
		log.Printf("Failed to get tests: %v", err)
		return
	}
	defer rows.Close()

	count := 0
	for rows.Next() {
		var testID, testName, category, unit string
		rows.Scan(&testID, &testName, &category, &unit)

		history := TestHistory{
			TestID:   testID,
			TestName: testName,
			Category: category,
			Unit:     unit,
			History:  make([]HistoryPoint, 0),
		}

		// Get baseline
		db.QueryRow(`
			SELECT mean, std_dev, min_value, max_value, p50, p95, p99
			FROM test_baselines WHERE test_id = ? AND branch = 'main'`,
			testID).Scan(&history.Baseline.Mean, &history.Baseline.StdDev,
			&history.Baseline.Min, &history.Baseline.Max,
			&history.Baseline.P50, &history.Baseline.P95, &history.Baseline.P99)

		// Get ALL history (complete, not just recent)
		historyRows, _ := db.Query(`
			SELECT r.timestamp, tr.value, tr.status, r.commit_sha
			FROM test_results tr
			JOIN test_runs r ON tr.run_id = r.run_id
			WHERE tr.test_id = ?
			ORDER BY r.timestamp ASC`, testID)

		var values []float64
		passed, warnings, failures := 0, 0, 0

		for historyRows.Next() {
			var h HistoryPoint
			var timestamp time.Time
			historyRows.Scan(&timestamp, &h.Value, &h.Status, &h.CommitSHA)
			h.Timestamp = timestamp.Format(time.RFC3339)
			history.History = append(history.History, h)
			values = append(values, h.Value)

			switch h.Status {
			case "passed":
				passed++
			case "warning":
				warnings++
			case "failed":
				failures++
			}
		}
		historyRows.Close()

		// Calculate statistics
		if len(values) > 0 {
			history.Statistics.TotalRuns = len(values)
			history.Statistics.PassRate = float64(passed) / float64(len(values)) * 100
			history.Statistics.WarningRate = float64(warnings) / float64(len(values)) * 100
			history.Statistics.FailRate = float64(failures) / float64(len(values)) * 100

			var sum float64
			min, max := values[0], values[0]
			for _, v := range values {
				sum += v
				if v < min {
					min = v
				}
				if v > max {
					max = v
				}
			}
			history.Statistics.AllTimeMean = sum / float64(len(values))
			history.Statistics.AllTimeMin = min
			history.Statistics.AllTimeMax = max
		}

		// Write to file (use - instead of . for filename)
		filename := strings.ReplaceAll(testID, ".", "-") + ".json"
		writeJSON(filepath.Join(historyDir, filename), history)
		count++
	}

	log.Printf("Generated %d test history files in %s", count, historyDir)
}

func generateMetricsJSON(db *sql.DB) {
	data := DashboardData{
		GeneratedAt: time.Now().Format(time.RFC3339),
		Tests:       make([]TestSummary, 0),
		Alerts:      make([]Alert, 0),
	}

	// Get all tests from library
	rows, err := db.Query(`
		SELECT test_id, test_name, category, test_type, expected_unit, critical
		FROM test_library WHERE enabled = 1
		ORDER BY category, test_id`)
	if err != nil {
		log.Printf("Failed to get tests: %v", err)
		return
	}
	defer rows.Close()

	for rows.Next() {
		var test TestSummary
		var critical int
		err := rows.Scan(&test.TestID, &test.TestName, &test.Category, &test.TestType, &test.Unit, &critical)
		if err != nil {
			continue
		}
		test.Critical = critical == 1

		// Get latest result
		var latestValue sql.NullFloat64
		var latestStatus sql.NullString
		db.QueryRow(`
			SELECT tr.value, tr.status
			FROM test_results tr
			JOIN test_runs r ON tr.run_id = r.run_id
			WHERE tr.test_id = ?
			ORDER BY r.timestamp DESC LIMIT 1`, test.TestID).Scan(&latestValue, &latestStatus)

		if latestValue.Valid {
			test.CurrentValue = &latestValue.Float64
		}
		if latestStatus.Valid {
			test.Status = latestStatus.String
		} else {
			test.Status = "no_data"
		}

		// Get baseline
		var baselineMean, baselineStdDev sql.NullFloat64
		db.QueryRow(`
			SELECT mean, std_dev FROM test_baselines
			WHERE test_id = ? AND branch = 'main'`,
			test.TestID).Scan(&baselineMean, &baselineStdDev)

		if baselineMean.Valid {
			test.BaselineMean = &baselineMean.Float64
		}
		if baselineStdDev.Valid {
			test.BaselineStdDev = &baselineStdDev.Float64
		}

		// Calculate deviation
		if test.CurrentValue != nil && test.BaselineMean != nil && *test.BaselineMean > 0 {
			dev := ((*test.CurrentValue - *test.BaselineMean) / *test.BaselineMean) * 100
			test.DeviationPct = &dev
		}

		// Get history
		historyRows, _ := db.Query(`
			SELECT r.timestamp, tr.value, tr.status, r.commit_sha
			FROM test_results tr
			JOIN test_runs r ON tr.run_id = r.run_id
			WHERE tr.test_id = ? AND r.timestamp > datetime('now', '-' || ? || ' days')
			ORDER BY r.timestamp ASC`, test.TestID, *days)

		test.History = make([]HistoryPoint, 0)
		var prevValue float64
		for historyRows.Next() {
			var h HistoryPoint
			var timestamp time.Time
			historyRows.Scan(&timestamp, &h.Value, &h.Status, &h.CommitSHA)
			h.Timestamp = timestamp.Format(time.RFC3339)
			test.History = append(test.History, h)
			prevValue = h.Value
		}
		historyRows.Close()

		// Determine trend
		if len(test.History) >= 2 && test.CurrentValue != nil {
			change := *test.CurrentValue - prevValue
			if change > 0.05*prevValue {
				test.Trend = "up" // Getting slower (worse for latency metrics)
			} else if change < -0.05*prevValue {
				test.Trend = "down" // Getting faster (better)
			} else {
				test.Trend = "flat"
			}
		}

		// Update summary
		data.Summary.Total++
		switch test.Status {
		case "passed":
			data.Summary.Passed++
		case "warning":
			data.Summary.Warnings++
		case "failed":
			data.Summary.Failures++
		}

		data.Tests = append(data.Tests, test)
	}

	// Get recent alerts
	alertRows, _ := db.Query(`
		SELECT a.created_at, tr.test_id, a.severity, a.message, a.actual_value, a.baseline_mean, a.deviation
		FROM alerts a
		JOIN test_results tr ON a.metric_id = tr.id
		WHERE a.created_at > datetime('now', '-7 days')
		ORDER BY a.created_at DESC LIMIT 20`)

	for alertRows.Next() {
		var alert Alert
		var timestamp time.Time
		alertRows.Scan(&timestamp, &alert.TestID, &alert.Severity, &alert.Message,
			&alert.Value, &alert.Baseline, &alert.Deviation)
		alert.Timestamp = timestamp.Format(time.RFC3339)
		data.Alerts = append(data.Alerts, alert)
	}
	alertRows.Close()

	writeJSON(filepath.Join(*outputDir, "metrics.json"), data)
	log.Printf("Generated metrics.json with %d tests", len(data.Tests))
}

func generateRunsJSON(db *sql.DB) {
	data := RunsData{
		GeneratedAt: time.Now().Format(time.RFC3339),
		Runs:        make([]TestRun, 0),
	}

	rows, err := db.Query(`
		SELECT r.run_id, r.timestamp, r.branch, r.commit_sha, r.status, r.duration_secs,
			COUNT(tr.id) as total,
			SUM(CASE WHEN tr.status = 'passed' THEN 1 ELSE 0 END) as passed,
			SUM(CASE WHEN tr.status = 'warning' THEN 1 ELSE 0 END) as warnings,
			SUM(CASE WHEN tr.status = 'failed' THEN 1 ELSE 0 END) as failures
		FROM test_runs r
		LEFT JOIN test_results tr ON r.run_id = tr.run_id
		WHERE r.timestamp > datetime('now', '-' || ? || ' days')
		GROUP BY r.run_id
		ORDER BY r.timestamp DESC`, *days)

	if err != nil {
		log.Printf("Failed to get runs: %v", err)
		return
	}
	defer rows.Close()

	for rows.Next() {
		var run TestRun
		var duration sql.NullFloat64
		err := rows.Scan(&run.RunID, &run.Timestamp, &run.Branch, &run.CommitSHA, &run.Status,
			&duration, &run.TotalTests, &run.Passed, &run.Warnings, &run.Failures)
		if err != nil {
			continue
		}
		if duration.Valid {
			run.Duration = duration.Float64
		}
		data.Runs = append(data.Runs, run)
	}

	writeJSON(filepath.Join(*outputDir, "runs.json"), data)
	log.Printf("Generated runs.json with %d runs", len(data.Runs))
}

func generateTestLibraryJSON(db *sql.DB) {
	data := TestLibraryData{
		GeneratedAt: time.Now().Format(time.RFC3339),
		Categories:  make(map[string]int),
		Tests:       make([]TestLibraryEntry, 0),
	}

	rows, err := db.Query(`
		SELECT test_id, test_name, category, test_type, description, expected_unit, critical, enabled, default_scale
		FROM test_library
		ORDER BY category, test_id`)
	if err != nil {
		log.Printf("Failed to get test library: %v", err)
		return
	}
	defer rows.Close()

	for rows.Next() {
		var test TestLibraryEntry
		var critical, enabled int
		var scale sql.NullInt64
		var description sql.NullString
		err := rows.Scan(&test.TestID, &test.TestName, &test.Category, &test.TestType,
			&description, &test.Unit, &critical, &enabled, &scale)
		if err != nil {
			continue
		}
		test.Critical = critical == 1
		test.Enabled = enabled == 1
		if description.Valid {
			test.Description = description.String
		}
		if scale.Valid {
			s := int(scale.Int64)
			test.Scale = &s
		}

		data.Tests = append(data.Tests, test)
		data.Total++
		data.Categories[test.Category]++
	}

	writeJSON(filepath.Join(*outputDir, "tests.json"), data)
	log.Printf("Generated tests.json with %d tests across %d categories", data.Total, len(data.Categories))
}

func generateAlertsJSON(db *sql.DB) {
	alerts := make([]Alert, 0)

	rows, _ := db.Query(`
		SELECT a.created_at, COALESCE(tr.test_id, 'unknown'), a.severity, a.message,
			a.actual_value, COALESCE(a.baseline_mean, 0), COALESCE(a.deviation, 0)
		FROM alerts a
		LEFT JOIN test_results tr ON a.metric_id = tr.id
		WHERE a.created_at > datetime('now', '-' || ? || ' days')
		ORDER BY a.created_at DESC`, *days)

	for rows.Next() {
		var alert Alert
		var timestamp time.Time
		rows.Scan(&timestamp, &alert.TestID, &alert.Severity, &alert.Message,
			&alert.Value, &alert.Baseline, &alert.Deviation)
		alert.Timestamp = timestamp.Format(time.RFC3339)
		alerts = append(alerts, alert)
	}
	rows.Close()

	writeJSON(filepath.Join(*outputDir, "alerts.json"), alerts)
	log.Printf("Generated alerts.json with %d alerts", len(alerts))
}

func writeJSON(path string, data interface{}) {
	f, err := os.Create(path)
	if err != nil {
		log.Printf("Failed to create %s: %v", path, err)
		return
	}
	defer f.Close()

	enc := json.NewEncoder(f)
	enc.SetIndent("", "  ")
	if err := enc.Encode(data); err != nil {
		log.Printf("Failed to write %s: %v", path, err)
	}
}
