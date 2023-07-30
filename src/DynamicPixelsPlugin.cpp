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

class DynamicPixelsPlugin : public FPPPlugins::Plugin, public httpserver::http_resource
{
private:
      std::vector<std::unique_ptr <DynamicPixelsItem>> _DynamicPixelsOutputs;
      Json::Value config;

public:
    DynamicPixelsPlugin() : FPPPlugins::Plugin("fpp-plugin-dynamicpixels")
    {
        LogInfo(VB_PLUGIN, "Initializing Dynamic Pixels Plugin\n");
        configLocation = FPP_DIR_CONFIG("/plugin-fpp-dynamicpixels.json");
        if (FileExists(configLocation)) {
            Json::Value root;
            if (LoadJsonFromFile(configLocation, root)) {
                if (root.isMember("brightness")) {
                  //  startBrightness = root["brightness"].asInt();
                }
            }
        }
        //setBrightness(startBrightness, false);
        registerCommand();
    }
    virtual ~DynamicPixelsPlugin()
    {
       // _DynamicPixelsOutputs.clear();
    }

    class DynamicPixelsPSUSwitchCommand : public Command
    {
    public:
        DynamicPixelsPSUSwitchCommand(DynamicPixelsPlugin *p) : Command("Dynamic Pixels PSU Control"), plugin(p)
        {
            args.push_back(CommandArg("PSU", "int", "Set PSU Number").setRange(1, 4).setDefaultValue("1"));
            args.push_back(CommandArg("state", "bool", "Set PSU On or Off").setDefaultValue("true"));
        }

        virtual std::unique_ptr<Command::Result> run(const std::vector<std::string> &args) override {
            std::string ipAddress = "";
            if (args.size() >= 1) {
              ipAddress = args[0];
            }

            //plugin->SetLightOff(ipAddress);
            return std::make_unique<Command::Result>("Dynamic Pixels PSU Control Set");
        }
     
        DynamicPixelsPlugin *plugin;
    };

    void registerCommand()
    {
        CommandManager::INSTANCE.addCommand(new DynamicPixelsPSUSwitchCommand(this));
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

        std::string configLocation;
};

extern "C"
{
    FPPPlugins::Plugin *createPlugin()
    {
        return new DynamicPixelsPlugin();
    }
}