// Package openbsd provides native PF firewall access via ioctl
package openbsd

import (
	"encoding/binary"
	"fmt"
	"os"
	"unsafe"

	"golang.org/x/sys/unix"
)

// PF ioctl commands (from sys/net/pfvar.h)
const (
	DIOCSTART        = 0x20004401
	DIOCSTOP         = 0x20004402
	DIOCGETSTATUS    = 0xc04c4419
	DIOCGETSTATE     = 0xc1084418
	DIOCGETSTATENV   = 0xc0104447
	DIOCGETSTATES    = 0xc0104415
	DIOCCLRSTATES    = 0xc00c4413
	DIOCGETRULES     = 0xcd604406
	DIOCGETRULE      = 0xcd604407
	DIOCGETTABLES    = 0xc0104440
	DIOCGETTABLE     = 0xc450443e
	DIOCCLRTABLES    = 0xc010443a
	DIOCADDTABLES    = 0xc0104437
	DIOCGETTFLAGS    = 0xc050443b
	DIOCGETADDRS     = 0xc4504449
	DIOCGETADDR      = 0xc4504448
	DIOCADDADDRS     = 0xc4504445
	DIOCDELADDRS     = 0xc4504446
	DIOCGETIFACES    = 0xc0204457
	DIOCXBEGIN       = 0xc00c4451
	DIOCXCOMMIT      = 0xc00c4452
	DIOCXROLLBACK    = 0xc00c4453
	DIOCSETTIMEOUT   = 0xc008441d
	DIOCGETTIMEOUT   = 0xc008441e
	DIOCSETLIMIT     = 0xc0084428
	DIOCGETLIMIT     = 0xc0084427
)

// PF device path
const pfDev = "/dev/pf"

// PFStatus represents the status of the PF firewall
type PFStatus struct {
	Running       bool   `json:"running"`
	Debug         uint32 `json:"debug"`
	Hostid        uint32 `json:"hostid"`
	States        uint32 `json:"states"`
	SrcNodes      uint32 `json:"src_nodes"`
	Since         int64  `json:"since"` // Running since (Unix time)
	StateInserts  uint64 `json:"state_inserts"`
	StateRemovals uint64 `json:"state_removals"`
	StateSearches uint64 `json:"state_searches"`
	Bytes         [2]uint64 `json:"bytes"`   // [in, out]
	Packets       [2]uint64 `json:"packets"` // [in, out]
}

// PFHandle provides access to the PF device
type PFHandle struct {
	fd int
}

// OpenPF opens the PF device for ioctl operations
func OpenPF() (*PFHandle, error) {
	fd, err := unix.Open(pfDev, unix.O_RDWR, 0)
	if err != nil {
		// Try read-only for status queries
		fd, err = unix.Open(pfDev, unix.O_RDONLY, 0)
		if err != nil {
			return nil, fmt.Errorf("open %s: %w", pfDev, err)
		}
	}
	return &PFHandle{fd: fd}, nil
}

// Close closes the PF handle
func (h *PFHandle) Close() error {
	return unix.Close(h.fd)
}

// pf_status structure (simplified, matches OpenBSD pfvar.h)
type pfStatus struct {
	Ifname        [16]byte
	Running       uint8
	_             [3]byte
	Since         int64
	Debug         uint32
	Hostid        uint32
	States        uint32
	SrcNodes      uint32
	SrcNodesMax   uint32
	StatesMax     uint32
	StateInserts  uint64
	StateRemovals uint64
	StateSearches uint64
	Pcounters     [2][3]uint64 // [in/out][packets/bytes/dropped]
	Fcounters     [2]uint64    // [in/out] bytes
	Scounters     [32]uint64   // various state counters
}

// GetStatus retrieves the current PF status
func (h *PFHandle) GetStatus() (*PFStatus, error) {
	var status pfStatus

	_, _, errno := unix.Syscall(
		unix.SYS_IOCTL,
		uintptr(h.fd),
		uintptr(DIOCGETSTATUS),
		uintptr(unsafe.Pointer(&status)),
	)
	if errno != 0 {
		return nil, fmt.Errorf("DIOCGETSTATUS: %w", errno)
	}

	return &PFStatus{
		Running:       status.Running != 0,
		Debug:         status.Debug,
		Hostid:        status.Hostid,
		States:        status.States,
		SrcNodes:      status.SrcNodes,
		Since:         status.Since,
		StateInserts:  status.StateInserts,
		StateRemovals: status.StateRemovals,
		StateSearches: status.StateSearches,
		Bytes:         [2]uint64{status.Pcounters[0][1], status.Pcounters[1][1]},
		Packets:       [2]uint64{status.Pcounters[0][0], status.Pcounters[1][0]},
	}, nil
}

// Enable enables PF
func (h *PFHandle) Enable() error {
	_, _, errno := unix.Syscall(unix.SYS_IOCTL, uintptr(h.fd), uintptr(DIOCSTART), 0)
	if errno != 0 {
		return fmt.Errorf("DIOCSTART: %w", errno)
	}
	return nil
}

// Disable disables PF
func (h *PFHandle) Disable() error {
	_, _, errno := unix.Syscall(unix.SYS_IOCTL, uintptr(h.fd), uintptr(DIOCSTOP), 0)
	if errno != 0 {
		return fmt.Errorf("DIOCSTOP: %w", errno)
	}
	return nil
}

// PFTimeout represents a PF timeout value
type PFTimeout struct {
	Name    string `json:"name"`
	Seconds uint32 `json:"seconds"`
}

// Timeout names
var timeoutNames = []string{
	"tcp.first",
	"tcp.opening",
	"tcp.established",
	"tcp.closing",
	"tcp.finwait",
	"tcp.closed",
	"udp.first",
	"udp.single",
	"udp.multiple",
	"icmp.first",
	"icmp.error",
	"other.first",
	"other.single",
	"other.multiple",
	"frag",
	"interval",
	"adaptive.start",
	"adaptive.end",
	"src.track",
}

// GetTimeouts retrieves all PF timeout values
func (h *PFHandle) GetTimeouts() ([]PFTimeout, error) {
	var timeouts []PFTimeout

	for i, name := range timeoutNames {
		var timeout [2]uint32 // [index, value]
		timeout[0] = uint32(i)

		_, _, errno := unix.Syscall(
			unix.SYS_IOCTL,
			uintptr(h.fd),
			uintptr(DIOCGETTIMEOUT),
			uintptr(unsafe.Pointer(&timeout)),
		)
		if errno != 0 {
			continue
		}

		timeouts = append(timeouts, PFTimeout{
			Name:    name,
			Seconds: timeout[1],
		})
	}

	return timeouts, nil
}

// PFLimit represents a PF limit value
type PFLimit struct {
	Name  string `json:"name"`
	Limit uint32 `json:"limit"`
}

var limitNames = []string{
	"states",
	"src-nodes",
	"frags",
	"tables",
	"table-entries",
}

// GetLimits retrieves all PF limit values
func (h *PFHandle) GetLimits() ([]PFLimit, error) {
	var limits []PFLimit

	for i, name := range limitNames {
		var limit [2]uint32 // [index, value]
		limit[0] = uint32(i)

		_, _, errno := unix.Syscall(
			unix.SYS_IOCTL,
			uintptr(h.fd),
			uintptr(DIOCGETLIMIT),
			uintptr(unsafe.Pointer(&limit)),
		)
		if errno != 0 {
			continue
		}

		limits = append(limits, PFLimit{
			Name:  name,
			Limit: limit[1],
		})
	}

	return limits, nil
}

// Table operations use more complex structures
// For now, provide a simpler interface that falls back to pfctl for complex ops

// PFTable represents a PF table
type PFTable struct {
	Name    string   `json:"name"`
	Flags   uint32   `json:"flags"`
	Addrs   int      `json:"addrs"`
}

// Helper functions for checking PF availability
func IsPFAvailable() bool {
	_, err := os.Stat(pfDev)
	return err == nil
}

// Quick status check without opening handle
func IsPFRunning() bool {
	pf, err := OpenPF()
	if err != nil {
		return false
	}
	defer pf.Close()

	status, err := pf.GetStatus()
	if err != nil {
		return false
	}
	return status.Running
}

// Ignore for build constraints
var _ = binary.LittleEndian
