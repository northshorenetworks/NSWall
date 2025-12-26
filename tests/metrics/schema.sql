-- NSWall Performance Metrics Database Schema
--
-- Tracks stress test and performance results over time for:
-- - Regression detection (alert when metrics deviate from baseline)
-- - Capacity planning (track limits over time)
-- - Release comparisons (compare branches/versions)
--

-- =============================================================================
-- TEST RUNS
-- =============================================================================

-- Each test run represents a single execution of stress tests
CREATE TABLE IF NOT EXISTS test_runs (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    run_id          TEXT UNIQUE NOT NULL,      -- UUID for this run
    timestamp       DATETIME DEFAULT CURRENT_TIMESTAMP,

    -- Git context
    branch          TEXT NOT NULL,
    commit_sha      TEXT NOT NULL,
    commit_message  TEXT,

    -- CI context
    ci_provider     TEXT,                       -- github, gitlab, local
    ci_run_id       TEXT,                       -- CI job ID
    ci_run_url      TEXT,                       -- Link to CI run

    -- Environment
    platform        TEXT NOT NULL,              -- openbsd, linux
    os_version      TEXT,                       -- 7.4, etc.
    cpu_count       INTEGER,
    memory_mb       INTEGER,

    -- Test metadata
    test_type       TEXT NOT NULL,              -- stress, performance, sizing
    test_suite      TEXT,                       -- pf, ipsec, nats, full
    duration_secs   REAL,

    -- Status
    status          TEXT DEFAULT 'running',     -- running, passed, failed, error
    error_message   TEXT,

    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_test_runs_branch ON test_runs(branch);
CREATE INDEX idx_test_runs_timestamp ON test_runs(timestamp);
CREATE INDEX idx_test_runs_commit ON test_runs(commit_sha);

-- =============================================================================
-- METRICS
-- =============================================================================

-- Individual metric measurements
CREATE TABLE IF NOT EXISTS metrics (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    run_id          TEXT NOT NULL REFERENCES test_runs(run_id),

    -- Metric identification
    category        TEXT NOT NULL,              -- pf, ipsec, nats, bgp, ospf, tables
    metric_name     TEXT NOT NULL,              -- parse_time_ms, load_time_ms, etc.

    -- Metric context (what was being tested)
    scale           INTEGER,                    -- Number of rules/tunnels/agents
    variant         TEXT,                       -- filter, nat, rdr, etc.

    -- Values
    value           REAL NOT NULL,              -- The measured value
    unit            TEXT NOT NULL,              -- ms, bytes, count, rate

    -- Optional bounds
    min_value       REAL,
    max_value       REAL,

    -- Derived stats (if multiple samples)
    sample_count    INTEGER DEFAULT 1,
    std_dev         REAL,
    p50             REAL,
    p95             REAL,
    p99             REAL,

    recorded_at     DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_metrics_run ON metrics(run_id);
CREATE INDEX idx_metrics_category ON metrics(category, metric_name);
CREATE INDEX idx_metrics_scale ON metrics(category, metric_name, scale);

-- =============================================================================
-- BASELINES
-- =============================================================================

-- Statistical baselines calculated from historical data
CREATE TABLE IF NOT EXISTS baselines (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,

    -- What this baseline represents
    branch          TEXT NOT NULL,              -- main, develop, etc.
    category        TEXT NOT NULL,
    metric_name     TEXT NOT NULL,
    scale           INTEGER,
    variant         TEXT,

    -- Statistical values
    mean            REAL NOT NULL,
    std_dev         REAL NOT NULL,
    min_value       REAL,
    max_value       REAL,
    sample_count    INTEGER NOT NULL,

    -- Percentiles
    p50             REAL,
    p95             REAL,
    p99             REAL,

    -- Thresholds for alerting
    warn_threshold  REAL,                       -- Alert if > mean + warn_threshold * std_dev
    fail_threshold  REAL,                       -- Fail if > mean + fail_threshold * std_dev

    -- Metadata
    last_updated    DATETIME DEFAULT CURRENT_TIMESTAMP,
    run_count       INTEGER DEFAULT 0,          -- Number of runs in this baseline

    UNIQUE(branch, category, metric_name, scale, variant)
);

CREATE INDEX idx_baselines_lookup ON baselines(branch, category, metric_name, scale);

-- =============================================================================
-- ALERTS
-- =============================================================================

-- Alerts generated when metrics deviate from baseline
CREATE TABLE IF NOT EXISTS alerts (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    run_id          TEXT NOT NULL REFERENCES test_runs(run_id),
    metric_id       INTEGER NOT NULL REFERENCES metrics(id),
    baseline_id     INTEGER REFERENCES baselines(id),

    -- Alert details
    severity        TEXT NOT NULL,              -- info, warning, failure
    message         TEXT NOT NULL,

    -- Comparison data
    actual_value    REAL NOT NULL,
    baseline_mean   REAL,
    baseline_stddev REAL,
    deviation       REAL,                       -- How many std devs from mean
    percent_change  REAL,                       -- Percent change from mean

    -- Status
    acknowledged    BOOLEAN DEFAULT FALSE,
    acknowledged_by TEXT,
    acknowledged_at DATETIME,

    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_alerts_run ON alerts(run_id);
CREATE INDEX idx_alerts_severity ON alerts(severity, created_at);

-- =============================================================================
-- TEST LIBRARY
-- =============================================================================

-- Canonical test definitions - each test has a unique library ID
CREATE TABLE IF NOT EXISTS test_library (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    test_id         TEXT UNIQUE NOT NULL,       -- Canonical test ID (e.g., "pf.filter.parse.1000")

    -- Test identification
    category        TEXT NOT NULL,              -- pf, ipsec, nats, bgp, ospf, tables
    test_name       TEXT NOT NULL,              -- Descriptive name
    test_type       TEXT NOT NULL,              -- parse, load, throughput, capacity, latency

    -- Test parameters (what scale/variant this test runs at)
    default_scale   INTEGER,                    -- Default scale for this test
    default_variant TEXT,                       -- Default variant

    -- Documentation
    description     TEXT,
    methodology     TEXT,                       -- How the test works
    expected_unit   TEXT NOT NULL,              -- ms, bytes, count, rate

    -- Test configuration
    enabled         BOOLEAN DEFAULT TRUE,
    critical        BOOLEAN DEFAULT FALSE,      -- Is this a blocking test?
    timeout_secs    INTEGER DEFAULT 300,

    -- Thresholds (tighter defaults - even 5% regression matters)
    warn_threshold_pct  REAL DEFAULT 5.0,       -- Warn if > 5% worse than baseline
    fail_threshold_pct  REAL DEFAULT 15.0,      -- Fail if > 15% worse than baseline

    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_test_library_category ON test_library(category);
CREATE INDEX idx_test_library_type ON test_library(test_type);

-- Insert canonical test definitions
INSERT OR IGNORE INTO test_library (test_id, category, test_name, test_type, default_scale, expected_unit, description, critical) VALUES
    -- PF Filter Tests
    ('pf.filter.parse.100', 'pf', 'PF Filter Parse 100 Rules', 'parse', 100, 'ms', 'Parse 100 filter rules with pfctl -nf', FALSE),
    ('pf.filter.parse.500', 'pf', 'PF Filter Parse 500 Rules', 'parse', 500, 'ms', 'Parse 500 filter rules with pfctl -nf', FALSE),
    ('pf.filter.parse.1000', 'pf', 'PF Filter Parse 1000 Rules', 'parse', 1000, 'ms', 'Parse 1000 filter rules with pfctl -nf', TRUE),
    ('pf.filter.parse.5000', 'pf', 'PF Filter Parse 5000 Rules', 'parse', 5000, 'ms', 'Parse 5000 filter rules with pfctl -nf', TRUE),
    ('pf.filter.parse.10000', 'pf', 'PF Filter Parse 10000 Rules', 'parse', 10000, 'ms', 'Parse 10000 filter rules with pfctl -nf', FALSE),

    ('pf.filter.load.100', 'pf', 'PF Filter Load 100 Rules', 'load', 100, 'ms', 'Load 100 filter rules with pfctl -f', FALSE),
    ('pf.filter.load.500', 'pf', 'PF Filter Load 500 Rules', 'load', 500, 'ms', 'Load 500 filter rules with pfctl -f', FALSE),
    ('pf.filter.load.1000', 'pf', 'PF Filter Load 1000 Rules', 'load', 1000, 'ms', 'Load 1000 filter rules with pfctl -f', TRUE),
    ('pf.filter.load.5000', 'pf', 'PF Filter Load 5000 Rules', 'load', 5000, 'ms', 'Load 5000 filter rules with pfctl -f', TRUE),
    ('pf.filter.load.10000', 'pf', 'PF Filter Load 10000 Rules', 'load', 10000, 'ms', 'Load 10000 filter rules with pfctl -f', FALSE),

    -- PF NAT Tests
    ('pf.nat.parse.1000', 'pf', 'PF NAT Parse 1000 Rules', 'parse', 1000, 'ms', 'Parse 1000 NAT rules', TRUE),
    ('pf.nat.load.1000', 'pf', 'PF NAT Load 1000 Rules', 'load', 1000, 'ms', 'Load 1000 NAT rules', TRUE),

    -- PF Table Tests
    ('pf.table.load.1000', 'tables', 'PF Table Load 1K Entries', 'load', 1000, 'ms', 'Load 1000 table entries', FALSE),
    ('pf.table.load.10000', 'tables', 'PF Table Load 10K Entries', 'load', 10000, 'ms', 'Load 10000 table entries', TRUE),
    ('pf.table.load.100000', 'tables', 'PF Table Load 100K Entries', 'load', 100000, 'ms', 'Load 100000 table entries', TRUE),
    ('pf.table.lookup.10000', 'tables', 'PF Table Lookup 10K', 'latency', 10000, 'ms', 'Lookup performance with 10K entries', TRUE),

    -- IPsec Tests
    ('ipsec.iked.parse.100', 'ipsec', 'iked Parse 100 Tunnels', 'parse', 100, 'ms', 'Parse iked.conf with 100 tunnels', FALSE),
    ('ipsec.iked.parse.500', 'ipsec', 'iked Parse 500 Tunnels', 'parse', 500, 'ms', 'Parse iked.conf with 500 tunnels', TRUE),
    ('ipsec.iked.parse.1000', 'ipsec', 'iked Parse 1000 Tunnels', 'parse', 1000, 'ms', 'Parse iked.conf with 1000 tunnels', TRUE),
    ('ipsec.iked.parse.2000', 'ipsec', 'iked Parse 2000 Tunnels', 'parse', 2000, 'ms', 'Parse iked.conf with 2000 tunnels', TRUE),

    ('ipsec.ipsecctl.load.100', 'ipsec', 'ipsecctl Load 100 Flows', 'load', 100, 'ms', 'Load 100 IPsec flows', FALSE),
    ('ipsec.ipsecctl.load.500', 'ipsec', 'ipsecctl Load 500 Flows', 'load', 500, 'ms', 'Load 500 IPsec flows', TRUE),
    ('ipsec.ipsecctl.load.1000', 'ipsec', 'ipsecctl Load 1000 Flows', 'load', 1000, 'ms', 'Load 1000 IPsec flows', TRUE),
    ('ipsec.ipsecctl.load.2000', 'ipsec', 'ipsecctl Load 2000 Flows', 'load', 2000, 'ms', 'Load 2000 IPsec flows', TRUE),

    ('ipsec.capacity.max', 'ipsec', 'IPsec Max Tunnels', 'capacity', NULL, 'count', 'Maximum sustainable tunnel count', TRUE),

    -- BGP Tests
    ('bgp.parse.1000', 'bgp', 'BGP Parse 1000 Routes', 'parse', 1000, 'ms', 'Parse bgpd.conf with 1000 networks', TRUE),
    ('bgp.parse.5000', 'bgp', 'BGP Parse 5000 Routes', 'parse', 5000, 'ms', 'Parse bgpd.conf with 5000 networks', TRUE),
    ('bgp.parse.10000', 'bgp', 'BGP Parse 10000 Routes', 'parse', 10000, 'ms', 'Parse bgpd.conf with 10000 networks', FALSE),

    -- OSPF Tests
    ('ospf.parse.1000', 'ospf', 'OSPF Parse 1000 Routes', 'parse', 1000, 'ms', 'Parse ospfd.conf with 1000 redistributes', TRUE),
    ('ospf.parse.5000', 'ospf', 'OSPF Parse 5000 Routes', 'parse', 5000, 'ms', 'Parse ospfd.conf with 5000 redistributes', FALSE),

    -- NATS Tests
    ('nats.connect.100', 'nats', 'NATS Connect 100 Agents', 'throughput', 100, 'ms', 'Time to connect 100 agents', FALSE),
    ('nats.connect.500', 'nats', 'NATS Connect 500 Agents', 'throughput', 500, 'ms', 'Time to connect 500 agents', TRUE),
    ('nats.connect.1000', 'nats', 'NATS Connect 1000 Agents', 'throughput', 1000, 'ms', 'Time to connect 1000 agents', TRUE),
    ('nats.connect.2000', 'nats', 'NATS Connect 2000 Agents', 'throughput', 2000, 'ms', 'Time to connect 2000 agents', FALSE),

    ('nats.throughput.500', 'nats', 'NATS Throughput 500 Agents', 'throughput', 500, 'rate', 'Messages per second with 500 agents', TRUE),
    ('nats.throughput.1000', 'nats', 'NATS Throughput 1000 Agents', 'throughput', 1000, 'rate', 'Messages per second with 1000 agents', TRUE),

    ('nats.latency.p50', 'nats', 'NATS Latency P50', 'latency', NULL, 'ms', 'Median request/reply latency', TRUE),
    ('nats.latency.p99', 'nats', 'NATS Latency P99', 'latency', NULL, 'ms', '99th percentile request/reply latency', TRUE),

    ('nats.capacity.max', 'nats', 'NATS Max Connections', 'capacity', NULL, 'count', 'Maximum sustainable connections', TRUE);

-- Test results linked to library
CREATE TABLE IF NOT EXISTS test_results (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    run_id          TEXT NOT NULL REFERENCES test_runs(run_id),
    test_id         TEXT NOT NULL REFERENCES test_library(test_id),

    -- Result
    value           REAL NOT NULL,
    unit            TEXT NOT NULL,

    -- Status
    status          TEXT NOT NULL,              -- passed, warning, failed, error, skipped
    message         TEXT,

    -- Comparison to baseline
    baseline_value  REAL,
    deviation_pct   REAL,                       -- Percent deviation from baseline
    deviation_std   REAL,                       -- Standard deviations from mean

    -- Timing
    started_at      DATETIME,
    completed_at    DATETIME,
    duration_ms     INTEGER,

    recorded_at     DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_test_results_run ON test_results(run_id);
CREATE INDEX idx_test_results_test ON test_results(test_id);
CREATE INDEX idx_test_results_status ON test_results(status);

-- Test baselines per branch
CREATE TABLE IF NOT EXISTS test_baselines (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    test_id         TEXT NOT NULL REFERENCES test_library(test_id),
    branch          TEXT NOT NULL,

    -- Statistical values
    mean            REAL NOT NULL,
    std_dev         REAL NOT NULL,
    min_value       REAL,
    max_value       REAL,
    sample_count    INTEGER NOT NULL,

    -- Percentiles
    p50             REAL,
    p95             REAL,
    p99             REAL,

    -- History (JSON array of last N values)
    history         TEXT,                       -- JSON array of recent values

    last_updated    DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE(test_id, branch)
);

CREATE INDEX idx_test_baselines_lookup ON test_baselines(test_id, branch);

-- =============================================================================
-- METRIC DEFINITIONS
-- =============================================================================

-- Define expected metrics and their properties
CREATE TABLE IF NOT EXISTS metric_definitions (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    category        TEXT NOT NULL,
    metric_name     TEXT NOT NULL,

    -- Display info
    display_name    TEXT NOT NULL,
    description     TEXT,
    unit            TEXT NOT NULL,

    -- Direction: 'lower' means lower is better, 'higher' means higher is better
    direction       TEXT DEFAULT 'lower',

    -- Default thresholds (std devs from mean) - tight for early detection
    default_warn    REAL DEFAULT 1.5,
    default_fail    REAL DEFAULT 2.5,

    -- Is this a critical metric?
    critical        BOOLEAN DEFAULT FALSE,

    UNIQUE(category, metric_name)
);

-- Insert default metric definitions
INSERT OR IGNORE INTO metric_definitions (category, metric_name, display_name, description, unit, direction, critical) VALUES
    -- PF Metrics
    ('pf', 'parse_time_ms', 'PF Parse Time', 'Time to parse pf.conf with pfctl -nf', 'ms', 'lower', TRUE),
    ('pf', 'load_time_ms', 'PF Load Time', 'Time to load rules with pfctl -f', 'ms', 'lower', TRUE),
    ('pf', 'rules_loaded', 'Rules Loaded', 'Number of rules successfully loaded', 'count', 'higher', FALSE),
    ('pf', 'memory_delta_kb', 'Memory Delta', 'Memory increase after loading rules', 'KB', 'lower', FALSE),
    ('pf', 'rules_per_sec', 'Rule Load Rate', 'Rules loaded per second', 'rate', 'higher', FALSE),

    -- Table Metrics
    ('tables', 'load_time_ms', 'Table Load Time', 'Time to load table entries', 'ms', 'lower', TRUE),
    ('tables', 'lookup_time_ms', 'Table Lookup Time', 'Time for table lookups', 'ms', 'lower', TRUE),
    ('tables', 'entries_loaded', 'Entries Loaded', 'Number of entries loaded', 'count', 'higher', FALSE),
    ('tables', 'memory_per_entry', 'Memory Per Entry', 'Bytes per table entry', 'bytes', 'lower', FALSE),

    -- IPsec Metrics
    ('ipsec', 'config_gen_time_ms', 'Config Generation Time', 'Time to generate IPsec config', 'ms', 'lower', FALSE),
    ('ipsec', 'parse_time_ms', 'IPsec Parse Time', 'Time to parse iked/ipsecctl config', 'ms', 'lower', TRUE),
    ('ipsec', 'load_time_ms', 'IPsec Load Time', 'Time to load IPsec configuration', 'ms', 'lower', TRUE),
    ('ipsec', 'max_tunnels', 'Max Tunnels', 'Maximum sustainable tunnel count', 'count', 'higher', TRUE),
    ('ipsec', 'tunnels_per_sec', 'Tunnel Load Rate', 'Tunnels configured per second', 'rate', 'higher', FALSE),

    -- BGP Metrics
    ('bgp', 'parse_time_ms', 'BGP Parse Time', 'Time to parse bgpd.conf', 'ms', 'lower', TRUE),
    ('bgp', 'routes_per_sec', 'Route Parse Rate', 'Routes parsed per second', 'rate', 'higher', FALSE),

    -- OSPF Metrics
    ('ospf', 'parse_time_ms', 'OSPF Parse Time', 'Time to parse ospfd.conf', 'ms', 'lower', TRUE),

    -- NATS Metrics
    ('nats', 'connection_time_p50', 'Connection Time P50', 'Median connection establishment time', 'ms', 'lower', TRUE),
    ('nats', 'connection_time_p99', 'Connection Time P99', '99th percentile connection time', 'ms', 'lower', TRUE),
    ('nats', 'max_connections', 'Max Connections', 'Maximum sustainable connections', 'count', 'higher', TRUE),
    ('nats', 'throughput_msg_sec', 'Message Throughput', 'Messages per second', 'rate', 'higher', TRUE),
    ('nats', 'latency_p50', 'Request Latency P50', 'Median request/reply latency', 'ms', 'lower', TRUE),
    ('nats', 'latency_p99', 'Request Latency P99', '99th percentile latency', 'ms', 'lower', TRUE),
    ('nats', 'memory_per_agent_kb', 'Memory Per Agent', 'Memory per connected agent', 'KB', 'lower', FALSE);

-- =============================================================================
-- VIEWS
-- =============================================================================

-- Latest baseline for each metric
CREATE VIEW IF NOT EXISTS v_latest_baselines AS
SELECT
    b.*,
    md.display_name,
    md.description,
    md.direction,
    md.critical
FROM baselines b
LEFT JOIN metric_definitions md ON b.category = md.category AND b.metric_name = md.metric_name
WHERE b.last_updated = (
    SELECT MAX(last_updated)
    FROM baselines b2
    WHERE b2.branch = b.branch
      AND b2.category = b.category
      AND b2.metric_name = b.metric_name
      AND b2.scale = b.scale
);

-- Recent test runs with alert summary
CREATE VIEW IF NOT EXISTS v_runs_with_alerts AS
SELECT
    tr.*,
    COUNT(DISTINCT a.id) as alert_count,
    SUM(CASE WHEN a.severity = 'failure' THEN 1 ELSE 0 END) as failure_count,
    SUM(CASE WHEN a.severity = 'warning' THEN 1 ELSE 0 END) as warning_count
FROM test_runs tr
LEFT JOIN alerts a ON tr.run_id = a.run_id
GROUP BY tr.id
ORDER BY tr.timestamp DESC;

-- Metric trends over time
CREATE VIEW IF NOT EXISTS v_metric_trends AS
SELECT
    m.category,
    m.metric_name,
    m.scale,
    m.variant,
    tr.branch,
    DATE(tr.timestamp) as date,
    AVG(m.value) as avg_value,
    MIN(m.value) as min_value,
    MAX(m.value) as max_value,
    COUNT(*) as sample_count
FROM metrics m
JOIN test_runs tr ON m.run_id = tr.run_id
GROUP BY m.category, m.metric_name, m.scale, m.variant, tr.branch, DATE(tr.timestamp)
ORDER BY date DESC;
