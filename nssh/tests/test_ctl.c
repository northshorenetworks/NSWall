/*
 * Unit tests for ctl.c CRUD functions
 * Compile with: cc -o test_ctl test_ctl.c -I..
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <sys/stat.h>
#include <errno.h>

#define TEST_DIR "/tmp/nssh_test"
#define TEST_CONF TEST_DIR "/test.conf"

static int tests_passed = 0;
static int tests_failed = 0;

#define ASSERT(cond, msg) do { \
    if (cond) { \
        printf("PASS: %s\n", msg); \
        tests_passed++; \
    } else { \
        printf("FAIL: %s\n", msg); \
        tests_failed++; \
    } \
} while(0)

/* Mock implementations of ctl.c functions for testing */

/* Write config content to file */
static int
write_config(const char *file, const char *content)
{
    FILE *f = fopen(file, "w");
    if (f == NULL)
        return -1;
    fprintf(f, "%s", content);
    fclose(f);
    chmod(file, 0600);
    return 0;
}

/* Read config file content */
static char *
read_config(const char *file)
{
    FILE *f;
    long size;
    char *buf;

    f = fopen(file, "r");
    if (f == NULL)
        return NULL;

    fseek(f, 0, SEEK_END);
    size = ftell(f);
    fseek(f, 0, SEEK_SET);

    buf = malloc(size + 1);
    if (buf == NULL) {
        fclose(f);
        return NULL;
    }

    fread(buf, 1, size, f);
    buf[size] = '\0';
    fclose(f);
    return buf;
}

/* Set config key to value (simulates ctl_set_config) */
static int
set_config(const char *file, const char *key, const char *value)
{
    FILE *f, *tmp;
    char tmpfile[256];
    char line[1024];
    int found = 0;

    snprintf(tmpfile, sizeof(tmpfile), "%s.tmp", file);

    f = fopen(file, "r");
    tmp = fopen(tmpfile, "w");
    if (tmp == NULL) {
        if (f) fclose(f);
        return -1;
    }

    if (f != NULL) {
        while (fgets(line, sizeof(line), f) != NULL) {
            if (strncmp(line, key, strlen(key)) == 0 &&
                (line[strlen(key)] == ' ' || line[strlen(key)] == '\t')) {
                found = 1;
                if (value)
                    fprintf(tmp, "%s %s\n", key, value);
                else
                    fprintf(tmp, "%s\n", key);
            } else {
                fprintf(tmp, "%s", line);
            }
        }
        fclose(f);
    }

    if (!found) {
        if (value)
            fprintf(tmp, "%s %s\n", key, value);
        else
            fprintf(tmp, "%s\n", key);
    }

    fclose(tmp);
    rename(tmpfile, file);
    chmod(file, 0600);
    return 0;
}

/* Remove config key (simulates ctl_unset_config) */
static int
unset_config(const char *file, const char *key)
{
    FILE *f, *tmp;
    char tmpfile[256];
    char line[1024];
    int removed = 0;

    snprintf(tmpfile, sizeof(tmpfile), "%s.tmp", file);

    f = fopen(file, "r");
    if (f == NULL)
        return -1;

    tmp = fopen(tmpfile, "w");
    if (tmp == NULL) {
        fclose(f);
        return -1;
    }

    while (fgets(line, sizeof(line), f) != NULL) {
        if (strncmp(line, key, strlen(key)) == 0 &&
            (line[strlen(key)] == ' ' || line[strlen(key)] == '\t' ||
             line[strlen(key)] == '\n')) {
            removed = 1;
            continue;
        }
        fprintf(tmp, "%s", line);
    }

    fclose(f);
    fclose(tmp);
    rename(tmpfile, file);
    chmod(file, 0600);
    return removed ? 0 : 1;
}

/* Append line to config (simulates ctl_append_config) */
static int
append_config(const char *file, const char *line)
{
    FILE *f = fopen(file, "a");
    if (f == NULL)
        return -1;
    fprintf(f, "%s\n", line);
    fclose(f);
    chmod(file, 0600);
    return 0;
}

/* Test setup/teardown */
static void
setup(void)
{
    mkdir(TEST_DIR, 0700);
    unlink(TEST_CONF);
}

static void
teardown(void)
{
    unlink(TEST_CONF);
    rmdir(TEST_DIR);
}

/* Test cases */

static void
test_write_config(void)
{
    int ret = write_config(TEST_CONF, "router-id 10.0.0.1\n");
    ASSERT(ret == 0, "write_config returns success");

    char *content = read_config(TEST_CONF);
    ASSERT(content != NULL, "read_config returns content");
    ASSERT(strstr(content, "router-id 10.0.0.1") != NULL,
           "write_config writes correct content");
    free(content);
}

static void
test_set_config_new_key(void)
{
    write_config(TEST_CONF, "");

    int ret = set_config(TEST_CONF, "router-id", "10.0.0.1");
    ASSERT(ret == 0, "set_config new key returns success");

    char *content = read_config(TEST_CONF);
    ASSERT(strstr(content, "router-id 10.0.0.1") != NULL,
           "set_config adds new key");
    free(content);
}

static void
test_set_config_replace(void)
{
    write_config(TEST_CONF, "router-id 0.0.0.0\n");

    int ret = set_config(TEST_CONF, "router-id", "10.0.0.1");
    ASSERT(ret == 0, "set_config replace returns success");

    char *content = read_config(TEST_CONF);
    ASSERT(strstr(content, "router-id 10.0.0.1") != NULL,
           "set_config replaces existing key");
    ASSERT(strstr(content, "router-id 0.0.0.0") == NULL,
           "set_config removes old value");
    free(content);
}

static void
test_unset_config(void)
{
    write_config(TEST_CONF, "router-id 10.0.0.1\nAS 65000\n");

    int ret = unset_config(TEST_CONF, "AS");
    ASSERT(ret == 0, "unset_config returns success");

    char *content = read_config(TEST_CONF);
    ASSERT(strstr(content, "AS 65000") == NULL,
           "unset_config removes key");
    ASSERT(strstr(content, "router-id 10.0.0.1") != NULL,
           "unset_config preserves other keys");
    free(content);
}

static void
test_unset_config_not_found(void)
{
    write_config(TEST_CONF, "router-id 10.0.0.1\n");

    int ret = unset_config(TEST_CONF, "nonexistent");
    ASSERT(ret == 1, "unset_config returns 1 for not found");
}

static void
test_append_config(void)
{
    write_config(TEST_CONF, "router-id 10.0.0.1\n");

    int ret = append_config(TEST_CONF, "area 0.0.0.0");
    ASSERT(ret == 0, "append_config returns success");

    char *content = read_config(TEST_CONF);
    ASSERT(strstr(content, "area 0.0.0.0") != NULL,
           "append_config adds line");
    free(content);
}

static void
test_config_permissions(void)
{
    write_config(TEST_CONF, "test\n");

    struct stat sb;
    stat(TEST_CONF, &sb);
    ASSERT((sb.st_mode & 0777) == 0600,
           "config file has 0600 permissions");
}

static void
test_empty_file_handling(void)
{
    write_config(TEST_CONF, "");

    int ret = set_config(TEST_CONF, "key", "value");
    ASSERT(ret == 0, "set_config works on empty file");

    char *content = read_config(TEST_CONF);
    ASSERT(strstr(content, "key value") != NULL,
           "set_config adds to empty file");
    free(content);
}

static void
test_multiple_operations(void)
{
    write_config(TEST_CONF, "");

    set_config(TEST_CONF, "router-id", "10.0.0.1");
    set_config(TEST_CONF, "AS", "65000");
    append_config(TEST_CONF, "network 10.0.0.0/8");
    unset_config(TEST_CONF, "AS");
    set_config(TEST_CONF, "router-id", "10.0.0.2");

    char *content = read_config(TEST_CONF);
    ASSERT(strstr(content, "router-id 10.0.0.2") != NULL,
           "multiple ops: router-id updated");
    ASSERT(strstr(content, "AS 65000") == NULL,
           "multiple ops: AS removed");
    ASSERT(strstr(content, "network 10.0.0.0/8") != NULL,
           "multiple ops: network preserved");
    free(content);
}

static void
test_special_characters(void)
{
    write_config(TEST_CONF, "");

    int ret = set_config(TEST_CONF, "option", "domain-name-servers 8.8.8.8;");
    ASSERT(ret == 0, "set_config handles special chars");

    char *content = read_config(TEST_CONF);
    ASSERT(strstr(content, "8.8.8.8;") != NULL,
           "special characters preserved");
    free(content);
}

int
main(void)
{
    printf("================================\n");
    printf("NSsh ctl.c Unit Tests\n");
    printf("================================\n\n");

    setup();

    test_write_config();
    test_set_config_new_key();
    test_set_config_replace();
    test_unset_config();
    test_unset_config_not_found();
    test_append_config();
    test_config_permissions();
    test_empty_file_handling();
    test_multiple_operations();
    test_special_characters();

    teardown();

    printf("\n================================\n");
    printf("Results: %d passed, %d failed\n", tests_passed, tests_failed);
    printf("================================\n");

    return tests_failed > 0 ? 1 : 0;
}
