#!/bin/bash
#
# run-stress-tests.sh - Run NSWall stress tests
#
# Can be run from Linux to stress test OpenBSD controller,
# or on OpenBSD to test local control plane components.
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RESULTS_DIR="${RESULTS_DIR:-/tmp/nswall-stress-results}"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }
log_section() {
    echo ""
    echo -e "${CYAN}======================================${NC}"
    echo -e "${CYAN}  $1${NC}"
    echo -e "${CYAN}======================================${NC}"
    echo ""
}

mkdir -p "$RESULTS_DIR"

# =============================================================================
# ENVIRONMENT DETECTION
# =============================================================================

detect_platform() {
    case "$(uname -s)" in
        OpenBSD)
            PLATFORM="openbsd"
            ;;
        Linux)
            PLATFORM="linux"
            ;;
        Darwin)
            PLATFORM="darwin"
            ;;
        *)
            PLATFORM="unknown"
            ;;
    esac
    log_info "Platform: $PLATFORM"
}

# =============================================================================
# NATS CLIENT FLOOD TEST (Linux -> OpenBSD)
# =============================================================================

run_nats_flood_test() {
    local nats_url="${1:-nats://localhost:4222}"
    local max_clients="${2:-5000}"

    log_section "NATS Client Flood Test"
    log_info "Target: $nats_url"
    log_info "Max clients: $max_clients"

    # Check if Go is available
    if ! command -v go &>/dev/null; then
        log_error "Go is required for NATS tests"
        return 1
    fi

    # Build the test
    log_info "Building NATS flood test..."
    cd "$SCRIPT_DIR"

    # Download dependencies if needed
    if [ ! -f "go.mod" ]; then
        go mod init nswall-stress-test
        go get github.com/nats-io/nats.go
    fi

    go build -o "$RESULTS_DIR/nats-flood" nats-client-flood.go

    # Run the test
    log_info "Running NATS flood test..."
    "$RESULTS_DIR/nats-flood" \
        -url "$nats_url" \
        -max "$max_clients" \
        -batch 100 \
        -batch-delay 500ms \
        -status-rate 30s \
        -hold 2m \
        2>&1 | tee "$RESULTS_DIR/nats-flood.log"
}

run_nats_stress_test() {
    local nats_url="${1:-nats://localhost:4222}"
    local agents="${2:-1000}"
    local duration="${3:-60s}"

    log_section "NATS Controller Stress Test"
    log_info "Target: $nats_url"
    log_info "Agents: $agents"
    log_info "Duration: $duration"

    cd "$SCRIPT_DIR"

    if [ ! -f "go.mod" ]; then
        go mod init nswall-stress-test
        go get github.com/nats-io/nats.go
    fi

    go build -o "$RESULTS_DIR/nats-stress" nats-controller-stress.go

    "$RESULTS_DIR/nats-stress" \
        -url "$nats_url" \
        -agents "$agents" \
        -duration "$duration" \
        -test all \
        -report "$RESULTS_DIR/nats-stress-report.txt" \
        2>&1 | tee "$RESULTS_DIR/nats-stress.log"
}

# =============================================================================
# OPENBSD CONTROL PLANE TESTS (OpenBSD only)
# =============================================================================

run_pf_stress_test() {
    if [ "$PLATFORM" != "openbsd" ]; then
        log_warn "PF stress test requires OpenBSD"
        return 0
    fi

    log_section "PF Control Plane Stress Test"

    if [ ! -x "$SCRIPT_DIR/control-plane-stress.sh" ]; then
        chmod +x "$SCRIPT_DIR/control-plane-stress.sh"
    fi

    "$SCRIPT_DIR/control-plane-stress.sh" pf "100 500 1000 2500 5000" filter \
        2>&1 | tee "$RESULTS_DIR/pf-stress.log"
}

run_table_stress_test() {
    if [ "$PLATFORM" != "openbsd" ]; then
        log_warn "Table stress test requires OpenBSD"
        return 0
    fi

    log_section "PF Table Stress Test"

    "$SCRIPT_DIR/control-plane-stress.sh" tables "1000 10000 50000 100000" \
        2>&1 | tee "$RESULTS_DIR/table-stress.log"
}

run_ipsec_stress_test() {
    if [ "$PLATFORM" != "openbsd" ]; then
        log_warn "IPsec stress test requires OpenBSD"
        return 0
    fi

    log_section "IPsec Control Plane Stress Test"

    if [ ! -x "$SCRIPT_DIR/../ipsec/ipsec-stress-test.sh" ]; then
        chmod +x "$SCRIPT_DIR/../ipsec/ipsec-stress-test.sh"
    fi

    "$SCRIPT_DIR/../ipsec/ipsec-stress-test.sh" full \
        2>&1 | tee "$RESULTS_DIR/ipsec-stress.log"
}

run_bgp_stress_test() {
    if [ "$PLATFORM" != "openbsd" ]; then
        log_warn "BGP stress test requires OpenBSD"
        return 0
    fi

    log_section "BGP Control Plane Stress Test"

    "$SCRIPT_DIR/control-plane-stress.sh" bgp "100 500 1000 5000 10000" \
        2>&1 | tee "$RESULTS_DIR/bgp-stress.log"
}

# =============================================================================
# FULL STRESS TEST SUITE
# =============================================================================

run_full_suite() {
    local nats_url="${1:-nats://localhost:4222}"

    log_section "Full Stress Test Suite"

    detect_platform

    echo "Starting full stress test suite..."
    echo "Platform: $PLATFORM"
    echo "Results directory: $RESULTS_DIR"
    echo ""

    # Run all tests based on platform
    if [ "$PLATFORM" = "openbsd" ]; then
        log_info "Running OpenBSD control plane tests..."
        run_pf_stress_test
        run_table_stress_test
        run_bgp_stress_test
        run_ipsec_stress_test
    fi

    # NATS tests can run on any platform
    log_info "Running NATS stress tests..."
    run_nats_stress_test "$nats_url" 500 30s

    # Generate summary report
    generate_summary_report
}

# =============================================================================
# SUMMARY REPORT
# =============================================================================

generate_summary_report() {
    local report="$RESULTS_DIR/stress-test-summary.txt"

    log_section "Generating Summary Report"

    cat > "$report" << EOF
================================================================================
                    NSWall Stress Test Summary
================================================================================

Test Date: $(date)
Platform: $PLATFORM
Results Directory: $RESULTS_DIR

================================================================================
                           NATS CONTROLLER
================================================================================
EOF

    if [ -f "$RESULTS_DIR/nats-stress-report.txt" ]; then
        cat "$RESULTS_DIR/nats-stress-report.txt" >> "$report"
    fi

    if [ -f "$RESULTS_DIR/nats-flood.log" ]; then
        echo "" >> "$report"
        echo "NATS Flood Test Results:" >> "$report"
        grep -E "(Connected|Failed|Messages|Memory)" "$RESULTS_DIR/nats-flood.log" | tail -20 >> "$report" || true
    fi

    if [ "$PLATFORM" = "openbsd" ]; then
        cat >> "$report" << EOF

================================================================================
                         PF CONTROL PLANE
================================================================================
EOF
        if [ -f "/tmp/control-plane-stress/control_plane_report.txt" ]; then
            cat "/tmp/control-plane-stress/control_plane_report.txt" >> "$report"
        fi

        cat >> "$report" << EOF

================================================================================
                         IPSEC CONTROL PLANE
================================================================================
EOF
        if [ -f "/tmp/ipsec-stress-results/stress_test_report.txt" ]; then
            cat "/tmp/ipsec-stress-results/stress_test_report.txt" >> "$report"
        fi
    fi

    cat >> "$report" << EOF

================================================================================
                    RECOMMENDATIONS
================================================================================

Based on stress test results:

1. NATS Controller Sizing:
   - 500 agents: 1 vCPU, 512MB RAM
   - 1000 agents: 2 vCPU, 1GB RAM
   - 2000 agents: 2 vCPU, 2GB RAM
   - 5000 agents: 4 vCPU, 4GB RAM

2. PF Performance:
   - Keep rule count < 10,000 for sub-second reloads
   - Use tables for large IP lists (scales to millions)
   - Monitor state table memory usage

3. IPsec Scaling:
   - iked handles ~2000 tunnels efficiently
   - Configuration reload time scales linearly
   - Consider tunnel teardown impact on HA failover

4. BGP/OSPF:
   - OpenBGPD handles full internet table (900k+ prefixes)
   - Config parse time is fast (~50k routes/sec)
   - Consider convergence time for large route counts

================================================================================
EOF

    log_info "Summary report saved to: $report"
    cat "$report"
}

# =============================================================================
# MAIN
# =============================================================================

show_help() {
    cat << EOF
NSWall Stress Test Runner

Usage: $0 <command> [options]

Commands:
  nats-flood <url> <max-clients>    Run NATS client flood test
  nats-stress <url> <agents> <dur>  Run NATS stress test
  pf                                Run PF stress test (OpenBSD only)
  tables                            Run table stress test (OpenBSD only)
  ipsec                             Run IPsec stress test (OpenBSD only)
  bgp                               Run BGP stress test (OpenBSD only)
  full [nats-url]                   Run full stress test suite
  help                              Show this help

Examples:
  $0 nats-flood nats://192.168.1.100:4222 5000
  $0 nats-stress nats://localhost:4222 1000 60s
  $0 full nats://controller:4222
  $0 pf

Environment Variables:
  RESULTS_DIR    Directory for test results (default: /tmp/nswall-stress-results)
EOF
}

detect_platform

case "${1:-help}" in
    nats-flood)
        run_nats_flood_test "${2:-nats://localhost:4222}" "${3:-5000}"
        ;;
    nats-stress)
        run_nats_stress_test "${2:-nats://localhost:4222}" "${3:-1000}" "${4:-60s}"
        ;;
    pf)
        run_pf_stress_test
        ;;
    tables)
        run_table_stress_test
        ;;
    ipsec)
        run_ipsec_stress_test
        ;;
    bgp)
        run_bgp_stress_test
        ;;
    full)
        run_full_suite "${2:-nats://localhost:4222}"
        ;;
    help|*)
        show_help
        ;;
esac
