include /opt/fpp/src/makefiles/common/setup.mk
include /opt/fpp/src/makefiles/platform/*.mk

all: libfpp-plugin-dynamicpixels.so
debug: all

OBJECTS_fpp_dynamicpixels_so += src/DynamicPixelsPlugin.o
LIBS_fpp_dynamicpixels_so += -L/opt/fpp/src -lfpp -ljsoncpp -lhttpserver
CXXFLAGS_src/dynamicpixelsPlugin.o += -I/opt/fpp/src

%.o: %.cpp Makefile
	$(CCACHE) $(CC) $(CFLAGS) $(CXXFLAGS) $(CXXFLAGS_$@) -c $< -o $@

libfpp-plugin-dynamicpixels.so: $(OBJECTS_fpp_dynamicpixels_so) /opt/fpp/src/libfpp.so
	$(CCACHE) $(CC) -shared $(CFLAGS_$@) $(OBJECTS_fpp_dynamicpixels_so) $(LIBS_fpp_dynamicpixels_so) $(LDFLAGS) -o $@

clean:
	rm -f libfpp-plugin-dynamicpixels.so $(OBJECTS_fpp_dynamicpixels_so)