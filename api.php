<?
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

    $ep = array(
        'method' => 'POST',
        'endpoint' => 'psu/:psu/:action',
        'callback' => 'fppDynamicPixelsControlPSU');
    array_push($result, $ep);

    $ep = array(
        'method' => 'POST',
        'endpoint' => 'psu/all/:action',
        'callback' => 'fppDynamicPixelsControlAllPSUs');
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
    
    // Method 1: Check for specific GPIO pins to identify controller
    // For now, we'll return the first controller as default
    // In a real implementation, you might:
    // - Check hardware detection files
    // - Test GPIO pin availability
    // - Read EEPROM data
    // - Check device tree overlays
    
    // Simple detection logic - check which GPIO pins are available
    foreach ($config['Controllers'] as $controller) {
        foreach ($controller['Variants'] as $variant) {
            // Test if the GPIO pins for this controller variant exist
            $testPin = $variant['controlpins'][0]['gpiopin'] ?? null;
            if ($testPin && checkGPIOPinExists($testPin)) {
                return array(
                    'controller' => $controller['Controller'],
                    'variant' => $variant['variant'],
                    'config' => $variant
                );
            }
        }
    }
    
    // Fallback: return first controller if detection fails
    $firstController = $config['Controllers'][0];
    $firstVariant = $firstController['Variants'][0];
    return array(
        'controller' => $firstController['Controller'],
        'variant' => $firstVariant['variant'],
        'config' => $firstVariant
    );
}

// Helper function to check if GPIO pin exists (basic check)
function checkGPIOPinExists($gpiopin) {
    // This is a simple check - in reality you might check:
    // - /sys/class/gpio/ entries
    // - Device tree information
    // - FPP GPIO configuration
    
    // For now, return true to use the first available controller
    return true;
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