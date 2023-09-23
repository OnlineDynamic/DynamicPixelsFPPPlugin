#!/bin/bash

# fpp-plugin-dynamicpixels install script

BASEDIR=$(dirname $0)
cd $BASEDIR
cd ..

make "SRCDIR=${SRCDIR}"


. ${FPPDIR}/scripts/common

# Execute script to fix mac address to be static

setSetting restartFlag 1

