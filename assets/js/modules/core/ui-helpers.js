// WP POS v1.9.0 - UI Helper Functions Module
// Extracted from main.js for modular architecture
// Provides utility functions for UI operations, formatting, and visual feedback

class UIHelpers {
    /**
     * Display a toast notification with a progress bar
     * @param {string} message - The message to display
     */
    showToast(message) {
        // Remove any existing static toast
        let oldToast = document.getElementById('jpos-toast');
        if (oldToast) oldToast.remove();
        
        // Create new toast
        let toast = document.createElement('div');
        toast.id = 'jpos-toast';
        toast.innerHTML = `<span id="jpos-toast-message"></span><div id="jpos-toast-loader"></div>`;
        toast.style.position = 'fixed';
        toast.style.left = '50%';
        toast.style.bottom = '32px';
        toast.style.transform = 'translateX(-50%) translateY(40px)';
        toast.style.minWidth = '120px';
        toast.style.maxWidth = '90vw';
        toast.style.background = 'rgba(255,255,255,0.60)';
        toast.style.color = '#111';
        toast.style.fontWeight = 'bold';
        toast.style.fontSize = '0.95rem';
        toast.style.padding = '0.5rem 1rem 0.7rem 1rem';
        toast.style.borderRadius = '0.6rem';
        toast.style.boxShadow = '0 4px 24px 0 rgba(0,0,0,0.10)';
        toast.style.backdropFilter = 'blur(8px)';
        toast.style.zIndex = '9999';
        toast.style.display = 'flex';
        toast.style.flexDirection = 'column';
        toast.style.alignItems = 'center';
        toast.style.opacity = '0';
        toast.style.pointerEvents = 'none';
        toast.style.transition = 'opacity 0.3s, transform 0.3s';
        toast.style.overflow = 'hidden';
        
        document.body.appendChild(toast);
        document.getElementById('jpos-toast-message').textContent = message;
        
        const loader = document.getElementById('jpos-toast-loader');
        loader.style.height = '3px';
        loader.style.width = '100%';
        loader.style.background = 'rgba(0,0,0,0.10)';
        loader.style.borderRadius = '0 0 0.6rem 0.6rem';
        loader.style.overflow = 'hidden';
        loader.style.position = 'absolute';
        loader.style.left = '0';
        loader.style.bottom = '0';
        loader.style.margin = '0';
        loader.innerHTML = `<div style="height:100%;width:100%;background:#111;border-radius:0 0 0.6rem 0.6rem;transform:scaleX(1);transform-origin:left;transition:transform 2.5s linear;"></div>`;
        
        toast.style.position = 'fixed';
        toast.style.bottom = '32px';
        toast.style.left = '50%';
        toast.style.transform = 'translateX(-50%) translateY(40px)';
        toast.style.overflow = 'visible';
        toast.appendChild(loader);
        
        setTimeout(() => {
            loader.firstChild.style.transform = 'scaleX(0)';
        }, 10);
        
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(-50%) translateY(0)';
        }, 10);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(-50%) translateY(40px)';
            setTimeout(() => { toast.remove(); }, 400);
        }, 2500);
        
        toast.style.height = '24px';
        toast.style.minHeight = '24px';
        toast.style.maxHeight = '24px';
        toast.style.background = 'rgba(255,255,255,0.6)';
        toast.style.backdropFilter = 'blur(8px)';
        toast.style.padding = '0 1rem 0 1rem';
        toast.style.display = 'flex';
        toast.style.alignItems = 'center';
        toast.style.justifyContent = 'center';
        toast.style.position = 'fixed';
        toast.style.left = '50%';
        toast.style.bottom = '32px';
        toast.style.transform = 'translateX(-50%) translateY(40px)';
        toast.style.overflow = 'visible';
        
        // Adjust message style for vertical centering
        const msg = toast.querySelector('#jpos-toast-message');
        msg.style.lineHeight = '24px';
        msg.style.height = '24px';
        msg.style.display = 'block';
        
        // Loader bar at the bottom, overlay, not increasing height
        loader.style.height = '3px';
        loader.style.width = '100%';
        loader.style.background = 'rgba(0,0,0,0.10)';
        loader.style.borderRadius = '0 0 0.6rem 0.6rem';
        loader.style.position = 'absolute';
        loader.style.left = '0';
        loader.style.bottom = '0';
        loader.style.margin = '0';
        loader.innerHTML = `<div style="height:100%;width:100%;background:#111;border-radius:0 0 0.6rem 0.6rem;transform:scaleX(1);transform-origin:left;transition:transform 2.5s linear;"></div>`;
    }

    /**
     * Format a date/time into a readable string
     * @param {Date|string} dt - The date to format
     * @returns {string} Formatted date/time string
     */
    formatDateTime(dt) {
        if (!dt) return '';
        const date = typeof dt === 'string' ? new Date(dt) : dt;
        if (isNaN(date.getTime())) return dt;
        return date.toLocaleString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric', 
            hour: 'numeric', 
            minute: '2-digit' 
        });
    }

    /**
     * Format a date relative to today (Today, Yesterday, or full date)
     * @param {string} dateString - The date string to format
     * @returns {string} Formatted relative date string
     */
    formatRelativeDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        
        // Reset time parts for date comparison
        today.setHours(0, 0, 0, 0);
        yesterday.setHours(0, 0, 0, 0);
        const compareDate = new Date(date);
        compareDate.setHours(0, 0, 0, 0);
        
        const timeString = date.toLocaleString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        
        if (compareDate.getTime() === today.getTime()) {
            return `Today @ ${timeString}`;
        } else if (compareDate.getTime() === yesterday.getTime()) {
            return `Yesterday @ ${timeString}`;
        } else {
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }
    }

    /**
     * Generate skeleton loader HTML for loading states
     * @param {string} type - The type of skeleton loader (list-rows, variation-edit-rows)
     * @param {number} count - Number of skeleton rows to generate
     * @returns {string} HTML string for skeleton loader
     */
    getSkeletonLoaderHtml(type = 'list-rows', count = null) {
        let rowHtml = '';
        let columns = 6;
        let actualCount;
        
        if (type === 'variation-edit-rows') {
            columns = 4;
            actualCount = count !== null ? count : 4;
        } else {
            actualCount = count !== null ? count : 20;
        }

        for (let i = 0; i < actualCount; i++) {
            let blocks = '';
            for (let j = 1; j <= columns; j++) {
                blocks += `<div class="block"></div>`;
            }
            rowHtml += `<div class="row">${blocks}</div>`;
        }
        
        return `<div class="skeleton-loader ${type}">${rowHtml}</div>`;
    }

    /**
     * Add syntax highlighting to JSON strings for display
     * @param {string} jsonString - The JSON string to highlight
     * @returns {string} HTML string with syntax highlighting
     */
    highlightJSON(jsonString) {
        return jsonString
            .replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                let cls = '';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = ''; // Keys stay default color
                    } else {
                        cls = 'json-string'; // String values get colored
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'json-boolean'; // Boolean values get colored
                } else if (/null/.test(match)) {
                    cls = 'json-null'; // Null values get colored
                } else if (/^-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?$/.test(match)) {
                    cls = 'json-number'; // Number values get colored
                }
                
                if (cls) {
                    return '<span class="' + cls + '">' + match + '</span>';
                } else {
                    return match; // Keys and punctuation stay uncolored
                }
            });
    }
}

// Export as singleton
window.UIHelpers = UIHelpers;