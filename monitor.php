<?php
include_once(__DIR__ . '/logo_base64.php');

// Get system IP from URL parameter
$systemIp = isset($_GET['ip']) ? $_GET['ip'] : 'localhost';
$systemName = isset($_GET['name']) ? $_GET['name'] : 'Local System';

// Function to make API calls to the target system
function makeApiCall($ip, $endpoint, $timeout = 5) {
    $url = "http://{$ip}{$endpoint}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        return json_decode($response, true);
    }
    
    return null;
}

// Function to get system status (enhanced)
function getSystemStatus($ip) {
    return makeApiCall($ip, '/api/fppd/status');
}

// Function to get system info
function getSystemInfo($ip) {
    return makeApiCall($ip, '/api/system/info');
}

// Function to get cape information
function getCapeInfo($ip) {
    return makeApiCall($ip, '/api/cape');
}

// Function to get statistics (contains sensors and network info)
function getStatistics($ip) {
    return makeApiCall($ip, '/api/statistics/usage');
}

// Function to get PSU status
function getPSUStatus($ip) {
    return makeApiCall($ip, '/api/plugin/fpp-plugin-DynamicPixels/psu/status');
}

// Function to get disk usage (from filesystem info)
function getDiskUsage($ip) {
    // Try to get disk usage from uploads directory info
    $uploadInfo = makeApiCall($ip, '/api/files/uploads');
    if ($uploadInfo && isset($uploadInfo['size'])) {
        return $uploadInfo;
    }
    return null;
}

// Fetch all data
$systemStatus = getSystemStatus($systemIp);
$systemInfo = getSystemInfo($systemIp);
$statistics = getStatistics($systemIp);
$capeInfo = getCapeInfo($systemIp);
$psuStatus = getPSUStatus($systemIp);
$diskUsage = getDiskUsage($systemIp);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Monitor - <?php echo htmlspecialchars($systemName); ?></title>
    <style>
        .monitor-container {
            max-width: 1400px;
            margin: 20px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .monitor-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .monitor-logo {
            max-height: 50px;
            border-radius: 4px;
        }

        .monitor-title {
            flex: 1;
        }

        .monitor-title h1 {
            margin: 0;
            font-size: 1.8em;
            font-weight: 300;
        }

        .monitor-title p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 1em;
        }

        .refresh-button {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .refresh-button:hover {
            background: rgba(255,255,255,0.3);
        }

        .monitor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .monitor-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .monitor-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .monitor-card h2 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-online { background: #28a745; }
        .status-offline { background: #dc3545; }
        .status-warning { background: #ffc107; }
        .status-playing { background: #007bff; }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: #555;
        }

        .info-value {
            color: #333;
            font-family: monospace;
        }

        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 5px 0;
        }

        .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8em;
            font-weight: bold;
        }

        .progress-low { background: #28a745; }
        .progress-medium { background: #ffc107; }
        .progress-high { background: #fd7e14; }
        .progress-critical { background: #dc3545; }

        .large-card {
            grid-column: span 2;
        }

        .sensor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .sensor-item {
            background: white;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .sensor-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #667eea;
        }

        .sensor-label {
            font-size: 0.8em;
            color: #666;
            margin-top: 5px;
        }

        .auto-refresh {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: 20px;
        }

        .auto-refresh input[type="checkbox"] {
            margin: 0;
        }

        .sensors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .sensor-item {
            background: var(--surface-color);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            text-align: center;
        }

        .sensor-status {
            font-size: 0.8em;
            padding: 3px 8px;
            border-radius: 12px;
            margin-top: 5px;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-idle {
            background: #f8d7da;
            color: #721c24;
        }

        .plugins-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .plugin-item {
            background: var(--surface-color);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .plugin-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .plugin-name {
            font-size: 1.1em;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 8px;
        }

        .plugin-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85em;
            color: var(--text-muted);
        }

        .plugin-hash {
            font-family: monospace;
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
            cursor: help;
        }

        .plugin-date {
            font-size: 0.9em;
        }

        .plugin-count {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
            text-align: center;
            font-weight: 600;
            color: var(--text-muted);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .stat-item {
            text-align: center;
            background: var(--surface-color);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .stat-label {
            font-size: 0.9em;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 1.4em;
            font-weight: bold;
            color: var(--primary-color);
        }

        .error-message {
            color: #dc3545;
            font-style: italic;
            text-align: center;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .monitor-grid {
                grid-template-columns: 1fr;
            }
            .large-card {
                grid-column: span 1;
            }
            .monitor-header {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<div class="monitor-container">
    <div class="monitor-header">
        <img src="<?php echo $logoBase64; ?>" alt="DynamicPixels Logo" class="monitor-logo">
        <div class="monitor-title">
            <h1>System Monitor</h1>
            <p><?php echo htmlspecialchars($systemName); ?> (<?php echo htmlspecialchars($systemIp); ?>)</p>
        </div>
        <div class="auto-refresh">
            <input type="checkbox" id="autoRefresh">
            <label for="autoRefresh">Auto-refresh (30s)</label>
        </div>
        <button class="refresh-button" onclick="window.location.reload()">🔄 Refresh</button>
    </div>

    <div class="monitor-grid">
        <!-- System Status Card -->
        <div class="monitor-card">
            <h2>
                <span class="status-indicator <?php echo $systemStatus ? 'status-online' : 'status-offline'; ?>"></span>
                System Status
            </h2>
            <?php if ($systemStatus): ?>
                <div class="info-row">
                    <span class="info-label">FPP Mode:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStatus['mode_name'] ?? 'Unknown'); ?></span>
                </div>
                <?php 
                // Get temperatures from statistics
                $cpuTemp = null;
                $caseTemp = null;
                if ($statistics && isset($statistics['systemInfo']['sensors'])) {
                    foreach ($statistics['systemInfo']['sensors'] as $sensor) {
                        if (isset($sensor['valueType']) && $sensor['valueType'] === 'Temperature') {
                            if (strpos($sensor['label'], 'CPU') !== false) {
                                $cpuTemp = $sensor['formatted'];
                            } elseif (preg_match('/D\d+\/\d+/', $sensor['label'])) {
                                // Match D12/12 Temp, D24/24 Temp, etc.
                                $caseTemp = $sensor['formatted'];
                            }
                        }
                    }
                }
                ?>
                <?php if ($cpuTemp): ?>
                <div class="info-row">
                    <span class="info-label">CPU Temperature:</span>
                    <span class="info-value"><?php echo htmlspecialchars($cpuTemp); ?>°C</span>
                </div>
                <?php endif; ?>
                <?php if ($caseTemp): ?>
                <div class="info-row">
                    <span class="info-label">Case Temperature:</span>
                    <span class="info-value"><?php echo htmlspecialchars($caseTemp); ?>°C</span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Uptime:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStatus['uptimeStr'] ?? 'Unknown'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Time:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStatus['timeStrFull'] ?? date('H:i:s')); ?></span>
                </div>
            <?php else: ?>
                <div class="error-message">Unable to connect to system</div>
            <?php endif; ?>
        </div>

        <!-- System Information Card -->
        <div class="monitor-card">
            <h2>📋 System Information</h2>
            <?php if ($systemInfo): ?>
                <div class="info-row">
                    <span class="info-label">Platform:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemInfo['Platform'] ?? 'Unknown'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Version:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemInfo['Version'] ?? 'Unknown'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Branch:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemInfo['Branch'] ?? 'Unknown'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Kernel:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemInfo['Kernel'] ?? 'Unknown'); ?></span>
                </div>
            <?php else: ?>
                <div class="error-message">System information unavailable</div>
            <?php endif; ?>
        </div>

        <!-- Player Status Card -->
        <div class="monitor-card">
            <h2>
                <span class="status-indicator <?php echo ($systemStatus && isset($systemStatus['status_name']) && $systemStatus['status_name'] !== 'idle') ? 'status-playing' : 'status-online'; ?>"></span>
                Player Status
            </h2>
            <?php if ($systemStatus): ?>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStatus['status_name'] ?? 'Unknown'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Volume:</span>
                    <span class="info-value"><?php echo htmlspecialchars(($systemStatus['volume'] ?? 'N/A') . '%'); ?></span>
                </div>
                <?php if (isset($systemStatus['current_playlist']['playlist']) && !empty($systemStatus['current_playlist']['playlist'])): ?>
                <div class="info-row">
                    <span class="info-label">Playlist:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStatus['current_playlist']['playlist']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Position:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStatus['current_playlist']['index'] . '/' . $systemStatus['current_playlist']['count']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (isset($systemStatus['current_sequence']) && !empty($systemStatus['current_sequence'])): ?>
                <div class="info-row">
                    <span class="info-label">Sequence:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStatus['current_sequence']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (isset($systemStatus['current_song']) && !empty($systemStatus['current_song'])): ?>
                <div class="info-row">
                    <span class="info-label">Media:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStatus['current_song']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (isset($systemStatus['time_elapsed']) && $systemStatus['time_elapsed'] !== '00:00'): ?>
                <div class="info-row">
                    <span class="info-label">Time:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStatus['time_elapsed'] . ' / ' . $systemStatus['time_remaining']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Scheduler:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStatus['scheduler']['status'] ?? 'Unknown'); ?></span>
                </div>
            <?php else: ?>
                <div class="error-message">Player status unavailable</div>
            <?php endif; ?>
        </div>

        <!-- PSU Status Card -->
        <div class="monitor-card">
            <h2>⚡ PSU Status</h2>
            <?php if ($psuStatus && isset($psuStatus['psus'])): ?>
                <?php 
                // Extract current sensor data from statistics
                $currentSensors = array();
                if ($statistics && isset($statistics['systemInfo']['sensors'])) {
                    foreach ($statistics['systemInfo']['sensors'] as $sensor) {
                        if (isset($sensor['valueType']) && $sensor['valueType'] === 'Current') {
                            // Extract PSU number from label (e.g., "PSU1: " -> "1")
                            if (preg_match('/PSU(\d+)/', $sensor['label'], $matches)) {
                                $currentSensors[$matches[1]] = $sensor;
                            }
                        }
                    }
                }
                ?>
                
                <?php foreach ($psuStatus['psus'] as $psu): ?>
                <div class="info-row">
                    <span class="info-label">PSU <?php echo htmlspecialchars($psu['psu']); ?>:</span>
                    <span class="info-value">
                        <span class="status-indicator <?php echo ($psu['status'] === 'on') ? 'status-online' : (($psu['status'] === 'off') ? 'status-offline' : 'status-warning'); ?>"></span>
                        <?php echo htmlspecialchars(ucfirst($psu['status'])); ?>
                        <?php if (isset($currentSensors[$psu['psu']])): ?>
                            <span style="margin-left: 10px; color: #667eea; font-weight: 600;">
                                <?php echo htmlspecialchars($currentSensors[$psu['psu']]['formatted']); ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>
                
                <div class="info-row">
                    <span class="info-label">Controller:</span>
                    <span class="info-value"><?php echo htmlspecialchars($psuStatus['controller'] ?? 'Unknown'); ?></span>
                </div>
            <?php else: ?>
                <div class="error-message">PSU status unavailable</div>
            <?php endif; ?>
        </div>

        <!-- System Resources Card -->
        <div class="monitor-card">
            <h2>💾 System Resources</h2>
            <?php if ($statistics && isset($statistics['systemInfo']['utilization'])): ?>
                <?php 
                $utilization = $statistics['systemInfo']['utilization'];
                $memUsedPercent = isset($utilization['Memory']) ? $utilization['Memory'] : 0;
                $cpuUsagePercent = isset($utilization['CPU']) ? $utilization['CPU'] : 0;
                $diskInfo = isset($utilization['Disk']['Root']) ? $utilization['Disk']['Root'] : null;
                $diskUsedPercent = 0;
                if ($diskInfo && $diskInfo['Total'] > 0) {
                    $diskUsedPercent = (($diskInfo['Total'] - $diskInfo['Free']) / $diskInfo['Total']) * 100;
                }
                
                $memProgressClass = $memUsedPercent < 60 ? 'progress-low' : ($memUsedPercent < 80 ? 'progress-medium' : 'progress-high');
                $cpuProgressClass = $cpuUsagePercent < 60 ? 'progress-low' : ($cpuUsagePercent < 80 ? 'progress-medium' : 'progress-high');
                $diskProgressClass = $diskUsedPercent < 60 ? 'progress-low' : ($diskUsedPercent < 80 ? 'progress-medium' : 'progress-high');
                ?>
                
                <!-- CPU Usage -->
                <div class="info-row">
                    <span class="info-label">CPU Usage:</span>
                    <span class="info-value"><?php echo number_format($cpuUsagePercent, 1); ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $cpuProgressClass; ?>" style="width: <?php echo $cpuUsagePercent; ?>%">
                        <?php echo number_format($cpuUsagePercent, 1); ?>%
                    </div>
                </div>
                
                <!-- Memory Usage -->
                <div class="info-row" style="margin-top: 15px;">
                    <span class="info-label">Memory Usage:</span>
                    <span class="info-value"><?php echo number_format($memUsedPercent, 1); ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $memProgressClass; ?>" style="width: <?php echo $memUsedPercent; ?>%">
                        <?php echo number_format($memUsedPercent, 1); ?>%
                    </div>
                </div>
                
                <!-- Disk Usage -->
                <?php if ($diskInfo): ?>
                <div class="info-row" style="margin-top: 15px;">
                    <span class="info-label">Disk Usage:</span>
                    <span class="info-value"><?php echo number_format($diskUsedPercent, 1); ?>% (<?php echo number_format(($diskInfo['Total'] - $diskInfo['Free']) / 1073741824, 1); ?> / <?php echo number_format($diskInfo['Total'] / 1073741824, 1); ?> GB)</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $diskProgressClass; ?>" style="width: <?php echo $diskUsedPercent; ?>%">
                        <?php echo number_format($diskUsedPercent, 1); ?>%
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Platform Info -->
                <?php if (isset($statistics['systemInfo']['platform'])): ?>
                <div class="info-row" style="margin-top: 15px;">
                    <span class="info-label">Platform:</span>
                    <span class="info-value"><?php echo htmlspecialchars($statistics['systemInfo']['platform'] . ' ' . $statistics['systemInfo']['platformVariant']); ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Uptime -->
                <?php if (isset($utilization['Uptime'])): ?>
                <div class="info-row">
                    <span class="info-label">Uptime:</span>
                    <span class="info-value"><?php echo htmlspecialchars($utilization['Uptime']); ?></span>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="error-message">System resources unavailable</div>
            <?php endif; ?>
        </div>

        <!-- Network Information Card -->
        <div class="monitor-card">
            <h2>🌐 Network</h2>
            <?php if ($systemInfo): ?>
                <div class="info-row">
                    <span class="info-label">Hostname:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemInfo['HostName'] ?? 'Unknown'); ?></span>
                </div>
                <?php if (isset($systemInfo['IPs']) && is_array($systemInfo['IPs']) && count($systemInfo['IPs']) > 0): ?>
                    <div class="info-row">
                        <span class="info-label">IP Address<?php echo count($systemInfo['IPs']) > 1 ? 'es' : ''; ?>:</span>
                        <span class="info-value"><?php echo htmlspecialchars(implode(', ', $systemInfo['IPs'])); ?></span>
                    </div>
                <?php else: ?>
                    <div class="info-row">
                        <span class="info-label">IP Address:</span>
                        <span class="info-value"><?php echo htmlspecialchars($ip); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (isset($systemInfo['Platform'])): ?>
                <div class="info-row">
                    <span class="info-label">Platform:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemInfo['Platform']); ?></span>
                </div>
                <?php endif; ?>
            <?php elseif ($networkInfo): ?>
                <?php foreach ($networkInfo as $interface => $info): ?>
                    <?php if ($info['ip']): ?>
                    <div class="info-row">
                        <span class="info-label"><?php echo htmlspecialchars($interface); ?>:</span>
                        <span class="info-value"><?php echo htmlspecialchars($info['ip']); ?></span>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="error-message">Network information unavailable</div>
            <?php endif; ?>
        </div>

                <!-- Statistics Card (Large) -->
        <?php if ($statistics || $capeInfo): ?>
        <div class="monitor-card large-card">
            <h2>📊 System Statistics</h2>
            <div class="stats-grid">
                <?php if ($statistics): ?>
                    <?php if (isset($statistics['bridgeStatistics'])): ?>
                    <div class="stat-item">
                        <div class="stat-label">Bridge Packets</div>
                        <div class="stat-value"><?php echo number_format($statistics['bridgeStatistics']['packetsReceived'] ?? 0); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($statistics['warnings'])): ?>
                    <div class="stat-item">
                        <div class="stat-label">Warnings</div>
                        <div class="stat-value"><?php echo count($statistics['warnings']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($statistics['uptime'])): ?>
                    <div class="stat-item">
                        <div class="stat-label">Uptime</div>
                        <div class="stat-value"><?php echo number_format($statistics['uptime'] / 3600, 1); ?> hrs</div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($capeInfo): ?>
                    <?php if (isset($capeInfo['version'])): ?>
                    <div class="stat-item">
                        <div class="stat-label">Cape Version</div>
                        <div class="stat-value">v<?php echo htmlspecialchars($capeInfo['version']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($capeInfo['vendor']['name'])): ?>
                    <div class="stat-item">
                        <div class="stat-label">Cape Vendor</div>
                        <div class="stat-value"><?php echo htmlspecialchars($capeInfo['vendor']['name']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($capeInfo['serialNumber'])): ?>
                    <div class="stat-item">
                        <div class="stat-label">Serial Number</div>
                        <div class="stat-value"><?php echo htmlspecialchars($capeInfo['serialNumber']); ?></div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Temperature & Current Sensors Card -->
        <?php if ($playerStatus && isset($playerStatus['sensors'])): ?>
        <div class="monitor-card large-card">
            <h2>🌡️ Temperature & Current Sensors</h2>
            <div class="sensors-grid">
                <?php foreach ($playerStatus['sensors'] as $sensor): ?>
                    <?php if (isset($sensor['label'], $sensor['value'], $sensor['formatted'])): ?>
                    <div class="sensor-item">
                        <div class="sensor-label"><?php echo htmlspecialchars($sensor['label']); ?></div>
                        <div class="sensor-value"><?php echo htmlspecialchars($sensor['formatted']); ?></div>
                        <?php if (strpos($sensor['label'], 'PSU') !== false): ?>
                        <div class="sensor-status <?php echo ($sensor['value'] > 0.1) ? 'status-active' : 'status-idle'; ?>">
                            <?php echo ($sensor['value'] > 0.1) ? 'Active' : 'Idle'; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <?php if (isset($playerStatus['sensors']) && count($playerStatus['sensors']) == 0): ?>
            <div class="info-message">No sensors detected</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Installed Plugins Card -->
        <?php if ($statistics && isset($statistics['plugins'])): ?>
        <div class="monitor-card large-card">
            <h2>🔌 Installed Plugins</h2>
            <div class="plugins-grid">
                <?php 
                $plugins = $statistics['plugins'];
                ksort($plugins); // Sort alphabetically
                foreach ($plugins as $pluginName => $pluginInfo): 
                    // Format the plugin name nicely
                    $displayName = str_replace(['fpp-plugin-', 'fpp-', 'FPP-Plugin-'], '', $pluginName);
                    $displayName = str_replace('-', ' ', $displayName);
                    $displayName = ucwords($displayName);
                    
                    // Format the commit date
                    $commitDate = isset($pluginInfo['commitDate']) ? date('M j, Y', strtotime($pluginInfo['commitDate'])) : 'Unknown';
                    $shortHash = isset($pluginInfo['hash']) ? substr($pluginInfo['hash'], 0, 7) : 'N/A';
                ?>
                    <div class="plugin-item">
                        <div class="plugin-name"><?php echo htmlspecialchars($displayName); ?></div>
                        <div class="plugin-details">
                            <span class="plugin-hash" title="<?php echo htmlspecialchars($pluginInfo['hash'] ?? 'N/A'); ?>">
                                <?php echo htmlspecialchars($shortHash); ?>
                            </span>
                            <span class="plugin-date"><?php echo htmlspecialchars($commitDate); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="plugin-count">
                Total: <?php echo count($plugins); ?> plugin<?php echo count($plugins) !== 1 ? 's' : ''; ?> installed
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-refresh functionality
let autoRefreshInterval;
const autoRefreshCheckbox = document.getElementById('autoRefresh');

autoRefreshCheckbox.addEventListener('change', function() {
    if (this.checked) {
        autoRefreshInterval = setInterval(() => {
            window.location.reload();
        }, 30000); // 30 seconds
    } else {
        clearInterval(autoRefreshInterval);
    }
});

// Update timestamps every second
setInterval(() => {
    const now = new Date();
    const timestamp = now.toLocaleString();
    // Could update relative timestamps here
}, 1000);
</script>

</body>
</html>