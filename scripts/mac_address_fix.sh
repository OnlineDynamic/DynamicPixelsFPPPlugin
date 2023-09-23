#!/bin/sh
magic="/etc/systemd/network/00-usb_ethernet-mac-fix.link"
RPIoui="b8:27:eb"

if [ ! -f $magic ]; then
    mac=$(awk '/Serial/ {print $3}' /proc/cpuinfo | sed 's/^.*\(..\)\(..\)\(..\)$/\1:\2:\3/')
    # or you can get the same info from OTP
    # mac=$(vcgencmd otp_dump | grep "^28:" | sed -e 's/^.*\(..\)\(..\)\(..\)$/\1:\2:\3/')
    cat > $magic <<EOF
[Match]
Property=ID_MODEL_ID=8152

[Link]
MACAddress=RPIoui:$mac
EOF

sudo update-initramfs -k all -u

fi
