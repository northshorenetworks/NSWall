# NSWall SNMP Agent Makefile

PREFIX ?= /usr/local
BINDIR ?= $(PREFIX)/bin
SYSCONFDIR ?= /etc
DATADIR ?= $(PREFIX)/share

VERSION = 2.0.0
GOFLAGS = -ldflags "-s -w -X main.version=$(VERSION)"

.PHONY: all build clean install

all: build

build:
	go build $(GOFLAGS) -o nswall-snmpd

static:
	CGO_ENABLED=0 go build $(GOFLAGS) -o nswall-snmpd

clean:
	rm -f nswall-snmpd

install: build
	install -d $(DESTDIR)$(BINDIR)
	install -m 755 nswall-snmpd $(DESTDIR)$(BINDIR)/nswall-snmpd
	install -d $(DESTDIR)$(DATADIR)/snmp/mibs
	install -m 644 NSWALL-MIB.txt $(DESTDIR)$(DATADIR)/snmp/mibs/NSWALL-MIB.txt

deps:
	go mod download
	go mod verify

fmt:
	go fmt ./...

test:
	go test -v ./...
