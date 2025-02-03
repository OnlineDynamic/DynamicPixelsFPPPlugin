#!/bin/bash

# fpp-plugin-dynamicpixels install script

BASEDIR=$(dirname $0)
cd $BASEDIR
cd ..

make "SRCDIR=${SRCDIR}"


. ${FPPDIR}/scripts/common

# Add required Apache CSP (Content-Security-Policy allowed domains
${FPPDIR}/scripts/ManageApacheContentPolicy.sh add connect-src https://dynamicpixels.co.uk
${FPPDIR}/scripts/ManageApacheContentPolicy.sh add img-src https://dynamicpixels.co.uk

# install jq utility for PSU Control script to work
sudo apt-get -y install jq

# Execute script to fix mac address to be static

# Need to force reboot for CSP change to take affect
setSetting rebootFlag 1

