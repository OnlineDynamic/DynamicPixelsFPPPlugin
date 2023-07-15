#include "fpp-pch.h"

#include <fstream>
#include <sstream>
#include <string>
#include <algorithm>
#include <cstring>

#include <istream>
#include <ostream>

#include <iostream>

#include <thread>

#include <vector>

#include <unistd.h>
#include <termios.h>
#include <chrono>
#include <thread>
#include <cmath>

#include <httpserver.hpp>
#include "common.h"
#include "settings.h"
#include "Plugin.h"
#include "Plugins.h"
#include "log.h"
#include "MultiSync.h"

#if __has_include("channeloutput/ChannelOutputSetup.h")
#include "channeloutput/ChannelOutputSetup.h"
#elif __has_include("channeloutput/channeloutput.h")
#include "channeloutput/channeloutput.h"
#endif

#include "fppversion_defines.h"

#include "commands/Commands.h"

#include "TPLinkSwitch.h"
#include "TPLinkItem.h"

class DynamicPixelsPlugin : public FPPPlugin, public httpserver::http_resource
{
private:
    std::vector<std::unique_ptr<TPLinkItem>> _TPLinkOutputs;
    Json::Value config;

public:
    DynamicPixelsPlugin() : FPPPlugin("fpp-plugin-dynamicpixels")
    {
        LogInfo(VB_PLUGIN, "Initializing Dynamic Pixels Plugin\n");
        readFiles();
        registerCommand();
    }
    virtual ~DynamicPixelsPlugin()
    {
        _TPLinkOutputs.clear();
    }

    class DynamicPixelsPSUCommand : public Command
    {
    public:
        DynamicPixelsPSUCommand(DynamicPixelsPlugin *p) : Command("Dynamic Pixels PSU Control"), plugin(p)
        {
            args.push_back(CommandArg("PSU", "int", "Set PSU Number").setRange(0, 255).setDefaultValue("0"));
            args.push_back(CommandArg("state", "bool", "Set PSU On or Off").setDefaultValue("true"));
        }

        virtual std::unique_ptr<Command::Result> run(const std::vector<std::string> &args) override
        {
            bool psuOn = true;
            int psu_num = 0;
            if (args.size() >= 3)
            {
                psu_num = std::stoi(args[0]);
            }
            if (args.size() >= 2)
            {
                psuOn = args[1] == "true";
            }

            plugin->SetSwitchState(psu_num, psuOn);
            return std::make_unique<Command::Result>("Dynamic Pixels PSU Set");
        }
        DynamicPixelsPlugin *plugin;
    };

    void registerCommand()
    {
        CommandManager::INSTANCE.addCommand(new DynamicPixelsPSUCommand(this));
    }

    virtual const std::shared_ptr<httpserver::http_response> render_GET(const httpserver::http_request &req) override
    {
        std::string v = getTopics();
        return std::shared_ptr<httpserver::http_response>(new httpserver::string_response(v, 200));
    }

#if FPP_MAJOR_VERSION < 4 || FPP_MINOR_VERSION < 1
    virtual void modifyChannelData(int ms, uint8_t *seqData) override{
#else
    virtual void modifySequenceData(int ms, uint8_t *seqData) override
    {
#endif
        try {
            sendChannelData(seqData);
} catch (std::exception const &ex)
{
    std::cout << ex.what();
}
}

virtual void playlistCallback(const Json::Value &playlist, const std::string &action, const std::string &section, int item)
{
    if (settings["Start"] == "PlaylistStart" && action == "start")
    {
        EnableTPLinkItems();
    }
}

void EnableTPLinkItems()
{
    for (auto &output : _TPLinkOutputs)
    {
        output->EnableOutput();
    }
}

void sendChannelData(unsigned char *data)
{
    for (auto &output : _TPLinkOutputs)
    {
        output->SendData(data);
    }
}

void saveDataToFile()
{
    std::ofstream outfile;
#if FPP_MAJOR_VERSION < 6
    outfile.open("/home/fpp/media/config/fpp-plugin-dynamicpixels");
#else
        outfile.open(FPP_DIR_CONFIG("/fpp-plugin-dynamicpixels"));
#endif

    if (_TPLinkOutputs.size() == 0)
    {
        outfile << "nooutputsfound;1;null";
        outfile << "\n";
    }

    for (auto &out : _TPLinkOutputs)
    {
        outfile << out->GetIPAddress();
        outfile << ";";
        outfile << out->GetStartChannel();
        outfile << ";";
        outfile << out->GetType();
        outfile << "\n";
    }
    outfile.close();
}

void readFiles()
{
    // read topic, payload and start channel settings from JSON setting file.
#if FPP_MAJOR_VERSION < 6
    std::string configLocation = ("/home/fpp/media/config/plugin.tplink.json");
#else
        std::string configLocation = FPP_DIR_CONFIG("/plugin.tplink.json");
#endif
    if (LoadJsonFromFile(configLocation, config))
    {
        for (unsigned int i = 0; i < config.size(); i++)
        {
            std::string const ip = config[i]["ip"].asString();
            std::string const devicetype = config[i].get("devicetype", "light").asString();
            unsigned int sc = config[i].get("startchannel", 1).asInt();
            if (!ip.empty())
            {
                std::unique_ptr<TPLinkItem> tplinkItem;
                if (devicetype.find("light") != std::string::npos)
                {
                    tplinkItem = std::make_unique<TPLinkLight>(ip, sc);
                }
                else if (devicetype.find("switch") != std::string::npos)
                {
                    int const plugNum = config[i].get("plugnumber", 0).asInt();
                    tplinkItem = std::make_unique<TPLinkSwitch>(ip, sc, plugNum);
                }
                else
                {
                    LogInfo(VB_PLUGIN, "Devicetype not found '%s'", devicetype.c_str());
                    tplinkItem = std::make_unique<TPLinkLight>(ip, sc);
                }
                LogInfo(VB_PLUGIN, "Added %s\n", tplinkItem->GetConfigString().c_str());
                _TPLinkOutputs.push_back(std::move(tplinkItem));
            }
        }
    }
    saveDataToFile();
}

std::string getTopics()
{
    std::string topics;
    for (auto &out : _TPLinkOutputs)
    {
        topics += out->GetIPAddress();
        topics += ",";
    }
    return topics;
}

void SetSwitchState(std::string const &ip, bool state, int plug_num)
{
    TPLinkSwitch tplinkSwitch(ip, 1, plug_num);
    if (state)
    {
        tplinkSwitch.setRelayOn();
    }
    else
    {
        tplinkSwitch.setRelayOff();
    }
}

void SetLightOnRGB(std::string const &ip, uint8_t r, uint8_t g, uint8_t b, int color_temp, int period)
{
    TPLinkLight tplinkLight(ip, 1);
    tplinkLight.setLightOnRGB(r, g, b, color_temp, period);
}

void SetLightOnHSV(std::string const &ip, int hue, int sat, int bright, int color_temp, int period)
{
    TPLinkLight tplinkLight(ip, 1);
    tplinkLight.setLightOnHSV(hue, sat, bright, color_temp, period);
}

void SetLightOff(std::string const &ip)
{
    TPLinkLight tplinkLight(ip, 1);
    tplinkLight.setLightOff();
}
};

extern "C"
{
    FPPPlugin *createPlugin()
    {
        return new DynamicPixelsPlugin();
    }
}