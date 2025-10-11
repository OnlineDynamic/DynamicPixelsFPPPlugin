<?php
$pluginName = "fpp-plugin-DynamicPixels";
include_once "/opt/fpp/www/common.php";
include_once "/opt/fpp/www/common/menuHead.inc";
?>

<div class="title">DynamicPixels Network Discovery</div>

<div class="pageContent">
    <style>
        .network-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .discovery-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .discovery-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.2rem;
            font-weight: 300;
        }
        
        .discovery-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            border: 1px solid #e0e6ed;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .summary-card h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .summary-card p {
            margin: 0;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .controllers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .controller-card {
            background: white;
            border: 1px solid #e0e6ed;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .controller-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        
        .controller-header {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white;
            padding: 20px;
            position: relative;
        }
        
        .controller-header h3 {
            margin: 0 0 5px 0;
            font-size: 1.3rem;
            font-weight: 500;
        }
        
        .controller-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .controller-type {
            position: absolute;
            top: 15px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .controller-body {
            padding: 20px;
        }
        
        .controller-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.8rem;
            color: #7f8c8d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1rem;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .psu-section {
            border-top: 1px solid #ecf0f1;
            padding-top: 15px;
        }
        
        .psu-title {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .psu-controls {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .psu-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .psu-status-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        
        .psu-status-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #495057;
        }
        
        .psu-status-indicator {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .psu-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .psu-status-on {
            background: #28a745;
            box-shadow: 0 0 3px rgba(40, 167, 69, 0.5);
        }
        
        .psu-status-off {
            background: #dc3545;
            box-shadow: 0 0 3px rgba(220, 53, 69, 0.5);
        }
        
        .psu-status-unknown {
            background: #6c757d;
            box-shadow: 0 0 3px rgba(108, 117, 125, 0.5);
        }
        
        .psu-status-controlled {
            background: #17a2b8;
            box-shadow: 0 0 3px rgba(23, 162, 184, 0.5);
        }
        
        .psu-status-text {
            font-size: 0.7rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .psu-btn {
            flex: 1;
            min-width: 60px;
            padding: 8px 12px;
            border: 1px solid #3498db;
            background: white;
            color: #3498db;
            border-radius: 5px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .psu-btn:hover {
            background: #3498db;
            color: white;
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-online {
            background: #27ae60;
        }
        
        .status-offline {
            background: #e74c3c;
        }
        
        .refresh-btn {
            background: #74b9ff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            margin-bottom: 20px;
            transition: background 0.2s;
        }
        
        .refresh-btn:hover {
            background: #0984e3;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .error {
            background: #fdcb6e;
            color: #e17055;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .controllers-grid {
                grid-template-columns: 1fr;
            }
            
            .controller-info {
                grid-template-columns: 1fr;
            }
            
            .discovery-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>

    <div class="network-container">
        <div class="discovery-header">
            <h1>DynamicPixels Network Discovery</h1>
            <p>Real-time monitoring of all DynamicPixels controllers on your network with PSU status and remote control</p>
        </div>

        <button class="refresh-btn" onclick="refreshNetworkData()">
            <i class="fas fa-sync-alt"></i> Refresh Network
        </button>

        <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2196f3;">
            <h4 style="margin: 0 0 10px 0; color: #1976d2;">PSU Status Legend</h4>
            <div style="display: flex; gap: 20px; flex-wrap: wrap; font-size: 0.9rem;">
                <div><span class="psu-status-dot psu-status-on" style="display: inline-block; margin-right: 5px;"></span><strong>ON</strong> - PSU powered on</div>
                <div><span class="psu-status-dot psu-status-off" style="display: inline-block; margin-right: 5px;"></span><strong>OFF</strong> - PSU powered off</div>
                <div><span class="psu-status-dot psu-status-controlled" style="display: inline-block; margin-right: 5px;"></span><strong>CTRL</strong> - Under FPP control (status monitoring limited)</div>
                <div><span class="psu-status-dot psu-status-unknown" style="display: inline-block; margin-right: 5px;"></span><strong>UNK</strong> - Status unknown</div>
            </div>
        </div>

        <div id="loading" class="loading" style="display: none;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Discovering DynamicPixels controllers...</p>
        </div>

        <div id="error" class="error" style="display: none;"></div>

        <div id="summary" class="summary-cards"></div>
        
        <div id="controllers" class="controllers-grid"></div>
    </div>
</div>

<script>
let networkData = null;

function showLoading() {
    document.getElementById('loading').style.display = 'block';
    document.getElementById('error').style.display = 'none';
    document.getElementById('summary').innerHTML = '';
    document.getElementById('controllers').innerHTML = '';
}

function hideLoading() {
    document.getElementById('loading').style.display = 'none';
}

function showError(message) {
    document.getElementById('error').textContent = message;
    document.getElementById('error').style.display = 'block';
    hideLoading();
}

function renderSummary(data) {
    const summaryHtml = `
        <div class="summary-card">
            <h3>${data.summary.totalSystems}</h3>
            <p>Total FPP Systems</p>
        </div>
        <div class="summary-card">
            <h3>${data.summary.dynamicPixelsCount}</h3>
            <p>DynamicPixels Controllers</p>
        </div>
        <div class="summary-card">
            <h3>${data.dynamicPixelsSystems.reduce((total, sys) => total + sys.psuCount, 0)}</h3>
            <p>Total PSUs</p>
        </div>
        <div class="summary-card">
            <h3>${new Date(data.summary.timestamp).toLocaleTimeString()}</h3>
            <p>Last Updated</p>
        </div>
    `;
    document.getElementById('summary').innerHTML = summaryHtml;
}

function getControllerTypeDisplay(type) {
    if (type.includes('D12')) return 'D12';
    if (type.includes('D24')) return 'D24';
    return type;
}

function renderControllers(systems) {
    const controllersHtml = systems.map(system => `
        <div class="controller-card">
            <div class="controller-header">
                <h3>${system.hostname}</h3>
                <p><span class="status-indicator status-online"></span>${system.addresses.length > 1 ? system.addresses.join(', ') : system.primaryAddress}</p>
                <div class="controller-type">${getControllerTypeDisplay(system.controllerType)}</div>
            </div>
            <div class="controller-body">
                <div class="controller-info">
                    <div class="info-item">
                        <div class="info-label">Controller Type</div>
                        <div class="info-value">${system.controllerType}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Version</div>
                        <div class="info-value">v${system.controllerVersion}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Serial Number</div>
                        <div class="info-value">${system.serialNumber || 'N/A'}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Designer</div>
                        <div class="info-value">${system.designer || 'N/A'}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">FPP Mode</div>
                        <div class="info-value">${system.fppModeString}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Last Seen</div>
                        <div class="info-value">${new Date(system.lastSeen).toLocaleTimeString()}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">PSU Count</div>
                        <div class="info-value">${system.psuCount} PSUs</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Network Interfaces</div>
                        <div class="info-value">${system.addresses.length} IP${system.addresses.length > 1 ? 's' : ''}</div>
                    </div>
                </div>
                <div class="psu-section">
                    <div class="psu-title">PSU Status & Controls</div>
                    <div class="psu-status-grid">
                        ${system.psuStatus && system.psuStatus.length > 0 ? 
                            system.psuStatus.map(psu => {
                                const statusClass = psu.status === 'on' ? 'psu-status-on' : 
                                                  psu.status === 'off' ? 'psu-status-off' : 
                                                  psu.method === 'fpp_controlled' ? 'psu-status-controlled' : 'psu-status-unknown';
                                const statusText = psu.status === 'unknown' && psu.method === 'fpp_controlled' ? 'CTRL' : 
                                                 psu.status === 'unknown' ? 'UNK' : psu.status.toUpperCase();
                                return `
                                    <div class="psu-status-item">
                                        <div class="psu-status-label">PSU ${psu.psu}</div>
                                        <div class="psu-status-indicator">
                                            <div class="psu-status-dot ${statusClass}"></div>
                                            <span class="psu-status-text">${statusText}</span>
                                        </div>
                                    </div>
                                `;
                            }).join('') :
                            Array.from({length: system.psuCount}, (_, i) => `
                                <div class="psu-status-item">
                                    <div class="psu-status-label">PSU ${i + 1}</div>
                                    <div class="psu-status-indicator">
                                        <div class="psu-status-dot psu-status-unknown"></div>
                                        <span class="psu-status-text">UNK</span>
                                    </div>
                                </div>
                            `).join('')
                        }
                    </div>
                    <div class="psu-controls">
                        ${Array.from({length: system.psuCount}, (_, i) => 
                            `<button class="psu-btn" onclick="controlPSU('${system.primaryAddress}', ${i + 1}, 'on')">PSU ${i + 1} ON</button>`
                        ).join('')}
                        <button class="psu-btn" onclick="controlAllPSUs('${system.primaryAddress}', 'on')" style="background: #27ae60; color: white; border-color: #27ae60;">All ON</button>
                        <button class="psu-btn" onclick="controlAllPSUs('${system.primaryAddress}', 'off')" style="background: #e74c3c; color: white; border-color: #e74c3c;">All OFF</button>
                        <button class="psu-btn" onclick="openSystemMonitor('${system.primaryAddress}', '${system.hostname}')" style="background: #667eea; color: white; border-color: #667eea; margin-top: 10px; width: 100%;">📊 System Monitor</button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
    
    document.getElementById('controllers').innerHTML = controllersHtml;
}

function refreshNetworkData() {
    showLoading();
    
    fetch('/api/plugin/fpp-plugin-DynamicPixels/network/discover')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            networkData = data;
            hideLoading();
            renderSummary(data);
            renderControllers(data.dynamicPixelsSystems);
            
            if (data.dynamicPixelsSystems.length === 0) {
                document.getElementById('controllers').innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #7f8c8d;">
                        <i class="fas fa-search fa-3x" style="margin-bottom: 20px; opacity: 0.3;"></i>
                        <h3>No DynamicPixels Controllers Found</h3>
                        <p>No DynamicPixels controllers were detected on your network. Make sure your controllers are powered on and connected to the same network.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Network discovery error:', error);
            showError(`Failed to discover network: ${error.message}`);
        });
}

function controlPSU(address, psu, action) {
    const url = `http://${address}/api/plugin/fpp-plugin-DynamicPixels/psu/${psu}/${action}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`PSU ${psu} ${action} successful on ${address}`);
                // Refresh network data to show updated status
                setTimeout(refreshNetworkData, 500);
            } else {
                console.error(`PSU ${psu} ${action} failed on ${address}:`, data);
            }
        })
        .catch(error => {
            console.error(`Error controlling PSU ${psu} on ${address}:`, error);
        });
}

function controlAllPSUs(address, action) {
    const url = `http://${address}/api/plugin/fpp-plugin-DynamicPixels/psu/all/${action}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.overallSuccess) {
                console.log(`All PSUs ${action} successful on ${address}`);
                // Refresh network data to show updated status
                setTimeout(refreshNetworkData, 500);
            } else {
                console.error(`All PSUs ${action} failed on ${address}:`, data);
            }
        })
        .catch(error => {
            console.error(`Error controlling all PSUs on ${address}:`, error);
        });
}

function openSystemMonitor(address, hostname) {
    // Open system monitor in a new tab
    const monitorUrl = `/plugin.php?plugin=fpp-plugin-DynamicPixels&page=monitor.php&ip=${encodeURIComponent(address)}&name=${encodeURIComponent(hostname)}`;
    window.open(monitorUrl, '_blank');
}

// Auto-refresh every 30 seconds
setInterval(refreshNetworkData, 30000);

// Initial load
document.addEventListener('DOMContentLoaded', function() {
    refreshNetworkData();
});
</script>

<?php include_once "/opt/fpp/www/common/menuFoot.inc"; ?>