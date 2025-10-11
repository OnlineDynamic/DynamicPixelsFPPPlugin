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
        $psuInfo = array(
            'psu' => $psuConfig['psu'],
            'gpionum' => $psuConfig['gpionum'],
            'gpiopin' => $psuConfig['gpiopin'],
            'status' => 'unknown' // Note: FPP GPIO status would need separate API call
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

?>