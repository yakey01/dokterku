{{-- GPS Help Button Component --}}
@props(['class' => '', 'text' => 'GPS Help', 'icon' => '🔒'])

<button 
    type="button"
    onclick="showGPSHelp()"
    class="gps-help-btn {{ $class }}"
    title="Get help with GPS permission issues"
>
    <span class="gps-help-icon">{{ $icon }}</span>
    <span class="gps-help-text">{{ $text }}</span>
</button>

<script>
function showGPSHelp() {
    // Run GPS diagnostics first
    if (window.GPSDiagnostic) {
        window.GPSDiagnostic.runDiagnostics().then(results => {
            console.log('🔍 GPS Diagnostics:', results);
            
            // Show appropriate help based on results
            if (results.permissions.denied || (results.geolocation.code === 1)) {
                showPermissionHelp();
            } else if (results.geolocation.code === 2) {
                showGPSUnavailableHelp();
            } else if (results.geolocation.code === 3) {
                showTimeoutHelp();
            } else {
                showGeneralHelp();
            }
        });
    } else {
        showGeneralHelp();
    }
}

function showPermissionHelp() {
    const modal = document.createElement('div');
    modal.innerHTML = `
        <div style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        ">
            <div style="
                background: white;
                padding: 24px;
                border-radius: 12px;
                max-width: 500px;
                width: 90%;
                max-height: 80vh;
                overflow-y: auto;
            ">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="margin: 0; color: #1f2937;">🔒 GPS Permission Denied</h3>
                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" 
                            style="background: none; border: none; font-size: 20px; cursor: pointer; color: #6b7280;">✕</button>
                </div>
                
                <p style="color: #374151; margin-bottom: 16px;">
                    <strong>Your browser has denied location access.</strong> Here's how to fix it:
                </p>
                
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #1f2937; margin-bottom: 8px;">🌐 Desktop Browsers:</h4>
                    <ol style="color: #374151; padding-left: 20px;">
                        <li>Click the lock icon 🔒 in your browser's address bar</li>
                        <li>Change location permission from "Block" to "Allow"</li>
                        <li>Refresh the page and try again</li>
                    </ol>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #1f2937; margin-bottom: 8px;">📱 Mobile Browsers:</h4>
                    <ol style="color: #374151; padding-left: 20px;">
                        <li><strong>Chrome Mobile:</strong> Settings → Site Settings → Location → Allow</li>
                        <li><strong>Safari Mobile:</strong> Settings → Safari → Location → Allow</li>
                        <li><strong>Firefox Mobile:</strong> Settings → Privacy & Security → Location → Allow</li>
                    </ol>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove(); window.location.reload();" 
                            style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                        🔄 Refresh Page
                    </button>
                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" 
                            style="background: #6b7280; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function showGPSUnavailableHelp() {
    const modal = document.createElement('div');
    modal.innerHTML = `
        <div style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        ">
            <div style="
                background: white;
                padding: 24px;
                border-radius: 12px;
                max-width: 500px;
                width: 90%;
            ">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="margin: 0; color: #1f2937;">📡 GPS Unavailable</h3>
                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" 
                            style="background: none; border: none; font-size: 20px; cursor: pointer; color: #6b7280;">✕</button>
                </div>
                
                <p style="color: #374151; margin-bottom: 16px;">
                    <strong>GPS location is currently unavailable.</strong> Try these solutions:
                </p>
                
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #1f2937; margin-bottom: 8px;">📱 Mobile Devices:</h4>
                    <ol style="color: #374151; padding-left: 20px;">
                        <li>Go to device Settings → Location</li>
                        <li>Turn on "Use location" or "Location Services"</li>
                        <li>Select "High accuracy" mode (Android)</li>
                        <li>Go outdoors for better GPS signal</li>
                    </ol>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #1f2937; margin-bottom: 8px;">💻 Desktop/Laptop:</h4>
                    <ol style="color: #374151; padding-left: 20px;">
                        <li>Ensure you have internet connection</li>
                        <li>Try using a different browser</li>
                        <li>Check if location services are enabled</li>
                    </ol>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove(); window.location.reload();" 
                            style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                        🔄 Try Again
                    </button>
                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" 
                            style="background: #6b7280; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function showTimeoutHelp() {
    const modal = document.createElement('div');
    modal.innerHTML = `
        <div style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        ">
            <div style="
                background: white;
                padding: 24px;
                border-radius: 12px;
                max-width: 500px;
                width: 90%;
            ">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="margin: 0; color: #1f2937;">⏱️ GPS Timeout</h3>
                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" 
                            style="background: none; border: none; font-size: 20px; cursor: pointer; color: #6b7280;">✕</button>
                </div>
                
                <p style="color: #374151; margin-bottom: 16px;">
                    <strong>GPS detection took too long.</strong> This usually means:
                </p>
                
                <ul style="color: #374151; padding-left: 20px; margin-bottom: 20px;">
                    <li>Poor GPS signal (try going outdoors)</li>
                    <li>Slow internet connection</li>
                    <li>GPS hardware issues</li>
                    <li>Device in power saving mode</li>
                </ul>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove(); window.location.reload();" 
                            style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                        🔄 Try Again
                    </button>
                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" 
                            style="background: #6b7280; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function showGeneralHelp() {
    const modal = document.createElement('div');
    modal.innerHTML = `
        <div style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        ">
            <div style="
                background: white;
                padding: 24px;
                border-radius: 12px;
                max-width: 500px;
                width: 90%;
            ">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="margin: 0; color: #1f2937;">🌍 GPS Help</h3>
                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" 
                            style="background: none; border: none; font-size: 20px; cursor: pointer; color: #6b7280;">✕</button>
                </div>
                
                <p style="color: #374151; margin-bottom: 16px;">
                    <strong>Having trouble with GPS location?</strong> Here are some common solutions:
                </p>
                
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #1f2937; margin-bottom: 8px;">🔒 Permission Issues:</h4>
                    <ul style="color: #374151; padding-left: 20px;">
                        <li>Click the lock icon in your browser's address bar</li>
                        <li>Change location permission to "Allow"</li>
                        <li>Refresh the page</li>
                    </ul>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #1f2937; margin-bottom: 8px;">📱 Device Settings:</h4>
                    <ul style="color: #374151; padding-left: 20px;">
                        <li>Enable GPS/Location Services in device settings</li>
                        <li>Go outdoors for better GPS signal</li>
                        <li>Check internet connection</li>
                    </ul>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove(); window.location.reload();" 
                            style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                        🔄 Refresh Page
                    </button>
                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" 
                            style="background: #6b7280; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}
</script>

<style>
.gps-help-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

.gps-help-btn:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
}

.gps-help-btn:active {
    transform: translateY(0);
}

.gps-help-icon {
    font-size: 16px;
}

.gps-help-text {
    font-size: 14px;
}

@media (max-width: 640px) {
    .gps-help-btn {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    .gps-help-icon {
        font-size: 14px;
    }
    
    .gps-help-text {
        font-size: 12px;
    }
}
</style>
