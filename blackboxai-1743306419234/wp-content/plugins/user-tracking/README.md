# User Tracking for Google Ads

WordPress plugin to track user behavior from Google Ads and detect fraudulent clicks.

## Requirements
- WordPress 6.7.2 or higher
- PHP 8.0 or higher

## Features

- Tracks user sessions with detailed information (IP, URLs accessed, referrers)
- Advanced fraud detection with multiple detection methods
- Real-time alerts via Email and Telegram
- Comprehensive admin dashboard with charts and statistics
- Responsive design for all devices

## Changelog

### 1.0.4 (Current)
- **New Features**:
  - Added database version check and update system
  - Implemented advanced fraud detection filters:
    * Click frequency monitoring
    * Session duration analysis
    * User interaction tracking
    * Multiple ad click detection
    * Suspicious IP checks
    * Repetitive behavior detection
    * Geographic anomaly detection

- **Improvements**:
  - Enhanced database schema with new fields:
    * entry_time, exit_time for session tracking
    * interactions for user engagement
    * ad_clicks counter
  - Added fraud_patterns table for behavior analysis
  - Improved dashboard with database status alerts

- **Bug Fixes**:
  - Fixed CSV export functionality
  - Resolved database migration issues

### 1.0.2
- Added detailed fraud alerts with IP, URLs and referrer information
- Fixed chart height limit (max 600px) in dashboard
- Improved admin UI with better data visualization

### 1.0.1
- Initial release with basic tracking functionality
- Simple fraud detection
- Basic admin interface

## Installation

1. Upload the plugin files to the `/wp-content/plugins/user-tracking` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings in User Tracking → Settings

## Configuration

1. Set up email alerts in plugin settings
2. Configure Telegram bot (optional)
3. Adjust fraud detection thresholds as needed

## Frequently Asked Questions

### How often are fraud checks run?
Checks run every hour by default.

### Where can I view the tracking data?
Go to User Tracking → Dashboard in WordPress admin.

### How do I get support?
Contact support@example.com for any questions.