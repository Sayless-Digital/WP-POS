// WP POS v1.9.0 - Sessions Management Module
// Handles cash drawer session history and reporting

class SessionsManager {
    constructor(state, uiHelpers) {
        this.state = state;
        this.ui = uiHelpers;
    }

    /**
     * Fetch sessions from API
     * @returns {Promise<void>}
     */
    async fetchSessions() {
        const container = document.getElementById('session-list');
        container.innerHTML = this.ui.getSkeletonLoaderHtml('list-rows', 20);
        
        try {
            const response = await fetch('/jpos/api/sessions.php');
            if (!response.ok) throw new Error(`API Error: ${response.statusText}`);
            
            const result = await response.json();
            if (!result.success) throw new Error(result.data.message);
            
            this.state.sessions = result.data || [];
            this.renderSessions();
        } catch (error) {
            console.error("Error in fetchSessions:", error);
            container.innerHTML = `<p class="p-10 text-center text-red-400">Error: Could not fetch session data. ${error.message}</p>`;
        }
    }

    /**
     * Render sessions list
     */
    renderSessions() {
        const container = document.getElementById('session-list');
        container.innerHTML = '';
        
        if (this.state.sessions.length === 0) {
            container.innerHTML = '<p class="p-10 text-center text-slate-400">No past sessions found.</p>';
            return;
        }
        
        this.state.sessions.forEach(session => {
            const row = document.createElement('div');
            row.className = 'grid grid-cols-12 gap-4 items-center bg-slate-800 hover:bg-slate-700/50 p-3 rounded-lg text-sm font-mono';
            
            const difference = parseFloat(session.difference || 0);
            let diffColor = 'text-green-400';
            
            if (difference < 0) {
                diffColor = 'text-red-400';
            } else if (difference > 0) {
                diffColor = 'text-yellow-400';
            }
            
            row.innerHTML = `
                <div class="col-span-2 text-slate-200 font-sans">${session.user_name}</div>
                <div class="col-span-3 text-slate-400">${this.ui.formatDateTime(session.time_opened)}</div>
                <div class="col-span-3 text-slate-400">${this.ui.formatDateTime(session.time_closed)}</div>
                <div class="col-span-1 text-right text-slate-300">$${session.opening_amount.toFixed(2)}</div>
                <div class="col-span-1 text-right text-slate-300">$${session.closing_amount.toFixed(2)}</div>
                <div class="col-span-2 text-right font-bold ${diffColor}">$${difference.toFixed(2)}</div>
            `;
            
            container.appendChild(row);
        });
    }

    /**
     * Get session statistics summary
     * @returns {Object} Session statistics
     */
    getSessionStats() {
        if (!this.state.sessions || this.state.sessions.length === 0) {
            return {
                totalSessions: 0,
                totalOpening: 0,
                totalClosing: 0,
                totalDifference: 0,
                avgDifference: 0
            };
        }

        let totalOpening = 0;
        let totalClosing = 0;
        let totalDifference = 0;

        this.state.sessions.forEach(session => {
            totalOpening += parseFloat(session.opening_amount || 0);
            totalClosing += parseFloat(session.closing_amount || 0);
            totalDifference += parseFloat(session.difference || 0);
        });

        return {
            totalSessions: this.state.sessions.length,
            totalOpening: totalOpening,
            totalClosing: totalClosing,
            totalDifference: totalDifference,
            avgDifference: totalDifference / this.state.sessions.length
        };
    }

    /**
     * Filter sessions by date range
     * @param {Date} startDate - Start date
     * @param {Date} endDate - End date
     * @returns {Array} Filtered sessions
     */
    filterSessionsByDate(startDate, endDate) {
        if (!this.state.sessions) return [];

        return this.state.sessions.filter(session => {
            const sessionDate = new Date(session.time_opened);
            return sessionDate >= startDate && sessionDate <= endDate;
        });
    }

    /**
     * Filter sessions by user
     * @param {string} userName - User name to filter by
     * @returns {Array} Filtered sessions
     */
    filterSessionsByUser(userName) {
        if (!this.state.sessions) return [];

        return this.state.sessions.filter(session => 
            session.user_name.toLowerCase().includes(userName.toLowerCase())
        );
    }
}

// Export as singleton
window.SessionsManager = SessionsManager;