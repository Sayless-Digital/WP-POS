# Auto-Refresh Timer Testing Guide

## Quick Console Check

Open your browser's Developer Console (F12) and look for these messages to verify the fix is working:

### ✅ Success Indicators

When auto-refresh is **enabled** and working correctly:

```
🔄 Auto-refresh init() called
✓ Auto-refresh DOM elements found
🔍 Loading auto-refresh settings from state: {auto_refresh_enabled: true, auto_refresh_interval: 1, ...}
📊 Settings loaded: enabled=true, interval=1min
✓ Auto-refresh is enabled (1 minutes)
🚀 Starting auto-refresh timer...
✓ Countdown indicator shown
✅ Auto-refresh timer started: 1 minutes (60 seconds)
✅ Auto-refresh manager initialized successfully
```

When auto-refresh is **disabled**:

```
🔄 Auto-refresh init() called
✓ Auto-refresh DOM elements found
🔍 Loading auto-refresh settings from state: {auto_refresh_enabled: false, ...}
📊 Settings loaded: enabled=false, interval=5min
ℹ️ Auto-refresh is disabled
ℹ️ Auto-refresh manager initialized (disabled or inactive)
```

### ❌ Error Indicators

If you see these messages, something is wrong:

```
❌ Auto-refresh indicator elements not found in DOM
```
**Solution:** Check that the auto-refresh indicator HTML elements exist in index.php

```
⚠️ No settings found in state, auto-refresh disabled
```
**Solution:** Settings failed to load from the API. Check network tab and API endpoint.

```
⚠️ Cannot start: enabled=false, interval=0
```
**Solution:** Settings not properly configured or loaded.

```
❌ Cannot start: DOM elements not available
```
**Solution:** Initialization called before DOM elements were created.

## Visual Indicators

### Timer is Active
- **Location:** Bottom-left corner of screen
- **Appearance:** Dark box with clock icon and countdown (e.g., "05:23")
- **Behavior:** Counts down by 1 second every second
- **Last 10 seconds:** Text turns red and animates with pulse effect

### Timer is Inactive
- **Appearance:** No indicator visible
- **Reason:** Auto-refresh is disabled in settings

## Step-by-Step Test Scenarios

### Scenario 1: First Time Setup
1. Open browser console (F12 → Console tab)
2. Log into POS
3. Go to Settings → General tab
4. Enable "Auto-Refresh" checkbox
5. Set interval to **1 minute** (for quick testing)
6. Click "Save Settings"
7. **Check console for:** `✅ Auto-refresh re-initialized and started`
8. **Check screen for:** Countdown showing `01:00` in bottom-left
9. Wait 10 seconds
10. **Check screen for:** Countdown showing `00:50` (and counting down)

### Scenario 2: Auto-Refresh Cycle
1. Continue from Scenario 1
2. Wait for countdown to reach `00:05`
3. **Check screen for:** Timer turns red and pulses
4. Wait for countdown to reach `00:00`
5. **Check screen for:** "Refreshing..." toast message
6. **Check browser:** Page reloads automatically
7. **Check console for:** Initialization messages appear again
8. **Check screen for:** Countdown showing `01:00` after reload

### Scenario 3: Manual Refresh
1. Enable auto-refresh with 5 minute interval
2. Wait for countdown to show `04:45` (15 seconds elapsed)
3. Press F5 or click browser refresh
4. **Check console for:** Full initialization sequence
5. **Check screen for:** Countdown reappears (should show `05:00`, not `04:45`)
   - Note: Timer resets on manual refresh, which is expected behavior

### Scenario 4: Disable Timer
1. With timer running, go to Settings
2. Uncheck "Enable Auto-Refresh"
3. Click "Save Settings"
4. **Check console for:** `ℹ️ Auto-refresh re-initialized (disabled)`
5. **Check screen for:** Countdown disappears
6. Wait 2 minutes
7. **Check browser:** Page does NOT refresh (as expected)

### Scenario 5: Change Interval While Running
1. Enable auto-refresh with 1 minute interval
2. Wait for countdown to reach `00:40`
3. Go to Settings
4. Change interval to **5 minutes**
5. Click "Save Settings"
6. **Check console for:** Timer stopped then restarted messages
7. **Check screen for:** Countdown resets to `05:00`

### Scenario 6: Settings Persist After Logout
1. Enable auto-refresh with 2 minute interval
2. Wait for timer to show `01:45`
3. Click "Logout"
4. Log back in
5. **Check screen for:** Countdown appears showing `02:00`
   - Note: Timer resets on fresh login, which is expected

## Troubleshooting Guide

### Problem: Timer doesn't appear after enabling

**Check:**
1. Console for error messages
2. Network tab for failed API calls to `api/settings.php`
3. Settings were actually saved (try reloading settings page)

**Fix:**
```javascript
// In console, manually check state
window.stateManager.getState('settings')
// Should show: {auto_refresh_enabled: true, auto_refresh_interval: X, ...}
```

### Problem: Timer appears but doesn't count down

**Check:**
1. Console for interval errors
2. Verify countdown element is updating

**Fix:**
```javascript
// In console, check if timer is running
window.autoRefreshManager.getStatus()
// Should show: {enabled: true, isActive: true, remainingSeconds: X}
```

### Problem: Timer doesn't re-appear after page reload

**Check:**
1. Console for initialization messages
2. Verify settings are in state after reload

**Fix:**
```javascript
// In console after reload
window.stateManager.getState('settings')
// If null or undefined, settings didn't load

// Manually trigger initialization
window.autoRefreshManager.init()
```

### Problem: Page refreshes but timer doesn't restart

**Check:**
1. Console for initialization after refresh
2. Look for DOM element errors

**This was the original bug - should be fixed in v1.9.197**

## Console Commands for Testing

Open browser console and try these commands:

```javascript
// Check current auto-refresh status
window.autoRefreshManager.getStatus()

// Manually start timer (if enabled in settings)
window.autoRefreshManager.start()

// Manually stop timer
window.autoRefreshManager.stop()

// Check current settings
window.stateManager.getState('settings')

// Force re-initialization
window.autoRefreshManager.init()

// Check if auto-refresh manager exists
window.autoRefreshManager
```

## Expected Behavior Summary

| Action | Expected Behavior |
|--------|-------------------|
| Enable in settings | Timer appears immediately with full interval |
| Disable in settings | Timer disappears immediately |
| Change interval | Timer resets to new interval |
| Timer reaches 0 | Page refreshes, timer reappears with full interval |
| Manual page refresh | Timer reappears with full interval (resets) |
| Navigate between pages | Timer continues counting (no reset) |
| Logout | Timer stops |
| Login | Timer appears if enabled in settings |

## Performance Notes

- Timer uses `setInterval()` with 1-second updates
- Minimal CPU usage (simple countdown)
- No network calls while running (only on initialization)
- Cleanly stops timer before page refresh to avoid memory leaks

## Browser Compatibility

Tested and working on:
- ✅ Chrome/Edge (Chromium)
- ✅ Firefox
- ✅ Safari

## Need Help?

If the timer still doesn't work after following this guide:

1. **Check Version:** Verify you're running v1.9.197 or later
   - Look for version in console: `WP POS v1.9.197 loaded`

2. **Clear Cache:** Hard refresh (Ctrl+Shift+R or Cmd+Shift+R)

3. **Check Console:** Look for red error messages

4. **Export Logs:** Right-click console → Save as... → Send to developer

5. **Settings File:** Check that auto-refresh settings exist in WordPress database
   - WP Admin → Options → `jpos_receipt_settings`
   - Should contain: `auto_refresh_enabled` and `auto_refresh_interval`


