#pragma once

#include "DynamicPixelsItem.h"

#include <string>

class DynamicPixelsPSUSwitch : public DynamicPixelsItem{
public:
    DynamicPixelsPSUSwitch(int psu_num );
    virtual ~DynamicPixelsPSUSwitch();

    std::string setPSUOn();
    std::string setPSUOff();

    std::string GetType() const override {return "PSU Switch";}

private:
    int m_psu_num;
    std::string m_deviceId;

};