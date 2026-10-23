#!/bin/bash

# fpp-plugin-dynamicpixels install script

BASEDIR=$(dirname $0)
cd $BASEDIR
cd ..

# Older versions of this plugin built a C++ brightness library which is no
# longer part of the plugin, remove any stale copy so fppd doesn't load it
rm -f libfpp-plugin-DynamicPixels.so src/*.o src/*.d


. ${FPPDIR}/scripts/common

# Add required Apache CSP (Content-Security-Policy allowed domains
${FPPDIR}/scripts/ManageApacheContentPolicy.sh add connect-src https://dynamicpixels.co.uk
${FPPDIR}/scripts/ManageApacheContentPolicy.sh add img-src https://dynamicpixels.co.uk
${FPPDIR}/scripts/ManageApacheContentPolicy.sh add connect-src https://www.dynamicpixels.co.uk
${FPPDIR}/scripts/ManageApacheContentPolicy.sh add img-src https://www.dynamicpixels.co.uk

# install jq utility for PSU Control script to work
sudo apt-get -y install jq

# Execute script to fix mac address to be static

# Need to force reboot for CSP change to take affect
setSetting rebootFlag 1

