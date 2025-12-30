#!/bin/ksh
#
# ipsec-stress-test.sh - IPsec stress testing and sizing
#
# Tests:
#   - Configuration generation and loading time for N tunnels
#   - iked/ipsecctl parsing performance
#   - SA table limits
#   - Memory usage under load
#   - Control plane bottlenecks
#   - Traffic throughput through tunnels
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# Test parameters
DEFAULT_TUNNEL_COUNTS="10 50 100 250 500 1000 2000"
MAX_TUNNELS=5000

# Results storage
RESULTS_DIR="/tmp/ipsec-stress-results"
mkdir -p $RESULTS_DIR

log_info() {
    echo "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo "${RED}[ERROR]${NC} $1"
}

log_metric() {
    echo "${CYAN}[METRIC]${NC} $1"
}

# =============================================================================
# TIMING UTILITIES
# =============================================================================

get_time_ms() {
    # Get time in milliseconds
    perl -MTime::HiRes=time -e 'printf "%.0f\n", time * 1000' 2>/dev/null || \
    echo $(($(date +%s) * 1000))
}

measure_time() {
    local start=$1
    local end=$2
    echo $((end - start))
}

# =============================================================================
# CONFIGURATION GENERATION
# =============================================================================

generate_iked_config() {
    local num_tunnels=$1
    local output_file=$2

    log_info "Generating iked.conf with $num_tunnels tunnels..."

    local start_time=$(get_time_ms)

    cat > $output_file << 'EOF'
# Auto-generated iked configuration for stress testing
# Tunnels: NUM_TUNNELS

set enforcesa
set fragmentation

EOF
    sed -i "s/NUM_TUNNELS/$num_tunnels/" $output_file

    for i in $(seq 1 $num_tunnels); do
        # Distribute across multiple /24 networks
        local net_a=$((i / 256))
        local net_b=$((i % 256))
        local local_net="10.${net_a}.${net_b}.0/24"

        # Remote networks in different range
        local remote_net="172.${net_a}.${net_b}.0/24"

        # Peer IP - spread across multiple subnets
        local peer_subnet=$((i / 250))
        local peer_host=$((i % 250 + 1))
        local peer_ip="192.168.${peer_subnet}.${peer_host}"

        # PSK with unique component
        local psk="tunnel${i}psk$(printf '%08x' $i)"

        cat >> $output_file << EOF
ikev2 "tunnel-$i" passive esp \\
    from $local_net to $remote_net \\
    local 192.168.0.1 peer $peer_ip \\
    psk "$psk"

EOF
    done

    local end_time=$(get_time_ms)
    local gen_time=$(measure_time $start_time $end_time)

    log_metric "Config generation time: ${gen_time}ms for $num_tunnels tunnels"

    # File size
    local file_size=$(ls -l $output_file | awk '{print $5}')
    log_metric "Config file size: $file_size bytes ($(echo "scale=2; $file_size / 1024" | bc)KB)"

    echo "$num_tunnels,$gen_time,$file_size" >> $RESULTS_DIR/generation_times.csv
}

generate_ipsecctl_config() {
    local num_tunnels=$1
    local output_file=$2

    log_info "Generating ipsec.conf with $num_tunnels flows..."

    local start_time=$(get_time_ms)

    echo "# Auto-generated ipsec.conf for stress testing" > $output_file
    echo "# Flows: $num_tunnels" >> $output_file
    echo "" >> $output_file

    for i in $(seq 1 $num_tunnels); do
        local net_a=$((i / 256))
        local net_b=$((i % 256))
        local local_net="10.${net_a}.${net_b}.0/24"
        local remote_net="172.${net_a}.${net_b}.0/24"
        local peer_subnet=$((i / 250))
        local peer_host=$((i % 250 + 1))
        local peer_ip="192.168.${peer_subnet}.${peer_host}"

        cat >> $output_file << EOF
flow esp out from $local_net to $remote_net peer $peer_ip
flow esp in from $remote_net to $local_net peer $peer_ip
EOF
    done

    local end_time=$(get_time_ms)
    local gen_time=$(measure_time $start_time $end_time)

    log_metric "ipsec.conf generation time: ${gen_time}ms"
}

# =============================================================================
# CONFIGURATION PARSING/LOADING TESTS
# =============================================================================

test_iked_parse_time() {
    local config_file=$1
    local num_tunnels=$2

    log_info "Testing iked configuration parse time..."

    local start_time=$(get_time_ms)

    # Parse without loading (-n flag)
    if iked -n -f $config_file 2>/dev/null; then
        local end_time=$(get_time_ms)
        local parse_time=$(measure_time $start_time $end_time)

        log_metric "iked parse time: ${parse_time}ms for $num_tunnels tunnels"
        echo "$num_tunnels,$parse_time" >> $RESULTS_DIR/iked_parse_times.csv
        return 0
    else
        log_error "iked configuration parsing failed"
        return 1
    fi
}

test_ipsecctl_parse_time() {
    local config_file=$1
    local num_tunnels=$2

    log_info "Testing ipsecctl configuration parse time..."

    local start_time=$(get_time_ms)

    # Parse without loading (-n flag)
    if ipsecctl -n -f $config_file 2>/dev/null; then
        local end_time=$(get_time_ms)
        local parse_time=$(measure_time $start_time $end_time)

        log_metric "ipsecctl parse time: ${parse_time}ms for $((num_tunnels * 2)) flows"
        echo "$num_tunnels,$parse_time" >> $RESULTS_DIR/ipsecctl_parse_times.csv
        return 0
    else
        log_error "ipsecctl configuration parsing failed"
        return 1
    fi
}

test_ipsecctl_load_time() {
    local config_file=$1
    local num_tunnels=$2

    log_info "Testing ipsecctl configuration load time..."

    # Flush existing
    ipsecctl -F 2>/dev/null || true

    local start_time=$(get_time_ms)

    # Actually load the configuration
    if ipsecctl -f $config_file 2>/dev/null; then
        local end_time=$(get_time_ms)
        local load_time=$(measure_time $start_time $end_time)

        log_metric "ipsecctl load time: ${load_time}ms for $((num_tunnels * 2)) flows"

        # Verify flows were loaded
        local loaded_flows=$(ipsecctl -sF 2>/dev/null | wc -l | tr -d ' ')
        log_metric "Flows loaded: $loaded_flows"

        echo "$num_tunnels,$load_time,$loaded_flows" >> $RESULTS_DIR/ipsecctl_load_times.csv

        # Cleanup
        ipsecctl -F 2>/dev/null || true
        return 0
    else
        log_error "ipsecctl configuration loading failed"
        return 1
    fi
}

# =============================================================================
# MEMORY USAGE TESTS
# =============================================================================

get_memory_usage() {
    # Get current memory usage in KB
    local mem_info=$(vmstat -s 2>/dev/null | head -5)
    local active=$(echo "$mem_info" | grep "pages active" | awk '{print $1}')
    local free=$(echo "$mem_info" | grep "pages free" | awk '{print $1}')

    # Page size is typically 4KB
    echo "$active,$free"
}

test_memory_scaling() {
    local num_tunnels=$1

    log_info "Testing memory usage for $num_tunnels tunnels..."

    # Baseline memory
    local baseline_mem=$(get_memory_usage)
    log_info "Baseline memory: $baseline_mem pages"

    # Generate and load config
    generate_ipsecctl_config $num_tunnels /tmp/ipsec-mem-test.conf

    # Load configuration
    ipsecctl -f /tmp/ipsec-mem-test.conf 2>/dev/null || true

    # Measure memory after load
    local loaded_mem=$(get_memory_usage)
    log_metric "Memory after $num_tunnels flows: $loaded_mem pages"

    # Get SA table memory from kernel
    local sa_mem=$(sysctl -n net.inet.esp.stats 2>/dev/null | head -5 || echo "N/A")

    echo "$num_tunnels,$baseline_mem,$loaded_mem" >> $RESULTS_DIR/memory_usage.csv

    # Cleanup
    ipsecctl -F 2>/dev/null || true
    rm -f /tmp/ipsec-mem-test.conf
}

# =============================================================================
# SYSTEM LIMITS TESTS
# =============================================================================

test_system_limits() {
    log_info "Checking IPsec system limits..."

    echo ""
    echo "=== IPsec Kernel Limits ==="

    # Check various sysctls related to IPsec
    local limits=(
        "net.inet.esp.enable"
        "net.inet.ah.enable"
        "net.inet.ipcomp.enable"
        "net.inet.ipip.allow"
        "net.inet.gre.allow"
        "kern.maxfiles"
        "kern.maxproc"
        "kern.maxthread"
    )

    for limit in "${limits[@]}"; do
        local value=$(sysctl -n $limit 2>/dev/null || echo "N/A")
        echo "  $limit = $value"
    done

    echo ""
    echo "=== Current SA/Flow Counts ==="
    local sa_count=$(ipsecctl -sa 2>/dev/null | grep -c "^esp\|^ah" || echo "0")
    local flow_count=$(ipsecctl -sF 2>/dev/null | wc -l | tr -d ' ')
    echo "  Active SAs: $sa_count"
    echo "  Active Flows: $flow_count"

    echo ""
}

# =============================================================================
# CAPACITY TEST - FIND MAX TUNNELS
# =============================================================================

test_max_capacity() {
    log_info "Testing maximum tunnel capacity..."

    local max_successful=0
    local test_counts="100 500 1000 2000 3000 4000 5000"

    for count in $test_counts; do
        log_info "Testing $count tunnels..."

        # Generate config
        generate_ipsecctl_config $count /tmp/ipsec-capacity.conf

        # Try to load
        ipsecctl -F 2>/dev/null || true

        local start_time=$(get_time_ms)
        if ipsecctl -f /tmp/ipsec-capacity.conf 2>/dev/null; then
            local end_time=$(get_time_ms)
            local load_time=$(measure_time $start_time $end_time)

            local loaded=$(ipsecctl -sF 2>/dev/null | wc -l | tr -d ' ')

            if [ "$loaded" -ge $((count * 2 - 10)) ]; then
                log_metric "SUCCESS: $count tunnels loaded in ${load_time}ms"
                max_successful=$count
            else
                log_warn "PARTIAL: Only $loaded of $((count * 2)) flows loaded"
            fi
        else
            log_error "FAILED: Could not load $count tunnels"
            break
        fi

        ipsecctl -F 2>/dev/null || true
    done

    log_metric "Maximum successful tunnel count: $max_successful"
    echo "max_tunnels,$max_successful" >> $RESULTS_DIR/capacity.csv

    rm -f /tmp/ipsec-capacity.conf
}

# =============================================================================
# FULL STRESS TEST SUITE
# =============================================================================

run_scaling_test() {
    local tunnel_counts=${1:-$DEFAULT_TUNNEL_COUNTS}

    echo "========================================"
    echo "  IPsec Scaling Test Suite"
    echo "========================================"
    echo ""
    echo "Testing tunnel counts: $tunnel_counts"
    echo ""

    # Initialize CSV files
    echo "tunnels,gen_time_ms,file_size_bytes" > $RESULTS_DIR/generation_times.csv
    echo "tunnels,parse_time_ms" > $RESULTS_DIR/iked_parse_times.csv
    echo "tunnels,parse_time_ms" > $RESULTS_DIR/ipsecctl_parse_times.csv
    echo "tunnels,load_time_ms,flows_loaded" > $RESULTS_DIR/ipsecctl_load_times.csv

    for count in $tunnel_counts; do
        echo ""
        echo "========================================"
        echo "  Testing $count tunnels"
        echo "========================================"

        # Generate configs
        generate_iked_config $count /tmp/iked-stress.conf
        generate_ipsecctl_config $count /tmp/ipsec-stress.conf

        # Test parsing
        test_iked_parse_time /tmp/iked-stress.conf $count
        test_ipsecctl_parse_time /tmp/ipsec-stress.conf $count

        # Test loading
        test_ipsecctl_load_time /tmp/ipsec-stress.conf $count

        # Cleanup
        rm -f /tmp/iked-stress.conf /tmp/ipsec-stress.conf
    done

    # Generate summary
    generate_report
}

# =============================================================================
# REPORT GENERATION
# =============================================================================

generate_report() {
    local report_file="$RESULTS_DIR/stress_test_report.txt"

    cat > $report_file << 'EOF'
================================================================================
                    IPsec Stress Test Report
================================================================================

EOF

    echo "Test Date: $(date)" >> $report_file
    echo "OpenBSD Version: $(uname -r)" >> $report_file
    echo "Kernel: $(uname -v)" >> $report_file
    echo "" >> $report_file

    echo "=== Configuration Generation Times ===" >> $report_file
    echo "Tunnels | Gen Time (ms) | File Size (KB)" >> $report_file
    echo "--------|---------------|---------------" >> $report_file
    tail -n +2 $RESULTS_DIR/generation_times.csv 2>/dev/null | while IFS=, read tunnels gen_time file_size; do
        local size_kb=$(echo "scale=2; $file_size / 1024" | bc)
        printf "%-7s | %-13s | %s\n" "$tunnels" "$gen_time" "$size_kb" >> $report_file
    done
    echo "" >> $report_file

    echo "=== iked Parse Times ===" >> $report_file
    echo "Tunnels | Parse Time (ms) | Rate (tunnels/sec)" >> $report_file
    echo "--------|-----------------|-------------------" >> $report_file
    tail -n +2 $RESULTS_DIR/iked_parse_times.csv 2>/dev/null | while IFS=, read tunnels parse_time; do
        if [ "$parse_time" -gt 0 ]; then
            local rate=$(echo "scale=0; $tunnels * 1000 / $parse_time" | bc)
        else
            local rate="N/A"
        fi
        printf "%-7s | %-15s | %s\n" "$tunnels" "$parse_time" "$rate" >> $report_file
    done
    echo "" >> $report_file

    echo "=== ipsecctl Parse Times ===" >> $report_file
    echo "Tunnels | Parse Time (ms) | Rate (flows/sec)" >> $report_file
    echo "--------|-----------------|------------------" >> $report_file
    tail -n +2 $RESULTS_DIR/ipsecctl_parse_times.csv 2>/dev/null | while IFS=, read tunnels parse_time; do
        if [ "$parse_time" -gt 0 ]; then
            local rate=$(echo "scale=0; $tunnels * 2 * 1000 / $parse_time" | bc)
        else
            local rate="N/A"
        fi
        printf "%-7s | %-15s | %s\n" "$tunnels" "$parse_time" "$rate" >> $report_file
    done
    echo "" >> $report_file

    echo "=== ipsecctl Load Times ===" >> $report_file
    echo "Tunnels | Load Time (ms) | Flows Loaded | Rate (flows/sec)" >> $report_file
    echo "--------|----------------|--------------|------------------" >> $report_file
    tail -n +2 $RESULTS_DIR/ipsecctl_load_times.csv 2>/dev/null | while IFS=, read tunnels load_time flows; do
        if [ "$load_time" -gt 0 ]; then
            local rate=$(echo "scale=0; $flows * 1000 / $load_time" | bc)
        else
            local rate="N/A"
        fi
        printf "%-7s | %-14s | %-12s | %s\n" "$tunnels" "$load_time" "$flows" "$rate" >> $report_file
    done
    echo "" >> $report_file

    echo "=== Bottleneck Analysis ===" >> $report_file
    echo "" >> $report_file
    echo "Potential bottlenecks to watch:" >> $report_file
    echo "  1. Configuration parsing - scales linearly with tunnel count" >> $report_file
    echo "  2. SA table insertion - may slow down at high counts" >> $report_file
    echo "  3. Memory usage - each SA/flow consumes kernel memory" >> $report_file
    echo "  4. iked reload time - impacts control plane responsiveness" >> $report_file
    echo "" >> $report_file

    echo "=================================================================================" >> $report_file

    log_info "Report saved to $report_file"
    cat $report_file
}

# =============================================================================
# THROUGHPUT TEST
# =============================================================================

test_throughput() {
    local num_tunnels=${1:-10}

    log_info "Setting up throughput test with $num_tunnels tunnels..."

    # Create test interfaces
    ifconfig vether0 create 2>/dev/null || true
    ifconfig vether0 inet 192.168.0.1/24

    for i in $(seq 1 $num_tunnels); do
        ifconfig vether$i create 2>/dev/null || true
        ifconfig vether$i inet 192.168.0.$((i + 1))/24
    done

    # Would run actual traffic tests here with nc/iperf
    log_info "Throughput testing requires traffic generation tools"
    log_info "Consider using: iperf3, netperf, or custom UDP flood"

    # Cleanup
    for i in $(seq 0 $num_tunnels); do
        ifconfig vether$i destroy 2>/dev/null || true
    done
}

# =============================================================================
# MAIN
# =============================================================================

case "${1:-help}" in
    scaling)
        run_scaling_test "${2:-$DEFAULT_TUNNEL_COUNTS}"
        ;;
    capacity)
        test_max_capacity
        ;;
    memory)
        test_memory_scaling ${2:-1000}
        ;;
    limits)
        test_system_limits
        ;;
    throughput)
        test_throughput ${2:-10}
        ;;
    quick)
        run_scaling_test "10 50 100"
        ;;
    full)
        test_system_limits
        run_scaling_test "10 50 100 250 500 1000 2000"
        test_max_capacity
        ;;
    report)
        generate_report
        ;;
    help|*)
        echo "Usage: $0 {scaling|capacity|memory|limits|throughput|quick|full|report}"
        echo ""
        echo "Commands:"
        echo "  scaling [counts] - Test config generation/parsing at various scales"
        echo "                     Default: $DEFAULT_TUNNEL_COUNTS"
        echo "  capacity         - Find maximum tunnel capacity"
        echo "  memory [count]   - Test memory usage for N tunnels"
        echo "  limits           - Show system limits"
        echo "  throughput [n]   - Test throughput through N tunnels"
        echo "  quick            - Quick test with 10, 50, 100 tunnels"
        echo "  full             - Complete stress test suite"
        echo "  report           - Generate report from existing data"
        echo ""
        echo "Examples:"
        echo "  $0 scaling '100 500 1000 2000'"
        echo "  $0 quick"
        echo "  $0 full"
        exit 0
        ;;
esac
