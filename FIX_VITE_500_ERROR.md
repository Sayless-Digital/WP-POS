# Fix for 500 Error on Login Page - Vite Build Issue

## 🔴 Problem Identified

Your login page returns a 500 error because:
- The app expects compiled assets at `public/build/manifest.json`
- Vite assets were never built
- npm/Node.js is not available on Hostinger shared hosting

**Error:** `ViteManifestNotFoundException: Vite manifest not found at: /home/u479157563/domains/pos.jonesytt.com/public_html/public/build/manifest.json`

---

## 💡 Two Solutions

### Solution 1: Build Locally & Upload (Recommended for Full Features)
### Solution 2: Use CDN (Quick Fix, Loses Offline/PWA Features)

---

## 🚀 Solution 1: Build Locally & Upload

**Use this if:** You want all features including offline mode and PWA.

### Step 1: Build on Your Local Machine

```bash
# Navigate to your project directory on your LOCAL computer
cd /path/to/your/wp-pos-project

# Install dependencies (if not already done)
npm install

# Build for production
npm run build
```

This creates: `public/build/` directory with all compiled assets.

### Step 2: Upload Built Files to Server

Upload **only these files/folders** to your Hostinger server:

**From:** `public/build/` (your local machine)  
**To:** `/home/u479157563/domains/pos.jonesytt.com/public_html/public/build/`

**Files to upload:**
- `public/build/manifest.json`
- `public/build/assets/` (entire folder with all .js and .css files)

**Upload Method:**
- Use FileZilla, cPanel File Manager, or your preferred FTP client
- Preserve directory structure

### Step 3: Verify

Visit: `https://pos.jonesytt.com/login`  
✅ Should work immediately!

---

## ⚡ Solution 2: Remove Vite, Use CDN

**Use this if:** You can't build locally OR you don't need offline/PWA features.

### Files to Modify

#### File 1: `resources/views/layouts/guest.blade.php`

**Find line 15:**
```php
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

**Replace with:**
```php
<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
@livewireStyles
```

**Before closing `</body>` tag (around line 29), add:**
```php
@livewireScripts
```

#### File 2: `resources/views/layouts/app.blade.php`

**Find line 28:**
```php
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

**Replace with:**
```php
<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Alpine.js CDN (for Livewire) -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@livewireStyles
```

**Before closing `</body>` tag (around line 106), add:**
```php
@livewireScripts
<!-- Axios CDN -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    // Make axios available globally
    window.axios = axios;
    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
</script>
```

### How to Edit Files on Hostinger

**Option A: cPanel File Manager**
1. Login to your Hostinger cPanel
2. Navigate to File Manager
3. Go to: `domains/pos.jonesytt.com/public_html/resources/views/layouts/`
4. Right-click file → Edit
5. Make changes → Save

**Option B: FTP**
1. Download files via FTP
2. Edit locally
3. Upload back to server

---

## 📊 Comparison

| Feature | Solution 1: Build | Solution 2: CDN |
|---------|------------------|-----------------|
| **Setup Time** | 5-10 minutes | 2 minutes |
| **Requires** | Local Node.js | Just file editing |
| **Offline Mode** | ✅ Works | ❌ Won't work |
| **PWA Features** | ✅ Works | ❌ Won't work |
| **Service Worker** | ✅ Works | ❌ Won't work |
| **Performance** | ⚡ Optimized | 🐌 CDN latency |
| **File Size** | 📦 Smaller | 📦 Larger |
| **Future Updates** | Need rebuild | No rebuild |

---

## 🎯 Recommendation

**If you have Node.js locally:** Use **Solution 1** (Build & Upload)
- You get all features
- Better performance
- Proper production setup

**If you don't have Node.js:** Use **Solution 2** (CDN)
- Quick fix
- Core functionality works
- Acceptable for basic POS operations

---

## ✅ After Applying Either Solution

1. **Clear Laravel Cache:**
```bash
# Via terminal (if you have SSH)
php artisan optimize:clear
php artisan config:cache
php artisan view:cache

# OR via browser
# Visit: https://pos.jonesytt.com/clear-cache (if route exists)
```

2. **Clear Browser Cache:**
- Press `Ctrl + Shift + R` (Windows/Linux)
- Press `Cmd + Shift + R` (Mac)

3. **Test Login:**
- Visit: `https://pos.jonesytt.com/login`
- Should load without 500 error

---

## 🔧 Troubleshooting

### If Solution 1 doesn't work:
- Verify `public/build/manifest.json` exists on server
- Check file permissions: `chmod 644 public/build/manifest.json`
- Verify all files in `public/build/assets/` uploaded

### If Solution 2 doesn't work:
- Clear browser cache completely
- Check browser console for CDN loading errors
- Verify file edits were saved correctly
- Run `php artisan view:clear` to clear compiled views

### Still having issues?
Check Laravel logs:
```bash
tail -100 storage/logs/laravel.log
```

---

## 📝 Notes

- **Solution 1** is the proper production approach
- **Solution 2** is acceptable for Hostinger limitations
- Original README claimed "no build step" but Phase 9/10 added features requiring builds
- You only need to build once, then upload (not on every code change)
- For code changes without asset changes, no rebuild needed

---

## 🎉 Expected Result

After applying either solution:
- ✅ Login page loads successfully
- ✅ No 500 errors
- ✅ Authentication works
- ✅ Can access POS system
- ✅ Livewire components function

**Your POS system will be fully operational!**

---

**Created:** 2025-10-01  
**Issue:** ViteManifestNotFoundException on login  
**Server:** Hostinger shared hosting (no Node.js)