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
    std::vector<std::unique_ptr<DynamicPixelsItem>> _DynamicPixelsOutputs;
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

        plugin->SetPSUState(psu_num, psuOn);
        return std::make_unique<Command::Result>("Dynamic Pixels PSU Set");
    }
    DynamicPixelsPlugin *plugin;
};

void registerCommand()
{
    CommandManager::INSTANCE.addCommand(new DynamicPixelsPSUCommand(this));
}

// virtual const std::shared_ptr<httpserver::http_response> render_GET(const httpserver::http_request &req) override
// {
//     std::string v = getTopics();
//     return std::shared_ptr<httpserver::http_response>(new httpserver::string_response(v, 200));
// }

// virtual void modifySequenceData(int ms, uint8_t *seqData) override
// {
//     try
//     {
//         sendChannelData(seqData);
//     }
//     catch (std::exception const &ex)
//     {
//         std::cout << ex.what();
//     }
// }

// void EnableDynamicPixelsItems()
// {
//     for (auto &output : _DynamicPixelsOutputs)
//     {
//         output->EnableOutput();
//     }
// }

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

void SetPSUState(int psu_num, bool psuOn)
{
    DynamicPixelsPSUSwitch DynamicPixelsPSUSwitch(psu_num, psuOn);
    if (psuOn)
    {
        DynamicPixelsPSUSwitch.setPSUOn();
    }
    else
    {
        DynamicPixelsPSUSwitch.setPSUOff();
    }
}

extern "C"
{
    FPPPlugin *createPlugin()
    {
        return new DynamicPixelsPlugin();
    }
}