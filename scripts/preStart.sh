#!/bin/sh

echo "Running fpp-plugin-dynamicpixels PreStart Script"

BASEDIR=$(dirname $0)
cd $BASEDIR
cd ..

# Older versions of this plugin built a C++ brightness library which is no
# longer part of the plugin, remove any stale copy so fppd doesn't load it
rm -f libfpp-plugin-DynamicPixels.so src/*.o src/*.d
