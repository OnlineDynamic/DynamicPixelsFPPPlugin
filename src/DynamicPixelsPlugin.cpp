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

#include "DynamicPixelsPSUSwitch.h"
#include "DynamicPixelsItem.h"

class DynamicPixelsPlugin : public FPPPlugin, public httpserver::http_resource
{
private:
    std::vector<std::unique_ptr <DynamicPixelsItem>> _DynamicPixelsOutputs;
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
        _DynamicPixelsOutputs.clear();
    }

    class DynamicPixelsPSUSwitchCommand : public Command
    {
    public:
        DynamicPixelsPSUSwitchCommand(DynamicPixelsPlugin *p) : Command("Dynamic Pixels PSU Control"), plugin(p)
        {
            args.push_back(CommandArg("PSU", "int", "Set PSU Number").setRange(0, 4).setDefaultValue("1"));
            args.push_back(CommandArg("state", "bool", "Set PSU On or Off").setDefaultValue("true"));
        }

        virtual std::unique_ptr<Command::Result> run(const std::vector<std::string> &args) override
        {
            bool psuOn = true;
            int psu_num = 1;
            if (args.size() >= 1)
            {
                psu_num = std::stoi(args[0]);
            }
            if (args.size() >= 2)
            {
                psuOn = args[1] == "true";
            }
            std::string itemname = "PSU1";
            plugin->SetPSUState(itemname,psu_num, psuOn);
            return std::make_unique<Command::Result>("Dynamic Pixels PSU Set");
        }
        DynamicPixelsPlugin *plugin;
    };

    void registerCommand()
    {
        CommandManager::INSTANCE.addCommand(new DynamicPixelsPSUSwitchCommand(this));
    }


    void readFiles()
    {
        // read topic, payload and start channel settings from JSON setting file.
        std::string configLocation = FPP_DIR_CONFIG("/plugin.dynamicpixels.json");
        if (LoadJsonFromFile(configLocation, config))
        {
            for (unsigned int i = 0; i < config.size(); i++)
            {
                std::string const ip = config[i]["ip"].asString();
                std::string const devicetype = config[i].get("devicetype", "light").asString();
                unsigned int sc = config[i].get("startchannel", 1).asInt();
                if (!ip.empty())
                {
                    std::unique_ptr<DynamicPixelsItem> dynamicpixelsItem;
                    if (devicetype.find("light") != std::string::npos)
                    {
                //        dynamicpixelsItem = std::make_unique<DynamicPixelsLight>(ip, sc);
                    }
                    else if (devicetype.find("switch") != std::string::npos)
                    {
                        int const plugNum = config[i].get("plugnumber", 0).asInt();
                        std::string itemname = "testing";
                        int psu_num =1;
                        dynamicpixelsItem = std::make_unique<DynamicPixelsPSUSwitch>(itemname, psu_num);
                    }
                    else
                    {
                        LogInfo(VB_PLUGIN, "Devicetype not found '%s'", devicetype.c_str());
                        //dynamicpixelsItem = std::make_unique<DynamicPixelsLight>(ip, sc);
                    }
                //    LogInfo(VB_PLUGIN, "Added %s\n", dynamicpixelsItem->GetConfigString().c_str());
                    _DynamicPixelsOutputs.push_back(std::move(dynamicpixelsItem));
                }
            }
        }
        saveDataToFile();
    }

    void saveDataToFile()
    {
        std::ofstream outfile;

        outfile.open(FPP_DIR_CONFIG("/fpp-plugin-dynamicpixels"));

        if (_DynamicPixelsOutputs.size() == 0)
        {
            outfile << "nooutputsfound;1;null";
            outfile << "\n";
        }

        for (auto &out : _DynamicPixelsOutputs)
        {
            // outfile << out->GetIPAddress();
            // outfile << ";";
            // outfile << out->GetStartChannel();
            // outfile << ";";
            // outfile << out->GetType();
            // outfile << "\n";
        }
        outfile.close();
    }

    void SetPSUState(std::string itemname, int psu_num, bool psuOn)
    {
        DynamicPixelsPSUSwitch dynamicpixelsPSUSwitch(itemname, psu_num);
        if (psuOn)
        {
            dynamicpixelsPSUSwitch.setPSUOn();
        }
        else
        {
            dynamicpixelsPSUSwitch.setPSUOff();
        }
    }
};

extern "C"
{
    FPPPlugin *createPlugin()
    {
        return new DynamicPixelsPlugin();
    }
}