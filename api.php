<?php
include_once("/opt/fpp/www/common.php");
$pluginName = "fpp-plugin-DynamicPixels";
$pluginConfigFile = $settings['configDirectory'] . "/plugin." . $pluginName;

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
    $result['version'] = 'fpp-DynamicPixels v2.0';

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

// Helper function to detect which controller is installed
function detectInstalledController() {
    $config = loadPSUConfig();
    if (!$config || !isset($config['Controllers'])) {
        return null;
    }
    
    // Try to read cape-info.json for accurate hardware detection
    $capeInfoFile = '/home/fpp/media/tmp/cape-info.json';
    if (file_exists($capeInfoFile)) {
        $capeInfoData = file_get_contents($capeInfoFile);
        $capeInfo = json_decode($capeInfoData, true);
        
        if ($capeInfo && isset($capeInfo['name']) && isset($capeInfo['version'])) {
            $capeName = $capeInfo['name'];
            $capeVersion = $capeInfo['version'];
            
            // Look for matching controller in our configuration
            foreach ($config['Controllers'] as $controller) {
                if ($controller['Controller'] === $capeName) {
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
                }
            }
        }
    }
    
    // Fallback to GPIO pin detection if cape-info.json not available or no match
    foreach ($config['Controllers'] as $controller) {
        foreach ($controller['Variants'] as $variant) {
            $allPinsExist = true;
            
            // Check if ALL GPIO pins for this controller variant exist
            foreach ($variant['controlpins'] as $psuConfig) {
                $gpiopin = $psuConfig['gpiopin'];
                if (!checkGPIOPinExists($gpiopin)) {
                    $allPinsExist = false;
                    break;
                }
            }
            
            // If all pins exist for this controller, it's likely the correct one
            if ($allPinsExist) {
                return array(
                    'controller' => $controller['Controller'],
                    'variant' => $variant['variant'],
                    'config' => $variant,
                    'detection_method' => 'gpio_fallback'
                );
            }
        }
    }
    
    // Final fallback: return first controller if all detection fails
    $firstController = $config['Controllers'][0];
    $firstVariant = $firstController['Variants'][0];
    return array(
        'controller' => $firstController['Controller'],
        'variant' => $firstVariant['variant'],
        'config' => $firstVariant,
        'detection_method' => 'default_fallback'
    );
}

// Helper function to check if GPIO pin exists
function checkGPIOPinExists($gpiopin) {
    // Method 1: Try to call FPP's GPIO status API to see if pin exists
    $url = "http://localhost/api/gpio";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $gpioData = json_decode($response, true);
        if ($gpioData && is_array($gpioData)) {
            // Check if our GPIO pin is in the available pins
            foreach ($gpioData as $gpio) {
                if (isset($gpio['pin']) && $gpio['pin'] === $gpiopin) {
                    return true;
                }
            }
        }
    }
    
    // Method 2: Try to test the GPIO pin by attempting to read its status
    $testUrl = "http://localhost/api/command/GPIO/{$gpiopin}/status";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // If we get a valid response (not 404), the pin likely exists
    return ($httpCode === 200);
}

// Helper function to execute FPP GPIO command via REST API
function executeGPIOCommand($gpiopin, $action) {
    $url = "http://localhost/api/command/GPIO/{$gpiopin}/{$action}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
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
        // Try to get actual GPIO status
        $gpioStatus = 'unknown';
        $statusUrl = "http://localhost/api/command/GPIO/{$psuConfig['gpiopin']}/status";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $statusUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $responseData = json_decode($response, true);
            if ($responseData && isset($responseData['Status']) && $responseData['Status'] === 'OK') {
                if (isset($responseData['Value'])) {
                    // Convert GPIO value to on/off status
                    $gpioStatus = ($responseData['Value'] == 1) ? 'on' : 'off';
                }
            }
        }
        
        $psuInfo = array(
            'psu' => $psuConfig['psu'],
            'gpionum' => $psuConfig['gpionum'],
            'gpiopin' => $psuConfig['gpiopin'],
            'status' => $gpioStatus
        );
        array_push($result['psus'], $psuInfo);
    }
    
    return json($result);
}

// POST /api/plugin/fpp-DynamicPixels/psu/:psu/:action
function fppDynamicPixelsControlPSU() {
    $psuNumber = params('psu');
    $action = params('action');
    
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
        'commandUrl' => "http://localhost/api/command/GPIO/{$targetPSU['gpiopin']}/{$action}"
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
    $action = params('action');
    
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
            'commandUrl' => "http://localhost/api/command/GPIO/{$psuConfig['gpiopin']}/{$action}"
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
    
    // Deduplicate systems by UUID and consolidate IP addresses
    $systemsByUuid = array();
    
    foreach ($multiSyncData['systems'] as $system) {
        $uuid = isset($system['uuid']) ? $system['uuid'] : $system['address']; // fallback to address if no UUID
        $address = $system['address'];
        $hostname = isset($system['hostname']) ? $system['hostname'] : 'Unknown';
        
        if (!isset($systemsByUuid[$uuid])) {
            // First time seeing this UUID
            $systemsByUuid[$uuid] = $system;
            $systemsByUuid[$uuid]['addresses'] = array($address);
        } else {
            // We've seen this UUID before, add this IP to the addresses array
            if (!in_array($address, $systemsByUuid[$uuid]['addresses'])) {
                array_push($systemsByUuid[$uuid]['addresses'], $address);
            }
        }
    }
    
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
        
        // Try to get cape info from the primary address (first in list)
        $primaryAddress = $addresses[0];
        $statsUrl = "http://{$primaryAddress}/api/statistics/usage";
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
                    $psuStatusUrl = "http://{$primaryAddress}/api/plugin/fpp-plugin-DynamicPixels/psu/status";
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