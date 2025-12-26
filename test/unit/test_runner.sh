#!/bin/sh
#
# NSWall Unit Test Runner
# Runs all unit tests and reports results
#

set -e

# Colors for output (if terminal supports it)
if [ -t 1 ]; then
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    NC='\033[0m' # No Color
else
    RED=''
    GREEN=''
    YELLOW=''
    NC=''
fi

# Test counters
TESTS_RUN=0
TESTS_PASSED=0
TESTS_FAILED=0
TESTS_SKIPPED=0

# Test directory
TEST_DIR=$(dirname "$0")
cd "$TEST_DIR"

# Logging
log_pass() {
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf "${GREEN}[PASS]${NC} %s\n" "$1"
}

log_fail() {
    TESTS_FAILED=$((TESTS_FAILED + 1))
    printf "${RED}[FAIL]${NC} %s\n" "$1"
}

log_skip() {
    TESTS_SKIPPED=$((TESTS_SKIPPED + 1))
    printf "${YELLOW}[SKIP]${NC} %s\n" "$1"
}

log_info() {
    printf "[INFO] %s\n" "$1"
}

# Check if running on OpenBSD
check_openbsd() {
    if [ "$(uname)" = "OpenBSD" ]; then
        return 0
    else
        return 1
    fi
}

# Test: Shell script syntax
test_shell_syntax() {
    TESTS_RUN=$((TESTS_RUN + 1))
    log_info "Testing shell script syntax..."

    SYNTAX_ERRORS=0
    for script in ../../build-*.sh ../../buildports.sh; do
        if [ -f "$script" ]; then
            if ! sh -n "$script" 2>/dev/null; then
                log_info "Syntax error in: $script"
                SYNTAX_ERRORS=$((SYNTAX_ERRORS + 1))
            fi
        fi
    done

    if [ "$SYNTAX_ERRORS" -eq 0 ]; then
        log_pass "Shell script syntax check"
    else
        log_fail "Shell script syntax check ($SYNTAX_ERRORS errors)"
    fi
}

# Test: PF scripts syntax
test_pf_scripts() {
    TESTS_RUN=$((TESTS_RUN + 1))
    log_info "Testing PF script syntax..."

    if [ ! -d "../../nssh/pf" ]; then
        log_skip "PF scripts (directory not found)"
        return
    fi

    ERRORS=0
    for script in ../../nssh/pf/*.sh; do
        if [ -f "$script" ]; then
            if ! sh -n "$script" 2>/dev/null; then
                log_info "Syntax error in: $script"
                ERRORS=$((ERRORS + 1))
            fi
        fi
    done

    if [ "$ERRORS" -eq 0 ]; then
        log_pass "PF script syntax check"
    else
        log_fail "PF script syntax check ($ERRORS errors)"
    fi
}

# Test: Check required files exist
test_required_files() {
    TESTS_RUN=$((TESTS_RUN + 1))
    log_info "Testing required files exist..."

    MISSING=0
    for file in ../../Makefile ../../LICENSE ../../list; do
        if [ ! -f "$file" ]; then
            log_info "Missing: $file"
            MISSING=$((MISSING + 1))
        fi
    done

    if [ "$MISSING" -eq 0 ]; then
        log_pass "Required files check"
    else
        log_fail "Required files check ($MISSING missing)"
    fi
}

# Test: Check nssh source files
test_nssh_sources() {
    TESTS_RUN=$((TESTS_RUN + 1))
    log_info "Testing nssh source files..."

    if [ ! -d "../../nssh" ]; then
        log_fail "nssh directory not found"
        return
    fi

    REQUIRED_FILES="main.c commands.c commands.h externs.h"
    MISSING=0
    for file in $REQUIRED_FILES; do
        if [ ! -f "../../nssh/$file" ]; then
            log_info "Missing nssh source: $file"
            MISSING=$((MISSING + 1))
        fi
    done

    if [ "$MISSING" -eq 0 ]; then
        log_pass "nssh source files check"
    else
        log_fail "nssh source files check ($MISSING missing)"
    fi
}

# Test: Check platform configurations
test_platform_configs() {
    TESTS_RUN=$((TESTS_RUN + 1))
    log_info "Testing platform configurations..."

    if [ ! -d "../../PLATFORM" ]; then
        log_fail "PLATFORM directory not found"
        return
    fi

    PLATFORMS=$(ls -d ../../PLATFORM/*/ 2>/dev/null | wc -l)
    if [ "$PLATFORMS" -gt 0 ]; then
        log_pass "Platform configurations ($PLATFORMS platforms found)"
    else
        log_fail "No platform configurations found"
    fi
}

# Test: Check initial configuration files
test_initial_conf() {
    TESTS_RUN=$((TESTS_RUN + 1))
    log_info "Testing initial configuration files..."

    if [ ! -d "../../initial-conf" ]; then
        log_fail "initial-conf directory not found"
        return
    fi

    REQUIRED_CONFS="rc pf.conf"
    MISSING=0
    for conf in $REQUIRED_CONFS; do
        if [ ! -f "../../initial-conf/$conf" ]; then
            log_info "Missing config: $conf"
            MISSING=$((MISSING + 1))
        fi
    done

    if [ "$MISSING" -eq 0 ]; then
        log_pass "Initial configuration files check"
    else
        log_fail "Initial configuration files check ($MISSING missing)"
    fi
}

# Test: Compile nssh on OpenBSD
test_compile_nssh() {
    TESTS_RUN=$((TESTS_RUN + 1))

    if ! check_openbsd; then
        log_skip "Compile nssh (not running on OpenBSD)"
        return
    fi

    log_info "Testing nssh compilation..."

    if [ ! -f "../../nssh/Makefile" ]; then
        log_skip "Compile nssh (no Makefile)"
        return
    fi

    cd ../../nssh
    if make clean >/dev/null 2>&1 && make >/dev/null 2>&1; then
        log_pass "nssh compilation"
    else
        log_fail "nssh compilation"
    fi
    cd - >/dev/null
}

# Test: PF configuration syntax (OpenBSD only)
test_pf_syntax() {
    TESTS_RUN=$((TESTS_RUN + 1))

    if ! check_openbsd; then
        log_skip "PF configuration syntax (not running on OpenBSD)"
        return
    fi

    log_info "Testing PF configuration syntax..."

    if [ ! -f "../../initial-conf/pf.conf" ]; then
        log_skip "PF configuration syntax (pf.conf not found)"
        return
    fi

    if pfctl -n -f "../../initial-conf/pf.conf" 2>/dev/null; then
        log_pass "PF configuration syntax"
    else
        log_fail "PF configuration syntax"
    fi
}

# Test: Check packages
test_packages() {
    TESTS_RUN=$((TESTS_RUN + 1))
    log_info "Testing package availability..."

    if [ ! -d "../../packages" ]; then
        log_skip "Packages (directory not found)"
        return
    fi

    PACKAGES=$(ls ../../packages/*.tgz 2>/dev/null | wc -l)
    if [ "$PACKAGES" -gt 0 ]; then
        log_pass "Packages available ($PACKAGES packages)"
    else
        log_fail "No packages found"
    fi
}

# Run all tests
run_tests() {
    echo "=========================================="
    echo "       NSWall Unit Test Runner"
    echo "=========================================="
    echo ""
    echo "Platform: $(uname -s) $(uname -r)"
    echo "Date: $(date)"
    echo ""
    echo "=========================================="
    echo ""

    # Run test functions
    test_required_files
    test_nssh_sources
    test_shell_syntax
    test_pf_scripts
    test_platform_configs
    test_initial_conf
    test_packages
    test_compile_nssh
    test_pf_syntax

    echo ""
    echo "=========================================="
    echo "              Test Summary"
    echo "=========================================="
    printf "Tests run:     %d\n" "$TESTS_RUN"
    printf "${GREEN}Tests passed:  %d${NC}\n" "$TESTS_PASSED"
    printf "${RED}Tests failed:  %d${NC}\n" "$TESTS_FAILED"
    printf "${YELLOW}Tests skipped: %d${NC}\n" "$TESTS_SKIPPED"
    echo "=========================================="

    if [ "$TESTS_FAILED" -gt 0 ]; then
        exit 1
    fi
    exit 0
}

# Main
run_tests
