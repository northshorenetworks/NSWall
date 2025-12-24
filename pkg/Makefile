# $OpenBSD$

COMMENT =	network shell and appliance framework for OpenBSD

V =		2.0.0
DISTNAME =	nswall-${V}

CATEGORIES =	net sysutils

HOMEPAGE =	https://github.com/northshorenetworks/NSWall

MAINTAINER =	NSWall Developers <dev@example.com>

# ISC
PERMIT_PACKAGE =	Yes

WANTLIB +=	c edit termcap

# No fetch - we build from local source
# In a real port, this would fetch from GitHub
MASTER_SITES =	# local build

# Build dependencies
BUILD_DEPENDS =	# none beyond base

# Runtime dependencies
RUN_DEPENDS =	# all in base: pf, bgpd, ospfd, etc.

NO_TEST =	Yes

# Use OpenBSD make
USE_GMAKE =	No

# Our source is in the parent directory
WRKSRC =	${WRKDIR}/nswall-${V}

# Build the nssh shell
do-build:
	cd ${WRKSRC}/nssh && \
		${MAKE_PROGRAM} CFLAGS="${CFLAGS}" clean && \
		sh misc-precompile.sh && \
		sh compile.sh && \
		${MAKE_PROGRAM} CFLAGS="${CFLAGS}"

do-install:
	${INSTALL_PROGRAM} ${WRKSRC}/nssh/nsh \
		${PREFIX}/bin/nswall
	${INSTALL_DATA_DIR} ${PREFIX}/share/nswall
	${INSTALL_DATA_DIR} ${PREFIX}/share/nswall/pf
	${INSTALL_DATA_DIR} ${PREFIX}/share/examples/nswall
	${INSTALL_SCRIPT} ${WRKSRC}/nssh/pf/pf.subr \
		${PREFIX}/share/nswall/pf/
	${INSTALL_SCRIPT} ${WRKSRC}/nssh/pf/*.sh \
		${PREFIX}/share/nswall/pf/
	${INSTALL_SCRIPT} ${WRKSRC}/nssh/tcpdump/nstcpd \
		${PREFIX}/bin/
	${INSTALL_SCRIPT} ${WRKSRC}/nssh/save-rw.sh \
		${PREFIX}/bin/nswall-save
	${INSTALL_DATA} ${FILESDIR}/nswall.conf.sample \
		${PREFIX}/share/examples/nswall/
	${INSTALL_DATA_DIR} ${SYSCONFDIR}/nswall
	@if [ ! -f ${SYSCONFDIR}/nswall/nswall.conf ]; then \
		${INSTALL_DATA} ${FILESDIR}/nswall.conf.sample \
			${SYSCONFDIR}/nswall/nswall.conf; \
	fi

.include <bsd.port.mk>
