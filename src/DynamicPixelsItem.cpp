#include "DynamicPixelsItem.h"

#include <stdlib.h>
#include <cstdint>
#include <thread>
#include <cmath>


#include <netinet/tcp.h>
#include <stdint.h>     // for uint16_t, uint8_t, uint32_t
#include <stdio.h>      // for printf
#include <unistd.h>     // for close, read
#include <string>       // for string
#include <cstring>      // for ??? memcpy, memset, strncpy


#include <iostream>
#include <istream>
#include <ostream>



DynamicPixelsItem::DynamicPixelsItem(std::string const& itemname) :
    m_name(itemname)
{
    const PinCapabilities& pin = PinCapabilities::getPinByName(itemname);
    m_GPIONumber = pin.gpio;
    LogInfo(VB_PLUGIN, "%d is gpio number: %d",itemname, m_GPIONumber);
}

DynamicPixelsItem::~DynamicPixelsItem() {

}


