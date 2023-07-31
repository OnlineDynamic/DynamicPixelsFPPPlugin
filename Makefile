SRCDIR ?= /opt/fpp/src
include ${SRCDIR}/makefiles/common/setup.mk
include $(SRCDIR)/makefiles/platform/*.mk

all: libfpp-plugin-DynamicPixels.$(SHLIB_EXT)
debug: all

OBJECTS_fpp_DynamicPixels_so += src/FPPDynamicPixels.o
LIBS_fpp_DynamicPixels_so += -L${SRCDIR} -lfpp -ljsoncpp -lhttpserver
CXXFLAGS_src/FPPDynamicPixels.o += -I${SRCDIR}

%.o: %.cpp Makefile
	$(CCACHE) $(CC) $(CFLAGS) $(CXXFLAGS) $(CXXFLAGS_$@) -c $< -o $@

libfpp-plugin-DynamicPixels.$(SHLIB_EXT): $(OBJECTS_fpp_DynamicPixels_so) ${SRCDIR}/libfpp.$(SHLIB_EXT)
	$(CCACHE) $(CC) -shared $(CFLAGS_$@) $(OBJECTS_fpp_DynamicPixels_so) $(LIBS_fpp_DynamicPixels_so) $(LDFLAGS) -o $@

clean:
	rm -f libfpp-dynamicpixels.so $(OBJECTS_fpp_DynamicPixels_so)