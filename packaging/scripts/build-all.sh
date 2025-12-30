#!/bin/sh
# Build all NSWall components
# Run this on a development machine to build everything locally

set -e

VERSION="${VERSION:-2.0.0}"
BUILD_DIR="${BUILD_DIR:-./dist}"

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo "${GREEN}[BUILD]${NC} $1"
}

info() {
    echo "${BLUE}[INFO]${NC} $1"
}

# Create build directory
mkdir -p "$BUILD_DIR"

# Detect OS
OS=$(uname -s | tr '[:upper:]' '[:lower:]')
ARCH=$(uname -m)
case $ARCH in
    x86_64) ARCH="amd64" ;;
    aarch64|arm64) ARCH="arm64" ;;
esac

info "Building for $OS/$ARCH"
info "Version: $VERSION"

# Build API
log "Building nswall-api..."
cd api
go build -ldflags="-s -w -X main.version=${VERSION}" \
    -o "../$BUILD_DIR/nswall-api-${OS}-${ARCH}" \
    ./cmd/nswall-api
cd ..

# Build Controller
log "Building nswall-controller..."
cd controller
go build -ldflags="-s -w -X main.version=${VERSION}" \
    -o "../$BUILD_DIR/nswall-controller-${OS}-${ARCH}" \
    ./cmd/nswall-controller
cd ..

# Build for OpenBSD if requested
if [ "$BUILD_OPENBSD" = "1" ]; then
    log "Cross-compiling for OpenBSD..."

    cd api
    GOOS=openbsd GOARCH=amd64 CGO_ENABLED=0 go build \
        -ldflags="-s -w -X main.version=${VERSION}" \
        -o "../$BUILD_DIR/nswall-api-openbsd-amd64" \
        ./cmd/nswall-api
    cd ..

    cd controller
    GOOS=openbsd GOARCH=amd64 CGO_ENABLED=0 go build \
        -ldflags="-s -w -X main.version=${VERSION}" \
        -o "../$BUILD_DIR/nswall-controller-openbsd-amd64" \
        ./cmd/nswall-controller
    cd ..
fi

# Build Docker image if Docker is available
if command -v docker >/dev/null 2>&1; then
    log "Building Docker image..."
    docker build -t nswall-controller:${VERSION} \
        -f packaging/docker/Dockerfile.controller \
        --build-arg VERSION=${VERSION} \
        .

    docker tag nswall-controller:${VERSION} nswall-controller:latest
fi

# List built artifacts
log "Build complete!"
echo ""
info "Built artifacts:"
ls -la "$BUILD_DIR"

echo ""
info "To run the API:"
echo "  $BUILD_DIR/nswall-api-${OS}-${ARCH} -listen :8443"

echo ""
info "To run the Controller:"
echo "  $BUILD_DIR/nswall-controller-${OS}-${ARCH} -http :8080 -nats :4222"

echo ""
info "To run Controller via Docker:"
echo "  docker run -d -p 8080:8080 -p 4222:4222 nswall-controller:${VERSION}"
