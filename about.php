<style>
.dynamicpixels-container {
    max-width: 1200px;
    margin: 20px auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.dynamicpixels-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    text-align: center;
    position: relative;
}

.dynamicpixels-logo {
    max-height: 80px;
    margin-bottom: 15px;
}

.dynamicpixels-header h1 {
    margin: 0;
    font-size: 2.5em;
    font-weight: 300;
}

.dynamicpixels-header p {
    margin: 10px 0 0 0;
    font-size: 1.2em;
    opacity: 0.9;
}

.tab-container {
    border-bottom: 1px solid #ddd;
    background: #f8f9fa;
}

.tab-nav {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
}

.tab-nav li {
    flex: 1;
}

.tab-nav button {
    width: 100%;
    padding: 15px 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    color: #666;
    transition: all 0.3s ease;
    border-bottom: 3px solid transparent;
}

.tab-nav button:hover {
    background: #e9ecef;
    color: #333;
}

.tab-nav button.active {
    color: #667eea;
    border-bottom-color: #667eea;
    background: white;
}

.tab-content {
    min-height: 400px;
    padding: 30px;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.feature-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.feature-card h3 {
    color: #333;
    margin-top: 0;
}

.api-endpoint {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin: 10px 0;
    overflow: hidden;
}

.api-method {
    background: #667eea;
    color: white;
    padding: 8px 12px;
    font-weight: bold;
    display: inline-block;
    min-width: 60px;
    text-align: center;
}

.api-path {
    padding: 8px 12px;
    font-family: monospace;
    background: white;
}

.api-description {
    padding: 12px;
    border-top: 1px solid #dee2e6;
}

.credits-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.social-links {
    display: flex;
    gap: 15px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.social-links a {
    display: inline-flex;
    align-items: center;
    padding: 10px 15px;
    background: #667eea;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    transition: background 0.3s ease;
}

.social-links a:hover {
    background: #5a67d8;
    color: white;
}

.version-info {
    background: #e3f2fd;
    border: 1px solid #bbdefb;
    border-radius: 4px;
    padding: 15px;
    margin: 15px 0;
}

@media (max-width: 768px) {
    .dynamicpixels-container {
        margin: 10px;
        border-radius: 0;
    }
    
    .tab-nav {
        flex-direction: column;
    }
    
    .tab-nav button {
        border-bottom: 1px solid #ddd;
        border-right: none;
    }
    
    .feature-grid {
        grid-template-columns: 1fr;
    }
    
    .social-links {
        flex-direction: column;
    }
}
</style>

<div class="dynamicpixels-container">
    <div class="dynamicpixels-header">
        <img src="logo.png" alt="DynamicPixels Logo" class="dynamicpixels-logo" style="display: none;" id="dynamicpixels-logo">
        <h1>DynamicPixels Controller Plugin</h1>
        <p>Advanced FPP functionality for DynamicPixels range of controller capes</p>
    </div>

    <div class="tab-container">
        <ul class="tab-nav">
            <li><button class="tab-button active" data-tab="overview">Overview</button></li>
            <li><button class="tab-button" data-tab="setup">Setup</button></li>
            <li><button class="tab-button" data-tab="api">API</button></li>
            <li><button class="tab-button" data-tab="about">About</button></li>
        </ul>
    </div>

    <div class="tab-content">
        <!-- Overview Tab -->
        <div class="tab-pane active" id="overview">
            <h2>Plugin Overview</h2>
            <p>The DynamicPixels Controller Plugin enhances FPP with specialized functionality designed specifically for the DynamicPixels range of controller capes. This plugin provides advanced features for power management, GPIO control, and system optimization.</p>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <h3>🔌 PSU Control</h3>
                    <p>Advanced power supply management with intelligent switching and monitoring capabilities for optimal power efficiency.</p>
                </div>
                <div class="feature-card">
                    <h3>⚡ GPIO Management</h3>
                    <p>Comprehensive GPIO pin control with configurable inputs and outputs for custom hardware integration.</p>
                </div>
                <div class="feature-card">
                    <h3>📊 System Monitoring</h3>
                    <p>Real-time monitoring of controller status, power consumption, and system health metrics.</p>
                </div>
                <div class="feature-card">
                    <h3>🔧 Configuration Tools</h3>
                    <p>Easy-to-use configuration interface for customizing controller behavior and settings.</p>
                </div>
            </div>

            <div class="version-info">
                <h3>Compatibility</h3>
                <p><strong>FPP Version:</strong> 7.0+ (Recommended: 8.0+)</p>
                <p><strong>Hardware:</strong> DynamicPixels Controller Capes</p>
                <p><strong>Platform:</strong> Raspberry Pi 3B+, 4B, 5</p>
            </div>
        </div>

        <!-- Setup Tab -->
        <div class="tab-pane" id="setup">
            <h2>Setup & Configuration</h2>
            
            <h3>Prerequisites</h3>
            <ul>
                <li>FPP 9.0 or later installed and running</li>
                <li>DynamicPixels controller cape properly connected</li>
                <li>Administrative access to FPP</li>
            </ul>

            <h3>Installation Steps</h3>
            <ol>
                <li><strong>Install Plugin:</strong> Navigate to Content Setup → Plugin Manager and install the DynamicPixels Controller Plugin</li>
                <li><strong>Restart FPP:</strong> Reboot your FPP system to load the plugin</li>
                <li><strong>Configure Hardware:</strong> Go to Input/Output Setup → Channel Outputs and configure your DynamicPixels controller</li>
                <li><strong>Set PSU Options:</strong> Configure power supply settings in the plugin configuration page</li>
                <li><strong>Test Setup:</strong> Use the plugin's status page to verify proper operation</li>
            </ol>

            <h3>Configuration Files</h3>
            <div class="feature-card">
                <h4>PSU Configuration (PSU_config.json)</h4>
                <p>Controls power supply behavior including startup delays, shutdown sequences, and power monitoring thresholds.</p>
            </div>
            
            <div class="feature-card">
                <h4>Controller Physical Configs (controllerPhysicalConfigs.json)</h4>
                <p>Defines the physical characteristics and capabilities of connected DynamicPixels hardware.</p>
            </div>

            <h3>Troubleshooting</h3>
            <ul>
                <li><strong>Plugin not loading:</strong> Check FPP logs for error messages</li>
                <li><strong>Hardware not detected:</strong> Verify physical connections and power supply</li>
                <li><strong>GPIO issues:</strong> Ensure no conflicts with other plugins or FPP GPIO usage</li>
            </ul>
        </div>

        <!-- API Tab -->
        <div class="tab-pane" id="api">
            <h2>API Reference</h2>
            <p>The DynamicPixels plugin provides simplified REST API endpoints that automatically detect your installed controller and manage PSU control.</p>

            <h3>Controller Information</h3>
            <div class="api-endpoint">
                <div>
                    <span class="api-method">GET</span>
                    <span class="api-path">/api/plugin/fpp-DynamicPixels/controller/info</span>
                </div>
                <div class="api-description">
                    <strong>Description:</strong> Get information about the detected DynamicPixels controller<br>
                    <strong>Returns:</strong> Detected controller model, variant, and available PSUs
                </div>
            </div>

            <div class="api-endpoint">
                <div>
                    <span class="api-method">GET</span>
                    <span class="api-path">/api/plugin/fpp-DynamicPixels/version</span>
                </div>
                <div class="api-description">
                    <strong>Description:</strong> Get plugin version information<br>
                    <strong>Returns:</strong> Plugin version string
                </div>
            </div>

            <h3>PSU Status & Control</h3>
            <div class="api-endpoint">
                <div>
                    <span class="api-method">GET</span>
                    <span class="api-path">/api/plugin/fpp-DynamicPixels/psu/status</span>
                </div>
                <div class="api-description">
                    <strong>Description:</strong> Get status of all PSUs on the detected controller<br>
                    <strong>Returns:</strong> Array of PSU information with GPIO mappings
                </div>
            </div>

            <div class="api-endpoint">
                <div>
                    <span class="api-method">POST</span>
                    <span class="api-path">/api/plugin/fpp-DynamicPixels/psu/{psu_number}/{action}</span>
                </div>
                <div class="api-description">
                    <strong>Description:</strong> Control a specific PSU by number<br>
                    <strong>Parameters:</strong> psu_number (1, 2, 3, etc.), action (on|off)<br>
                    <strong>Example:</strong> <code>/psu/2/on</code> turns on PSU 2<br>
                    <strong>Returns:</strong> Success status and GPIO command details
                </div>
            </div>

            <div class="api-endpoint">
                <div>
                    <span class="api-method">POST</span>
                    <span class="api-path">/api/plugin/fpp-DynamicPixels/psu/all/{action}</span>
                </div>
                <div class="api-description">
                    <strong>Description:</strong> Control all PSUs simultaneously<br>
                    <strong>Parameters:</strong> action (on|off)<br>
                    <strong>Example:</strong> <code>/psu/all/off</code> turns off all PSUs<br>
                    <strong>Returns:</strong> Array of results for each PSU
                </div>
            </div>

            <h3>Usage Examples</h3>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
                <h4>cURL Examples:</h4>
                <pre style="background: #fff; padding: 10px; border-radius: 3px; margin: 10px 0;">
# Get controller information
curl http://localhost/api/plugin/fpp-DynamicPixels/controller/info

# Check PSU status
curl http://localhost/api/plugin/fpp-DynamicPixels/psu/status

# Turn on PSU 1
curl -X POST http://localhost/api/plugin/fpp-DynamicPixels/psu/1/on

# Turn off PSU 2
curl -X POST http://localhost/api/plugin/fpp-DynamicPixels/psu/2/off

# Turn off all PSUs
curl -X POST http://localhost/api/plugin/fpp-DynamicPixels/psu/all/off

# Turn on all PSUs
curl -X POST http://localhost/api/plugin/fpp-DynamicPixels/psu/all/on</pre>
            </div>

            <h3>Response Format</h3>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
                <h4>Successful PSU Control Response:</h4>
                <pre style="background: #fff; padding: 10px; border-radius: 3px; margin: 10px 0;">
{
  "controller": "D12",
  "variant": "0.1",
  "psu": "1",
  "action": "on",
  "gpiopin": "1-0039-2",
  "success": true,
  "timestamp": "2025-10-11 14:30:25",
  "commandUrl": "http://localhost/api/command/GPIO/1-0039-2/on"
}</pre>
            </div>

            <h3>Auto-Detection</h3>
            <p><strong>Important:</strong> This API automatically detects which DynamicPixels controller is installed (D12, D24, etc.) by reading the cape-info.json file from FPP's hardware detection system. The plugin maps PSU numbers to the correct GPIO pins based on your specific controller model and version. You don't need to specify the controller model - just use PSU numbers (1, 2, 3, etc.).</p>
            
            <p><strong>Detection Method:</strong> The plugin first attempts to read cape information from <code>/home/fpp/media/tmp/cape-info.json</code> for accurate hardware identification. If this file is not available, it falls back to GPIO pin testing to determine the controller type.</p>
        </div>

        <!-- About Tab -->
        <div class="tab-pane" id="about">
            <h2>About DynamicPixels Controller Plugin</h2>
            
            <div class="credits-section">
                <h3>Plugin Information</h3>
                <p><strong>Version:</strong> 2.0.0</p>
                <p><strong>Release Date:</strong> October 2025</p>
                <p><strong>License:</strong> MIT License</p>
                <p><strong>Plugin Developed By:</strong> Stuart Ledingham of Dynamic Pixels</p>
            </div>

            <div class="credits-section">
                <h3>About Dynamic Pixels</h3>
                <p>Dynamic Pixels specializes in creating innovative LED lighting controllers and solutions for professional and hobbyist lighting displays. Our controllers are designed for reliability, performance, and ease of use in demanding lighting applications.</p>
            </div>

            <div class="social-links">
                <a href="https://github.com/OnlineDynamic/DynamicPixelsFPPPlugin" target="_blank">
                    📦 GitHub Repository
                </a>
                <a href="https://github.com/OnlineDynamic/DynamicPixelsFPPPlugin/issues" target="_blank">
                    🐛 Report Issues
                </a>
                <a href="https://dynamicpixels.co.uk" target="_blank">
                    🌐 Dynamic Pixels Website
                </a>
                <a href="mailto:support@dynamicpixels.co.uk">
                    ✉️ Contact Support
                </a>
            </div>

            <div class="credits-section">
                <h3>Acknowledgments</h3>
                <ul>
                    <li><strong>FPP Team:</strong> For creating an amazing open-source platform</li>
                    <li><strong>Community Contributors:</strong> For testing, feedback, and suggestions</li>
                    <li><strong>Beta Testers:</strong> For helping improve stability and features</li>
                </ul>
            </div>

            <div class="version-info">
                <h3>Changelog</h3>
                <p><strong>v2.0.0:</strong> Complete UI redesign with tabbed interface, enhanced API</p>
                <p><strong>v1.5.0:</strong> Added advanced PSU control and GPIO management</p>
                <p><strong>v1.0.0:</strong> Initial release with basic controller support</p>
            </div>
        </div>
    </div>
</div>

<script>
// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    // Check if logo exists and show it
    const logo = document.getElementById('dynamicpixels-logo');
    const img = new Image();
    img.onload = function() {
        logo.style.display = 'block';
    };
    img.src = 'logo.png';
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and panes
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Add active class to clicked button and corresponding pane
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });
});
</script>
