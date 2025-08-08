/**
 * GPS Help System for Dokterku Application
 * Provides comprehensive troubleshooting guidance for GPS detection issues
 */

(function() {
    'use strict';

    // GPS Help System Configuration
    const GPS_HELP_CONFIG = {
        errorTypes: {
            permission_denied: {
                title: '🚫 GPS Permission Denied',
                description: 'Your browser has denied access to your location.',
                solutions: [
                    {
                        title: '🔒 Enable Location Access',
                        steps: [
                            'Look for the location icon (🔒) in your browser address bar',
                            'Click on the location icon',
                            'Select "Allow" or "Always allow" for location access',
                            'Refresh the page and try again'
                        ]
                    },
                    {
                        title: '🌐 Browser Settings',
                        steps: [
                            'Open your browser settings',
                            'Search for "Location" or "Privacy"',
                            'Enable location access for this website',
                            'Restart your browser'
                        ]
                    },
                    {
                        title: '📱 Mobile Device Settings',
                        steps: [
                            'Go to your device Settings',
                            'Find "Privacy" or "Location Services"',
                            'Enable location services',
                            'Allow location access for your browser'
                        ]
                    }
                ]
            },
            position_unavailable: {
                title: '📡 GPS Signal Unavailable',
                description: 'Your device cannot determine your current location.',
                solutions: [
                    {
                        title: '🌍 Move to Open Area',
                        steps: [
                            'Go outside or near a window',
                            'Move away from tall buildings',
                            'Avoid underground locations',
                            'Wait 30-60 seconds for GPS to acquire signal'
                        ]
                    },
                    {
                        title: '📱 Check Device GPS',
                        steps: [
                            'Enable GPS/Location Services on your device',
                            'Check if GPS is working in other apps',
                            'Restart your device if needed',
                            'Try using a different device'
                        ]
                    },
                    {
                        title: '🌐 Check Internet Connection',
                        steps: [
                            'Ensure you have a stable internet connection',
                            'Try switching between WiFi and mobile data',
                            'Check if other location-based apps work',
                            'Restart your internet connection'
                        ]
                    }
                ]
            },
            timeout: {
                title: '⏰ GPS Request Timeout',
                description: 'GPS detection is taking too long to respond.',
                solutions: [
                    {
                        title: '🔄 Try Again',
                        steps: [
                            'Wait a few seconds and try again',
                            'Move to a different location',
                            'Check your internet connection',
                            'Restart your browser'
                        ]
                    },
                    {
                        title: '📱 Optimize Device Settings',
                        steps: [
                            'Enable high-accuracy GPS mode',
                            'Close other apps using GPS',
                            'Clear browser cache and cookies',
                            'Update your browser to the latest version'
                        ]
                    },
                    {
                        title: '🌍 Environmental Factors',
                        steps: [
                            'Move to an outdoor location',
                            'Avoid areas with poor GPS signal',
                            'Wait for better weather conditions',
                            'Try during different times of day'
                        ]
                    }
                ]
            },
            https_required: {
                title: '🔒 HTTPS Connection Required',
                description: 'GPS location detection requires a secure connection.',
                solutions: [
                    {
                        title: '🌐 Use HTTPS',
                        steps: [
                            'Change the URL from http:// to https://',
                            'Contact your administrator to enable HTTPS',
                            'Use localhost for development testing',
                            'Access the application through a secure connection'
                        ]
                    },
                    {
                        title: '🏠 Local Development',
                        steps: [
                            'Use localhost instead of IP address',
                            'Access via http://localhost:8000',
                            'Configure your development environment for HTTPS',
                            'Use a local SSL certificate'
                        ]
                    }
                ]
            },
            browser_not_supported: {
                title: '❌ Browser Not Supported',
                description: 'Your browser does not support GPS location detection.',
                solutions: [
                    {
                        title: '🌐 Update Browser',
                        steps: [
                            'Update to the latest version of your browser',
                            'Try using Chrome, Firefox, Safari, or Edge',
                            'Enable JavaScript in your browser',
                            'Clear browser cache and cookies'
                        ]
                    },
                    {
                        title: '📱 Use Mobile Browser',
                        steps: [
                            'Try accessing from a mobile device',
                            'Use the device\'s native browser',
                            'Ensure mobile GPS is enabled',
                            'Check mobile browser permissions'
                        ]
                    }
                ]
            }
        },
        
        // Common troubleshooting steps
        commonSteps: [
            {
                title: '🔧 General Troubleshooting',
                steps: [
                    'Refresh the page and try again',
                    'Clear browser cache and cookies',
                    'Disable browser extensions temporarily',
                    'Try using incognito/private browsing mode',
                    'Restart your device and browser'
                ]
            },
            {
                title: '📱 Mobile Device Tips',
                steps: [
                    'Ensure location services are enabled',
                    'Check if GPS works in other apps',
                    'Try switching between WiFi and mobile data',
                    'Update your device\'s operating system',
                    'Check device battery level (low battery can affect GPS)'
                ]
            },
            {
                title: '🌐 Network Considerations',
                steps: [
                    'Check your internet connection stability',
                    'Try switching between different networks',
                    'Disable VPN or proxy if using one',
                    'Check firewall settings',
                    'Contact your network administrator if on corporate network'
                ]
            }
        ]
    };

    // GPS Help System Class
    class GPSHelpSystem {
        constructor() {
            this.isVisible = false;
            this.currentErrorType = null;
            this.init();
        }

        init() {
            this.createHelpButton();
            this.createHelpModal();
            this.bindEvents();
        }

        createHelpButton() {
            const helpButton = document.createElement('button');
            helpButton.id = 'gps-help-button';
            helpButton.innerHTML = '🆘 GPS Help';
            helpButton.className = 'gps-help-button';
            helpButton.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 10001;
                background: linear-gradient(135deg, #3b82f6, #1d4ed8);
                color: white;
                border: none;
                border-radius: 25px;
                padding: 12px 20px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
                transition: all 0.3s ease;
                display: none;
            `;

            helpButton.addEventListener('mouseenter', () => {
                helpButton.style.transform = 'translateY(-2px)';
                helpButton.style.boxShadow = '0 6px 16px rgba(59, 130, 246, 0.4)';
            });

            helpButton.addEventListener('mouseleave', () => {
                helpButton.style.transform = 'translateY(0)';
                helpButton.style.boxShadow = '0 4px 12px rgba(59, 130, 246, 0.3)';
            });

            helpButton.addEventListener('click', () => {
                this.showHelp();
            });

            document.body.appendChild(helpButton);
        }

        createHelpModal() {
            const modal = document.createElement('div');
            modal.id = 'gps-help-modal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                z-index: 10002;
                display: none;
                align-items: center;
                justify-content: center;
                font-family: system-ui, -apple-system, sans-serif;
            `;

            modal.innerHTML = `
                <div class="gps-help-content" style="
                    background: white;
                    border-radius: 16px;
                    max-width: 800px;
                    width: 90%;
                    max-height: 90vh;
                    overflow-y: auto;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                    position: relative;
                ">
                    <div class="gps-help-header" style="
                        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
                        color: white;
                        padding: 24px;
                        border-radius: 16px 16px 0 0;
                        position: sticky;
                        top: 0;
                        z-index: 1;
                    ">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h2 id="gps-help-title" style="margin: 0; font-size: 24px; font-weight: 700;">
                                    🆘 GPS Help Center
                                </h2>
                                <p id="gps-help-description" style="margin: 8px 0 0 0; opacity: 0.9; font-size: 16px;">
                                    Get help with GPS location detection issues
                                </p>
                            </div>
                            <button id="gps-help-close" style="
                                background: rgba(255, 255, 255, 0.2);
                                border: none;
                                color: white;
                                border-radius: 50%;
                                width: 40px;
                                height: 40px;
                                cursor: pointer;
                                font-size: 18px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                transition: background 0.3s ease;
                            ">×</button>
                        </div>
                    </div>
                    
                    <div class="gps-help-body" style="padding: 24px;">
                        <div id="gps-help-solutions"></div>
                        
                        <div class="gps-help-common" style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
                            <h3 style="color: #374151; margin: 0 0 16px 0; font-size: 18px;">🔧 General Troubleshooting</h3>
                            <div id="gps-help-common-steps"></div>
                        </div>
                        
                        <div class="gps-help-actions" style="
                            margin-top: 32px;
                            padding-top: 24px;
                            border-top: 1px solid #e5e7eb;
                            display: flex;
                            gap: 12px;
                            justify-content: flex-end;
                        ">
                            <button id="gps-help-retry" style="
                                background: #10b981;
                                color: white;
                                border: none;
                                padding: 12px 24px;
                                border-radius: 8px;
                                font-weight: 600;
                                cursor: pointer;
                                transition: background 0.3s ease;
                            ">🔄 Try GPS Again</button>
                            <button id="gps-help-manual" style="
                                background: #6b7280;
                                color: white;
                                border: none;
                                padding: 12px 24px;
                                border-radius: 8px;
                                font-weight: 600;
                                cursor: pointer;
                                transition: background 0.3s ease;
                            ">📝 Manual Input</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
        }

        bindEvents() {
            // Close button
            document.getElementById('gps-help-close').addEventListener('click', () => {
                this.hideHelp();
            });

            // Modal backdrop click
            document.getElementById('gps-help-modal').addEventListener('click', (e) => {
                if (e.target.id === 'gps-help-modal') {
                    this.hideHelp();
                }
            });

            // Retry button
            document.getElementById('gps-help-retry').addEventListener('click', () => {
                this.hideHelp();
                if (typeof window.autoDetectLocation === 'function') {
                    window.autoDetectLocation();
                }
            });

            // Manual input button
            document.getElementById('gps-help-manual').addEventListener('click', () => {
                this.hideHelp();
                this.showManualInputGuide();
            });

            // Keyboard events
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isVisible) {
                    this.hideHelp();
                }
            });
        }

        showHelp(errorType = null) {
            this.currentErrorType = errorType;
            this.isVisible = true;

            const modal = document.getElementById('gps-help-modal');
            const title = document.getElementById('gps-help-title');
            const description = document.getElementById('gps-help-description');
            const solutions = document.getElementById('gps-help-solutions');

            if (errorType && GPS_HELP_CONFIG.errorTypes[errorType]) {
                const errorConfig = GPS_HELP_CONFIG.errorTypes[errorType];
                title.textContent = errorConfig.title;
                description.textContent = errorConfig.description;
                this.renderSolutions(errorConfig.solutions);
            } else {
                title.textContent = '🆘 GPS Help Center';
                description.textContent = 'Get help with GPS location detection issues';
                this.renderGeneralHelp();
            }

            this.renderCommonSteps();
            modal.style.display = 'flex';
            document.getElementById('gps-help-button').style.display = 'none';
        }

        hideHelp() {
            this.isVisible = false;
            document.getElementById('gps-help-modal').style.display = 'none';
            document.getElementById('gps-help-button').style.display = 'block';
        }

        renderSolutions(solutions) {
            const container = document.getElementById('gps-help-solutions');
            container.innerHTML = '';

            solutions.forEach((solution, index) => {
                const solutionDiv = document.createElement('div');
                solutionDiv.style.cssText = `
                    margin-bottom: 24px;
                    padding: 20px;
                    background: #f9fafb;
                    border-radius: 12px;
                    border-left: 4px solid #3b82f6;
                `;

                solutionDiv.innerHTML = `
                    <h4 style="margin: 0 0 16px 0; color: #1f2937; font-size: 16px; font-weight: 600;">
                        ${solution.title}
                    </h4>
                    <ol style="margin: 0; padding-left: 20px; color: #4b5563;">
                        ${solution.steps.map(step => `<li style="margin-bottom: 8px;">${step}</li>`).join('')}
                    </ol>
                `;

                container.appendChild(solutionDiv);
            });
        }

        renderGeneralHelp() {
            const container = document.getElementById('gps-help-solutions');
            container.innerHTML = `
                <div style="
                    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
                    padding: 24px;
                    border-radius: 12px;
                    margin-bottom: 24px;
                ">
                    <h3 style="margin: 0 0 16px 0; color: #1e40af; font-size: 18px;">
                        🌍 GPS Location Detection Guide
                    </h3>
                    <p style="margin: 0 0 16px 0; color: #1e40af; line-height: 1.6;">
                        GPS location detection helps ensure accurate attendance tracking. 
                        Follow these steps to enable GPS on your device:
                    </p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                        <div style="background: white; padding: 16px; border-radius: 8px;">
                            <h4 style="margin: 0 0 8px 0; color: #1f2937;">📱 Mobile Device</h4>
                            <ul style="margin: 0; padding-left: 16px; color: #4b5563; font-size: 14px;">
                                <li>Enable Location Services</li>
                                <li>Allow browser location access</li>
                                <li>Move to open area</li>
                                <li>Wait for GPS signal</li>
                            </ul>
                        </div>
                        <div style="background: white; padding: 16px; border-radius: 8px;">
                            <h4 style="margin: 0 0 8px 0; color: #1f2937;">💻 Desktop Browser</h4>
                            <ul style="margin: 0; padding-left: 16px; color: #4b5563; font-size: 14px;">
                                <li>Use HTTPS connection</li>
                                <li>Allow location permission</li>
                                <li>Update browser version</li>
                                <li>Clear browser cache</li>
                            </ul>
                        </div>
                    </div>
                </div>
            `;
        }

        renderCommonSteps() {
            const container = document.getElementById('gps-help-common-steps');
            container.innerHTML = '';

            GPS_HELP_CONFIG.commonSteps.forEach(stepGroup => {
                const stepDiv = document.createElement('div');
                stepDiv.style.cssText = `
                    margin-bottom: 20px;
                    padding: 16px;
                    background: #f3f4f6;
                    border-radius: 8px;
                `;

                stepDiv.innerHTML = `
                    <h4 style="margin: 0 0 12px 0; color: #374151; font-size: 14px; font-weight: 600;">
                        ${stepGroup.title}
                    </h4>
                    <ul style="margin: 0; padding-left: 16px; color: #6b7280; font-size: 13px;">
                        ${stepGroup.steps.map(step => `<li style="margin-bottom: 4px;">${step}</li>`).join('')}
                    </ul>
                `;

                container.appendChild(stepDiv);
            });
        }

        showManualInputGuide() {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                z-index: 10003;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: system-ui, -apple-system, sans-serif;
            `;

            modal.innerHTML = `
                <div style="
                    background: white;
                    border-radius: 16px;
                    max-width: 600px;
                    width: 90%;
                    padding: 24px;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                ">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0; color: #1f2937; font-size: 20px;">📝 Manual Coordinate Input</h3>
                        <button onclick="this.closest('[style*=\'position: fixed\']').remove()" style="
                            background: none;
                            border: none;
                            font-size: 24px;
                            cursor: pointer;
                            color: #6b7280;
                        ">×</button>
                    </div>
                    
                    <div style="color: #4b5563; line-height: 1.6; margin-bottom: 20px;">
                        <p>If GPS detection is not working, you can manually enter your coordinates:</p>
                        
                        <div style="background: #f3f4f6; padding: 16px; border-radius: 8px; margin: 16px 0;">
                            <h4 style="margin: 0 0 8px 0; color: #1f2937;">📍 How to get coordinates:</h4>
                            <ol style="margin: 0; padding-left: 16px;">
                                <li>Open <a href="https://maps.google.com" target="_blank" style="color: #3b82f6;">Google Maps</a></li>
                                <li>Right-click on your location</li>
                                <li>Copy the coordinates (e.g., -6.2088, 106.8456)</li>
                                <li>Paste into the latitude and longitude fields</li>
                            </ol>
                        </div>
                        
                        <div style="background: #fef3c7; padding: 16px; border-radius: 8px; margin: 16px 0;">
                            <h4 style="margin: 0 0 8px 0; color: #92400e;">⚠️ Important Notes:</h4>
                            <ul style="margin: 0; padding-left: 16px; color: #92400e;">
                                <li>Use decimal format (e.g., -6.2088, not -6°12'31")</li>
                                <li>Latitude ranges from -90 to 90</li>
                                <li>Longitude ranges from -180 to 180</li>
                                <li>Ensure coordinates are accurate for proper attendance validation</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button onclick="this.closest('[style*=\'position: fixed\']').remove()" style="
                            padding: 8px 16px;
                            border: 1px solid #d1d5db;
                            background: white;
                            border-radius: 6px;
                            color: #374151;
                            cursor: pointer;
                        ">Close</button>
                        <a href="https://maps.google.com" target="_blank" style="
                            padding: 8px 16px;
                            background: #3b82f6;
                            border: none;
                            border-radius: 6px;
                            color: white;
                            cursor: pointer;
                            text-decoration: none;
                            display: inline-block;
                        ">🌐 Open Google Maps</a>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
        }

        // Public methods for external use
        showErrorHelp(errorType) {
            this.showHelp(errorType);
        }

        showGeneralHelp() {
            this.showHelp();
        }

        hide() {
            this.hideHelp();
        }
    }

    // Initialize GPS Help System
    const gpsHelpSystem = new GPSHelpSystem();

    // Global functions for external access
    window.GPSHelpSystem = {
        showErrorHelp: (errorType) => gpsHelpSystem.showErrorHelp(errorType),
        showGeneralHelp: () => gpsHelpSystem.showGeneralHelp(),
        hide: () => gpsHelpSystem.hide()
    };

    // Auto-show help button when GPS errors occur
    const originalConsoleError = console.error;
    console.error = function(...args) {
        const message = args[0]?.toString?.() || '';
        
        // Check for GPS-related errors
        if (message.includes('GPS') || message.includes('geolocation') || message.includes('location')) {
            const helpButton = document.getElementById('gps-help-button');
            if (helpButton) {
                helpButton.style.display = 'block';
            }
        }
        
        originalConsoleError.apply(console, args);
    };

    // Show help button when GPS detection fails
    if (typeof window.addEventListener === 'function') {
        window.addEventListener('gps-error', (event) => {
            const helpButton = document.getElementById('gps-help-button');
            if (helpButton) {
                helpButton.style.display = 'block';
            }
        });
    }

    console.log('✅ GPS Help System loaded successfully');
    console.log('🆘 Use GPSHelpSystem.showErrorHelp(errorType) to show specific help');
    console.log('🆘 Use GPSHelpSystem.showGeneralHelp() to show general help');

})();