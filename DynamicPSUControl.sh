#!/bin/sh
###########################################################
# DynamicPSUControl
###########################################################

while getopts p:a:c:v: flag
do
    case "${flag}" in
         p) psuNum=${OPTARG};;
         a) action=${OPTARG};;
         c) cape=${OPTARG};;
         v) variant=${OPTARG};;
    esac
done


json=$(cat PSU_config.json)

#GPIO=$(echo "$json" | jq -r '.Controllers[]| select(.Controller=="D12")| select(.Variants[].variant=="0.1")| .Variants[].controlpins[] | select(.psu=="2").gpionum')

GPIO=$(echo "$json" | jq -r '.Controllers[]| select(.Controller=="'"$cape"'")| select(.Variants[].variant=="'"$variant"'")| .Variants[].controlpins[] | select(.psu=="'"$psuNum"'").gpionum')

echo "GPIO: $GPIO"


# Set the GPIO number (NOT the pin number) based on the PSU number and cape model

#GPIO="63"


# Make sure the pin is setup for output.
/opt/fpp/src/fpp -G ${GPIO},Output

# Turn on/off the pin
/opt/fpp/src/fpp -g ${GPIO},Output,${action}
