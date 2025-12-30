#!/bin/sh
#
# NSWall Unified Test Runner
# Runs all available tests and generates consolidated report
#
# Usage: ./run-all-tests.sh [-v] [-q] [-h]
#   -v  Verbose output
#   -q  Quiet mode (only show summary)
#   -h  Show help
#

set -e

BASEDIR="$(cd "$(dirname "$0")/.." && pwd)"
LOGDIR="${BASEDIR}/test/results"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
REPORT="${LOGDIR}/test_report_${TIMESTAMP}.txt"
VERBOSE=0
QUIET=0

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

# Counters
TOTAL_PASSED=0
TOTAL_FAILED=0
TOTAL_SKIPPED=0

usage() {
    echo "Usage: $0 [-v] [-q] [-h]"
    echo "  -v  Verbose output"
    echo "  -q  Quiet mode (only show summary)"
    echo "  -h  Show help"
    exit 0
}

log() {
    echo "$1" | tee -a "$REPORT"
}

log_header() {
    log ""
    log "============================================"
    log "$1"
    log "============================================"
}

run_test_suite() {
    suite_name="$1"
    suite_dir="$2"
    suite_cmd="$3"

    log_header "Running: $suite_name"

    if [ ! -d "$suite_dir" ]; then
        log "${YELLOW}SKIPPED${NC}: $suite_name (directory not found)"
        TOTAL_SKIPPED=$((TOTAL_SKIPPED + 1))
        return 0
    fi

    cd "$suite_dir"

    if [ $VERBOSE -eq 1 ]; then
        if eval "$suite_cmd" 2>&1 | tee -a "$REPORT"; then
            log "${GREEN}PASSED${NC}: $suite_name"
            TOTAL_PASSED=$((TOTAL_PASSED + 1))
        else
            log "${RED}FAILED${NC}: $suite_name"
            TOTAL_FAILED=$((TOTAL_FAILED + 1))
        fi
    else
        if eval "$suite_cmd" >> "$REPORT" 2>&1; then
            [ $QUIET -eq 0 ] && echo "${GREEN}PASSED${NC}: $suite_name"
            log "PASSED: $suite_name"
            TOTAL_PASSED=$((TOTAL_PASSED + 1))
        else
            [ $QUIET -eq 0 ] && echo "${RED}FAILED${NC}: $suite_name"
            log "FAILED: $suite_name"
            TOTAL_FAILED=$((TOTAL_FAILED + 1))
        fi
    fi

    cd "$BASEDIR"
}

# Parse arguments
while getopts "vqh" opt; do
    case $opt in
        v) VERBOSE=1 ;;
        q) QUIET=1 ;;
        h) usage ;;
        *) usage ;;
    esac
done

# Setup
mkdir -p "$LOGDIR"

# Header
log "============================================"
log "NSWall Test Suite"
log "Date: $(date)"
log "Host: $(hostname)"
log "============================================"

# Run nssh C unit tests
if [ -f "${BASEDIR}/nssh/tests/test_ctl.c" ]; then
    run_test_suite "NSsh C Unit Tests" "${BASEDIR}/nssh/tests" "make clean all && ./test_ctl"
fi

# Run nssh shell integration tests
if [ -f "${BASEDIR}/nssh/tests/test_ctl_crud.sh" ]; then
    run_test_suite "NSsh Shell Integration Tests" "${BASEDIR}/nssh/tests" "sh ./test_ctl_crud.sh"
fi

# Run Go API tests
if [ -f "${BASEDIR}/api/services/api_test.go" ]; then
    run_test_suite "Services API Go Tests" "${BASEDIR}/api/services" "go test -v ./..."
fi

# Run WireGuard API tests if they exist
if [ -f "${BASEDIR}/api/wireguard/wireguard_test.go" ]; then
    run_test_suite "WireGuard API Go Tests" "${BASEDIR}/api/wireguard" "go test -v ./..."
fi

# Summary
log ""
log "============================================"
log "TEST SUMMARY"
log "============================================"
log "Passed:  $TOTAL_PASSED"
log "Failed:  $TOTAL_FAILED"
log "Skipped: $TOTAL_SKIPPED"
log "Total:   $((TOTAL_PASSED + TOTAL_FAILED + TOTAL_SKIPPED))"
log ""
log "Full report: $REPORT"
log "============================================"

# Print summary to console if quiet mode
if [ $QUIET -eq 1 ]; then
    echo ""
    echo "============================================"
    echo "TEST SUMMARY"
    echo "============================================"
    echo "Passed:  $TOTAL_PASSED"
    echo "Failed:  $TOTAL_FAILED"
    echo "Skipped: $TOTAL_SKIPPED"
    echo "============================================"
fi

# Exit with failure if any tests failed
if [ $TOTAL_FAILED -gt 0 ]; then
    exit 1
fi

exit 0
