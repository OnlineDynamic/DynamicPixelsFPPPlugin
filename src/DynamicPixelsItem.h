#pragma once

#include <stdlib.h>
#include <list>
#include <string>
#include <atomic>

#include <jsoncpp/json/json.h>

// #include "common.h"
#include "log.h"
#include "util/GPIOUtils.h"

class DynamicPixelsItem
{
public:
    DynamicPixelsItem(std::string const& itemname);

    virtual ~DynamicPixelsItem();

    unsigned int GetGPIONumber() const { return m_GPIONumber; }

    std::string m_name;

    virtual std::string GetType() const = 0;
  //  virtual std::string GetConfigString() const = 0;

protected:
    unsigned int m_GPIONumber;

 //   std::atomic<bool> m_unreachable;
 //   std::atomic<bool> m_issending;

private:
    // private members
};