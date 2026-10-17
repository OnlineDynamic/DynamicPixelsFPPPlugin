<?php
include_once("/opt/fpp/www/common.php");

/*
A way for plugins to provide their own PHP API endpoints.

To use, create a file called api.php file in the plugin's directory
and provide a getEndpointsPLUGINNAME() function which returns an
array describing the endpoints the plugin implements.  Since PHP
does not allow hyphens in function names, any hyphens in the plugin
name must be removed when substituting for PLUGINNAME above and if
the plugin name is used in any callback function names.  It is
also best to use unique endpoint names as shown below to eliminate
any conflicts with stock FPP code or other plugin API callbacks.

All endpoints are prefixed with /api/plugin/PLUGIN-NAME but only
the part after PLUGIN-NAME is specified in the getEndpointsPLUGINNAME()
data.  The plugin name is used as-is in the endpoint URL, hyphens
are not removed.  -- limonade.php is used for the underlying implementation so
param("param1" ) can be used for an api like /api/plugin/fpp-BigButtons/:param1

*/


function getEndpointsfpppluginDynamicPixels() {
    $result = array();

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'version',
        'callback' => 'fppDynamicPixelsVersion');
    array_push($result, $ep);

    // Simplified PSU Control Endpoints
    $ep = array(
        'method' => 'GET',
        'endpoint' => 'psu/status',
        'callback' => 'fppDynamicPixelsGetPSUStatus');
    array_push($result, $ep);

    // Support both GET and POST for "all" PSU control (must be before individual PSU routes)
    $ep = array(
        'method' => 'GET',
        'endpoint' => 'psu/all/:action',
        'callback' => 'fppDynamicPixelsControlAllPSUs');
    array_push($result, $ep);

    $ep = array(
        'method' => 'POST',
        'endpoint' => 'psu/all/:action',
        'callback' => 'fppDynamicPixelsControlAllPSUs');
    array_push($result, $ep);

    // Support both GET and POST for individual PSU control
    $ep = array(
        'method' => 'GET',
        'endpoint' => 'psu/:psu/:action',
        'callback' => 'fppDynamicPixelsControlPSU');
    array_push($result, $ep);

    $ep = array(
        'method' => 'POST',
        'endpoint' => 'psu/:psu/:action',
        'callback' => 'fppDynamicPixelsControlPSU');
    array_push($result, $ep);

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'controller/info',
        'callback' => 'fppDynamicPixelsGetControllerInfo');
    array_push($result, $ep);

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'network/discover',
        'callback' => 'fppDynamicPixelsNetworkDiscover');
    array_push($result, $ep);

    return $result;
}

// GET /api/plugin/fpp-DynamicPixels/version
function fppDynamicPixelsVersion() {
    $result = array();
    $result['version'] = 'fpp-plugin-DynamicPixels v2.1';

    return json($result);
}

// Helper function to load PSU configuration
function loadPSUConfig() {
    $configFile = dirname(__FILE__) . '/PSU_config.json';
    if (!file_exists($configFile)) {
        return null;
    }
    
    $configData = file_get_contents($configFile);
    return json_decode($configData, true);
}

// Helper to fetch a URL on this FPP instance, returns array(httpCode, body)
function dpLocalRequest($url, $method = 'GET', $body = null, $timeout = 2) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    return array($httpCode, $response, $error);
}

// Helper to read the installed cape name/version
function readCapeInfo() {
    $capeInfoFile = '/home/fpp/media/tmp/cape-info.json';
    if (file_exists($capeInfoFile)) {
        $capeInfo = json_decode(file_get_contents($capeInfoFile), true);
        if (is_array($capeInfo) && isset($capeInfo['name'])) {
            return $capeInfo;
        }
    }
    list($code, $response) = dpLocalRequest("http://localhost/api/cape");
    if ($code === 200 && $response) {
        $capeInfo = json_decode($response, true);
        if (is_array($capeInfo) && isset($capeInfo['name'])) {
            return $capeInfo;
        }
    }
    return null;
}

// Helper function to detect which controller is installed
function detectInstalledController() {
    $config = loadPSUConfig();
    if (!$config || !isset($config['Controllers'])) {
        return null;
    }

    // 1. Match on the cape EEPROM name (and version if possible)
    $capeInfo = readCapeInfo();
    if ($capeInfo) {
        $capeName = $capeInfo['name'];
        $capeVersion = isset($capeInfo['version']) ? $capeInfo['version'] : '';

        foreach ($config['Controllers'] as $controller) {
            if ($controller['Controller'] !== $capeName) {
                continue;
            }
            foreach ($controller['Variants'] as $variant) {
                if ($variant['variant'] === $capeVersion) {
                    return array(
                        'controller' => $controller['Controller'],
                        'variant' => $variant['variant'],
                        'config' => $variant,
                        'detection_method' => 'cape-info.json'
                    );
                }
            }
            // Known controller but unknown board revision, use the latest variant we know of
            $variant = end($controller['Variants']);
            return array(
                'controller' => $controller['Controller'],
                'variant' => $variant['variant'],
                'config' => $variant,
                'detection_method' => 'cape-name'
            );
        }
    }

    // 2. Fall back to checking which control pins FPP knows about.  Check the
    //    variants with the most pins first so a D24 (3 PSUs) isn't reported as
    //    a D12 (2 PSUs) just because they share pins.
    $availablePins = getAvailableGPIOPins();
    if (empty($availablePins)) {
        return null;
    }
    $candidates = array();
    foreach ($config['Controllers'] as $controller) {
        foreach ($controller['Variants'] as $variant) {
            $candidates[] = array('controller' => $controller['Controller'], 'variant' => $variant);
        }
    }
    usort($candidates, function ($a, $b) {
        return count($b['variant']['controlpins']) - count($a['variant']['controlpins']);
    });
    foreach ($candidates as $candidate) {
        $allPinsExist = true;
        foreach ($candidate['variant']['controlpins'] as $psuConfig) {
            if (!in_array($psuConfig['gpiopin'], $availablePins, true)) {
                $allPinsExist = false;
                break;
            }
        }
        if ($allPinsExist) {
            return array(
                'controller' => $candidate['controller'],
                'variant' => $candidate['variant']['variant'],
                'config' => $candidate['variant'],
                'detection_method' => 'gpio_fallback'
            );
        }
    }

    // Not a DynamicPixels controller - don't guess, as that would drive the wrong pins
    return null;
}

// Helper to get the list of GPIO pin names FPP knows about
function getAvailableGPIOPins() {
    static $pins = null;
    if ($pins !== null) {
        return $pins;
    }
    $pins = array();
    list($code, $response) = dpLocalRequest("http://localhost/api/gpio");
    if ($code === 200 && $response) {
        $gpioData = json_decode($response, true);
        if (is_array($gpioData)) {
            foreach ($gpioData as $gpio) {
                if (isset($gpio['pin'])) {
                    $pins[] = $gpio['pin'];
                }
            }
        }
    }
    return $pins;
}

// Helper function to check if GPIO pin exists
function checkGPIOPinExists($gpiopin) {
    return in_array($gpiopin, getAvailableGPIOPins(), true);
}

// Helper function to execute FPP GPIO command via REST API
function executeGPIOCommand($gpiopin, $action) {
    $data = json_encode(array(
        'command' => 'GPIO',
        'args' => array($gpiopin, $action === 'on' ? 'On' : 'Off')
    ));
    list($httpCode, $response, $error) = dpLocalRequest("http://localhost/api/command", 'POST', $data, 5);

    return array(
        'success' => ($httpCode === 200),
        'httpCode' => $httpCode,
        'response' => $response,
        'error' => $error
    );
}

// GET /api/plugin/fpp-DynamicPixels/controller/info
function fppDynamicPixelsGetControllerInfo() {
    $detected = detectInstalledController();
    if (!$detected) {
        return json(array('error' => 'No DynamicPixels controller detected'));
    }
    
    $result = array(
        'detectedController' => $detected['controller'],
        'detectedVariant' => $detected['variant'],
        'detectionMethod' => $detected['detection_method'],
        'psuCount' => count($detected['config']['controlpins']),
        'psus' => array()
    );
    
    foreach ($detected['config']['controlpins'] as $psuConfig) {
        $psuInfo = array(
            'psu' => $psuConfig['psu'],
            'gpionum' => $psuConfig['gpionum'],
            'gpiopin' => $psuConfig['gpiopin']
        );
        array_push($result['psus'], $psuInfo);
    }
    
    return json($result);
}

// Helper function to read GPIO pin status
//
// Only read-only methods are used here.  Tools such as gpioget request the line
// and can flip an output to an input, which would drop the PSU.
function readGPIOPinStatus($psuPin) {
    $methods = array();

    // Method 1: FPP GPIO API - returns the last value FPP set on the pin
    $methods[] = 'fpp_gpio_api';
    list($httpCode, $response) = dpLocalRequest("http://localhost/api/gpio/" . rawurlencode($psuPin));
    if ($response) {
        $gpioData = json_decode($response, true);
        if ($httpCode === 200 && $gpioData && isset($gpioData['Status']) && $gpioData['Status'] === 'OK' && isset($gpioData['value'])) {
            $value = intval($gpioData['value']);
            return array(
                'status' => $value === 1 ? 'on' : 'off',
                'method' => 'fpp_gpio_api',
                'pin' => $psuPin,
                'raw_value' => $value,
                'response_code' => $httpCode
            );
        }
    }

    // Method 2: read the I2C expander input register directly.  Pin names are
    // <bus>-<address>-<line>, eg 1-0039-2.  This fails harmlessly if the kernel
    // driver owns the device.
    if (preg_match('/^(\d+)-([0-9a-fA-F]{4})-(\d+)$/', $psuPin, $m)) {
        $methods[] = 'i2c_direct';
        $cmd = "i2cget -y " . intval($m[1]) . " 0x" . substr($m[2], 2) . " 0x00 2>/dev/null";
        $output = shell_exec($cmd);
        if ($output !== null && preg_match('/0x([0-9a-fA-F]{2})/', trim($output), $matches)) {
            $byteValue = hexdec($matches[1]);
            $bitValue = ($byteValue >> intval($m[3])) & 1;
            return array(
                'status' => $bitValue ? 'on' : 'off',
                'method' => 'i2c_direct',
                'pin' => $psuPin,
                'raw_value' => $bitValue,
                'register_value' => trim($output)
            );
        }
    }

    return array(
        'status' => 'unknown',
        'method' => 'fpp_controlled',
        'pin' => $psuPin,
        'note' => 'State not known until the PSU has been switched on/off since FPP started',
        'tried_methods' => $methods
    );
}

// GET /api/plugin/fpp-DynamicPixels/psu/status
function fppDynamicPixelsGetPSUStatus() {
    $detected = detectInstalledController();
    if (!$detected) {
        return json(array('error' => 'No DynamicPixels controller detected'));
    }
    
    $result = array(
        'controller' => $detected['controller'],
        'variant' => $detected['variant'],
        'psus' => array()
    );
    
    foreach ($detected['config']['controlpins'] as $psuConfig) {
        // Try to read the actual GPIO pin status using our enhanced helper
        $gpioPin = $psuConfig['gpiopin'];
        $statusInfo = readGPIOPinStatus($gpioPin);
        
        $psuInfo = array(
            'psu' => $psuConfig['psu'],
            'gpionum' => $psuConfig['gpionum'],
            'gpiopin' => $psuConfig['gpiopin'],
            'status' => $statusInfo['status'],
            'method' => $statusInfo['method'],
            'note' => isset($statusInfo['note']) ? $statusInfo['note'] : '',
            'raw_value' => isset($statusInfo['raw_value']) ? $statusInfo['raw_value'] : null,
            'tried_methods' => isset($statusInfo['tried_methods']) ? $statusInfo['tried_methods'] : null
        );
        
        // Add additional debug info if available
        if (isset($statusInfo['register_value'])) {
            $psuInfo['register_value'] = $statusInfo['register_value'];
        }
        if (isset($statusInfo['suggestion'])) {
            $psuInfo['suggestion'] = $statusInfo['suggestion'];
        }
        
        array_push($result['psus'], $psuInfo);
    }
    
    return json($result);
}

// POST /api/plugin/fpp-DynamicPixels/psu/:psu/:action
function fppDynamicPixelsControlPSU() {
    $psuNumber = strval(params('psu'));
    $action = strtolower(params('action'));
    
    // Validate action
    if (!in_array($action, array('on', 'off'))) {
        return json(array('error' => 'Invalid action. Use "on" or "off"'));
    }
    
    $detected = detectInstalledController();
    if (!$detected) {
        return json(array('error' => 'No DynamicPixels controller detected'));
    }
    
    // Find the specific PSU
    $targetPSU = null;
    foreach ($detected['config']['controlpins'] as $psuConfig) {
        if ($psuConfig['psu'] === $psuNumber) {
            $targetPSU = $psuConfig;
            break;
        }
    }
    
    if (!$targetPSU) {
        return json(array('error' => "PSU {$psuNumber} not found on detected controller {$detected['controller']} v{$detected['variant']}"));
    }
    
    // Execute GPIO command via FPP REST API
    $gpioResult = executeGPIOCommand($targetPSU['gpiopin'], $action);
    
    $result = array(
        'controller' => $detected['controller'],
        'variant' => $detected['variant'],
        'psu' => $psuNumber,
        'action' => $action,
        'gpiopin' => $targetPSU['gpiopin'],
        'success' => $gpioResult['success'],
        'timestamp' => date('Y-m-d H:i:s'),
        'command' => "GPIO {$targetPSU['gpiopin']} " . ($action === 'on' ? 'On' : 'Off')
    );
    
    if (!$gpioResult['success']) {
        $result['error'] = 'GPIO command failed';
        $result['httpCode'] = $gpioResult['httpCode'];
        $result['curlError'] = $gpioResult['error'];
    }
    
    return json($result);
}

// POST /api/plugin/fpp-DynamicPixels/psu/all/:action
function fppDynamicPixelsControlAllPSUs() {
    $action = strtolower(params('action'));
    
    // Validate action
    if (!in_array($action, array('on', 'off'))) {
        return json(array('error' => 'Invalid action. Use "on" or "off"'));
    }
    
    $detected = detectInstalledController();
    if (!$detected) {
        return json(array('error' => 'No DynamicPixels controller detected'));
    }
    
    $results = array();
    $overallSuccess = true;
    
    foreach ($detected['config']['controlpins'] as $psuConfig) {
        $gpioResult = executeGPIOCommand($psuConfig['gpiopin'], $action);
        
        $psuResult = array(
            'psu' => $psuConfig['psu'],
            'gpiopin' => $psuConfig['gpiopin'],
            'success' => $gpioResult['success'],
            'command' => "GPIO {$psuConfig['gpiopin']} " . ($action === 'on' ? 'On' : 'Off')
        );
        
        if (!$gpioResult['success']) {
            $psuResult['error'] = 'GPIO command failed';
            $psuResult['httpCode'] = $gpioResult['httpCode'];
            $psuResult['curlError'] = $gpioResult['error'];
            $overallSuccess = false;
        }
        
        array_push($results, $psuResult);
    }
    
    $response = array(
        'controller' => $detected['controller'],
        'variant' => $detected['variant'],
        'action' => $action,
        'overallSuccess' => $overallSuccess,
        'results' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    );
    
    return json($response);
}

// GET /api/plugin/fpp-DynamicPixels/network/discover
function fppDynamicPixelsNetworkDiscover() {
    $result = array();
    $result['discoveredSystems'] = array();
    $result['dynamicPixelsSystems'] = array();
    $result['summary'] = array();
    $result['_debug_version'] = 'v3-simplified-hostname-dedup';
    
    // Get all systems from FPP MultiSync API
    $multiSyncUrl = "http://localhost/api/fppd/multiSyncSystems";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $multiSyncUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        $result['error'] = 'Failed to fetch MultiSync systems';
        return json($result);
    }
    
    $multiSyncData = json_decode($response, true);
    if (!$multiSyncData || !isset($multiSyncData['systems'])) {
        $result['error'] = 'Invalid MultiSync response';
        return json($result);
    }
    
    $result['discoveredSystems'] = $multiSyncData['systems'];
    
    // Deduplicate systems - each unique system should remain separate
    // Only merge IPs if they are truly the same system (same hostname)
    // DEBUG: Simplified deduplication v2
    $systemsByHostname = array();
    
    foreach ($multiSyncData['systems'] as $system) {
        $address = $system['address'];
        $hostname = isset($system['hostname']) ? $system['hostname'] : 'Unknown-' . $address;
        
        // Use hostname as the unique key
        // Systems with different hostnames are always separate
        if (!isset($systemsByHostname[$hostname])) {
            // First time seeing this hostname
            $systemsByHostname[$hostname] = $system;
            $systemsByHostname[$hostname]['addresses'] = array($address);
        } else {
            // Same hostname - add this IP address
            if (!in_array($address, $systemsByHostname[$hostname]['addresses'])) {
                array_push($systemsByHostname[$hostname]['addresses'], $address);
            }
        }
    }
    
    // Use hostname-deduplicated systems
    $systemsByUuid = $systemsByHostname;
    $result['_debug_dedup_count'] = count($systemsByUuid);
    
    // Check each unique system for DynamicPixels cape info
    foreach ($systemsByUuid as $uuid => $system) {
        $addresses = $system['addresses'];
        $hostname = isset($system['hostname']) ? $system['hostname'] : 'Unknown';
        
        // Check if this might be a DynamicPixels controller by hostname
        $isDynamicPixelsCandidate = false;
        if (stripos($hostname, 'dynamicpixel') !== false || 
            stripos($hostname, 'd12') !== false || 
            stripos($hostname, 'd24') !== false) {
            $isDynamicPixelsCandidate = true;
        }
        
        // Use an IPv4 address as the primary where possible.  MultiSync can also
        // report IPv6 addresses which need bracketing in URLs and which FPP's
        // /proxy/ passthrough (used by the UI for remote PSU control) doesn't support.
        $primaryAddress = $addresses[0];
        foreach ($addresses as $addr) {
            if (filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $primaryAddress = $addr;
                break;
            }
        }
        $urlAddress = filter_var($primaryAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? "[{$primaryAddress}]" : $primaryAddress;
        $statsUrl = "http://{$urlAddress}/api/statistics/usage";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $statsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        
        $statsResponse = curl_exec($ch);
        $statsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $systemInfo = array(
            'uuid' => $uuid,
            'addresses' => $addresses,
            'primaryAddress' => $primaryAddress,
            'hostname' => $hostname,
            'model' => isset($system['model']) ? $system['model'] : 'Unknown',
            'version' => isset($system['version']) ? $system['version'] : 'Unknown',
            'fppMode' => isset($system['fppMode']) ? $system['fppMode'] : 0,
            'fppModeString' => isset($system['fppModeString']) ? $system['fppModeString'] : 'unknown',
            'lastSeen' => isset($system['lastSeenStr']) ? $system['lastSeenStr'] : 'Unknown',
            'channelRanges' => isset($system['channelRanges']) ? $system['channelRanges'] : '',
            'isDynamicPixelsCandidate' => $isDynamicPixelsCandidate,
            'capeInfo' => null,
            'isDynamicPixels' => false,
            'controllerType' => null,
            'controllerVersion' => null,
            'controllerId' => null,
            'serialNumber' => null,
            'designer' => null,
            'vendor' => null,
            'psuCount' => 0
        );
        
        if ($statsHttpCode === 200 && $statsResponse) {
            $statsData = json_decode($statsResponse, true);
            if ($statsData && isset($statsData['capeInfo']) && isset($statsData['capeInfo']['name'])) {
                $capeInfo = $statsData['capeInfo'];
                $systemInfo['capeInfo'] = $capeInfo;
                
                // Check if this is a DynamicPixels cape
                if (stripos($capeInfo['name'], 'dynamicpixels') !== false || 
                    (isset($capeInfo['vendor']['name']) && stripos($capeInfo['vendor']['name'], 'dynamic pixels') !== false)) {
                    
                    $systemInfo['isDynamicPixels'] = true;
                    $systemInfo['controllerType'] = $capeInfo['name'];
                    $systemInfo['controllerId'] = isset($capeInfo['id']) ? $capeInfo['id'] : null;
                    $systemInfo['serialNumber'] = isset($capeInfo['serialNumber']) ? $capeInfo['serialNumber'] : null;
                    $systemInfo['designer'] = isset($capeInfo['designer']) ? $capeInfo['designer'] : null;
                    $systemInfo['vendor'] = isset($capeInfo['vendor']) ? $capeInfo['vendor'] : null;
                    
                    // Extract version from the ID if available
                    if (isset($capeInfo['id'])) {
                        // Parse version from ID like "DynamicPixels-D12-12-v0.1"
                        if (preg_match('/v([0-9.]+)$/', $capeInfo['id'], $versionMatches)) {
                            $systemInfo['controllerVersion'] = $versionMatches[1];
                        }
                    }
                    
                    // Determine PSU count based on controller type
                    if (stripos($capeInfo['name'], 'd12') !== false) {
                        $systemInfo['psuCount'] = 2;
                    } elseif (stripos($capeInfo['name'], 'd24') !== false) {
                        $systemInfo['psuCount'] = 3;
                    }
                    
                    // Get PSU status from this controller
                    $psuStatusUrl = "http://{$urlAddress}/api/plugin/fpp-plugin-DynamicPixels/psu/status";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $psuStatusUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
                    
                    $psuResponse = curl_exec($ch);
                    $psuHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    $systemInfo['psuStatus'] = array();
                    if ($psuHttpCode === 200 && $psuResponse) {
                        $psuData = json_decode($psuResponse, true);
                        if ($psuData && isset($psuData['psus'])) {
                            $systemInfo['psuStatus'] = $psuData['psus'];
                        }
                    }
                    
                    array_push($result['dynamicPixelsSystems'], $systemInfo);
                }
            }
        }
    }
    
    // Generate summary
    $result['summary'] = array(
        'totalSystems' => count($systemsByUuid),
        'dynamicPixelsCount' => count($result['dynamicPixelsSystems']),
        'timestamp' => date('Y-m-d H:i:s')
    );
    
    return json($result);
}

?>