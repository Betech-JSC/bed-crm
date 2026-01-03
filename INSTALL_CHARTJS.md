# Chart.js Installation Guide

## Issue
Chart.js is not installed yet. The package.json has been updated but npm install needs to be run.

## Solution

### Option 1: Manual Installation (Recommended)
Run this command in your terminal:

```bash
npm install chart.js
```

### Option 2: If npm install fails
If you're having issues with npm/node, try:

1. **Check Node version:**
   ```bash
   node --version
   ```

2. **Clear npm cache:**
   ```bash
   npm cache clean --force
   ```

3. **Delete node_modules and reinstall:**
   ```bash
   rm -rf node_modules package-lock.json
   npm install
   ```

4. **Or use yarn instead:**
   ```bash
   yarn add chart.js
   ```

### After Installation

1. **Restart Vite dev server:**
   ```bash
   npm run dev
   ```

2. **Or rebuild assets:**
   ```bash
   npm run build
   ```

## Verification

After installation, verify chart.js is installed:

```bash
npm list chart.js
```

You should see:
```
chart.js@4.4.0
```

## Alternative: Use CDN (Quick Fix)

If npm install continues to fail, you can temporarily use CDN by adding to `resources/views/app.blade.php`:

```html
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
```

Then update chart components to use global `Chart` instead of import.

