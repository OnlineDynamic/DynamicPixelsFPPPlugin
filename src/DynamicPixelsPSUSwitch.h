#pragma once

#include "DynamicPixelsItem.h"

#include <string>

class DynamicPixelsPSUSwitch : public DynamicPixelsItem{
public:
    DynamicPixelsPSUSwitch(std::string const& ip, unsigned int startChannel, int plug_num );
    virtual ~DynamicPixelsPSUSwitch();

    bool SendData( unsigned char *data) override;

    std::string setPSUOn();
    std::string setPSUOff();

    std::string GetType() const override {return "Switch";}
    std::string GetConfigString() const override;

private:
    uint8_t m_w;
    int m_plug_num;
    std::string m_deviceId;

    void outputData( uint8_t w );
    std::string getDeviceId(int plug_num);
    std::string appendPlugData(std::string cmd);
};