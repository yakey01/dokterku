// Debugging script for Laporan Bulanan tab reset issue
// Run this in browser console: copy and paste the entire script

console.log('🔧 DEBUGGING LAPORAN BULANAN TAB RESET ISSUE');
console.log('================================================');

// Function to monitor tab state changes
function monitorTabChanges() {
    let lastState = null;
    let changeCount = 0;
    
    const monitor = setInterval(() => {
        const currentState = window.getCurrentTabState?.();
        
        if (currentState && JSON.stringify(currentState) !== JSON.stringify(lastState)) {
            changeCount++;
            console.log(`📊 [CHANGE #${changeCount}] Tab State Changed:`, {
                timestamp: new Date().toLocaleTimeString(),
                previous: lastState,
                current: currentState,
                financeTabChanged: lastState?.activeFinanceTab !== currentState?.activeFinanceTab
            });
            
            if (lastState?.activeFinanceTab === 'monthly' && currentState?.activeFinanceTab === 'overview') {
                console.error('🚨 DETECTED: Laporan Bulanan reset to Overview!');
                console.error('🔍 Reset context:', {
                    when: new Date().toLocaleTimeString(),
                    wasRefreshing: currentState.isRefreshing,
                    mainTab: currentState.activeTab,
                    changeNumber: changeCount
                });
            }
            
            lastState = { ...currentState };
        }
    }, 100); // Check every 100ms
    
    console.log('👁️ Tab monitor started - will run for 2 minutes');
    
    // Stop monitoring after 2 minutes
    setTimeout(() => {
        clearInterval(monitor);
        console.log('⏹️ Tab monitoring stopped');
    }, 120000);
    
    return monitor;
}

// Function to test Laporan Bulanan tab
function testLaporanBulanan() {
    console.log('🧪 TESTING LAPORAN BULANAN TAB...');
    
    const currentState = window.getCurrentTabState?.();
    console.log('📊 Current state before test:', currentState);
    
    // First ensure we're on Finance tab
    if (currentState?.activeTab !== 'finance') {
        console.log('📋 Switching to Finance tab first...');
        // You need to click Finance tab manually first
        alert('Please click the Finance tab first, then run testLaporanBulanan() again');
        return;
    }
    
    console.log('📊 Forcing Laporan Bulanan tab...');
    window.forceFinanceTab?.('monthly');
    
    // Check state every 500ms for 10 seconds
    let checkCount = 0;
    const checker = setInterval(() => {
        checkCount++;
        const state = window.getCurrentTabState?.();
        console.log(`⏱️ Check #${checkCount} (${checkCount * 0.5}s):`, state?.activeFinanceTab);
        
        if (state?.activeFinanceTab !== 'monthly') {
            console.error(`🚨 TAB RESET at ${checkCount * 0.5}s! Now showing:`, state?.activeFinanceTab);
            clearInterval(checker);
        }
        
        if (checkCount >= 20) { // 10 seconds total
            console.log('✅ Test completed - tab remained stable for 10 seconds');
            clearInterval(checker);
        }
    }, 500);
}

// Function to check if debugging tools are available
function checkDebugTools() {
    const tools = {
        getCurrentTabState: typeof window.getCurrentTabState === 'function',
        forceFinanceTab: typeof window.forceFinanceTab === 'function',
        debugTabReset: typeof window.debugTabReset === 'function'
    };
    
    console.log('🔧 Available debugging tools:', tools);
    
    if (Object.values(tools).every(Boolean)) {
        console.log('✅ All debugging tools available');
        return true;
    } else {
        console.error('❌ Some debugging tools missing - make sure you\'re on the Manager Dashboard');
        return false;
    }
}

// Main debugging function
function debugLaporanBulanan() {
    if (!checkDebugTools()) {
        console.log('💡 Navigate to Manager Dashboard first, then run this again');
        return;
    }
    
    console.log('🎯 STARTING COMPREHENSIVE DEBUG...');
    console.log('');
    
    console.log('STEP 1: Monitor tab changes');
    const monitor = monitorTabChanges();
    
    console.log('');
    console.log('STEP 2: Current state check');
    console.log('Current state:', window.getCurrentTabState?.());
    
    console.log('');
    console.log('STEP 3: Manual test instructions');
    console.log('📝 Now manually:');
    console.log('   1. Click Finance tab (bottom navigation)');
    console.log('   2. Click "Laporan Bulanan" tab');
    console.log('   3. Watch console for reset detection');
    console.log('');
    console.log('OR run testLaporanBulanan() for automated test');
    
    // Return monitor ID so it can be stopped manually
    return monitor;
}

// Export functions to global scope
window.debugLaporanBulanan = debugLaporanBulanan;
window.testLaporanBulanan = testLaporanBulanan;
window.monitorTabChanges = monitorTabChanges;

console.log('🚀 DEBUGGING READY!');
console.log('Run: debugLaporanBulanan()');
console.log('Or: testLaporanBulanan() (after clicking Finance tab)');