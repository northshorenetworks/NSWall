// Package openbsd provides native OpenBSD system interfaces
// This avoids parsing command output by using direct syscalls
package openbsd

import (
	"encoding/binary"
	"fmt"
	"time"
	"unsafe"

	"golang.org/x/sys/unix"
)

// System information from sysctl

// GetHostname returns the system hostname
func GetHostname() (string, error) {
	return unix.Sysctl("kern.hostname")
}

// GetOSRelease returns the OS release version
func GetOSRelease() (string, error) {
	return unix.Sysctl("kern.osrelease")
}

// GetOSVersion returns the OS version string
func GetOSVersion() (string, error) {
	return unix.Sysctl("kern.version")
}

// GetMachine returns the machine architecture
func GetMachine() (string, error) {
	return unix.Sysctl("hw.machine")
}

// GetNCPU returns the number of CPUs
func GetNCPU() (int, error) {
	n, err := unix.SysctlUint32("hw.ncpu")
	return int(n), err
}

// GetPhysMem returns total physical memory in bytes
func GetPhysMem() (uint64, error) {
	// hw.physmem is 64-bit on OpenBSD
	val, err := unix.SysctlRaw("hw.physmem")
	if err != nil {
		return 0, err
	}
	if len(val) == 8 {
		return binary.LittleEndian.Uint64(val), nil
	}
	if len(val) == 4 {
		return uint64(binary.LittleEndian.Uint32(val)), nil
	}
	return 0, fmt.Errorf("unexpected physmem size: %d", len(val))
}

// GetBootTime returns when the system was booted
func GetBootTime() (time.Time, error) {
	tv, err := unix.SysctlTimeval("kern.boottime")
	if err != nil {
		return time.Time{}, err
	}
	return time.Unix(tv.Sec, tv.Usec*1000), nil
}

// LoadAvg represents system load averages
type LoadAvg struct {
	Load1  float64
	Load5  float64
	Load15 float64
}

// GetLoadAvg returns the system load averages
func GetLoadAvg() (*LoadAvg, error) {
	// vm.loadavg returns 3 fixpt_t values (scale factor 2048)
	raw, err := unix.SysctlRaw("vm.loadavg")
	if err != nil {
		return nil, err
	}

	// OpenBSD loadavg structure: 3 x uint32 (fixpt_t) + 1 x int (fscale)
	if len(raw) < 16 {
		return nil, fmt.Errorf("unexpected loadavg size: %d", len(raw))
	}

	scale := float64(binary.LittleEndian.Uint32(raw[12:16]))
	if scale == 0 {
		scale = 2048 // default scale factor
	}

	return &LoadAvg{
		Load1:  float64(binary.LittleEndian.Uint32(raw[0:4])) / scale,
		Load5:  float64(binary.LittleEndian.Uint32(raw[4:8])) / scale,
		Load15: float64(binary.LittleEndian.Uint32(raw[8:12])) / scale,
	}, nil
}

// VMStats contains virtual memory statistics
type VMStats struct {
	FreePages     uint32
	ActivePages   uint32
	InactivePages uint32
	WiredPages    uint32
	PageSize      uint32
}

// GetVMStats returns VM statistics via sysctl
func GetVMStats() (*VMStats, error) {
	// Individual vm stats
	pageSize, _ := unix.SysctlUint32("hw.pagesize")
	if pageSize == 0 {
		pageSize = 4096
	}

	// These may not all be available on all OpenBSD versions
	// Fall back gracefully
	stats := &VMStats{
		PageSize: pageSize,
	}

	// Try uvmexp structure
	raw, err := unix.SysctlRaw("vm.uvmexp")
	if err == nil && len(raw) >= 100 {
		// uvmexp has many fields, extract key ones
		// Offsets depend on OpenBSD version, this is a simplified version
		stats.FreePages = binary.LittleEndian.Uint32(raw[16:20])
		stats.ActivePages = binary.LittleEndian.Uint32(raw[20:24])
		stats.InactivePages = binary.LittleEndian.Uint32(raw[24:28])
		stats.WiredPages = binary.LittleEndian.Uint32(raw[28:32])
	}

	return stats, nil
}

// Sensor types for hw.sensors
const (
	SensorTemp       = 0
	SensorFanRPM     = 1
	SensorVoltsDC    = 2
	SensorPower      = 3
	SensorCurrent    = 4
	SensorWatthour   = 5
	SensorAmpHour    = 6
	SensorIndicator  = 7
	SensorInteger    = 8
	SensorPercent    = 9
	SensorLux        = 10
	SensorDrive      = 11
	SensorTimedelta  = 12
	SensorHumidity   = 13
	SensorFreq       = 14
	SensorAngle      = 15
	SensorDistance   = 16
	SensorPressure   = 17
	SensorAccel      = 18
	SensorVelocity   = 19
)

// Sensor represents a hardware sensor reading
type Sensor struct {
	Device      string
	Type        int
	Description string
	Value       int64
	Status      int
}

// Helper to check if running on OpenBSD
func IsOpenBSD() bool {
	ostype, err := unix.Sysctl("kern.ostype")
	return err == nil && ostype == "OpenBSD"
}

// CPUInfo contains CPU information
type CPUInfo struct {
	Vendor string
	Model  string
	Speed  uint64 // MHz
	NCPUs  int
}

// GetCPUInfo returns CPU information
func GetCPUInfo() (*CPUInfo, error) {
	info := &CPUInfo{}

	// Try to get CPU speed
	if speed, err := unix.SysctlUint32("hw.cpuspeed"); err == nil {
		info.Speed = uint64(speed)
	}

	// Get CPU count
	if ncpu, err := unix.SysctlUint32("hw.ncpu"); err == nil {
		info.NCPUs = int(ncpu)
	}

	// Get CPU model string (if available)
	if model, err := unix.Sysctl("hw.model"); err == nil {
		info.Model = model
	}

	// Get vendor (if available)
	if vendor, err := unix.Sysctl("machdep.cpuvendor"); err == nil {
		info.Vendor = vendor
	}

	return info, nil
}

// DiskStats contains disk I/O statistics
type DiskStats struct {
	Name       string
	Bytes      uint64
	Operations uint64
}

// Swap info
type SwapInfo struct {
	Total uint64
	Used  uint64
	Free  uint64
}

// GetSwapInfo returns swap usage (requires parsing swapctl output or using struct)
// Note: OpenBSD doesn't expose swap via sysctl easily, keeping minimal
func GetSwapInfo() (*SwapInfo, error) {
	// This would require either parsing swapctl or using
	// more complex sysctl structures. For now, return empty.
	// The command-based approach in system.go is still valid here.
	return &SwapInfo{}, nil
}

// For route access, we'll use the route package
// For PF access, we need ioctl - see pf.go

// Utility: pointer to interface for sysctl
func sysctlRawToUint64(name string) (uint64, error) {
	raw, err := unix.SysctlRaw(name)
	if err != nil {
		return 0, err
	}
	switch len(raw) {
	case 4:
		return uint64(binary.LittleEndian.Uint32(raw)), nil
	case 8:
		return binary.LittleEndian.Uint64(raw), nil
	default:
		return 0, fmt.Errorf("unexpected size %d for %s", len(raw), name)
	}
}

// Ignore for build constraints - this is OpenBSD specific
var _ = unsafe.Sizeof(0)
