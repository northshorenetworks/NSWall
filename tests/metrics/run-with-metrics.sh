#!/bin/bash
#
# run-with-metrics.sh - Run stress tests with metrics collection
#
# Integrates stress tests with the metrics collector to:
# - Record all test results to the database
# - Compare against baselines and detect regressions
# - Generate alerts for significant deviations
# - Update baselines when running on main branch
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
METRICS_DB="${METRICS_DB:-$SCRIPT_DIR/metrics.db}"
METRICS_BIN="${METRICS_BIN:-$SCRIPT_DIR/metrics-collector}"
BASELINE_BRANCH="${BASELINE_BRANCH:-main}"
WARN_THRESHOLD="${WARN_THRESHOLD:-20}"
FAIL_THRESHOLD="${FAIL_THRESHOLD:-50}"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }
log_metric() { echo -e "${CYAN}[METRIC]${NC} $1"; }

# =============================================================================
# INITIALIZATION
# =============================================================================

init_metrics() {
    log_info "Initializing metrics collection..."

    # Build collector if needed
    if [ ! -f "$METRICS_BIN" ]; then
        log_info "Building metrics collector..."
        cd "$SCRIPT_DIR"
        go build -o "$METRICS_BIN" collector.go
    fi

    # Initialize database
    "$METRICS_BIN" -db "$METRICS_DB" -schema "$SCRIPT_DIR/schema.sql" init

    # Start a new run
    RUN_OUTPUT=$("$METRICS_BIN" -db "$METRICS_DB" start-run \
        -type stress \
        -suite "${TEST_SUITE:-full}")

    # Extract run ID
    RUN_ID=$(echo "$RUN_OUTPUT" | grep "RUN_ID=" | cut -d= -f2)
    export RUN_ID

    log_info "Started metrics run: $RUN_ID"
}

finalize_metrics() {
    local status="${1:-passed}"

    log_info "Finalizing metrics run..."

    # End the run
    "$METRICS_BIN" -db "$METRICS_DB" end-run -run-id "$RUN_ID" -status "$status"

    # Compare against baseline
    log_info "Comparing against baseline ($BASELINE_BRANCH)..."
    COMPARE_OUTPUT=$("$METRICS_BIN" -db "$METRICS_DB" compare \
        -run-id "$RUN_ID" \
        -baseline "$BASELINE_BRANCH" \
        -output text 2>&1) || true

    echo "$COMPARE_OUTPUT"

    # Check if we should update baseline (only on main branch)
    CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")
    if [ "$CURRENT_BRANCH" = "$BASELINE_BRANCH" ]; then
        log_info "Updating baseline for $BASELINE_BRANCH..."
        "$METRICS_BIN" -db "$METRICS_DB" update-baseline -branch "$BASELINE_BRANCH"
    fi

    # Generate markdown report for CI
    "$METRICS_BIN" -db "$METRICS_DB" compare \
        -run-id "$RUN_ID" \
        -baseline "$BASELINE_BRANCH" \
        -output markdown > "${RESULTS_DIR:-/tmp}/metrics-report.md" 2>/dev/null || true
}

# =============================================================================
# METRIC RECORDING HELPERS
# =============================================================================

record_metric() {
    local test_id="$1"
    local value="$2"
    local unit="${3:-ms}"

    log_metric "Recording $test_id: $value $unit"

    "$METRICS_BIN" -db "$METRICS_DB" -v record \
        -run-id "$RUN_ID" \
        -test-id "$test_id" \
        -value "$value" \
        -unit "$unit"
}

# Parse stress test output and record metrics
parse_pf_output() {
    local output="$1"

    # Extract metrics from control-plane-stress.sh output
    echo "$output" | while read -r line; do
        # Look for METRIC lines
        if [[ "$line" =~ \[METRIC\].*parse\ time:\ ([0-9]+)ms\ for\ ([0-9]+)\ rules ]]; then
            local time="${BASH_REMATCH[1]}"
            local rules="${BASH_REMATCH[2]}"
            record_metric "pf.filter.parse.$rules" "$time" "ms"
        elif [[ "$line" =~ \[METRIC\].*load\ time:\ ([0-9]+)ms ]]; then
            local time="${BASH_REMATCH[1]}"
            # Need context for which test this was
        fi
    done
}

parse_ipsec_output() {
    local output="$1"

    echo "$output" | while read -r line; do
        if [[ "$line" =~ iked\ parse\ time:\ ([0-9]+)ms\ for\ ([0-9]+)\ tunnels ]]; then
            local time="${BASH_REMATCH[1]}"
            local tunnels="${BASH_REMATCH[2]}"
            record_metric "ipsec.iked.parse.$tunnels" "$time" "ms"
        elif [[ "$line" =~ ipsecctl\ load\ time:\ ([0-9]+)ms\ for\ ([0-9]+)\ flows ]]; then
            local time="${BASH_REMATCH[1]}"
            local flows=$((${BASH_REMATCH[2]} / 2))  # Convert flows to tunnels
            record_metric "ipsec.ipsecctl.load.$flows" "$time" "ms"
        elif [[ "$line" =~ Maximum.*tunnel.*:\ ([0-9]+) ]]; then
            local max="${BASH_REMATCH[1]}"
            record_metric "ipsec.capacity.max" "$max" "count"
        fi
    done
}

parse_nats_output() {
    local output="$1"

    echo "$output" | while read -r line; do
        if [[ "$line" =~ Connection\ time\ P50:\ ([0-9]+).*P99:\ ([0-9]+) ]]; then
            local p50="${BASH_REMATCH[1]}"
            local p99="${BASH_REMATCH[2]}"
            # record_metric "nats.connect.time.p50" "$p50" "ms"
            # record_metric "nats.connect.time.p99" "$p99" "ms"
        elif [[ "$line" =~ Throughput:\ ([0-9.]+)\ messages/sec ]]; then
            local rate="${BASH_REMATCH[1]}"
            # record_metric "nats.throughput" "$rate" "rate"
        elif [[ "$line" =~ Maximum.*connections:\ ([0-9]+) ]]; then
            local max="${BASH_REMATCH[1]}"
            record_metric "nats.capacity.max" "$max" "count"
        fi
    done
}

# =============================================================================
# RUN TESTS WITH METRICS
# =============================================================================

run_pf_tests() {
    log_info "Running PF stress tests with metrics collection..."

    local test_scales="100 500 1000 5000"
    local output

    for scale in $test_scales; do
        log_info "Testing PF with $scale rules..."

        # Generate config
        output=$("$SCRIPT_DIR/../stress/control-plane-stress.sh" pf "$scale" filter 2>&1) || true

        # Parse and record metrics
        local parse_time=$(echo "$output" | grep -oP "parse time: \K[0-9]+" | tail -1)
        local load_time=$(echo "$output" | grep -oP "load time: \K[0-9]+" | tail -1)

        if [ -n "$parse_time" ]; then
            record_metric "pf.filter.parse.$scale" "$parse_time" "ms"
        fi
        if [ -n "$load_time" ]; then
            record_metric "pf.filter.load.$scale" "$load_time" "ms"
        fi
    done
}

run_ipsec_tests() {
    log_info "Running IPsec stress tests with metrics collection..."

    local test_scales="100 500 1000 2000"

    for scale in $test_scales; do
        log_info "Testing IPsec with $scale tunnels..."

        output=$("$SCRIPT_DIR/../ipsec/ipsec-stress-test.sh" scaling "$scale" 2>&1) || true

        local parse_time=$(echo "$output" | grep -oP "iked parse time: \K[0-9]+" | tail -1)
        local load_time=$(echo "$output" | grep -oP "ipsecctl load time: \K[0-9]+" | tail -1)

        if [ -n "$parse_time" ]; then
            record_metric "ipsec.iked.parse.$scale" "$parse_time" "ms"
        fi
        if [ -n "$load_time" ]; then
            record_metric "ipsec.ipsecctl.load.$scale" "$load_time" "ms"
        fi
    done

    # Capacity test
    output=$("$SCRIPT_DIR/../ipsec/ipsec-stress-test.sh" capacity 2>&1) || true
    local max_tunnels=$(echo "$output" | grep -oP "Maximum.*: \K[0-9]+" | tail -1)
    if [ -n "$max_tunnels" ]; then
        record_metric "ipsec.capacity.max" "$max_tunnels" "count"
    fi
}

run_nats_tests() {
    log_info "Running NATS stress tests with metrics collection..."

    local nats_url="${NATS_URL:-nats://localhost:4222}"

    # Build test tools
    cd "$SCRIPT_DIR/../stress"
    go build -o nats-stress nats-controller-stress.go 2>/dev/null || {
        log_warn "Could not build NATS stress test"
        return 0
    }

    # Connection test
    for agents in 100 500 1000; do
        log_info "Testing NATS with $agents agents..."

        output=$(./nats-stress -url "$nats_url" -agents $agents -duration 30s -test connections 2>&1) || true

        local p50=$(echo "$output" | grep -oP "P50: \K[0-9]+" | tail -1)
        local p99=$(echo "$output" | grep -oP "P99: \K[0-9]+" | tail -1)

        if [ -n "$p50" ]; then
            record_metric "nats.connect.$agents" "$p50" "ms"
        fi
    done

    # Throughput test
    output=$(./nats-stress -url "$nats_url" -agents 500 -duration 60s -test throughput 2>&1) || true
    local throughput=$(echo "$output" | grep -oP "Throughput: \K[0-9.]+" | tail -1)
    if [ -n "$throughput" ]; then
        record_metric "nats.throughput.500" "$throughput" "rate"
    fi

    # Latency test
    output=$(./nats-stress -url "$nats_url" -test latency 2>&1) || true
    local latency_p50=$(echo "$output" | grep -oP "P50: \K[0-9]+" | tail -1)
    local latency_p99=$(echo "$output" | grep -oP "P99: \K[0-9]+" | tail -1)
    if [ -n "$latency_p50" ]; then
        record_metric "nats.latency.p50" "$latency_p50" "ms"
    fi
    if [ -n "$latency_p99" ]; then
        record_metric "nats.latency.p99" "$latency_p99" "ms"
    fi

    cd - >/dev/null
}

run_table_tests() {
    log_info "Running PF table stress tests with metrics collection..."

    for entries in 1000 10000 100000; do
        log_info "Testing table with $entries entries..."

        output=$("$SCRIPT_DIR/../stress/control-plane-stress.sh" tables "$entries" 2>&1) || true

        local load_time=$(echo "$output" | grep -oP "Table load time: \K[0-9]+" | tail -1)
        if [ -n "$load_time" ]; then
            record_metric "pf.table.load.$entries" "$load_time" "ms"
        fi
    done
}

run_bgp_tests() {
    log_info "Running BGP stress tests with metrics collection..."

    for routes in 1000 5000 10000; do
        log_info "Testing BGP with $routes routes..."

        output=$("$SCRIPT_DIR/../stress/control-plane-stress.sh" bgp "$routes" 2>&1) || true

        local parse_time=$(echo "$output" | grep -oP "bgpd parse time: \K[0-9]+" | tail -1)
        if [ -n "$parse_time" ]; then
            record_metric "bgp.parse.$routes" "$parse_time" "ms"
        fi
    done
}

# =============================================================================
# MAIN
# =============================================================================

main() {
    local test_suite="${1:-quick}"

    log_info "Starting stress tests with metrics collection"
    log_info "Test suite: $test_suite"
    log_info "Baseline branch: $BASELINE_BRANCH"

    export TEST_SUITE="$test_suite"
    export RESULTS_DIR="${RESULTS_DIR:-/tmp/metrics-results}"
    mkdir -p "$RESULTS_DIR"

    # Initialize metrics
    init_metrics

    # Run tests based on platform and suite
    local platform=$(uname -s)
    local status="passed"

    case "$test_suite" in
        quick)
            if [ "$platform" = "OpenBSD" ]; then
                run_pf_tests || status="failed"
            fi
            ;;
        full)
            if [ "$platform" = "OpenBSD" ]; then
                run_pf_tests || status="failed"
                run_table_tests || status="failed"
                run_ipsec_tests || status="failed"
                run_bgp_tests || status="failed"
            fi
            run_nats_tests || status="failed"
            ;;
        pf)
            run_pf_tests || status="failed"
            run_table_tests || status="failed"
            ;;
        ipsec)
            run_ipsec_tests || status="failed"
            ;;
        nats)
            run_nats_tests || status="failed"
            ;;
        bgp)
            run_bgp_tests || status="failed"
            ;;
        *)
            log_error "Unknown test suite: $test_suite"
            status="error"
            ;;
    esac

    # Finalize and compare
    finalize_metrics "$status"

    log_info "Results saved to: $RESULTS_DIR"
    log_info "Metrics database: $METRICS_DB"

    # Return appropriate exit code
    if [ "$status" = "failed" ]; then
        exit 1
    fi
}

# Run if executed directly
if [ "${BASH_SOURCE[0]}" = "$0" ]; then
    main "$@"
fi
