/**
 * GPS Diagnostic Tool
 * Simple GPS troubleshooting utility
 */

class GPSDiagnostic {
    constructor() {
        this.results = {};
    }

    async runDiagnostics() {
        console.log('🔍 Starting GPS diagnostics...');
        
        this.results = {
            browser: this.checkBrowser(),
            permissions: await this.checkPermissions(),
            geolocation: await this.testGeolocation(),
            recommendations: []
        };

        this.generateRecommendations();
        return this.results;
    }

    checkBrowser() {
        return {
            userAgent: navigator.userAgent,
            geolocationSupported: 'geolocation' in navigator,
            https: window.location.protocol === 'https:',
            localhost: window.location.hostname === 'localhost'
        };
    }

    async checkPermissions() {
        if ('permissions' in navigator) {
            try {
                const permission = await navigator.permissions.query({ name: 'geolocation' });
                return {
                    state: permission.state,
                    granted: permission.state === 'granted',
                    denied: permission.state === 'denied'
                };
            } catch (error) {
                return { error: error.message };
            }
        }
        return { available: false };
    }

    async testGeolocation() {
        if (!('geolocation' in navigator)) {
            return { error: 'Geolocation not supported' };
        }

        try {
            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            });

            return {
                success: true,
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy
            };
        } catch (error) {
            return {
                success: false,
                error: error.message,
                code: error.code
            };
        }
    }

    generateRecommendations() {
        const { browser, permissions, geolocation } = this.results;

        if (!browser.geolocationSupported) {
            this.results.recommendations.push({
                type: 'error',
                message: 'Browser does not support geolocation'
            });
        }

        if (permissions.denied) {
            this.results.recommendations.push({
                type: 'error',
                message: 'Location permission denied. Enable in browser settings.',
                action: () => this.showPermissionGuide()
            });
        }

        if (!geolocation.success && geolocation.code === 1) {
            this.results.recommendations.push({
                type: 'error',
                message: 'Permission denied. Click to see instructions.',
                action: () => this.showPermissionGuide()
            });
        }
    }

    showPermissionGuide() {
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
                    <h3>🔒 Enable Location Access</h3>
                    <p><strong>To fix GPS permission:</strong></p>
                    <ol>
                        <li>Click the lock icon in your browser's address bar</li>
                        <li>Change location permission to "Allow"</li>
                        <li>Refresh the page</li>
                    </ol>
                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" 
                            style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                        Close
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
}

// Global instance
window.GPSDiagnostic = new GPSDiagnostic();
