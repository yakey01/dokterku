// Real-time Tindakan Status Updates via Broadcasting
// Handles status synchronization between bendahara validation-center and petugas tindakans

document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on relevant pages
    const isValidationCenter = window.location.pathname.includes('validation-center');
    const isTindakansPage = window.location.pathname.includes('tindakans');
    
    if (!isValidationCenter && !isTindakansPage) {
        return; // Only run on relevant pages
    }

    console.log('🔄 Initializing real-time tindakan status updates...');

    // Initialize Laravel Echo for broadcasting (if available)
    if (typeof window.Echo !== 'undefined') {
        // Listen to tindakan status updates
        window.Echo.channel('tindakan-updates')
            .listen('.status.updated', (data) => {
                console.log('📊 Received tindakan status update:', data);
                
                // Handle real-time table updates
                handleStatusUpdate(data);
            });

        // Listen to private channels for authenticated users
        if (window.Laravel && window.Laravel.user) {
            window.Echo.private('petugas-dashboard')
                .listen('.status.updated', (data) => {
                    console.log('👤 Received private tindakan update:', data);
                    handleStatusUpdate(data);
                });

            window.Echo.private('bendahara-validation')
                .listen('.status.updated', (data) => {
                    console.log('💰 Received bendahara validation update:', data);
                    handleStatusUpdate(data);
                });
        }
    } else {
        console.log('⚠️ Laravel Echo not available - using fallback polling');
        // Fallback to more frequent polling if Echo not available
        if (typeof window.Livewire !== 'undefined') {
            setInterval(() => {
                window.Livewire.components.forEach(component => {
                    if (component.name && component.name.includes('table')) {
                        component.call('$refresh');
                    }
                });
            }, 3000); // 3 second intervals for fallback
        }
    }

    function handleStatusUpdate(data) {
        try {
            // Find the table row for this tindakan
            const tableRow = document.querySelector(`[wire\\:key*="tindakan-${data.id}"]`) ||
                            document.querySelector(`tr[data-id="${data.id}"]`);
            
            if (tableRow) {
                // Update status badge visually
                const statusCell = tableRow.querySelector('[class*="status"]');
                if (statusCell) {
                    // Add visual feedback for update
                    statusCell.classList.add('animate-pulse');
                    setTimeout(() => {
                        statusCell.classList.remove('animate-pulse');
                    }, 1000);
                    
                    console.log(`✅ Updated status for tindakan ${data.id}: ${data.old_status_validasi} → ${data.status_validasi}`);
                }
            }

            // Force Livewire table refresh for immediate update
            if (typeof window.Livewire !== 'undefined') {
                window.Livewire.components.forEach(component => {
                    if (component.name && (component.name.includes('table') || component.name.includes('tindakan'))) {
                        component.call('$refresh');
                    }
                });
            }

            // Show toast notification for significant status changes
            if (data.old_status_validasi !== data.status_validasi) {
                const statusText = {
                    'disetujui': '✅ Disetujui',
                    'ditolak': '❌ Ditolak', 
                    'pending': '⏳ Menunggu Validasi'
                };

                showStatusNotification(
                    `Status Updated: ${data.procedure_name}`,
                    `${statusText[data.old_status_validasi]} → ${statusText[data.status_validasi]}`,
                    data.status_validasi
                );
            }

        } catch (error) {
            console.error('❌ Error handling status update:', error);
        }
    }

    function showStatusNotification(title, message, status) {
        // Create simple toast notification
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm
            ${status === 'disetujui' ? 'bg-green-500' : 
              status === 'ditolak' ? 'bg-red-500' : 'bg-yellow-500'} 
            text-white transition-all duration-300 transform translate-x-full`;
        
        toast.innerHTML = `
            <div class="font-semibold">${title}</div>
            <div class="text-sm opacity-90">${message}</div>
        `;
        
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);
        
        // Animate out after 3 seconds
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }

    console.log('✅ Real-time tindakan status updates initialized');
});