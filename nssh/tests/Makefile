# NSsh Tests Makefile

CC=		cc
CFLAGS=		-O2 -Wall -I..

TESTS=		test_ctl

all: ${TESTS}

test_ctl: test_ctl.c
	${CC} ${CFLAGS} -o $@ $<

test: ${TESTS}
	@echo "Running unit tests..."
	@./test_ctl
	@echo ""
	@echo "Running shell tests..."
	@sh ./test_ctl_crud.sh

clean:
	rm -f ${TESTS} *.o

.PHONY: all test clean
