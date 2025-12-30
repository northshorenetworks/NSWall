#!/bin/sh
#
# NSWall Build Script
# Portable build script for CI/CD and local development
#
# Usage:
#   ./build.sh [target]
#
# Targets:
#   all        - Build everything (default)
#   nssh       - Build nssh CLI only
#   clean      - Clean build artifacts
#   test       - Run unit tests
#   check      - Run syntax checks
#   package    - Create source package
#   help       - Show this help
#

set -e

SCRIPT_DIR=$(dirname "$0")
cd "$SCRIPT_DIR"

# Colors (if terminal supports it)
if [ -t 1 ]; then
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    NC='\033[0m'
else
    RED=''
    GREEN=''
    YELLOW=''
    NC=''
fi

log_info() {
    printf "${GREEN}[INFO]${NC} %s\n" "$1"
}

log_warn() {
    printf "${YELLOW}[WARN]${NC} %s\n" "$1"
}

log_error() {
    printf "${RED}[ERROR]${NC} %s\n" "$1"
}

# Check if running on OpenBSD
is_openbsd() {
    [ "$(uname)" = "OpenBSD" ]
}

# Build nssh
build_nssh() {
    log_info "Building nssh..."

    if [ ! -d "nssh" ]; then
        log_error "nssh directory not found"
        return 1
    fi

    cd nssh

    if [ ! -f "Makefile" ]; then
        log_warn "No Makefile found, attempting manual compilation"
        # Try to compile individual files
        for src in *.c; do
            if [ -f "$src" ]; then
                log_info "Compiling: $src"
                cc -c -I. "$src" 2>/dev/null || log_warn "Failed to compile $src (may need dependencies)"
            fi
        done
    else
        if is_openbsd; then
            make clean >/dev/null 2>&1 || true
            make
        else
            log_warn "Not running on OpenBSD, build may be incomplete"
            make clean >/dev/null 2>&1 || true
            make 2>&1 || log_warn "Build requires OpenBSD environment"
        fi
    fi

    cd ..
    log_info "nssh build complete"
}

# Run tests
run_tests() {
    log_info "Running tests..."

    if [ -x "test/unit/test_runner.sh" ]; then
        ./test/unit/test_runner.sh
    else
        log_warn "Test runner not found or not executable"
        return 1
    fi
}

# Check shell scripts
run_checks() {
    log_info "Running syntax checks..."

    ERRORS=0

    # Check shell scripts
    for script in build-*.sh buildports.sh build.sh; do
        if [ -f "$script" ]; then
            if sh -n "$script" 2>/dev/null; then
                printf "  ✓ %s\n" "$script"
            else
                printf "  ✗ %s\n" "$script"
                ERRORS=$((ERRORS + 1))
            fi
        fi
    done

    # Check PF scripts
    if [ -d "nssh/pf" ]; then
        for script in nssh/pf/*.sh; do
            if [ -f "$script" ]; then
                if sh -n "$script" 2>/dev/null; then
                    printf "  ✓ %s\n" "$script"
                else
                    printf "  ✗ %s\n" "$script"
                    ERRORS=$((ERRORS + 1))
                fi
            fi
        done
    fi

    if [ "$ERRORS" -gt 0 ]; then
        log_error "$ERRORS syntax errors found"
        return 1
    fi

    log_info "All syntax checks passed"
}

# Clean build artifacts
clean() {
    log_info "Cleaning build artifacts..."

    if [ -d "nssh" ]; then
        cd nssh
        if [ -f "Makefile" ]; then
            make clean >/dev/null 2>&1 || true
        fi
        rm -f *.o 2>/dev/null || true
        cd ..
    fi

    # Clean generated files
    rm -f bsd bsd.rd bsd.gz mr.fs 2>/dev/null || true
    rm -rf termdefs termcap.* terminfo.* 2>/dev/null || true

    log_info "Clean complete"
}

# Create source package
package() {
    log_info "Creating source package..."

    VERSION=${VERSION:-"dev"}
    PKGNAME="nswall-${VERSION}-src"

    tar czf "${PKGNAME}.tar.gz" \
        --exclude='.git' \
        --exclude='*.tar.gz' \
        --exclude='*.o' \
        --exclude='bsd' \
        --exclude='bsd.rd' \
        --exclude='bsd.gz' \
        nssh/ initial-conf/ packages/ PLATFORM/ tools/ docs/ test/ \
        Makefile build*.sh LICENSE README .github/

    log_info "Created ${PKGNAME}.tar.gz"
}

# Show help
show_help() {
    cat << 'EOF'
NSWall Build Script

Usage: ./build.sh [target]

Targets:
  all        Build everything (default)
  nssh       Build nssh CLI only
  clean      Clean build artifacts
  test       Run unit tests
  check      Run syntax checks
  package    Create source package
  help       Show this help

Environment Variables:
  VERSION    Version string for package (default: dev)

Examples:
  ./build.sh                  # Build all
  ./build.sh test             # Run tests
  ./build.sh clean            # Clean artifacts
  VERSION=2.0.0 ./build.sh package  # Create versioned package
EOF
}

# Main
case "${1:-all}" in
    all)
        run_checks
        build_nssh
        ;;
    nssh)
        build_nssh
        ;;
    clean)
        clean
        ;;
    test)
        run_tests
        ;;
    check)
        run_checks
        ;;
    package)
        package
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        log_error "Unknown target: $1"
        show_help
        exit 1
        ;;
esac
