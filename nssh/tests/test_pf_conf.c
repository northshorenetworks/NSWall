/*
 * Comprehensive pf.conf syntax and feature tests
 * Tests every feature from the pf.conf(5) man page
 *
 * Compile: cc -o test_pf_conf test_pf_conf.c
 * Run: ./test_pf_conf
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <sys/stat.h>
#include <sys/wait.h>

#define TEST_DIR "/tmp/pf_conf_test"
#define TEST_PF_CONF TEST_DIR "/pf.conf"
#define TEST_TABLE_FILE TEST_DIR "/table.txt"

static int tests_passed = 0;
static int tests_failed = 0;
static int tests_skipped = 0;

#define ASSERT(cond, msg) do { \
    if (cond) { \
        printf("  PASS: %s\n", msg); \
        tests_passed++; \
    } else { \
        printf("  FAIL: %s\n", msg); \
        tests_failed++; \
    } \
} while(0)

#define SKIP(msg) do { \
    printf("  SKIP: %s\n", msg); \
    tests_skipped++; \
} while(0)

#define TEST_SECTION(name) printf("\n=== %s ===\n", name)

/* Write pf.conf content */
static int
write_pf_conf(const char *content)
{
    FILE *f = fopen(TEST_PF_CONF, "w");
    if (f == NULL)
        return -1;
    fprintf(f, "%s", content);
    fclose(f);
    return 0;
}

/* Test pf.conf syntax with pfctl -nf */
static int
test_pf_syntax(const char *config)
{
    int ret;
    char cmd[512];

    write_pf_conf(config);
    snprintf(cmd, sizeof(cmd), "pfctl -nf %s >/dev/null 2>&1", TEST_PF_CONF);
    ret = system(cmd);
    return WEXITSTATUS(ret) == 0;
}

/* Setup test environment */
static void
setup(void)
{
    mkdir(TEST_DIR, 0700);
    unlink(TEST_PF_CONF);
    unlink(TEST_TABLE_FILE);

    /* Create sample table file */
    FILE *f = fopen(TEST_TABLE_FILE, "w");
    if (f) {
        fprintf(f, "10.0.0.0/8\n");
        fprintf(f, "172.16.0.0/12\n");
        fprintf(f, "192.168.0.0/16\n");
        fclose(f);
    }
}

static void
teardown(void)
{
    unlink(TEST_PF_CONF);
    unlink(TEST_TABLE_FILE);
    rmdir(TEST_DIR);
}

/*
 * ============================================
 * MACRO TESTS
 * ============================================
 */
static void
test_macros(void)
{
    TEST_SECTION("MACROS");

    /* Simple string macro */
    ASSERT(test_pf_syntax(
        "ext_if = \"em0\"\n"
        "pass on $ext_if\n"
    ), "simple string macro");

    /* Macro with IP address */
    ASSERT(test_pf_syntax(
        "server = \"192.168.1.1\"\n"
        "pass from $server\n"
    ), "macro with IP address");

    /* Macro with network */
    ASSERT(test_pf_syntax(
        "internal_net = \"10.0.0.0/8\"\n"
        "pass from $internal_net\n"
    ), "macro with network CIDR");

    /* Macro list */
    ASSERT(test_pf_syntax(
        "tcp_services = \"{ 22, 80, 443 }\"\n"
        "pass proto tcp to port $tcp_services\n"
    ), "macro with port list");

    /* Interface list macro */
    ASSERT(test_pf_syntax(
        "int_ifs = \"{ em0, em1, em2 }\"\n"
        "pass on $int_ifs\n"
    ), "macro with interface list");

    /* Nested macro (macro using another macro) */
    ASSERT(test_pf_syntax(
        "ext_if = \"em0\"\n"
        "ext_addr = \"($ext_if)\"\n"
        "pass from $ext_addr\n"
    ), "nested macro with interface address");

    /* Multiple macros */
    ASSERT(test_pf_syntax(
        "ext_if = \"em0\"\n"
        "int_if = \"em1\"\n"
        "server = \"192.168.1.1\"\n"
        "pass in on $ext_if from any to $server\n"
        "pass out on $int_if from $server\n"
    ), "multiple macros in rules");
}

/*
 * ============================================
 * TABLE TESTS
 * ============================================
 */
static void
test_tables(void)
{
    TEST_SECTION("TABLES");

    /* Empty persist table */
    ASSERT(test_pf_syntax(
        "table <bruteforce> persist\n"
    ), "empty persist table");

    /* Table with inline addresses */
    ASSERT(test_pf_syntax(
        "table <private> { 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16 }\n"
    ), "table with inline addresses");

    /* Table with const */
    ASSERT(test_pf_syntax(
        "table <martians> const { 0.0.0.0/8, 127.0.0.0/8, 224.0.0.0/4 }\n"
    ), "const table");

    /* Table with counters */
    ASSERT(test_pf_syntax(
        "table <sshbruteforce> persist counters\n"
    ), "table with counters");

    /* Table from file */
    ASSERT(test_pf_syntax(
        "table <rfc1918> persist file \"" TEST_TABLE_FILE "\"\n"
    ), "table from file");

    /* Table with negation */
    ASSERT(test_pf_syntax(
        "table <allowed> { 10.0.0.0/8, !10.1.0.0/16 }\n"
    ), "table with negation");

    /* Table used in rule */
    ASSERT(test_pf_syntax(
        "table <blocked> persist\n"
        "block in quick from <blocked>\n"
    ), "table used in block rule");

    /* Table with self keyword */
    ASSERT(test_pf_syntax(
        "table <myaddrs> { self }\n"
    ), "table with self keyword");
}

/*
 * ============================================
 * OPTIONS TESTS
 * ============================================
 */
static void
test_options(void)
{
    TEST_SECTION("OPTIONS");

    /* block-policy */
    ASSERT(test_pf_syntax("set block-policy drop\n"), "set block-policy drop");
    ASSERT(test_pf_syntax("set block-policy return\n"), "set block-policy return");

    /* state-policy */
    ASSERT(test_pf_syntax("set state-policy if-bound\n"), "set state-policy if-bound");
    ASSERT(test_pf_syntax("set state-policy floating\n"), "set state-policy floating");

    /* optimization */
    ASSERT(test_pf_syntax("set optimization normal\n"), "set optimization normal");
    ASSERT(test_pf_syntax("set optimization high-latency\n"), "set optimization high-latency");
    ASSERT(test_pf_syntax("set optimization satellite\n"), "set optimization satellite");
    ASSERT(test_pf_syntax("set optimization aggressive\n"), "set optimization aggressive");
    ASSERT(test_pf_syntax("set optimization conservative\n"), "set optimization conservative");

    /* loginterface */
    ASSERT(test_pf_syntax("set loginterface em0\n"), "set loginterface");
    ASSERT(test_pf_syntax("set loginterface none\n"), "set loginterface none");

    /* skip */
    ASSERT(test_pf_syntax("set skip on lo0\n"), "set skip on loopback");
    ASSERT(test_pf_syntax("set skip on { lo0, enc0 }\n"), "set skip on multiple interfaces");

    /* debug */
    ASSERT(test_pf_syntax("set debug none\n"), "set debug none");
    ASSERT(test_pf_syntax("set debug urgent\n"), "set debug urgent");
    ASSERT(test_pf_syntax("set debug misc\n"), "set debug misc");
    ASSERT(test_pf_syntax("set debug loud\n"), "set debug loud");

    /* reassemble */
    ASSERT(test_pf_syntax("set reassemble yes\n"), "set reassemble yes");
    ASSERT(test_pf_syntax("set reassemble no\n"), "set reassemble no");
    ASSERT(test_pf_syntax("set reassemble yes no-df\n"), "set reassemble yes no-df");

    /* hostid */
    ASSERT(test_pf_syntax("set hostid 1\n"), "set hostid");

    /* ruleset-optimization */
    ASSERT(test_pf_syntax("set ruleset-optimization none\n"), "set ruleset-optimization none");
    ASSERT(test_pf_syntax("set ruleset-optimization basic\n"), "set ruleset-optimization basic");
    ASSERT(test_pf_syntax("set ruleset-optimization profile\n"), "set ruleset-optimization profile");

    /* fingerprints */
    ASSERT(test_pf_syntax("set fingerprints \"/etc/pf.os\"\n"), "set fingerprints");

    /* syncookies */
    ASSERT(test_pf_syntax("set syncookies never\n"), "set syncookies never");
    ASSERT(test_pf_syntax("set syncookies always\n"), "set syncookies always");
    ASSERT(test_pf_syntax("set syncookies adaptive\n"), "set syncookies adaptive");
    ASSERT(test_pf_syntax(
        "set syncookies adaptive (start 25%, end 12%)\n"
    ), "set syncookies adaptive with thresholds");
}

/*
 * ============================================
 * TIMEOUT TESTS
 * ============================================
 */
static void
test_timeouts(void)
{
    TEST_SECTION("TIMEOUTS");

    /* TCP timeouts */
    ASSERT(test_pf_syntax("set timeout tcp.first 120\n"), "set timeout tcp.first");
    ASSERT(test_pf_syntax("set timeout tcp.opening 30\n"), "set timeout tcp.opening");
    ASSERT(test_pf_syntax("set timeout tcp.established 86400\n"), "set timeout tcp.established");
    ASSERT(test_pf_syntax("set timeout tcp.closing 900\n"), "set timeout tcp.closing");
    ASSERT(test_pf_syntax("set timeout tcp.finwait 45\n"), "set timeout tcp.finwait");
    ASSERT(test_pf_syntax("set timeout tcp.closed 90\n"), "set timeout tcp.closed");

    /* UDP timeouts */
    ASSERT(test_pf_syntax("set timeout udp.first 60\n"), "set timeout udp.first");
    ASSERT(test_pf_syntax("set timeout udp.single 30\n"), "set timeout udp.single");
    ASSERT(test_pf_syntax("set timeout udp.multiple 60\n"), "set timeout udp.multiple");

    /* ICMP timeouts */
    ASSERT(test_pf_syntax("set timeout icmp.first 20\n"), "set timeout icmp.first");
    ASSERT(test_pf_syntax("set timeout icmp.error 10\n"), "set timeout icmp.error");

    /* Other protocol timeouts */
    ASSERT(test_pf_syntax("set timeout other.first 60\n"), "set timeout other.first");
    ASSERT(test_pf_syntax("set timeout other.single 30\n"), "set timeout other.single");
    ASSERT(test_pf_syntax("set timeout other.multiple 60\n"), "set timeout other.multiple");

    /* Fragment timeout */
    ASSERT(test_pf_syntax("set timeout frag 30\n"), "set timeout frag");

    /* Interval */
    ASSERT(test_pf_syntax("set timeout interval 10\n"), "set timeout interval");

    /* Source tracking */
    ASSERT(test_pf_syntax("set timeout src.track 0\n"), "set timeout src.track");

    /* Adaptive timeouts */
    ASSERT(test_pf_syntax("set timeout adaptive.start 6000\n"), "set timeout adaptive.start");
    ASSERT(test_pf_syntax("set timeout adaptive.end 12000\n"), "set timeout adaptive.end");

    /* Multiple timeouts in block */
    ASSERT(test_pf_syntax(
        "set timeout { tcp.established 86400, tcp.closing 60, udp.first 30 }\n"
    ), "set timeout block");
}

/*
 * ============================================
 * LIMIT TESTS
 * ============================================
 */
static void
test_limits(void)
{
    TEST_SECTION("LIMITS");

    /* State limits */
    ASSERT(test_pf_syntax("set limit states 100000\n"), "set limit states");

    /* Source node limits */
    ASSERT(test_pf_syntax("set limit src-nodes 10000\n"), "set limit src-nodes");

    /* Fragment limits */
    ASSERT(test_pf_syntax("set limit frags 5000\n"), "set limit frags");

    /* Table limits */
    ASSERT(test_pf_syntax("set limit tables 1000\n"), "set limit tables");
    ASSERT(test_pf_syntax("set limit table-entries 200000\n"), "set limit table-entries");

    /* Anchor limits */
    ASSERT(test_pf_syntax("set limit anchors 512\n"), "set limit anchors");

    /* Multiple limits in block */
    ASSERT(test_pf_syntax(
        "set limit { states 100000, src-nodes 10000, tables 1000 }\n"
    ), "set limit block");
}

/*
 * ============================================
 * QUEUE TESTS (Traffic Shaping/HFSC)
 * ============================================
 */
static void
test_queues(void)
{
    TEST_SECTION("QUEUES");

    /* Root queue */
    ASSERT(test_pf_syntax(
        "queue root on em0 bandwidth 100M\n"
    ), "root queue on interface");

    /* Queue with max bandwidth */
    ASSERT(test_pf_syntax(
        "queue root on em0 bandwidth 100M max 100M\n"
    ), "queue with max bandwidth");

    /* Queue with burst */
    ASSERT(test_pf_syntax(
        "queue root on em0 bandwidth 100M burst 150M for 50ms\n"
    ), "queue with burst");

    /* Child queue */
    ASSERT(test_pf_syntax(
        "queue root on em0 bandwidth 100M\n"
        "queue bulk parent root bandwidth 20M\n"
    ), "child queue");

    /* Default queue */
    ASSERT(test_pf_syntax(
        "queue root on em0 bandwidth 100M\n"
        "queue std parent root bandwidth 50M default\n"
    ), "default queue");

    /* Queue with qlimit */
    ASSERT(test_pf_syntax(
        "queue root on em0 bandwidth 100M\n"
        "queue bulk parent root bandwidth 20M qlimit 100\n"
    ), "queue with qlimit");

    /* Queue hierarchy */
    ASSERT(test_pf_syntax(
        "queue root on em0 bandwidth 100M\n"
        "queue main parent root bandwidth 90M\n"
        "queue bulk parent main bandwidth 30M\n"
        "queue web parent main bandwidth 40M\n"
        "queue ssh parent main bandwidth 20M default\n"
    ), "queue hierarchy");

    /* Queue with min bandwidth */
    ASSERT(test_pf_syntax(
        "queue root on em0 bandwidth 100M\n"
        "queue voip parent root bandwidth 10M min 5M\n"
    ), "queue with min bandwidth");

    /* Match rule with queue assignment */
    ASSERT(test_pf_syntax(
        "queue root on em0 bandwidth 100M\n"
        "queue web parent root bandwidth 50M\n"
        "match out on em0 proto tcp to port 80 set queue web\n"
    ), "match rule with queue assignment");
}

/*
 * ============================================
 * FILTERING RULES - PASS
 * ============================================
 */
static void
test_pass_rules(void)
{
    TEST_SECTION("PASS RULES");

    /* Simple pass all */
    ASSERT(test_pf_syntax("pass all\n"), "pass all");

    /* Pass with direction */
    ASSERT(test_pf_syntax("pass in all\n"), "pass in all");
    ASSERT(test_pf_syntax("pass out all\n"), "pass out all");

    /* Pass on interface */
    ASSERT(test_pf_syntax("pass on em0\n"), "pass on interface");

    /* Pass with quick */
    ASSERT(test_pf_syntax("pass in quick on em0\n"), "pass in quick");

    /* Pass with logging */
    ASSERT(test_pf_syntax("pass in log on em0\n"), "pass in log");
    ASSERT(test_pf_syntax("pass in log (all) on em0\n"), "pass in log (all)");
    ASSERT(test_pf_syntax("pass in log (to pflog1) on em0\n"), "pass in log to interface");
    ASSERT(test_pf_syntax("pass in log (user) on em0\n"), "pass in log (user)");

    /* Pass with protocol */
    ASSERT(test_pf_syntax("pass proto tcp\n"), "pass proto tcp");
    ASSERT(test_pf_syntax("pass proto udp\n"), "pass proto udp");
    ASSERT(test_pf_syntax("pass proto icmp\n"), "pass proto icmp");
    ASSERT(test_pf_syntax("pass proto icmp6\n"), "pass proto icmp6");
    ASSERT(test_pf_syntax("pass proto { tcp, udp }\n"), "pass proto list");

    /* Pass with addresses */
    ASSERT(test_pf_syntax("pass from 10.0.0.0/8\n"), "pass from network");
    ASSERT(test_pf_syntax("pass to 192.168.1.1\n"), "pass to host");
    ASSERT(test_pf_syntax("pass from any to any\n"), "pass from any to any");
    ASSERT(test_pf_syntax("pass from 10.0.0.1 to 10.0.0.2\n"), "pass from host to host");

    /* Pass with ports */
    ASSERT(test_pf_syntax("pass proto tcp to port 22\n"), "pass to port");
    ASSERT(test_pf_syntax("pass proto tcp to port ssh\n"), "pass to port by name");
    ASSERT(test_pf_syntax("pass proto tcp to port { 22, 80, 443 }\n"), "pass to port list");
    ASSERT(test_pf_syntax("pass proto tcp to port 1024:65535\n"), "pass to port range");
    ASSERT(test_pf_syntax("pass proto tcp to port >= 1024\n"), "pass to port comparison");
    ASSERT(test_pf_syntax("pass proto tcp from port 80\n"), "pass from port");

    /* Pass with interface address */
    ASSERT(test_pf_syntax("pass from (em0)\n"), "pass from interface address");
    ASSERT(test_pf_syntax("pass from (em0:network)\n"), "pass from interface network");
    ASSERT(test_pf_syntax("pass from (em0:broadcast)\n"), "pass from interface broadcast");
    ASSERT(test_pf_syntax("pass from (em0:peer)\n"), "pass from interface peer");

    /* Pass with negation */
    ASSERT(test_pf_syntax("pass from ! 10.0.0.0/8\n"), "pass from negated network");

    /* Pass with state */
    ASSERT(test_pf_syntax("pass keep state\n"), "pass keep state");
    ASSERT(test_pf_syntax("pass modulate state\n"), "pass modulate state");
    ASSERT(test_pf_syntax("pass synproxy state\n"), "pass synproxy state");
    ASSERT(test_pf_syntax("pass no state\n"), "pass no state");

    /* Pass with state options */
    ASSERT(test_pf_syntax(
        "pass proto tcp keep state (max 100)\n"
    ), "pass state max");
    ASSERT(test_pf_syntax(
        "pass proto tcp keep state (max 100, source-track rule)\n"
    ), "pass state source-track rule");
    ASSERT(test_pf_syntax(
        "pass proto tcp keep state (max-src-nodes 100)\n"
    ), "pass state max-src-nodes");
    ASSERT(test_pf_syntax(
        "pass proto tcp keep state (max-src-states 10)\n"
    ), "pass state max-src-states");
    ASSERT(test_pf_syntax(
        "pass proto tcp keep state (max-src-conn 10)\n"
    ), "pass state max-src-conn");
    ASSERT(test_pf_syntax(
        "pass proto tcp keep state (max-src-conn-rate 10/5)\n"
    ), "pass state max-src-conn-rate");
    ASSERT(test_pf_syntax(
        "pass proto tcp keep state (overload <bruteforce>)\n"
        "table <bruteforce> persist\n"
    ), "pass state overload");
    ASSERT(test_pf_syntax(
        "pass proto tcp keep state (overload <bruteforce> flush)\n"
        "table <bruteforce> persist\n"
    ), "pass state overload flush");
    ASSERT(test_pf_syntax(
        "pass proto tcp keep state (overload <bruteforce> flush global)\n"
        "table <bruteforce> persist\n"
    ), "pass state overload flush global");
    ASSERT(test_pf_syntax(
        "pass proto tcp keep state (sloppy)\n"
    ), "pass state sloppy");
    ASSERT(test_pf_syntax(
        "pass proto tcp keep state (pflow)\n"
    ), "pass state pflow");
    ASSERT(test_pf_syntax(
        "pass proto tcp keep state (if-bound)\n"
    ), "pass state if-bound");
    ASSERT(test_pf_syntax(
        "pass proto tcp keep state (floating)\n"
    ), "pass state floating");

    /* Pass with flags */
    ASSERT(test_pf_syntax("pass proto tcp flags S/SA\n"), "pass flags S/SA");
    ASSERT(test_pf_syntax("pass proto tcp flags S/SAFR\n"), "pass flags S/SAFR");
    ASSERT(test_pf_syntax("pass proto tcp flags any\n"), "pass flags any");

    /* Pass with ICMP type */
    ASSERT(test_pf_syntax("pass proto icmp icmp-type echoreq\n"), "pass icmp echoreq");
    ASSERT(test_pf_syntax("pass proto icmp icmp-type { echoreq, unreach }\n"), "pass icmp type list");
    ASSERT(test_pf_syntax("pass proto icmp icmp-type unreach code needfrag\n"), "pass icmp code");
    ASSERT(test_pf_syntax("pass proto icmp6 icmp6-type echoreq\n"), "pass icmp6 echoreq");

    /* Pass with TOS/DSCP */
    ASSERT(test_pf_syntax("pass tos lowdelay\n"), "pass tos lowdelay");
    ASSERT(test_pf_syntax("pass tos 0x10\n"), "pass tos hex");

    /* Pass with OS fingerprint */
    ASSERT(test_pf_syntax("pass proto tcp from any os \"OpenBSD\"\n"), "pass os fingerprint");

    /* Pass with user/group */
    ASSERT(test_pf_syntax("pass proto tcp user root\n"), "pass user");
    ASSERT(test_pf_syntax("pass proto tcp user { root, www }\n"), "pass user list");
    ASSERT(test_pf_syntax("pass proto tcp group wheel\n"), "pass group");

    /* Pass with probability */
    ASSERT(test_pf_syntax("pass probability 50%\n"), "pass probability");

    /* Pass with label */
    ASSERT(test_pf_syntax("pass label \"ssh-traffic\"\n"), "pass label");

    /* Pass with tag */
    ASSERT(test_pf_syntax("pass tag EXTERNAL\n"), "pass tag");
    ASSERT(test_pf_syntax("pass tagged EXTERNAL\n"), "pass tagged");

    /* Pass with rtable */
    ASSERT(test_pf_syntax("pass rtable 1\n"), "pass rtable");

    /* Pass with received-on */
    ASSERT(test_pf_syntax("pass received-on em0\n"), "pass received-on");

    /* Pass with max-pkt-rate */
    ASSERT(test_pf_syntax("pass max-pkt-rate 100/1\n"), "pass max-pkt-rate");

    /* Pass with set prio */
    ASSERT(test_pf_syntax("pass set prio 5\n"), "pass set prio");
    ASSERT(test_pf_syntax("pass set prio (5, 6)\n"), "pass set prio tuple");

    /* Pass with set tos */
    ASSERT(test_pf_syntax("pass set tos lowdelay\n"), "pass set tos");

    /* Pass with reply-to */
    ASSERT(test_pf_syntax("pass in reply-to em0\n"), "pass reply-to interface");
    ASSERT(test_pf_syntax("pass in reply-to (em0 10.0.0.1)\n"), "pass reply-to with gateway");

    /* Pass with route-to */
    ASSERT(test_pf_syntax("pass out route-to em1\n"), "pass route-to interface");
    ASSERT(test_pf_syntax("pass out route-to (em1 10.0.0.1)\n"), "pass route-to with gateway");
    ASSERT(test_pf_syntax(
        "pass out route-to { (em1 10.0.0.1), (em2 10.0.0.2) } round-robin\n"
    ), "pass route-to round-robin");

    /* Pass with dup-to */
    ASSERT(test_pf_syntax("pass dup-to (em1 10.0.0.1)\n"), "pass dup-to");

    /* Pass with divert-to */
    ASSERT(test_pf_syntax("pass in divert-to 127.0.0.1 port 8080\n"), "pass divert-to");

    /* Pass with divert-reply */
    ASSERT(test_pf_syntax("pass out divert-reply\n"), "pass divert-reply");

    /* Pass with divert-packet */
    ASSERT(test_pf_syntax("pass in divert-packet port 700\n"), "pass divert-packet");

    /* Pass with allow-opts */
    ASSERT(test_pf_syntax("pass allow-opts\n"), "pass allow-opts");

    /* Pass with once */
    ASSERT(test_pf_syntax("pass once\n"), "pass once");

    /* Pass af-to (address family translation) */
    ASSERT(test_pf_syntax("pass in af-to inet6 from ::1\n"), "pass af-to");

    /* Pass with rdomain */
    ASSERT(test_pf_syntax("pass in on em0 rdomain 1\n"), "pass rdomain");
}

/*
 * ============================================
 * FILTERING RULES - BLOCK
 * ============================================
 */
static void
test_block_rules(void)
{
    TEST_SECTION("BLOCK RULES");

    /* Simple block */
    ASSERT(test_pf_syntax("block all\n"), "block all");

    /* Block with direction */
    ASSERT(test_pf_syntax("block in all\n"), "block in all");
    ASSERT(test_pf_syntax("block out all\n"), "block out all");

    /* Block with quick */
    ASSERT(test_pf_syntax("block in quick on em0\n"), "block in quick");

    /* Block return types */
    ASSERT(test_pf_syntax("block drop in\n"), "block drop");
    ASSERT(test_pf_syntax("block return in\n"), "block return");
    ASSERT(test_pf_syntax("block return-rst in\n"), "block return-rst");
    ASSERT(test_pf_syntax("block return-icmp in\n"), "block return-icmp");
    ASSERT(test_pf_syntax("block return-icmp (port-unr) in\n"), "block return-icmp type");
    ASSERT(test_pf_syntax("block return-icmp6 in\n"), "block return-icmp6");

    /* Block with logging */
    ASSERT(test_pf_syntax("block in log\n"), "block in log");
    ASSERT(test_pf_syntax("block in log (all) quick from any\n"), "block in log all");

    /* Block from specific sources */
    ASSERT(test_pf_syntax("block in from 192.168.1.100\n"), "block from host");
    ASSERT(test_pf_syntax("block in from 10.0.0.0/8\n"), "block from network");

    /* Block with table */
    ASSERT(test_pf_syntax(
        "table <blocked> persist\n"
        "block in quick from <blocked>\n"
    ), "block from table");

    /* Block to specific destinations */
    ASSERT(test_pf_syntax("block out to 192.168.1.0/24\n"), "block to network");

    /* Block specific ports */
    ASSERT(test_pf_syntax("block proto tcp to port 23\n"), "block telnet");
    ASSERT(test_pf_syntax("block proto tcp to port { 23, 21 }\n"), "block port list");

    /* Block with label */
    ASSERT(test_pf_syntax("block label \"blocked-traffic\"\n"), "block with label");
}

/*
 * ============================================
 * MATCH RULES
 * ============================================
 */
static void
test_match_rules(void)
{
    TEST_SECTION("MATCH RULES");

    /* Simple match */
    ASSERT(test_pf_syntax("match all\n"), "match all");

    /* Match with direction */
    ASSERT(test_pf_syntax("match in all\n"), "match in");
    ASSERT(test_pf_syntax("match out all\n"), "match out");

    /* Match with set prio */
    ASSERT(test_pf_syntax("match out on em0 set prio 5\n"), "match set prio");

    /* Match with set tos */
    ASSERT(test_pf_syntax("match out set tos lowdelay\n"), "match set tos");

    /* Match with tag */
    ASSERT(test_pf_syntax("match in tag EXTERNAL\n"), "match tag");

    /* Match for scrub */
    ASSERT(test_pf_syntax("match in all scrub (no-df)\n"), "match scrub no-df");
    ASSERT(test_pf_syntax("match in all scrub (min-ttl 64)\n"), "match scrub min-ttl");
    ASSERT(test_pf_syntax("match in all scrub (max-mss 1440)\n"), "match scrub max-mss");
    ASSERT(test_pf_syntax("match in all scrub (random-id)\n"), "match scrub random-id");
    ASSERT(test_pf_syntax("match in all scrub (reassemble tcp)\n"), "match scrub reassemble tcp");
    ASSERT(test_pf_syntax(
        "match in all scrub (no-df min-ttl 64 max-mss 1440 random-id)\n"
    ), "match scrub multiple options");

    /* Match for NAT */
    ASSERT(test_pf_syntax(
        "match out on em0 from 10.0.0.0/8 nat-to (em0)\n"
    ), "match nat-to");

    /* Match for redirect */
    ASSERT(test_pf_syntax(
        "match in on em0 proto tcp to port 80 rdr-to 192.168.1.10\n"
    ), "match rdr-to");

    /* Match for binat */
    ASSERT(test_pf_syntax(
        "match on em0 from 10.0.0.1 binat-to 192.168.1.1\n"
    ), "match binat-to");
}

/*
 * ============================================
 * NAT RULES
 * ============================================
 */
static void
test_nat_rules(void)
{
    TEST_SECTION("NAT RULES");

    /* Basic NAT */
    ASSERT(test_pf_syntax(
        "match out on em0 from 10.0.0.0/8 nat-to (em0)\n"
    ), "basic NAT");

    /* NAT with static address */
    ASSERT(test_pf_syntax(
        "match out on em0 from 10.0.0.0/8 nat-to 192.168.1.1\n"
    ), "NAT to static address");

    /* NAT with port range */
    ASSERT(test_pf_syntax(
        "match out on em0 from 10.0.0.0/8 nat-to (em0) port 1024:65535\n"
    ), "NAT with port range");

    /* NAT with static-port */
    ASSERT(test_pf_syntax(
        "match out on em0 from 10.0.0.0/8 nat-to (em0) static-port\n"
    ), "NAT with static-port");

    /* NAT with bitmask */
    ASSERT(test_pf_syntax(
        "match out on em0 from 10.0.0.0/24 nat-to 192.168.1.0/24 bitmask\n"
    ), "NAT with bitmask");

    /* NAT with random */
    ASSERT(test_pf_syntax(
        "match out on em0 from 10.0.0.0/8 nat-to { 192.168.1.1, 192.168.1.2 } random\n"
    ), "NAT with random");

    /* NAT with source-hash */
    ASSERT(test_pf_syntax(
        "match out on em0 from 10.0.0.0/8 nat-to { 192.168.1.1, 192.168.1.2 } source-hash\n"
    ), "NAT with source-hash");

    /* NAT with round-robin */
    ASSERT(test_pf_syntax(
        "match out on em0 from 10.0.0.0/8 nat-to { 192.168.1.1, 192.168.1.2 } round-robin\n"
    ), "NAT with round-robin");

    /* NAT with sticky-address */
    ASSERT(test_pf_syntax(
        "match out on em0 from 10.0.0.0/8 nat-to { 192.168.1.1, 192.168.1.2 } round-robin sticky-address\n"
    ), "NAT with sticky-address");
}

/*
 * ============================================
 * REDIRECT (RDR) RULES
 * ============================================
 */
static void
test_rdr_rules(void)
{
    TEST_SECTION("REDIRECT (RDR) RULES");

    /* Basic redirect */
    ASSERT(test_pf_syntax(
        "match in on em0 proto tcp to port 80 rdr-to 192.168.1.10\n"
    ), "basic redirect");

    /* Redirect with port */
    ASSERT(test_pf_syntax(
        "match in on em0 proto tcp to port 80 rdr-to 192.168.1.10 port 8080\n"
    ), "redirect with port");

    /* Redirect to pool */
    ASSERT(test_pf_syntax(
        "match in on em0 proto tcp to port 80 rdr-to { 192.168.1.10, 192.168.1.11 }\n"
    ), "redirect to pool");

    /* Redirect with round-robin */
    ASSERT(test_pf_syntax(
        "match in on em0 proto tcp to port 80 rdr-to { 192.168.1.10, 192.168.1.11 } round-robin\n"
    ), "redirect round-robin");

    /* Redirect with sticky-address */
    ASSERT(test_pf_syntax(
        "match in on em0 proto tcp to port 80 rdr-to { 192.168.1.10, 192.168.1.11 } round-robin sticky-address\n"
    ), "redirect sticky-address");

    /* Redirect external to internal */
    ASSERT(test_pf_syntax(
        "match in on em0 proto tcp to (em0) port 22 rdr-to 10.0.0.5\n"
    ), "redirect external to internal");
}

/*
 * ============================================
 * BINAT RULES
 * ============================================
 */
static void
test_binat_rules(void)
{
    TEST_SECTION("BINAT RULES");

    /* Basic binat */
    ASSERT(test_pf_syntax(
        "match on em0 from 10.0.0.1 binat-to 192.168.1.1\n"
    ), "basic binat");

    /* Binat for network */
    ASSERT(test_pf_syntax(
        "match on em0 from 10.0.0.0/24 binat-to 192.168.1.0/24\n"
    ), "binat for network");
}

/*
 * ============================================
 * ANTISPOOF
 * ============================================
 */
static void
test_antispoof(void)
{
    TEST_SECTION("ANTISPOOF");

    /* Basic antispoof */
    ASSERT(test_pf_syntax("antispoof for em0\n"), "basic antispoof");

    /* Antispoof with logging */
    ASSERT(test_pf_syntax("antispoof log for em0\n"), "antispoof with log");

    /* Antispoof with quick */
    ASSERT(test_pf_syntax("antispoof quick for em0\n"), "antispoof with quick");

    /* Antispoof with log and quick */
    ASSERT(test_pf_syntax("antispoof log quick for em0\n"), "antispoof log quick");

    /* Antispoof for multiple interfaces */
    ASSERT(test_pf_syntax("antispoof for { em0, em1 }\n"), "antispoof multiple interfaces");

    /* Antispoof with inet */
    ASSERT(test_pf_syntax("antispoof for em0 inet\n"), "antispoof inet");

    /* Antispoof with inet6 */
    ASSERT(test_pf_syntax("antispoof for em0 inet6\n"), "antispoof inet6");

    /* Antispoof with label */
    ASSERT(test_pf_syntax("antispoof for em0 label \"antispoof-em0\"\n"), "antispoof with label");
}

/*
 * ============================================
 * ANCHORS
 * ============================================
 */
static void
test_anchors(void)
{
    TEST_SECTION("ANCHORS");

    /* Simple anchor */
    ASSERT(test_pf_syntax("anchor \"external\"\n"), "simple anchor");

    /* Anchor with direction */
    ASSERT(test_pf_syntax("anchor \"incoming\" in\n"), "anchor in");
    ASSERT(test_pf_syntax("anchor \"outgoing\" out\n"), "anchor out");

    /* Anchor on interface */
    ASSERT(test_pf_syntax("anchor \"dmz\" on em1\n"), "anchor on interface");

    /* Anchor with quick */
    ASSERT(test_pf_syntax("anchor \"quick-rules\" quick\n"), "anchor quick");

    /* Anchor with proto */
    ASSERT(test_pf_syntax("anchor \"tcp-rules\" proto tcp\n"), "anchor proto");

    /* Anchor with from/to */
    ASSERT(test_pf_syntax("anchor \"local\" from 10.0.0.0/8\n"), "anchor from");

    /* Anchor with inline rules */
    ASSERT(test_pf_syntax(
        "anchor \"inline\" {\n"
        "    pass proto tcp to port 22\n"
        "    pass proto tcp to port 80\n"
        "}\n"
    ), "anchor with inline rules");

    /* Wildcard anchor */
    ASSERT(test_pf_syntax("anchor \"authpf/*\"\n"), "wildcard anchor");

    /* NAT anchor */
    ASSERT(test_pf_syntax("anchor \"nat-rules\" out on em0\n"), "NAT anchor");

    /* Load anchor */
    ASSERT(test_pf_syntax("load anchor \"external\" from \"/etc/pf.external\"\n"), "load anchor");

    /* Nested anchor reference */
    ASSERT(test_pf_syntax("anchor \"parent/child\"\n"), "nested anchor path");
}

/*
 * ============================================
 * SCRUB (NORMALIZATION)
 * ============================================
 */
static void
test_scrub(void)
{
    TEST_SECTION("SCRUB (NORMALIZATION)");

    /* Match scrub - modern syntax */
    ASSERT(test_pf_syntax("match in all scrub (no-df)\n"), "scrub no-df");
    ASSERT(test_pf_syntax("match in all scrub (min-ttl 64)\n"), "scrub min-ttl");
    ASSERT(test_pf_syntax("match in all scrub (max-mss 1440)\n"), "scrub max-mss");
    ASSERT(test_pf_syntax("match in all scrub (random-id)\n"), "scrub random-id");
    ASSERT(test_pf_syntax("match in all scrub (reassemble tcp)\n"), "scrub reassemble tcp");

    /* Combined scrub options */
    ASSERT(test_pf_syntax(
        "match in all scrub (no-df random-id max-mss 1440)\n"
    ), "scrub combined options");

    /* Scrub for specific protocol */
    ASSERT(test_pf_syntax("match in proto tcp scrub (max-mss 1440)\n"), "scrub proto tcp");

    /* Fragment reassembly */
    ASSERT(test_pf_syntax("match in all scrub (reassemble tcp)\n"), "fragment reassembly");
}

/*
 * ============================================
 * COMPLEX/COMBINED CONFIGURATIONS
 * ============================================
 */
static void
test_complex_configs(void)
{
    TEST_SECTION("COMPLEX CONFIGURATIONS");

    /* Full firewall config */
    ASSERT(test_pf_syntax(
        "# Macros\n"
        "ext_if = \"em0\"\n"
        "int_if = \"em1\"\n"
        "tcp_services = \"{ 22, 80, 443 }\"\n"
        "\n"
        "# Tables\n"
        "table <bruteforce> persist\n"
        "table <private> const { 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16 }\n"
        "\n"
        "# Options\n"
        "set block-policy drop\n"
        "set skip on lo0\n"
        "\n"
        "# Normalization\n"
        "match in all scrub (no-df random-id)\n"
        "\n"
        "# NAT\n"
        "match out on $ext_if from <private> nat-to ($ext_if)\n"
        "\n"
        "# Antispoof\n"
        "antispoof quick for $ext_if\n"
        "\n"
        "# Default deny\n"
        "block all\n"
        "\n"
        "# Allow outbound\n"
        "pass out\n"
        "\n"
        "# Allow services\n"
        "pass in on $ext_if proto tcp to port $tcp_services\n"
        "\n"
        "# Block brute force\n"
        "block in quick from <bruteforce>\n"
    ), "full firewall configuration");

    /* Load balancer config */
    ASSERT(test_pf_syntax(
        "ext_if = \"em0\"\n"
        "webservers = \"{ 192.168.1.10, 192.168.1.11, 192.168.1.12 }\"\n"
        "\n"
        "match in on $ext_if proto tcp to port 80 rdr-to $webservers round-robin sticky-address\n"
        "pass in on $ext_if proto tcp to port 80\n"
    ), "load balancer configuration");

    /* VPN gateway config */
    ASSERT(test_pf_syntax(
        "ext_if = \"em0\"\n"
        "vpn_if = \"tun0\"\n"
        "\n"
        "set skip on { lo0, $vpn_if }\n"
        "\n"
        "# Allow VPN traffic\n"
        "pass in on $ext_if proto udp to port 1194\n"
        "pass in on $ext_if proto esp\n"
        "pass in on $ext_if proto ah\n"
        "\n"
        "# Allow IPsec IKE\n"
        "pass in on $ext_if proto udp to port { 500, 4500 }\n"
    ), "VPN gateway configuration");

    /* QoS configuration */
    ASSERT(test_pf_syntax(
        "ext_if = \"em0\"\n"
        "\n"
        "# Queue definition\n"
        "queue root on $ext_if bandwidth 100M\n"
        "queue bulk parent root bandwidth 30M\n"
        "queue web parent root bandwidth 40M\n"
        "queue ssh parent root bandwidth 20M default\n"
        "queue voip parent root bandwidth 10M min 5M\n"
        "\n"
        "# Queue assignment\n"
        "match out on $ext_if proto tcp to port { 80, 443 } set queue web\n"
        "match out on $ext_if proto tcp to port 22 set queue ssh\n"
        "match out on $ext_if proto udp to port { 5060, 10000:20000 } set queue voip\n"
    ), "QoS configuration");

    /* IPv6 configuration */
    ASSERT(test_pf_syntax(
        "ext_if = \"em0\"\n"
        "\n"
        "# Block by default\n"
        "block all\n"
        "\n"
        "# Allow IPv6 essential ICMP\n"
        "pass inet6 proto icmp6 icmp6-type { echoreq, echorep }\n"
        "pass inet6 proto icmp6 icmp6-type { routersol, routeradv, neighbrsol, neighbradv }\n"
        "\n"
        "# Allow outbound\n"
        "pass out inet6\n"
    ), "IPv6 configuration");

    /* SSH protection config */
    ASSERT(test_pf_syntax(
        "table <bruteforce> persist\n"
        "\n"
        "block in quick from <bruteforce>\n"
        "\n"
        "pass in on em0 proto tcp to port 22 keep state \\\n"
        "    (max-src-conn 5, max-src-conn-rate 3/60, \\\n"
        "     overload <bruteforce> flush global)\n"
    ), "SSH brute force protection");

    /* DMZ configuration */
    ASSERT(test_pf_syntax(
        "ext_if = \"em0\"\n"
        "int_if = \"em1\"\n"
        "dmz_if = \"em2\"\n"
        "webserver = \"192.168.2.10\"\n"
        "\n"
        "# NAT internal to external\n"
        "match out on $ext_if from ($int_if:network) nat-to ($ext_if)\n"
        "\n"
        "# Redirect web to DMZ\n"
        "match in on $ext_if proto tcp to port 80 rdr-to $webserver\n"
        "\n"
        "# Block all by default\n"
        "block all\n"
        "\n"
        "# Allow DMZ web server\n"
        "pass in on $ext_if proto tcp to $webserver port 80\n"
        "pass out on $dmz_if proto tcp to $webserver port 80\n"
        "\n"
        "# Allow internal to DMZ\n"
        "pass in on $int_if from ($int_if:network) to ($dmz_if:network)\n"
    ), "DMZ configuration");
}

/*
 * ============================================
 * SPECIAL SYNTAX AND EDGE CASES
 * ============================================
 */
static void
test_special_syntax(void)
{
    TEST_SECTION("SPECIAL SYNTAX AND EDGE CASES");

    /* Comments */
    ASSERT(test_pf_syntax("# This is a comment\npass all\n"), "comment syntax");

    /* Line continuation */
    ASSERT(test_pf_syntax(
        "pass in on em0 proto tcp \\\n"
        "    from any to any port 22\n"
    ), "line continuation");

    /* Multiple rules on same line (invalid - should fail) */
    /* This would be a negative test case */

    /* Empty config */
    ASSERT(test_pf_syntax(""), "empty config");

    /* Only comments */
    ASSERT(test_pf_syntax("# comment only\n"), "comment only config");

    /* Whitespace handling */
    ASSERT(test_pf_syntax("    pass all    \n"), "whitespace handling");

    /* Case sensitivity */
    ASSERT(test_pf_syntax("pass on Em0\n"), "interface case");

    /* IPv4 address formats */
    ASSERT(test_pf_syntax("pass from 10.0.0.1\n"), "IPv4 host");
    ASSERT(test_pf_syntax("pass from 10.0.0.0/8\n"), "IPv4 CIDR");
    ASSERT(test_pf_syntax("pass from 10.0.0.0/255.0.0.0\n"), "IPv4 netmask");

    /* IPv6 address formats */
    ASSERT(test_pf_syntax("pass from ::1\n"), "IPv6 loopback");
    ASSERT(test_pf_syntax("pass from 2001:db8::/32\n"), "IPv6 CIDR");
    ASSERT(test_pf_syntax("pass from fe80::/10\n"), "IPv6 link-local");
    ASSERT(test_pf_syntax("pass from ::ffff:192.168.1.1\n"), "IPv4-mapped IPv6");

    /* Port ranges */
    ASSERT(test_pf_syntax("pass proto tcp to port 1:1024\n"), "port range colon");
    ASSERT(test_pf_syntax("pass proto tcp to port 1024:65535\n"), "port range high");
    ASSERT(test_pf_syntax("pass proto tcp to port >1024\n"), "port greater than");
    ASSERT(test_pf_syntax("pass proto tcp to port <1024\n"), "port less than");
    ASSERT(test_pf_syntax("pass proto tcp to port !=22\n"), "port not equal");

    /* Service names */
    ASSERT(test_pf_syntax("pass proto tcp to port ssh\n"), "port name ssh");
    ASSERT(test_pf_syntax("pass proto tcp to port http\n"), "port name http");
    ASSERT(test_pf_syntax("pass proto tcp to port https\n"), "port name https");
    ASSERT(test_pf_syntax("pass proto udp to port domain\n"), "port name domain");

    /* Protocol numbers */
    ASSERT(test_pf_syntax("pass proto 6\n"), "protocol by number (TCP)");
    ASSERT(test_pf_syntax("pass proto 17\n"), "protocol by number (UDP)");

    /* Interface groups */
    ASSERT(test_pf_syntax("pass on egress\n"), "egress interface group");

    /* Self keyword */
    ASSERT(test_pf_syntax("pass from self\n"), "self keyword");

    /* Any keyword */
    ASSERT(test_pf_syntax("pass from any to any\n"), "any keyword");

    /* No-route */
    ASSERT(test_pf_syntax("pass from no-route\n"), "no-route keyword");

    /* Urpf-failed */
    ASSERT(test_pf_syntax("pass from urpf-failed\n"), "urpf-failed keyword");
}

/*
 * ============================================
 * MAIN
 * ============================================
 */
int
main(void)
{
    printf("=============================================\n");
    printf("pf.conf Comprehensive Syntax Tests\n");
    printf("=============================================\n");
    printf("Testing all features from pf.conf(5) man page\n");

    setup();

    /* Check if pfctl is available */
    if (system("which pfctl >/dev/null 2>&1") != 0) {
        printf("\nWARNING: pfctl not found. Tests require OpenBSD or FreeBSD.\n");
        printf("Tests will be skipped.\n");
        teardown();
        return 0;
    }

    /* Check if running as root (needed for pfctl) */
    if (geteuid() != 0) {
        printf("\nWARNING: Not running as root. Some tests may fail.\n");
    }

    /* Run all tests */
    test_macros();
    test_tables();
    test_options();
    test_timeouts();
    test_limits();
    test_queues();
    test_pass_rules();
    test_block_rules();
    test_match_rules();
    test_nat_rules();
    test_rdr_rules();
    test_binat_rules();
    test_antispoof();
    test_anchors();
    test_scrub();
    test_complex_configs();
    test_special_syntax();

    teardown();

    printf("\n=============================================\n");
    printf("RESULTS\n");
    printf("=============================================\n");
    printf("Passed:  %d\n", tests_passed);
    printf("Failed:  %d\n", tests_failed);
    printf("Skipped: %d\n", tests_skipped);
    printf("Total:   %d\n", tests_passed + tests_failed + tests_skipped);
    printf("=============================================\n");

    return tests_failed > 0 ? 1 : 0;
}
