# NSsh Tests Makefile
# Integrated with NSWall test framework

CC=		cc
CFLAGS=		-O2 -Wall -I..

TESTS=		test_ctl
LOGDIR=		/tmp/nssh_test_results
TIMESTAMP!=	date +%Y%m%d_%H%M%S

all: ${TESTS}

test_ctl: test_ctl.c
	${CC} ${CFLAGS} -o $@ $<

test: ${TESTS}
	@mkdir -p ${LOGDIR}
	@echo "================================"
	@echo "NSsh Test Suite - $(shell date)"
	@echo "================================"
	@echo ""
	@echo "[1/3] Running C unit tests..."
	@./test_ctl 2>&1 | tee ${LOGDIR}/unit_tests_${TIMESTAMP}.log
	@echo ""
	@echo "[2/3] Running shell integration tests..."
	@sh ./test_ctl_crud.sh 2>&1 | tee ${LOGDIR}/integration_tests_${TIMESTAMP}.log
	@echo ""
	@echo "[3/3] Running full daemon CRUD tests..."
	@sh ./test_daemon_crud.sh 2>&1 | tee ${LOGDIR}/daemon_crud_${TIMESTAMP}.log
	@echo ""
	@echo "================================"
	@echo "Test logs saved to: ${LOGDIR}"
	@echo "================================"

test-verbose: ${TESTS}
	@echo "Running unit tests with verbose output..."
	./test_ctl
	@echo ""
	@echo "Running integration tests with verbose output..."
	sh -x ./test_ctl_crud.sh

test-report: test
	@echo ""
	@echo "=== Test Summary Report ==="
	@grep -E "^(PASS|FAIL|Results):" ${LOGDIR}/*.log 2>/dev/null || echo "No test logs found"

clean:
	rm -f ${TESTS} *.o
	rm -rf ${LOGDIR}

.PHONY: all test test-verbose test-report clean
