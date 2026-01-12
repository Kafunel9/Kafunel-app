# Kafunel Optimizer AI - Plugin Structure

## Overview
Kafunel Optimizer AI is an advanced image optimization plugin with AI integration for WordPress. It provides both local optimization (using GD/ImageMagick) and external API-based optimization services.

## Directory Structure
```
kafunel-optimizer-ai/
├── kafunel-optimizer-ai.php          # Main plugin file
├── kafunel-pro-stub.php              # Pro version stub
├── config.php                        # Configuration constants
├── install.php                       # Installation/uninstallation routines
├── readme.txt                        # WordPress plugin readme
├── LICENSE                           # GPL v3 license
├── includes/
│   ├── class-kafunel-settings.php    # Settings page handler
│   ├── class-kafunel-engine.php      # Core optimization engine
│   ├── class-kafunel-media-integration.php # Media library integration
│   └── class-kafunel-api-handler.php # API handling
├── templates/
│   └── settings-page.php             # Settings page template
├── assets/
│   ├── css/
│   │   ├── admin.css                 # Admin panel styles
│   │   └── media.css                 # Media library styles
│   └── js/
│       ├── admin.js                  # Admin panel scripts
│       └── media.js                  # Media library scripts
└── vendor/                           # Third-party libraries (future)
```

## Key Features

### 1. Core Optimization Engine (`includes/class-kafunel-engine.php`)
- Local optimization using GD and ImageMagick
- External API integration for AI processing
- Multiple compression levels (lossless, optimal, lossy, maximum)
- Format conversion (JPEG, PNG, WebP, AVIF)
- Automatic resizing
- Daily usage tracking

### 2. Media Library Integration (`includes/class-kafunel-media-integration.php`)
- Adds "Optimize with Kafunel" button to media items
- Bulk optimization capability
- Custom status column in media library
- Optimization status indicators

### 3. Settings Management (`includes/class-kafunel-settings.php`)
- Admin settings page under "Settings" menu
- Secure API key storage
- Compression level selection
- Format conversion options
- Daily usage statistics

### 4. API Integration (`includes/class-kafunel-api-handler.php`)
- REST API endpoints for optimization
- API connection testing
- Bulk optimization via API
- Usage statistics reporting

## Technical Implementation

### Security Measures
- Nonce verification for all AJAX requests
- Capability checks (manage_options)
- Input sanitization and validation
- Escaping output for display
- No hardcoded API keys

### Architecture
- Object-oriented design with modular classes
- Hooks and filters following WordPress standards
- Separation of concerns (engine, UI, API)
- Proper error handling and logging

### Free vs Pro Features
**Free Version:**
- Up to 10 optimizations per day
- Batch processing up to 20 images
- Local optimization only
- Basic compression levels

**Pro Version:**
- Unlimited daily optimizations
- AI background removal
- AI-powered upscale
- Video optimization
- Detailed reports
- Priority support

### Supported Formats
**Input:** JPEG, PNG, GIF, WebP
**Output:** JPEG, PNG, WebP, AVIF

## Hooks and Filters

### Actions
- `kafunel_optimization_attempted`
- `kafunel_optimization_successful`
- `kafunel_optimization_failed`

### Filters
- `kafunel_custom_api_endpoint`
- Various WordPress standard hooks

## Contact Information
- **Development:** Kafunel Team
- **Support:** support@kafunel.com
- **Commercial:** kafunel9@gmail.com
- **Phone/WhatsApp:** +221 33 867 42 76 / +221 77 541 82 31

## License
GPL v3 - Open source with full commercial use allowed

## Dependencies
- WordPress 6.0+
- PHP 7.4+
- GD Library or ImageMagick (for local optimization)
- cURL (for API calls)