# Build and Deploy Frontend Assets

This guide explains how to build and deploy the frontend assets (CSS, JavaScript) for the WP-POS system when deploying to production hosting (like Hostinger) where Node.js is not available.

---

## 📦 Overview

The WP-POS system uses Vite to bundle frontend assets including:
- Tailwind CSS styles
- JavaScript modules (offline mode, PWA, service workers)
- Alpine.js integration
- Axios for API calls

**Build Location:** Local development machine  
**Deploy Location:** Production server (`public/build/` directory)

---

## 🛠️ Prerequisites

On your **local development machine**, ensure you have:
- Node.js 18+ installed
- npm or yarn package manager
- Project files cloned/downloaded

---

## 📋 Build Process

### Step 1: Navigate to Project Directory

```bash
cd /path/to/wp-pos
```

### Step 2: Install Dependencies

**First time only:**
```bash
npm install
```

This installs all required packages from `package.json`:
- Vite
- Tailwind CSS
- Laravel Vite Plugin
- PostCSS
- Autoprefixer

### Step 3: Build for Production

```bash
npm run build
```

**What this does:**
- Compiles Tailwind CSS directives into standard CSS
- Bundles JavaScript modules into optimized files
- Minifies and optimizes all assets
- Generates `manifest.json` for asset mapping
- Creates versioned filenames for cache busting

**Build output:** `public/build/` directory containing:
```
public/build/
├── manifest.json
└── assets/
    ├── app-[hash].js
    ├── app-[hash].css
    └── [other bundled files]
```

**Build time:** Typically 10-30 seconds

---

## 🚀 Deployment to Production Server

### Option 1: FTP/SFTP Upload

**Using FileZilla, WinSCP, or similar:**

1. Connect to your server
2. Navigate to: `/home/u479157563/domains/pos.jonesytt.com/public_html/public/`
3. Upload the **entire** `build/` folder from your local `public/build/` directory
4. Ensure all files and subdirectories are included

**Critical files to verify:**
- ✅ `public/build/manifest.json`
- ✅ `public/build/assets/*.js`
- ✅ `public/build/assets/*.css`

### Option 2: cPanel File Manager

1. Login to Hostinger cPanel
2. Open File Manager
3. Navigate to: `domains/pos.jonesytt.com/public_html/public/`
4. Click "Upload" in top menu
5. Select and upload: `public/build/` folder contents
6. Verify all files uploaded successfully

### Option 3: Git + Build Script (Advanced)

**If you use Git on the server:**

```bash
# On production server
cd /home/u479157563/domains/pos.jonesytt.com/public_html
git pull origin main

# Then copy pre-built assets from your local machine
# (Server doesn't have Node.js, so build locally first)
```

---

## 🔄 When to Rebuild

You need to rebuild and redeploy assets when you modify:

### ✅ Always Rebuild For:
- Changes to `resources/css/app.css`
- Changes to `resources/js/app.js`
- Changes to any imported JavaScript modules
- Updates to Tailwind configuration
- Changes to Vite configuration
- Updates to npm dependencies

### ❌ No Rebuild Needed For:
- PHP/Blade template changes (unless they affect CSS/JS)
- Database migrations
- Configuration changes
- Controller/Model updates
- Most backend code changes

---

## 📊 File Size Reference

**Typical production build sizes:**
- CSS: ~50-150 KB (minified)
- JavaScript: ~100-300 KB (minified)
- Total: ~200-500 KB

**If your build is significantly larger:**
- Review imported dependencies
- Check for unnecessary modules
- Consider code splitting

---

## ✅ Verification Steps

After deployment, verify assets are working:

### 1. Check Manifest File
Visit: `https://pos.jonesytt.com/build/manifest.json`  
Should return JSON with asset mappings.

### 2. Test Application
- Visit: `https://pos.jonesytt.com/login`
- Page should load without errors
- Styles should be applied
- JavaScript should be functional

### 3. Check Browser Console
- Press F12 to open Developer Tools
- Check Console tab
- Should see: `[PWA] Offline mode initialized`
- No 404 errors for assets

### 4. Verify Service Worker (PWA)
- Open Developer Tools → Application tab
- Check "Service Workers" section
- Should see: `service-worker.js` registered

---

## 🔧 Build Optimization

### Development Build (Local Testing)

```bash
npm run dev
```

**Use for:**
- Local development
- Hot module replacement
- Faster rebuilds
- Source maps included

**Do not use in production**

### Production Build

```bash
npm run build
```

**Includes:**
- Minification
- Tree shaking
- Code splitting
- Asset optimization
- No source maps (smaller)

---

## 🐛 Troubleshooting

### Build Fails with Errors

**Check:**
```bash
# Clear node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Assets Not Loading on Production

**Verify:**
1. `public/build/manifest.json` exists on server
2. All files in `public/build/assets/` uploaded
3. File permissions (should be 644 for files, 755 for directories)
4. Clear Laravel cache: `php artisan optimize:clear`
5. Clear browser cache: Ctrl+Shift+R

### Large Bundle Sizes

**Optimize:**
```bash
# Analyze bundle
npm run build -- --report

# Review what's being bundled
# Remove unused dependencies
# Consider lazy loading
```

---

## 📝 Quick Reference Commands

```bash
# Install dependencies (first time)
npm install

# Build for production
npm run build

# Development mode (local only)
npm run dev

# Clean build
rm -rf public/build && npm run build
```

---

## 🔐 Production Checklist

Before deploying to production:

- [ ] Run `npm run build` locally
- [ ] Verify `public/build/` directory created
- [ ] Check `manifest.json` exists
- [ ] Upload entire `public/build/` to server
- [ ] Test login page loads
- [ ] Verify styles applied correctly
- [ ] Check JavaScript functionality
- [ ] Test offline mode (if applicable)
- [ ] Clear server-side cache
- [ ] Clear browser cache and test

---

## 🎯 Best Practices

### 1. Version Control
- Add `public/build/` to `.gitignore`
- Build assets locally, don't commit them
- Each deployment builds fresh assets

### 2. Automation
Consider creating a deployment script:
```bash
#!/bin/bash
# deploy.sh
npm run build
# rsync or scp to server
rsync -avz public/build/ user@server:/path/to/public/build/
```

### 3. Backup
Before updating assets:
- Backup existing `public/build/` directory
- Test new build locally first
- Have rollback plan ready

---

## 📈 Performance Tips

### 1. CDN Integration (Optional)
If using CDN, update `.env`:
```env
ASSET_URL=https://cdn.yourdomain.com
```

### 2. Preload Critical Assets
Already configured in layouts with proper `@vite()` directives.

### 3. Cache Headers
Configure in `.htaccess` or web server:
```apache
<FilesMatch "\.(js|css)$">
    Header set Cache-Control "max-age=31536000, public"
</FilesMatch>
```

---

## 🔄 Update Workflow

**Standard deployment workflow:**

1. **Develop locally**
   ```bash
   npm run dev  # Local development server
   ```

2. **Test changes**
   - Verify functionality
   - Check responsive design
   - Test in multiple browsers

3. **Build for production**
   ```bash
   npm run build
   ```

4. **Deploy assets**
   - Upload `public/build/` to server
   - Verify upload complete

5. **Deploy code**
   - Upload PHP/Blade changes
   - Run migrations if needed
   - Clear caches

6. **Verify production**
   - Test live site
   - Monitor for errors
   - Check performance

---

## 📞 Support

For build issues:
- Check Node.js version: `node --version` (need 18+)
- Check npm version: `npm --version`
- Review `package.json` for correct dependencies
- Check Vite documentation: https://vitejs.dev

For deployment issues:
- Verify server paths
- Check file permissions
- Review Laravel logs
- Clear all caches

---

## 🎉 Success

Your WP-POS frontend assets are now built and deployed! The application should be fully functional with:
- ✅ Styled interface (Tailwind CSS)
- ✅ Interactive features (Alpine.js)
- ✅ Offline capability (Service Workers)
- ✅ PWA functionality (Manifest)
- ✅ Optimized performance

**Note:** You only need to rebuild when frontend assets change. Backend-only changes don't require rebuilding.

---

**Last Updated:** 2025-10-01  
**Build Tool:** Vite 5.x  
**Target:** Production deployment on shared hosting