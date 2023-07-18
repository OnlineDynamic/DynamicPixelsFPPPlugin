#include "DynamicPixelsPSUSwitch.h"

#include "fpp-pch.h"
#include "common.h"
#include "settings.h"

#include <stdlib.h>
#include <cstdint>
#include <thread>
#include <cmath>

#include <stdint.h> // for uint16_t, uint8_t, uint32_t
#include <stdio.h>  // for printf
#include <unistd.h> // for close, read
#include <string>   // for string
#include <cstring>  // for ??? memcpy, memset, strncpy

#include <iostream>
#include <istream>
#include <ostream>

#include "util/GPIOUtils.h"

DynamicPixelsPSUSwitch::DynamicPixelsPSUSwitch(std::string const& itemname, int psu_num) : DynamicPixelsItem(itemname),
                                                              m_psu_num(psu_num)
{
   // m_deviceId = getDeviceId(psu_num);
}

DynamicPixelsPSUSwitch::~DynamicPixelsPSUSwitch()
{
}

std::string DynamicPixelsPSUSwitch::setPSUOn()
{
    // set PSU On
}

std::string DynamicPixelsPSUSwitch::setPSUOff()
{
    // set PSU Off
}
