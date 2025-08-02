# JPOS - Technology Stack

## Architecture
- **Frontend**: Single Page Application (SPA) built with vanilla JavaScript
- **Backend**: PHP with WordPress/WooCommerce integration
- **Database**: MySQL (via WordPress)
- **Authentication**: WordPress user system with WooCommerce permissions

## Frontend Technologies
- **JavaScript**: Vanilla ES6+ (no frameworks)
- **CSS Framework**: Tailwind CSS (loaded via CDN)
- **Charts**: Chart.js for data visualization
- **Icons**: Font Awesome
- **Build Process**: None - files served directly from browser

## Backend Technologies
- **PHP**: 7.4+ (WordPress requirement)
- **WordPress**: Latest version with WooCommerce plugin
- **Database**: MySQL 5.7+
- **PDF Generation**: TCPDF library for report exports

## Key Technical Decisions
- **No Build System**: Direct file editing and deployment for simplicity
- **RESTful API**: Clean separation between frontend and backend
- **WordPress Integration**: Leverages existing WooCommerce infrastructure
- **Vanilla JavaScript**: No framework dependencies for easier maintenance

## Development Workflow
1. Edit files directly (no compilation needed)
2. Refresh browser to see changes
3. Upload files to web server for deployment
4. Track changes in `@build-log.mdc`

## Common Commands
Since this project has no build system, development is straightforward:

```bash
# No build commands needed - files are served directly
# For development, simply edit files and refresh browser

# For deployment
scp -r . user@server:/path/to/jpos/

# For version tracking
# Update @build-log.mdc manually with changes
```

## Dependencies
All dependencies are loaded via CDN:
- Tailwind CSS: `https://cdn.tailwindcss.com`
- Chart.js: `https://cdn.jsdelivr.net/npm/chart.js`
- Font Awesome: `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css`

## Performance Considerations
- Large main.js file (2,357 lines) - consider splitting for better maintainability
- Multiple API calls - could be optimized with batching
- No caching implemented - opportunity for improvement