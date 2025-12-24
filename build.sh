#!/bin/sh
#
# NSWall Local Build Script
# Build and optionally install nswall on OpenBSD
#
# Usage:
#   ./build.sh              # Build only
#   ./build.sh install      # Build and install
#   ./build.sh clean        # Clean build artifacts
#   ./build.sh package      # Create a .tgz package
#

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
VERSION="2.0.0"
PREFIX="${PREFIX:-/usr/local}"
SYSCONFDIR="${SYSCONFDIR:-/etc}"
DESTDIR="${DESTDIR:-}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

info() {
    printf "${GREEN}==>${NC} %s\n" "$1"
}

warn() {
    printf "${YELLOW}==> WARNING:${NC} %s\n" "$1"
}

error() {
    printf "${RED}==> ERROR:${NC} %s\n" "$1"
    exit 1
}

# Check we're on OpenBSD
check_os() {
    if [ "$(uname)" != "OpenBSD" ]; then
        error "This script must be run on OpenBSD"
    fi
    info "OpenBSD $(uname -r) detected"
}

# Build nssh (the shell)
build_nssh() {
    info "Building nssh..."
    cd "${SCRIPT_DIR}/nssh"

    # Generate compile.c with build info
    sh misc-precompile.sh 2>/dev/null || true
    sh compile.sh

    # Build with OpenBSD make
    make clean 2>/dev/null || true
    make CFLAGS="-O2 -pipe -Wall"

    if [ -f nsh ]; then
        info "nssh built successfully"
    else
        error "nssh build failed"
    fi
}

# Install to system
do_install() {
    info "Installing NSWall to ${DESTDIR}${PREFIX}..."

    # Check for root
    if [ "$(id -u)" != "0" ]; then
        error "Installation requires root privileges"
    fi

    # Create directories
    install -d -m 755 "${DESTDIR}${PREFIX}/bin"
    install -d -m 755 "${DESTDIR}${PREFIX}/share/nswall/pf"
    install -d -m 755 "${DESTDIR}${PREFIX}/share/examples/nswall"
    install -d -m 750 "${DESTDIR}${SYSCONFDIR}/nswall"
    install -d -m 755 "${DESTDIR}/var/run/nswall"
    install -d -m 755 "${DESTDIR}/var/db/nswall"

    # Install binaries
    install -m 555 "${SCRIPT_DIR}/nssh/nsh" "${DESTDIR}${PREFIX}/bin/nswall"
    install -m 555 "${SCRIPT_DIR}/nssh/tcpdump/nstcpd" "${DESTDIR}${PREFIX}/bin/nstcpd"
    install -m 555 "${SCRIPT_DIR}/nssh/save-rw.sh" "${DESTDIR}${PREFIX}/bin/nswall-save"

    # Install PF support files
    install -m 644 "${SCRIPT_DIR}/nssh/pf/pf.subr" "${DESTDIR}${PREFIX}/share/nswall/pf/"
    for f in "${SCRIPT_DIR}"/nssh/pf/*.sh; do
        [ -f "$f" ] && install -m 555 "$f" "${DESTDIR}${PREFIX}/share/nswall/pf/"
    done

    # Install example config
    install -m 644 "${SCRIPT_DIR}/pkg/files/nswall.conf.sample" \
        "${DESTDIR}${PREFIX}/share/examples/nswall/"

    # Install default config if not present
    if [ ! -f "${DESTDIR}${SYSCONFDIR}/nswall/nswall.conf" ]; then
        install -m 640 "${SCRIPT_DIR}/pkg/files/nswall.conf.sample" \
            "${DESTDIR}${SYSCONFDIR}/nswall/nswall.conf"
    fi

    # Add to shells
    if [ -z "${DESTDIR}" ]; then
        if ! grep -q "^${PREFIX}/bin/nswall$" /etc/shells 2>/dev/null; then
            echo "${PREFIX}/bin/nswall" >> /etc/shells
            info "Added ${PREFIX}/bin/nswall to /etc/shells"
        fi
    fi

    info "Installation complete!"
    echo ""
    echo "To use nswall as your login shell:"
    echo "  chsh -s ${PREFIX}/bin/nswall"
    echo ""
    echo "Or run directly:"
    echo "  ${PREFIX}/bin/nswall"
    echo ""
}

# Create a package tarball
do_package() {
    info "Creating package tarball..."

    PKGDIR="${SCRIPT_DIR}/pkg-build"
    rm -rf "${PKGDIR}"
    mkdir -p "${PKGDIR}"

    # Build first
    build_nssh

    # Install to package staging area
    DESTDIR="${PKGDIR}" PREFIX="/usr/local" SYSCONFDIR="/etc" do_install_quiet

    # Create tarball
    TARBALL="nswall-${VERSION}-$(uname -m).tgz"
    cd "${PKGDIR}"
    tar czf "${SCRIPT_DIR}/${TARBALL}" .

    rm -rf "${PKGDIR}"

    info "Package created: ${TARBALL}"
    echo ""
    echo "To install:"
    echo "  tar xzf ${TARBALL} -C /"
    echo ""
}

# Quiet install for packaging
do_install_quiet() {
    # Create directories
    install -d -m 755 "${DESTDIR}${PREFIX}/bin"
    install -d -m 755 "${DESTDIR}${PREFIX}/share/nswall/pf"
    install -d -m 755 "${DESTDIR}${PREFIX}/share/examples/nswall"
    install -d -m 750 "${DESTDIR}${SYSCONFDIR}/nswall"

    # Install binaries
    install -m 555 "${SCRIPT_DIR}/nssh/nsh" "${DESTDIR}${PREFIX}/bin/nswall"
    install -m 555 "${SCRIPT_DIR}/nssh/tcpdump/nstcpd" "${DESTDIR}${PREFIX}/bin/nstcpd"
    install -m 555 "${SCRIPT_DIR}/nssh/save-rw.sh" "${DESTDIR}${PREFIX}/bin/nswall-save"

    # Install PF support files
    install -m 644 "${SCRIPT_DIR}/nssh/pf/pf.subr" "${DESTDIR}${PREFIX}/share/nswall/pf/"
    for f in "${SCRIPT_DIR}"/nssh/pf/*.sh; do
        [ -f "$f" ] && install -m 555 "$f" "${DESTDIR}${PREFIX}/share/nswall/pf/"
    done

    # Install example config
    install -m 644 "${SCRIPT_DIR}/pkg/files/nswall.conf.sample" \
        "${DESTDIR}${PREFIX}/share/examples/nswall/"
    install -m 640 "${SCRIPT_DIR}/pkg/files/nswall.conf.sample" \
        "${DESTDIR}${SYSCONFDIR}/nswall/nswall.conf"
}

# Clean build artifacts
do_clean() {
    info "Cleaning build artifacts..."
    cd "${SCRIPT_DIR}/nssh"
    make clean 2>/dev/null || true
    rm -f compile.c
    rm -f "${SCRIPT_DIR}"/*.tgz
    rm -rf "${SCRIPT_DIR}/pkg-build"
    info "Clean complete"
}

# Show usage
usage() {
    echo "NSWall Build Script"
    echo ""
    echo "Usage: $0 [command]"
    echo ""
    echo "Commands:"
    echo "  build     Build nswall (default)"
    echo "  install   Build and install to system (requires root)"
    echo "  package   Create a .tgz package for distribution"
    echo "  clean     Remove build artifacts"
    echo "  help      Show this help message"
    echo ""
    echo "Environment variables:"
    echo "  PREFIX      Installation prefix (default: /usr/local)"
    echo "  SYSCONFDIR  System config directory (default: /etc)"
    echo "  DESTDIR     Staging directory for install"
    echo ""
}

# Main
case "${1:-build}" in
    build)
        check_os
        build_nssh
        ;;
    install)
        check_os
        build_nssh
        do_install
        ;;
    package)
        check_os
        do_package
        ;;
    clean)
        do_clean
        ;;
    help|--help|-h)
        usage
        ;;
    *)
        error "Unknown command: $1"
        ;;
esac
