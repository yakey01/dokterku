{{-- World-Class Healthcare Top Bar Component --}}
<div class="petugas-top-bar">
    {{-- Main Header Section --}}
    <div class="top-bar-header">
        <div class="header-left">
            {{-- Hospital Branding --}}
            <div class="hospital-brand">
                <div class="brand-icon">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                </div>
                <div class="brand-text">
                    <h1 class="brand-title">Klinik DokterKu</h1>
                    <p class="brand-subtitle">Healthcare Management System</p>
                </div>
            </div>

            {{-- Breadcrumb Navigation --}}
            <div class="breadcrumb-nav">
                <nav class="breadcrumb">
                    <a href="/petugas" class="breadcrumb-item">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                        </svg>
                        Dashboard
                    </a>
                    <span class="breadcrumb-separator">/</span>
                    <a href="/petugas/jumlah-pasien-harians" class="breadcrumb-item">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Data Jumlah Pasien Harian
                    </a>
                    <span class="breadcrumb-separator">/</span>
                    <span class="breadcrumb-current">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Input Data Baru
                    </span>
                </nav>
            </div>
        </div>

        <div class="header-right">
            {{-- Save Status Indicator --}}
            <div class="save-status" id="saveStatus">
                <div class="status-indicator status-ready">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Siap Input</span>
                </div>
            </div>

            {{-- User Context --}}
            <div class="user-context">
                <div class="user-avatar">
                    <div class="avatar-circle">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                </div>
                <div class="user-info">
                    <p class="user-name">{{ auth()->user()->name }}</p>
                    <p class="user-role">
                        <span class="role-badge role-petugas">Petugas</span>
                    </p>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="quick-actions">
                <button type="button" class="action-btn" onclick="window.history.back()" title="Kembali">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </button>
                <button type="button" class="action-btn" onclick="location.reload()" title="Refresh">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Page Context Section --}}
    <div class="page-context">
        <div class="context-content">
            <div class="page-title-section">
                <div class="page-icon">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div class="page-title-content">
                    <h2 class="page-title">Input Data Jumlah Pasien Harian</h2>
                    <p class="page-description">
                        Masukkan data jumlah pasien per hari untuk perhitungan jasa pelayanan (jaspel) dokter
                    </p>
                </div>
            </div>

            {{-- Progress Indicator --}}
            <div class="form-progress-section">
                <div class="progress-header">
                    <span class="progress-label">Progress Form</span>
                    <span class="progress-percentage" id="progressPercentage">0%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-steps">
                    <div class="step step-1 active" data-step="1">
                        <div class="step-circle">1</div>
                        <span class="step-label">Info Dasar</span>
                    </div>
                    <div class="step step-2" data-step="2">
                        <div class="step-circle">2</div>
                        <span class="step-label">Data Pasien</span>
                    </div>
                    <div class="step step-3" data-step="3">
                        <div class="step-circle">3</div>
                        <span class="step-label">Review</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Help & Tips Section --}}
        <div class="help-section">
            <div class="help-card">
                <div class="help-icon">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="help-content">
                    <h4 class="help-title">Tips Pengisian</h4>
                    <ul class="help-tips">
                        <li>Pastikan tanggal sesuai dengan hari pelayanan</li>
                        <li>Pilih dokter yang sesuai dengan poli yang dipilih</li>
                        <li>Data akan otomatis menghitung jaspel sesuai formula</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Top Bar Styling --}}
<style>
/* =================================
   WORLD-CLASS HEALTHCARE TOP BAR
   Professional Medical UI 2025
   ================================= */

.petugas-top-bar {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-bottom: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 0;
    z-index: 1000;
    margin: -24px -24px 24px -24px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", sans-serif;
}

/* Header Section */
.top-bar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid #f1f5f9;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 32px;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

/* Hospital Branding */
.hospital-brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.brand-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2);
}

.brand-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.brand-title {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    line-height: 1.2;
}

.brand-subtitle {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    margin: 0;
    line-height: 1;
}

/* Breadcrumb Navigation */
.breadcrumb-nav {
    display: flex;
    align-items: center;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.breadcrumb-item {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #64748b;
    text-decoration: none;
    padding: 6px 10px;
    border-radius: 6px;
    transition: all 0.15s ease;
    font-weight: 500;
}

.breadcrumb-item:hover {
    background: #f1f5f9;
    color: #3b82f6;
}

.breadcrumb-separator {
    color: #cbd5e1;
    font-weight: 400;
}

.breadcrumb-current {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #3b82f6;
    font-weight: 600;
    padding: 6px 10px;
    background: rgba(59, 130, 246, 0.05);
    border-radius: 6px;
    border: 1px solid rgba(59, 130, 246, 0.1);
}

/* Save Status Indicator */
.save-status {
    display: flex;
    align-items: center;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.15s ease;
}

.status-ready {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.status-saving {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.status-saved {
    background: rgba(34, 197, 94, 0.1);
    color: #16a34a;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

/* User Context */
.user-context {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 16px;
    background: rgba(59, 130, 246, 0.05);
    border: 1px solid rgba(59, 130, 246, 0.1);
    border-radius: 12px;
}

.user-avatar {
    display: flex;
    align-items: center;
}

.avatar-circle {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.user-name {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    line-height: 1.2;
}

.user-role {
    margin: 0;
    line-height: 1;
}

.role-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.role-petugas {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

/* Quick Actions */
.quick-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.action-btn {
    width: 36px;
    height: 36px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s ease;
}

.action-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #3b82f6;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

/* Page Context Section */
.page-context {
    padding: 20px 24px;
    background: rgba(248, 250, 252, 0.5);
    border-bottom: 1px solid #f1f5f9;
}

.context-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 24px;
}

.page-title-section {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
}

.page-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 4px 8px rgba(16, 185, 129, 0.2);
}

.page-title-content {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    line-height: 1.2;
}

.page-description {
    font-size: 14px;
    color: #64748b;
    margin: 0;
    line-height: 1.4;
    max-width: 500px;
}

/* Form Progress Section */
.form-progress-section {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-width: 300px;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.progress-label {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
}

.progress-percentage {
    font-size: 13px;
    font-weight: 700;
    color: #3b82f6;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: #f1f5f9;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 100%);
    width: 0%;
    transition: width 0.3s ease;
    border-radius: 3px;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    opacity: 0.4;
    transition: opacity 0.15s ease;
}

.step.active {
    opacity: 1;
}

.step-circle {
    width: 24px;
    height: 24px;
    background: #f1f5f9;
    border: 2px solid #e2e8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    transition: all 0.15s ease;
}

.step.active .step-circle {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

.step-label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-align: center;
}

/* Help Section */
.help-section {
    display: flex;
    align-items: flex-start;
}

.help-card {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    min-width: 280px;
}

.help-icon {
    width: 36px;
    height: 36px;
    background: rgba(99, 102, 241, 0.1);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6366f1;
    flex-shrink: 0;
}

.help-content {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.help-title {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.help-tips {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.help-tips li {
    font-size: 12px;
    color: #64748b;
    line-height: 1.4;
    position: relative;
    padding-left: 16px;
}

.help-tips li::before {
    content: "•";
    color: #3b82f6;
    font-weight: bold;
    position: absolute;
    left: 0;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .context-content {
        flex-direction: column;
        gap: 16px;
    }
    
    .form-progress-section,
    .help-section {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .top-bar-header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
    
    .header-left {
        flex-direction: column;
        gap: 16px;
        width: 100%;
    }
    
    .header-right {
        width: 100%;
        justify-content: space-between;
    }
    
    .page-title-section {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .page-title {
        font-size: 20px;
    }
    
    .help-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>

{{-- JavaScript for Dynamic Features --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form Progress Tracking
    function updateFormProgress() {
        const requiredFields = document.querySelectorAll('input[required], select[required], textarea[required]');
        const filledFields = Array.from(requiredFields).filter(field => {
            if (field.type === 'checkbox' || field.type === 'radio') {
                return field.checked;
            }
            return field.value.trim() !== '';
        });
        
        const percentage = Math.round((filledFields.length / requiredFields.length) * 100);
        const progressFill = document.getElementById('progressFill');
        const progressPercentage = document.getElementById('progressPercentage');
        
        if (progressFill && progressPercentage) {
            progressFill.style.width = percentage + '%';
            progressPercentage.textContent = percentage + '%';
        }
        
        // Update step indicators
        updateStepIndicators(percentage);
        
        // Update save status
        updateSaveStatus(percentage);
    }
    
    function updateStepIndicators(percentage) {
        const steps = document.querySelectorAll('.step');
        steps.forEach((step, index) => {
            const stepNumber = index + 1;
            const threshold = (stepNumber * 100) / steps.length;
            
            if (percentage >= threshold - 15) {
                step.classList.add('active');
            } else {
                step.classList.remove('active');
            }
        });
    }
    
    function updateSaveStatus(percentage) {
        const saveStatus = document.getElementById('saveStatus');
        const statusIndicator = saveStatus?.querySelector('.status-indicator');
        
        if (!statusIndicator) return;
        
        statusIndicator.className = 'status-indicator';
        
        if (percentage >= 100) {
            statusIndicator.classList.add('status-ready');
            statusIndicator.innerHTML = `
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Siap Simpan</span>
            `;
        } else if (percentage >= 50) {
            statusIndicator.classList.add('status-saving');
            statusIndicator.innerHTML = `
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Sedang Input</span>
            `;
        } else {
            statusIndicator.classList.add('status-ready');
            statusIndicator.innerHTML = `
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Mulai Input</span>
            `;
        }
    }
    
    // Monitor form changes
    document.addEventListener('input', updateFormProgress);
    document.addEventListener('change', updateFormProgress);
    
    // Initial progress update
    setTimeout(updateFormProgress, 500);
    
    // Auto-refresh progress every 2 seconds
    setInterval(updateFormProgress, 2000);
    
    console.log('🎨 World-Class Healthcare Top Bar Initialized!');
});
</script>