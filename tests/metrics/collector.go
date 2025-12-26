// Package main provides a metrics collector for NSWall performance testing
//
// Features:
//   - Record test results from stress tests
//   - Compare against baselines and detect regressions
//   - Calculate statistics and update baselines
//   - Generate alerts when metrics deviate
//   - Export reports in various formats
//
// Usage:
//   metrics-collector record --test-id pf.filter.parse.1000 --value 150 --unit ms
//   metrics-collector compare --run-id abc123 --baseline main
//   metrics-collector report --format markdown
//   metrics-collector update-baseline --branch main
package main

import (
	"database/sql"
	"encoding/json"
	"flag"
	"fmt"
	"log"
	"math"
	"os"
	"os/exec"
	"sort"
	"strings"
	"time"

	"github.com/google/uuid"
	_ "github.com/mattn/go-sqlite3"
)

// Configuration
var (
	dbPath     = flag.String("db", "metrics.db", "Path to SQLite database")
	schemaPath = flag.String("schema", "schema.sql", "Path to schema file")
	verbose    = flag.Bool("v", false, "Verbose output")
)

// Database connection
var db *sql.DB

// TestResult represents a single test result
type TestResult struct {
	RunID         string    `json:"run_id"`
	TestID        string    `json:"test_id"`
	Value         float64   `json:"value"`
	Unit          string    `json:"unit"`
	Status        string    `json:"status"`
	Message       string    `json:"message,omitempty"`
	BaselineValue float64   `json:"baseline_value,omitempty"`
	DeviationPct  float64   `json:"deviation_pct,omitempty"`
	DeviationStd  float64   `json:"deviation_std,omitempty"`
	StartedAt     time.Time `json:"started_at,omitempty"`
	CompletedAt   time.Time `json:"completed_at,omitempty"`
}

// TestRun represents a test run
type TestRun struct {
	RunID         string    `json:"run_id"`
	Timestamp     time.Time `json:"timestamp"`
	Branch        string    `json:"branch"`
	CommitSHA     string    `json:"commit_sha"`
	CommitMessage string    `json:"commit_message,omitempty"`
	CIProvider    string    `json:"ci_provider,omitempty"`
	CIRunID       string    `json:"ci_run_id,omitempty"`
	CIRunURL      string    `json:"ci_run_url,omitempty"`
	Platform      string    `json:"platform"`
	OSVersion     string    `json:"os_version,omitempty"`
	CPUCount      int       `json:"cpu_count,omitempty"`
	MemoryMB      int       `json:"memory_mb,omitempty"`
	TestType      string    `json:"test_type"`
	TestSuite     string    `json:"test_suite,omitempty"`
	DurationSecs  float64   `json:"duration_secs,omitempty"`
	Status        string    `json:"status"`
	ErrorMessage  string    `json:"error_message,omitempty"`
}

// Baseline represents statistical baseline for a test
type Baseline struct {
	TestID      string    `json:"test_id"`
	Branch      string    `json:"branch"`
	Mean        float64   `json:"mean"`
	StdDev      float64   `json:"std_dev"`
	MinValue    float64   `json:"min_value"`
	MaxValue    float64   `json:"max_value"`
	SampleCount int       `json:"sample_count"`
	P50         float64   `json:"p50"`
	P95         float64   `json:"p95"`
	P99         float64   `json:"p99"`
	History     []float64 `json:"history"`
	LastUpdated time.Time `json:"last_updated"`
}

// Alert represents an alert generated from test comparison
type Alert struct {
	RunID        string  `json:"run_id"`
	TestID       string  `json:"test_id"`
	Severity     string  `json:"severity"`
	Message      string  `json:"message"`
	ActualValue  float64 `json:"actual_value"`
	BaselineMean float64 `json:"baseline_mean"`
	DeviationPct float64 `json:"deviation_pct"`
	DeviationStd float64 `json:"deviation_std"`
}

// ComparisonReport represents the result of comparing a run against baseline
type ComparisonReport struct {
	RunID          string   `json:"run_id"`
	Branch         string   `json:"branch"`
	BaselineBranch string   `json:"baseline_branch"`
	Timestamp      string   `json:"timestamp"`
	TotalTests     int      `json:"total_tests"`
	Passed         int      `json:"passed"`
	Warnings       int      `json:"warnings"`
	Failures       int      `json:"failures"`
	Alerts         []Alert  `json:"alerts"`
	Results        []TestResult `json:"results"`
}

func main() {
	flag.Parse()

	if len(flag.Args()) < 1 {
		printUsage()
		os.Exit(1)
	}

	// Initialize database
	var err error
	db, err = initDB(*dbPath, *schemaPath)
	if err != nil {
		log.Fatalf("Failed to initialize database: %v", err)
	}
	defer db.Close()

	// Route command
	cmd := flag.Args()[0]
	args := flag.Args()[1:]

	switch cmd {
	case "init":
		cmdInit()
	case "start-run":
		cmdStartRun(args)
	case "record":
		cmdRecord(args)
	case "end-run":
		cmdEndRun(args)
	case "compare":
		cmdCompare(args)
	case "update-baseline":
		cmdUpdateBaseline(args)
	case "report":
		cmdReport(args)
	case "list-tests":
		cmdListTests(args)
	case "trend":
		cmdTrend(args)
	case "export":
		cmdExport(args)
	default:
		log.Fatalf("Unknown command: %s", cmd)
	}
}

func printUsage() {
	fmt.Println(`NSWall Metrics Collector

Usage: metrics-collector [options] <command> [args]

Commands:
  init                      Initialize the database
  start-run                 Start a new test run
  record                    Record a test result
  end-run                   End a test run
  compare                   Compare run against baseline
  update-baseline           Update baseline from recent runs
  report                    Generate a report
  list-tests                List available tests
  trend                     Show metric trends
  export                    Export data

Options:`)
	flag.PrintDefaults()
}

// =============================================================================
// DATABASE INITIALIZATION
// =============================================================================

func initDB(dbPath, schemaPath string) (*sql.DB, error) {
	db, err := sql.Open("sqlite3", dbPath)
	if err != nil {
		return nil, err
	}

	// Check if schema exists
	if schemaPath != "" {
		if _, err := os.Stat(schemaPath); err == nil {
			schema, err := os.ReadFile(schemaPath)
			if err != nil {
				return nil, fmt.Errorf("failed to read schema: %w", err)
			}
			if _, err := db.Exec(string(schema)); err != nil {
				return nil, fmt.Errorf("failed to apply schema: %w", err)
			}
		}
	}

	return db, nil
}

func cmdInit() {
	log.Println("Database initialized successfully")
}

// =============================================================================
// TEST RUN MANAGEMENT
// =============================================================================

func cmdStartRun(args []string) {
	fs := flag.NewFlagSet("start-run", flag.ExitOnError)
	branch := fs.String("branch", "", "Git branch")
	commit := fs.String("commit", "", "Git commit SHA")
	testType := fs.String("type", "stress", "Test type")
	testSuite := fs.String("suite", "", "Test suite")
	fs.Parse(args)

	// Get git info if not provided
	if *branch == "" {
		*branch = getGitBranch()
	}
	if *commit == "" {
		*commit = getGitCommit()
	}

	runID := uuid.New().String()
	platform := getPlatform()
	osVersion := getOSVersion()

	run := TestRun{
		RunID:     runID,
		Timestamp: time.Now(),
		Branch:    *branch,
		CommitSHA: *commit,
		Platform:  platform,
		OSVersion: osVersion,
		TestType:  *testType,
		TestSuite: *testSuite,
		Status:    "running",
	}

	// Insert run
	_, err := db.Exec(`
		INSERT INTO test_runs (run_id, branch, commit_sha, platform, os_version, test_type, test_suite, status)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
		run.RunID, run.Branch, run.CommitSHA, run.Platform, run.OSVersion, run.TestType, run.TestSuite, run.Status)
	if err != nil {
		log.Fatalf("Failed to start run: %v", err)
	}

	// Output run ID
	fmt.Printf("RUN_ID=%s\n", runID)
	log.Printf("Started test run: %s", runID)
}

func cmdEndRun(args []string) {
	fs := flag.NewFlagSet("end-run", flag.ExitOnError)
	runID := fs.String("run-id", "", "Run ID")
	status := fs.String("status", "passed", "Run status (passed, failed, error)")
	fs.Parse(args)

	if *runID == "" {
		log.Fatal("--run-id is required")
	}

	_, err := db.Exec(`
		UPDATE test_runs SET status = ? WHERE run_id = ?`,
		*status, *runID)
	if err != nil {
		log.Fatalf("Failed to end run: %v", err)
	}

	log.Printf("Ended test run: %s with status: %s", *runID, *status)
}

// =============================================================================
// RECORDING RESULTS
// =============================================================================

func cmdRecord(args []string) {
	fs := flag.NewFlagSet("record", flag.ExitOnError)
	runID := fs.String("run-id", "", "Run ID")
	testID := fs.String("test-id", "", "Test ID from library")
	value := fs.Float64("value", 0, "Metric value")
	unit := fs.String("unit", "ms", "Metric unit")
	fs.Parse(args)

	if *runID == "" || *testID == "" {
		log.Fatal("--run-id and --test-id are required")
	}

	// Get baseline for comparison
	baseline, err := getBaseline(*testID, "main")

	result := TestResult{
		RunID:       *runID,
		TestID:      *testID,
		Value:       *value,
		Unit:        *unit,
		CompletedAt: time.Now(),
	}

	// Compare against baseline
	if err == nil && baseline.SampleCount > 0 {
		result.BaselineValue = baseline.Mean
		result.DeviationPct = ((*value - baseline.Mean) / baseline.Mean) * 100
		if baseline.StdDev > 0 {
			result.DeviationStd = (*value - baseline.Mean) / baseline.StdDev
		}

		// Determine status based on deviation
		result.Status = "passed"
		if result.DeviationPct > 50 || result.DeviationStd > 3 {
			result.Status = "failed"
			result.Message = fmt.Sprintf("%.1f%% worse than baseline (%.1f std devs)", result.DeviationPct, result.DeviationStd)
		} else if result.DeviationPct > 20 || result.DeviationStd > 2 {
			result.Status = "warning"
			result.Message = fmt.Sprintf("%.1f%% worse than baseline", result.DeviationPct)
		}
	} else {
		result.Status = "passed"
		result.Message = "No baseline available"
	}

	// Insert result
	_, err = db.Exec(`
		INSERT INTO test_results (run_id, test_id, value, unit, status, message, baseline_value, deviation_pct, deviation_std, completed_at)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		result.RunID, result.TestID, result.Value, result.Unit, result.Status, result.Message,
		result.BaselineValue, result.DeviationPct, result.DeviationStd, result.CompletedAt)
	if err != nil {
		log.Fatalf("Failed to record result: %v", err)
	}

	// Generate alert if needed
	if result.Status != "passed" {
		createAlert(result)
	}

	// Output result
	fmt.Printf("RESULT=%s\n", result.Status)
	if *verbose {
		log.Printf("Recorded %s: %.2f %s (%s)", *testID, *value, *unit, result.Status)
		if result.Message != "" {
			log.Printf("  %s", result.Message)
		}
	}
}

// =============================================================================
// BASELINE MANAGEMENT
// =============================================================================

func getBaseline(testID, branch string) (*Baseline, error) {
	var baseline Baseline
	var historyJSON sql.NullString

	err := db.QueryRow(`
		SELECT test_id, branch, mean, std_dev, min_value, max_value, sample_count, p50, p95, p99, history, last_updated
		FROM test_baselines WHERE test_id = ? AND branch = ?`,
		testID, branch).Scan(
		&baseline.TestID, &baseline.Branch, &baseline.Mean, &baseline.StdDev,
		&baseline.MinValue, &baseline.MaxValue, &baseline.SampleCount,
		&baseline.P50, &baseline.P95, &baseline.P99, &historyJSON, &baseline.LastUpdated)

	if err != nil {
		return nil, err
	}

	if historyJSON.Valid {
		json.Unmarshal([]byte(historyJSON.String), &baseline.History)
	}

	return &baseline, nil
}

func cmdUpdateBaseline(args []string) {
	fs := flag.NewFlagSet("update-baseline", flag.ExitOnError)
	branch := fs.String("branch", "main", "Branch to update baseline for")
	minSamples := fs.Int("min-samples", 5, "Minimum samples required")
	maxHistory := fs.Int("max-history", 50, "Maximum history entries to keep")
	fs.Parse(args)

	// Get all test IDs
	rows, err := db.Query(`SELECT DISTINCT test_id FROM test_library WHERE enabled = 1`)
	if err != nil {
		log.Fatalf("Failed to get tests: %v", err)
	}
	defer rows.Close()

	updated := 0
	for rows.Next() {
		var testID string
		rows.Scan(&testID)

		// Get recent results for this test on this branch
		resultRows, err := db.Query(`
			SELECT tr.value FROM test_results tr
			JOIN test_runs r ON tr.run_id = r.run_id
			WHERE tr.test_id = ? AND r.branch = ? AND tr.status != 'error'
			ORDER BY tr.recorded_at DESC LIMIT ?`,
			testID, *branch, *maxHistory)
		if err != nil {
			continue
		}

		var values []float64
		for resultRows.Next() {
			var v float64
			resultRows.Scan(&v)
			values = append(values, v)
		}
		resultRows.Close()

		if len(values) < *minSamples {
			if *verbose {
				log.Printf("Skipping %s: only %d samples", testID, len(values))
			}
			continue
		}

		// Calculate statistics
		stats := calculateStats(values)
		historyJSON, _ := json.Marshal(values)

		// Upsert baseline
		_, err = db.Exec(`
			INSERT INTO test_baselines (test_id, branch, mean, std_dev, min_value, max_value, sample_count, p50, p95, p99, history, last_updated)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
			ON CONFLICT(test_id, branch) DO UPDATE SET
				mean = excluded.mean, std_dev = excluded.std_dev,
				min_value = excluded.min_value, max_value = excluded.max_value,
				sample_count = excluded.sample_count,
				p50 = excluded.p50, p95 = excluded.p95, p99 = excluded.p99,
				history = excluded.history, last_updated = CURRENT_TIMESTAMP`,
			testID, *branch, stats.Mean, stats.StdDev, stats.Min, stats.Max, len(values),
			stats.P50, stats.P95, stats.P99, string(historyJSON))
		if err != nil {
			log.Printf("Failed to update baseline for %s: %v", testID, err)
			continue
		}

		updated++
		if *verbose {
			log.Printf("Updated %s: mean=%.2f stddev=%.2f (n=%d)", testID, stats.Mean, stats.StdDev, len(values))
		}
	}

	log.Printf("Updated %d baselines for branch %s", updated, *branch)
}

// =============================================================================
// COMPARISON AND ALERTS
// =============================================================================

func cmdCompare(args []string) {
	fs := flag.NewFlagSet("compare", flag.ExitOnError)
	runID := fs.String("run-id", "", "Run ID to compare")
	baselineBranch := fs.String("baseline", "main", "Baseline branch to compare against")
	output := fs.String("output", "text", "Output format (text, json, markdown)")
	failOnWarning := fs.Bool("fail-on-warning", false, "Exit with error on warnings")
	fs.Parse(args)

	if *runID == "" {
		log.Fatal("--run-id is required")
	}

	report := ComparisonReport{
		RunID:          *runID,
		BaselineBranch: *baselineBranch,
		Timestamp:      time.Now().Format(time.RFC3339),
	}

	// Get run info
	db.QueryRow(`SELECT branch FROM test_runs WHERE run_id = ?`, *runID).Scan(&report.Branch)

	// Get all results for this run
	rows, err := db.Query(`
		SELECT test_id, value, unit, status, message, baseline_value, deviation_pct, deviation_std
		FROM test_results WHERE run_id = ?`, *runID)
	if err != nil {
		log.Fatalf("Failed to get results: %v", err)
	}
	defer rows.Close()

	for rows.Next() {
		var r TestResult
		var baselineValue, deviationPct, deviationStd sql.NullFloat64
		var message sql.NullString

		err := rows.Scan(&r.TestID, &r.Value, &r.Unit, &r.Status, &message,
			&baselineValue, &deviationPct, &deviationStd)
		if err != nil {
			continue
		}

		if message.Valid {
			r.Message = message.String
		}
		if baselineValue.Valid {
			r.BaselineValue = baselineValue.Float64
		}
		if deviationPct.Valid {
			r.DeviationPct = deviationPct.Float64
		}
		if deviationStd.Valid {
			r.DeviationStd = deviationStd.Float64
		}

		report.Results = append(report.Results, r)
		report.TotalTests++

		switch r.Status {
		case "passed":
			report.Passed++
		case "warning":
			report.Warnings++
			report.Alerts = append(report.Alerts, Alert{
				RunID:        *runID,
				TestID:       r.TestID,
				Severity:     "warning",
				Message:      r.Message,
				ActualValue:  r.Value,
				BaselineMean: r.BaselineValue,
				DeviationPct: r.DeviationPct,
				DeviationStd: r.DeviationStd,
			})
		case "failed":
			report.Failures++
			report.Alerts = append(report.Alerts, Alert{
				RunID:        *runID,
				TestID:       r.TestID,
				Severity:     "failure",
				Message:      r.Message,
				ActualValue:  r.Value,
				BaselineMean: r.BaselineValue,
				DeviationPct: r.DeviationPct,
				DeviationStd: r.DeviationStd,
			})
		}
	}

	// Output report
	switch *output {
	case "json":
		enc := json.NewEncoder(os.Stdout)
		enc.SetIndent("", "  ")
		enc.Encode(report)
	case "markdown":
		printMarkdownReport(report)
	default:
		printTextReport(report)
	}

	// Exit with appropriate code
	if report.Failures > 0 {
		os.Exit(2)
	}
	if report.Warnings > 0 && *failOnWarning {
		os.Exit(1)
	}
}

func createAlert(result TestResult) {
	severity := result.Status
	if severity == "failed" {
		severity = "failure"
	}

	_, err := db.Exec(`
		INSERT INTO alerts (run_id, metric_id, severity, message, actual_value, baseline_mean, deviation, percent_change)
		SELECT ?, m.id, ?, ?, ?, ?, ?, ?
		FROM metrics m WHERE m.run_id = ? ORDER BY m.id DESC LIMIT 1`,
		result.RunID, severity, result.Message, result.Value, result.BaselineValue,
		result.DeviationStd, result.DeviationPct, result.RunID)
	if err != nil && *verbose {
		log.Printf("Failed to create alert: %v", err)
	}
}

// =============================================================================
// REPORTING
// =============================================================================

func cmdReport(args []string) {
	fs := flag.NewFlagSet("report", flag.ExitOnError)
	format := fs.String("format", "text", "Output format (text, json, markdown)")
	branch := fs.String("branch", "main", "Branch to report on")
	days := fs.Int("days", 7, "Number of days to include")
	fs.Parse(args)

	// Get recent runs
	rows, err := db.Query(`
		SELECT run_id, timestamp, branch, commit_sha, test_type, status
		FROM test_runs
		WHERE branch = ? AND timestamp > datetime('now', '-' || ? || ' days')
		ORDER BY timestamp DESC`, *branch, *days)
	if err != nil {
		log.Fatalf("Failed to get runs: %v", err)
	}
	defer rows.Close()

	switch *format {
	case "json":
		var runs []TestRun
		for rows.Next() {
			var r TestRun
			rows.Scan(&r.RunID, &r.Timestamp, &r.Branch, &r.CommitSHA, &r.TestType, &r.Status)
			runs = append(runs, r)
		}
		enc := json.NewEncoder(os.Stdout)
		enc.SetIndent("", "  ")
		enc.Encode(runs)
	default:
		fmt.Printf("Test Runs for %s (last %d days)\n", *branch, *days)
		fmt.Println("=" + strings.Repeat("=", 79))
		fmt.Printf("%-36s %-20s %-10s %s\n", "Run ID", "Timestamp", "Status", "Commit")
		fmt.Println("-" + strings.Repeat("-", 79))
		for rows.Next() {
			var r TestRun
			rows.Scan(&r.RunID, &r.Timestamp, &r.Branch, &r.CommitSHA, &r.TestType, &r.Status)
			fmt.Printf("%-36s %-20s %-10s %.8s\n", r.RunID, r.Timestamp.Format("2006-01-02 15:04"), r.Status, r.CommitSHA)
		}
	}
}

func cmdListTests(args []string) {
	fs := flag.NewFlagSet("list-tests", flag.ExitOnError)
	category := fs.String("category", "", "Filter by category")
	critical := fs.Bool("critical", false, "Show only critical tests")
	fs.Parse(args)

	query := `SELECT test_id, test_name, category, test_type, default_scale, expected_unit, critical FROM test_library WHERE enabled = 1`
	if *category != "" {
		query += fmt.Sprintf(` AND category = '%s'`, *category)
	}
	if *critical {
		query += ` AND critical = 1`
	}

	rows, err := db.Query(query)
	if err != nil {
		log.Fatalf("Failed to list tests: %v", err)
	}
	defer rows.Close()

	fmt.Printf("%-30s %-30s %-10s %-10s %s\n", "Test ID", "Name", "Category", "Type", "Critical")
	fmt.Println(strings.Repeat("-", 100))
	for rows.Next() {
		var testID, name, category, testType, unit string
		var scale sql.NullInt64
		var critical bool
		rows.Scan(&testID, &name, &category, &testType, &scale, &unit, &critical)
		critStr := ""
		if critical {
			critStr = "YES"
		}
		fmt.Printf("%-30s %-30s %-10s %-10s %s\n", testID, name, category, testType, critStr)
	}
}

func cmdTrend(args []string) {
	fs := flag.NewFlagSet("trend", flag.ExitOnError)
	testID := fs.String("test-id", "", "Test ID to show trend for")
	branch := fs.String("branch", "main", "Branch")
	limit := fs.Int("limit", 20, "Number of data points")
	fs.Parse(args)

	if *testID == "" {
		log.Fatal("--test-id is required")
	}

	rows, err := db.Query(`
		SELECT tr.value, r.timestamp, r.commit_sha
		FROM test_results tr
		JOIN test_runs r ON tr.run_id = r.run_id
		WHERE tr.test_id = ? AND r.branch = ?
		ORDER BY r.timestamp DESC LIMIT ?`,
		*testID, *branch, *limit)
	if err != nil {
		log.Fatalf("Failed to get trend: %v", err)
	}
	defer rows.Close()

	fmt.Printf("Trend for %s on %s\n", *testID, *branch)
	fmt.Println(strings.Repeat("=", 60))

	var values []float64
	for rows.Next() {
		var value float64
		var timestamp time.Time
		var commit string
		rows.Scan(&value, &timestamp, &commit)
		values = append(values, value)
		fmt.Printf("%s  %.8s  %.2f\n", timestamp.Format("2006-01-02 15:04"), commit, value)
	}

	if len(values) > 0 {
		stats := calculateStats(values)
		fmt.Println(strings.Repeat("-", 60))
		fmt.Printf("Mean: %.2f  StdDev: %.2f  Min: %.2f  Max: %.2f\n",
			stats.Mean, stats.StdDev, stats.Min, stats.Max)
	}
}

func cmdExport(args []string) {
	fs := flag.NewFlagSet("export", flag.ExitOnError)
	format := fs.String("format", "json", "Output format (json, csv)")
	output := fs.String("output", "-", "Output file (- for stdout)")
	days := fs.Int("days", 30, "Number of days to export")
	fs.Parse(args)

	rows, err := db.Query(`
		SELECT tr.test_id, tr.value, tr.unit, tr.status, r.timestamp, r.branch, r.commit_sha
		FROM test_results tr
		JOIN test_runs r ON tr.run_id = r.run_id
		WHERE r.timestamp > datetime('now', '-' || ? || ' days')
		ORDER BY r.timestamp DESC`, *days)
	if err != nil {
		log.Fatalf("Failed to export: %v", err)
	}
	defer rows.Close()

	var out *os.File
	if *output == "-" {
		out = os.Stdout
	} else {
		out, err = os.Create(*output)
		if err != nil {
			log.Fatalf("Failed to create output file: %v", err)
		}
		defer out.Close()
	}

	switch *format {
	case "csv":
		fmt.Fprintln(out, "test_id,value,unit,status,timestamp,branch,commit")
		for rows.Next() {
			var testID, unit, status, branch, commit string
			var value float64
			var timestamp time.Time
			rows.Scan(&testID, &value, &unit, &status, &timestamp, &branch, &commit)
			fmt.Fprintf(out, "%s,%.4f,%s,%s,%s,%s,%s\n",
				testID, value, unit, status, timestamp.Format(time.RFC3339), branch, commit)
		}
	default:
		var results []map[string]interface{}
		for rows.Next() {
			var testID, unit, status, branch, commit string
			var value float64
			var timestamp time.Time
			rows.Scan(&testID, &value, &unit, &status, &timestamp, &branch, &commit)
			results = append(results, map[string]interface{}{
				"test_id":   testID,
				"value":     value,
				"unit":      unit,
				"status":    status,
				"timestamp": timestamp.Format(time.RFC3339),
				"branch":    branch,
				"commit":    commit,
			})
		}
		enc := json.NewEncoder(out)
		enc.SetIndent("", "  ")
		enc.Encode(results)
	}
}

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

type Stats struct {
	Mean   float64
	StdDev float64
	Min    float64
	Max    float64
	P50    float64
	P95    float64
	P99    float64
}

func calculateStats(values []float64) Stats {
	if len(values) == 0 {
		return Stats{}
	}

	// Sort for percentiles
	sorted := make([]float64, len(values))
	copy(sorted, values)
	sort.Float64s(sorted)

	// Calculate mean
	var sum float64
	for _, v := range values {
		sum += v
	}
	mean := sum / float64(len(values))

	// Calculate std dev
	var variance float64
	for _, v := range values {
		variance += (v - mean) * (v - mean)
	}
	stddev := math.Sqrt(variance / float64(len(values)))

	return Stats{
		Mean:   mean,
		StdDev: stddev,
		Min:    sorted[0],
		Max:    sorted[len(sorted)-1],
		P50:    sorted[len(sorted)/2],
		P95:    sorted[len(sorted)*95/100],
		P99:    sorted[len(sorted)*99/100],
	}
}

func getGitBranch() string {
	out, err := exec.Command("git", "rev-parse", "--abbrev-ref", "HEAD").Output()
	if err != nil {
		return "unknown"
	}
	return strings.TrimSpace(string(out))
}

func getGitCommit() string {
	out, err := exec.Command("git", "rev-parse", "HEAD").Output()
	if err != nil {
		return "unknown"
	}
	return strings.TrimSpace(string(out))
}

func getPlatform() string {
	out, err := exec.Command("uname", "-s").Output()
	if err != nil {
		return "unknown"
	}
	return strings.ToLower(strings.TrimSpace(string(out)))
}

func getOSVersion() string {
	out, err := exec.Command("uname", "-r").Output()
	if err != nil {
		return ""
	}
	return strings.TrimSpace(string(out))
}

func printTextReport(report ComparisonReport) {
	fmt.Println("Comparison Report")
	fmt.Println("================================================================================")
	fmt.Printf("Run ID:          %s\n", report.RunID)
	fmt.Printf("Branch:          %s\n", report.Branch)
	fmt.Printf("Baseline:        %s\n", report.BaselineBranch)
	fmt.Printf("Timestamp:       %s\n", report.Timestamp)
	fmt.Println()
	fmt.Printf("Total Tests:     %d\n", report.TotalTests)
	fmt.Printf("Passed:          %d\n", report.Passed)
	fmt.Printf("Warnings:        %d\n", report.Warnings)
	fmt.Printf("Failures:        %d\n", report.Failures)
	fmt.Println()

	if len(report.Alerts) > 0 {
		fmt.Println("Alerts:")
		fmt.Println("--------------------------------------------------------------------------------")
		for _, a := range report.Alerts {
			fmt.Printf("[%s] %s: %s\n", strings.ToUpper(a.Severity), a.TestID, a.Message)
			fmt.Printf("    Value: %.2f (baseline: %.2f, %.1f%% deviation)\n",
				a.ActualValue, a.BaselineMean, a.DeviationPct)
		}
	}
}

func printMarkdownReport(report ComparisonReport) {
	fmt.Println("# Performance Comparison Report")
	fmt.Println()
	fmt.Printf("- **Run ID:** `%s`\n", report.RunID)
	fmt.Printf("- **Branch:** `%s`\n", report.Branch)
	fmt.Printf("- **Baseline:** `%s`\n", report.BaselineBranch)
	fmt.Printf("- **Timestamp:** %s\n", report.Timestamp)
	fmt.Println()
	fmt.Println("## Summary")
	fmt.Println()
	fmt.Printf("| Metric | Count |\n")
	fmt.Printf("|--------|-------|\n")
	fmt.Printf("| Total Tests | %d |\n", report.TotalTests)
	fmt.Printf("| ✅ Passed | %d |\n", report.Passed)
	fmt.Printf("| ⚠️ Warnings | %d |\n", report.Warnings)
	fmt.Printf("| ❌ Failures | %d |\n", report.Failures)
	fmt.Println()

	if len(report.Alerts) > 0 {
		fmt.Println("## Alerts")
		fmt.Println()
		fmt.Println("| Severity | Test | Deviation | Message |")
		fmt.Println("|----------|------|-----------|---------|")
		for _, a := range report.Alerts {
			emoji := "⚠️"
			if a.Severity == "failure" {
				emoji = "❌"
			}
			fmt.Printf("| %s %s | `%s` | %.1f%% | %s |\n",
				emoji, a.Severity, a.TestID, a.DeviationPct, a.Message)
		}
	}
}
