# NSWall Controller Makefile

VERSION ?= 2.0.0
GOOS ?= $(shell go env GOOS)
GOARCH ?= $(shell go env GOARCH)
BUILD_DIR = build
BINARY = nswall-controller

# Build flags
LDFLAGS = -s -w -X main.version=$(VERSION)

.PHONY: all build clean test deps

all: build

deps:
	go mod download
	go mod tidy

build: deps
	@mkdir -p $(BUILD_DIR)
	CGO_ENABLED=0 GOOS=$(GOOS) GOARCH=$(GOARCH) go build \
		-ldflags "$(LDFLAGS)" \
		-o $(BUILD_DIR)/$(BINARY) \
		./cmd/nswall-controller

# Build for OpenBSD specifically
build-openbsd: deps
	@mkdir -p $(BUILD_DIR)
	CGO_ENABLED=0 GOOS=openbsd GOARCH=amd64 go build \
		-ldflags "$(LDFLAGS)" \
		-o $(BUILD_DIR)/$(BINARY)-openbsd-amd64 \
		./cmd/nswall-controller

# Build for Linux
build-linux: deps
	@mkdir -p $(BUILD_DIR)
	CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build \
		-ldflags "$(LDFLAGS)" \
		-o $(BUILD_DIR)/$(BINARY)-linux-amd64 \
		./cmd/nswall-controller

# Build all platforms
build-all: build-openbsd build-linux
	@echo "Built for all platforms"

test:
	go test -v ./...

clean:
	rm -rf $(BUILD_DIR)

# Install (for development)
install: build
	cp $(BUILD_DIR)/$(BINARY) /usr/local/bin/

# Run locally
run: build
	$(BUILD_DIR)/$(BINARY) -http :8080 -nats :4222 -data ./data

# Docker build
docker:
	docker build -t nswall-controller:$(VERSION) .
