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

## Thay đổi phiên bản (Changelog)

### Tiếng Việt

#### 1.0.7 (Hiện tại)
- **Tính năng mới**:
  - Tích hợp Google Analytics 4 (GA4) với báo cáo riêng
  - Thêm bảng dữ liệu GA4 trong database
  - Tự động kiểm tra và cập nhật cấu trúc database
  - Trình hướng dẫn kết nối GA4 dễ sử dụng

- **Cải tiến**:
  - Tối ưu hiệu năng hệ thống
  - Giao diện quản trị được cải thiện
  - Xử lý lỗi chi tiết và rõ ràng hơn

#### 1.0.6
- Thêm tính năng xuất báo cáo chi tiết
- Hiển thị thông tin thiết bị và vị trí
- Theo dõi thời gian sử dụng phiên

### English

## Changelog

### 1.0.9 (Current)
- **Cải tiến kết nối GA4**:
  - Xử lý lỗi chi tiết hơn với các trường hợp:
    * Sai API Key
    * Sai Property ID  
    * Lỗi timeout
    * Lỗi permission
  - Tự động chuẩn hóa Property ID
  - Thông báo lỗi rõ ràng với hướng dẫn khắc phục

- **Cải tiến khác**:
  - Tối ưu thời gian chờ kết nối
  - Nâng cao độ ổn định khi test kết nối

### 1.0.8
- **Cải tiến hướng dẫn**:
  - Thêm hướng dẫn chi tiết từng bước lấy GA4 Property ID và API Key
  - Hướng dẫn trực quan với hình ảnh minh họa
  - Link trực tiếp đến trang quản trị Google Analytics và Google Cloud

- **Tính năng mới**:
  - Tích hợp Google Analytics 4 (GA4) với báo cáo riêng
  - Thêm bảng dữ liệu GA4 trong database
  - Tự động kiểm tra và cập nhật cấu trúc database
  - Trình hướng dẫn kết nối GA4 dễ sử dụng

- **Cải tiến khác**:
  - Tối ưu giao diện cài đặt
  - Thêm kiểm tra kết nối GA4
  - Xử lý lỗi chi tiết hơn

### 1.0.7
- **Tính năng mới**:
  - Tích hợp GA4 với báo cáo riêng
  - Thêm bảng `wp_ga4_data` để lưu trữ dữ liệu từ GA4
  - Kiểm tra môi trường tự động phát hiện thiếu bảng/cột
  - Nút cập nhật database trong trang cài đặt
  - Trình thuật sĩ kết nối GA4

- **Cải tiến**:
  - Tối ưu truy vấn database
  - Xử lý lỗi chi tiết hơn
  - Giao diện quản lý thân thiện hơn

### 1.0.6
- **Tính năng mới**:
  - Xuất CSV với đầy đủ thông tin phiên
  - Thêm thông tin thiết bị, vị trí địa lý
  - Theo dõi thời gian phiên

### 1.0.5
- Cải tiến quá trình cập nhật database
- Thêm thông báo trạng thái real-time
- Xử lý lỗi tốt hơn

### 1.0.5
- Enhanced database update process with real-time status notifications
- Added loading indicators during database updates
- Better error handling and display

### 1.0.4
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