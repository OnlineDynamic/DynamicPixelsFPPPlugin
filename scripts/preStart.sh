#!/bin/sh

echo "Running fpp-plugin-dynamicpixels PreStart Script"

./mac_address_fix.sh

BASEDIR=$(dirname $0)
cd $BASEDIR
cd ..
make "SRCDIR=${SRCDIR}"

