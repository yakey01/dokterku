<div class="world-class-empty-state">
    <div class="empty-state-container">
        <!-- Animated Icon -->
        <div class="empty-icon-wrapper">
            <svg class="empty-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        
        <!-- Main Message -->
        <h3 class="empty-title">Belum Ada Data Pasien</h3>
        <p class="empty-description">
            Mulai input data pasien harian untuk perhitungan jaspel
        </p>
        
        <!-- Action Button -->
        @if(isset($createUrl))
        <div class="empty-action">
            <a href="{{ $createUrl }}" class="empty-btn">
                <svg class="empty-btn-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Tambah Data Pasien</span>
            </a>
        </div>
        @endif
        
        <!-- Quick Stats -->
        <div class="empty-stats">
            <div class="stat-item">
                <div class="stat-icon">📊</div>
                <div class="stat-text">
                    <span class="stat-value">Rp 7.000</span>
                    <span class="stat-label">Per Pasien Umum</span>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">💰</div>
                <div class="stat-text">
                    <span class="stat-value">Rp 5.000</span>
                    <span class="stat-label">Per Pasien BPJS</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.world-class-empty-state {
    padding: 4rem 2rem;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
    background: linear-gradient(135deg, #fef3c7 0%, #ffffff 50%, #fef3c7 100%);
    border-radius: 12px;
    margin: 2rem 0;
}

.empty-state-container {
    text-align: center;
    max-width: 500px;
}

.empty-icon-wrapper {
    margin: 0 auto 2rem;
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border-radius: 50%;
    box-shadow: 0 8px 24px rgba(245, 158, 11, 0.3);
    animation: pulse 2s infinite;
}

.empty-icon {
    width: 60px;
    height: 60px;
    color: white;
}

.empty-title {
    font-size: 1.75rem !important;
    font-weight: 700 !important;
    color: #000000 !important;
    margin-bottom: 0.75rem !important;
}

.empty-description {
    font-size: 1.125rem !important;
    color: #4b5563 !important;
    font-weight: 500 !important;
    margin-bottom: 2rem !important;
}

.empty-action {
    margin-bottom: 3rem;
}

.empty-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.75rem;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    font-weight: 600;
    font-size: 1rem;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.empty-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
}

.empty-btn-icon {
    width: 20px;
    height: 20px;
}

.empty-stats {
    display: flex;
    justify-content: center;
    gap: 2rem;
    padding-top: 2rem;
    border-top: 2px solid #e5e7eb;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.stat-icon {
    font-size: 2rem;
}

.stat-text {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.stat-value {
    font-size: 1.125rem;
    font-weight: 700;
    color: #000000;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.9;
    }
}

@media (max-width: 640px) {
    .empty-stats {
        flex-direction: column;
        gap: 1rem;
    }
    
    .stat-item {
        justify-content: center;
    }
}
</style>