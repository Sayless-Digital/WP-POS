// WP POS - Auto-Refresh Module
// Handles automatic page refresh based on user-configured intervals

class AutoRefreshManager {
    constructor(state, uiHelpers) {
        this.state = state;
        this.ui = uiHelpers;
        this.intervalId = null;
        this.countdownId = null;
        this.remainingSeconds = 0;
        this.enabled = false;
        this.intervalMinutes = 5;
        this.initialized = false;
        
        // DOM elements
        this.indicator = null;
        this.countdownDisplay = null;
    }
    
    /**
     * Initialize the auto-refresh system
     * This method can be called multiple times safely (e.g., on page reload)
     */
    init() {
        console.log('🔄 Auto-refresh init() called');
        
        // Stop any existing timers first
        this.stop();
        
        // Get DOM elements
        this.indicator = document.getElementById('auto-refresh-indicator');
        this.countdownDisplay = document.getElementById('auto-refresh-countdown');
        
        if (!this.indicator || !this.countdownDisplay) {
            console.error('❌ Auto-refresh indicator elements not found in DOM');
            return false;
        }
        
        console.log('✓ Auto-refresh DOM elements found');
        
        // Load settings from app state
        this.loadSettings();
        
        // Start if enabled
        if (this.enabled) {
            console.log(`✓ Auto-refresh is enabled (${this.intervalMinutes} minutes)`);
            this.start();
            this.initialized = true;
            return true;
        } else {
            console.log('ℹ️ Auto-refresh is disabled');
            this.initialized = true;
            return false;
        }
    }
    
    /**
     * Load auto-refresh settings from app state
     */
    loadSettings() {
        let settings = this.state.getState('settings');
        
        console.log('🔍 Loading auto-refresh settings from state:', settings);
        
        // Handle case where settings might be wrapped in API response format
        // e.g., {message: 'Success', data: {...}, timestamp: '...'}
        if (settings && settings.data && typeof settings.data === 'object') {
            console.log('⚙️ Settings are wrapped, unwrapping data...');
            settings = settings.data;
        }
        
        if (settings) {
            this.enabled = settings.auto_refresh_enabled === true;
            this.intervalMinutes = parseInt(settings.auto_refresh_interval) || 5;
            
            console.log(`📊 Settings loaded: enabled=${this.enabled}, interval=${this.intervalMinutes}min`);
        } else {
            console.warn('⚠️ No settings found in state, auto-refresh disabled');
            this.enabled = false;
            this.intervalMinutes = 5;
        }
    }
    
    /**
     * Start the auto-refresh timer
     */
    start() {
        console.log('🚀 Starting auto-refresh timer...');
        console.log('   enabled=' + this.enabled + ', intervalMinutes=' + this.intervalMinutes);
        console.log('   indicator=' + (this.indicator ? 'found' : 'null') + ', countdownDisplay=' + (this.countdownDisplay ? 'found' : 'null'));
        
        // Don't call stop() here - it hides the indicator
        // Clear existing timer only if it exists
        if (this.countdownId) {
            clearInterval(this.countdownId);
            this.countdownId = null;
        }
        
        if (!this.enabled || this.intervalMinutes <= 0) {
            console.warn('⚠️ Cannot start: enabled=' + this.enabled + ', interval=' + this.intervalMinutes);
            // Hide indicator if not enabled
            if (this.indicator) {
                this.indicator.classList.add('hidden');
            }
            return false;
        }
        
        // Verify DOM elements are available
        if (!this.indicator || !this.countdownDisplay) {
            console.error('❌ Cannot start: DOM elements not available');
            console.error('   Indicator:', this.indicator);
            console.error('   Countdown Display:', this.countdownDisplay);
            return false;
        }
        
        // Calculate remaining seconds
        this.remainingSeconds = this.intervalMinutes * 60;
        
        // Show indicator
        this.indicator.classList.remove('hidden');
        console.log('✓ Countdown indicator shown');
        
        // Update countdown immediately
        this.updateCountdown();
        
        // Start countdown timer (updates every second)
        this.countdownId = setInterval(() => {
            this.remainingSeconds--;
            
            if (this.remainingSeconds <= 0) {
                this.refresh();
            } else {
                this.updateCountdown();
            }
        }, 1000);
        
        console.log(`✅ Auto-refresh timer started: ${this.intervalMinutes} minutes (${this.remainingSeconds} seconds)`);
        return true;
    }
    
    /**
     * Stop the auto-refresh timer
     */
    stop() {
        if (this.countdownId) {
            clearInterval(this.countdownId);
            this.countdownId = null;
            console.log('⏹️ Auto-refresh timer stopped');
        }
        
        // Hide indicator
        if (this.indicator) {
            this.indicator.classList.add('hidden');
        }
    }
    
    /**
     * Update the countdown display
     */
    updateCountdown() {
        if (!this.countdownDisplay) return;
        
        const minutes = Math.floor(this.remainingSeconds / 60);
        const seconds = this.remainingSeconds % 60;
        
        // Format as MM:SS
        const formattedTime = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        this.countdownDisplay.textContent = formattedTime;
        
        // Add visual cue when less than 10 seconds remaining
        if (this.remainingSeconds <= 10) {
            this.countdownDisplay.classList.add('text-red-400');
            this.indicator.classList.add('animate-pulse');
        } else {
            this.countdownDisplay.classList.remove('text-red-400');
            this.indicator.classList.remove('animate-pulse');
        }
    }
    
    /**
     * Perform the page refresh
     */
    refresh() {
        console.log('Auto-refresh: Reloading page...');
        
        // Show a brief notification before refresh
        if (this.ui && this.ui.showToast) {
            this.ui.showToast('Refreshing...', 'info');
        }
        
        // Reload the page after a brief delay
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }
    
    /**
     * Reset the timer (restart countdown from beginning)
     */
    reset() {
        if (this.enabled) {
            this.start();
        }
    }
    
    /**
     * Update settings and restart if needed
     * @param {boolean} enabled - Whether auto-refresh is enabled
     * @param {number} intervalMinutes - Refresh interval in minutes
     */
    updateSettings(enabled, intervalMinutes) {
        const wasEnabled = this.enabled;
        
        this.enabled = enabled;
        this.intervalMinutes = parseInt(intervalMinutes) || 5;
        
        // Restart if enabled, stop if disabled
        if (this.enabled) {
            this.start();
            
            if (!wasEnabled) {
                console.log('Auto-refresh enabled');
            }
        } else {
            this.stop();
            
            if (wasEnabled) {
                console.log('Auto-refresh disabled');
            }
        }
    }
    
    /**
     * Get current status
     * @returns {Object} Current auto-refresh status
     */
    getStatus() {
        return {
            enabled: this.enabled,
            intervalMinutes: this.intervalMinutes,
            remainingSeconds: this.remainingSeconds,
            isActive: this.countdownId !== null
        };
    }
}

// Export class
window.AutoRefreshManager = AutoRefreshManager;