#!/usr/bin/env python

import time
import os
import argparse   
import sys
import json
import subprocess

class Logger(object):
    def __init__(self, filename="/home/fpp/media/logs/fpp-plugin-dynamicpixels.log"):
        self.terminal = sys.stdout
        self.log = open(filename, "a")

    def write(self, message):
        self.terminal.write(message)
        self.log.write(message)

sys.stdout = Logger("/home/fpp/media/logs/fpp-plugin-dynamicpixels.log")

parser = argparse.ArgumentParser(description='dynamicpixels Plugin')
parser.add_argument('-l','--list', help='Plugin Actions',action='store_true')
args = parser.parse_args()

if args.list:
   #Tell the plugin that we should be registered for c++
   print("c++")