# NSWall API Server Makefile

PREFIX ?= /usr/local
BINDIR ?= $(PREFIX)/bin
SYSCONFDIR ?= /etc

VERSION = 2.0.0
GOFLAGS = -ldflags "-s -w -X main.version=$(VERSION)"

.PHONY: all build clean install

all: build

build:
	cd cmd/nswall-api && go build $(GOFLAGS) -o ../../nswall-api

static:
	cd cmd/nswall-api && CGO_ENABLED=0 go build $(GOFLAGS) -o ../../nswall-api

clean:
	rm -f nswall-api

install: build
	install -d $(DESTDIR)$(BINDIR)
	install -m 755 nswall-api $(DESTDIR)$(BINDIR)/nswall-api
	install -d $(DESTDIR)$(SYSCONFDIR)/rc.d
	install -m 755 rc.d/nswall_api $(DESTDIR)$(SYSCONFDIR)/rc.d/nswall_api

deps:
	go mod download
	go mod verify

fmt:
	go fmt ./...

test:
	go test -v ./...
