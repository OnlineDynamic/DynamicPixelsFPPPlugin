#!/bin/bash

# fpp-plugin-dynamicpixels install script

BASEDIR=$(dirname $0)
cd $BASEDIR
cd ..

make "SRCDIR=${SRCDIR}"


. ${FPPDIR}/scripts/common

# install jq utility for PSU Control script to work
sudo apt-get install jq

# Execute script to fix mac address to be static

setSetting restartFlag 1

