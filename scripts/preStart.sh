#!/bin/sh

echo "Running fpp-plugin-dynamicpixels PreStart Script"

BASEDIR=$(dirname $0)
cd $BASEDIR
cd ..
make "SRCDIR=${SRCDIR}"

