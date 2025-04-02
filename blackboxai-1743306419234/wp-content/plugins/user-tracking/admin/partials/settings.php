<?php
/**
 * Settings page template
 */
?>
<div class="wrap">
    <h1>User Tracking Settings</h1>

    <form method="post" action="options.php">
        <?php settings_fields('user_tracking_settings'); ?>
        <?php do_settings_sections('user-tracking-settings'); ?>
        <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
    </form>

    <div class="settings-advanced">
        <h2>Advanced Options</h2>
        
        <div class="card">
            <h3>Data Management</h3>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="clear_tracking_data">
                <?php wp_nonce_field('clear_tracking_data_nonce'); ?>
                <p>
                    <label>
                        <input type="checkbox" name="confirm_clear" required>
                        I understand this will permanently delete all tracking data
                    </label>
                </p>
                <button type="submit" class="button button-danger">Clear All Tracking Data</button>
            </form>
        </div>

        <div class="card">
            <h3>Blocklist Management</h3>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="update_blocklist">
                <?php wp_nonce_field('update_blocklist_nonce'); ?>
                <p>
                    <label for="blocklist">Add IPs or User Agents to Blocklist (one per line):</label><br>
                    <textarea name="blocklist" id="blocklist" rows="5" cols="50" class="large-text code"></textarea>
                </p>
                <button type="submit" class="button">Update Blocklist</button>
            </form>
        </div>

        <div class="card">
            <h3>Test Connections</h3>
            <div class="connection-tests">
                <button id="test-email" class="button button-secondary">
                    <span class="dashicons dashicons-email-alt"></span> Test Email
                </button>
                <button id="test-telegram" class="button button-secondary" style="margin-left:10px;">
                    <span class="dashicons dashicons-share"></span> Test Telegram
                </button>
                <button id="preview-alerts" class="button button-secondary" style="margin-left:10px;">
                    <span class="dashicons dashicons-visibility"></span> Preview Alerts
                </button>
                <div id="test-results" style="margin-top:10px;"></div>
            </div>
        </div>

        <div class="card">
            <h3>GA4 Integration</h3>
            <table class="form-table">
                <tr>
                    <th>GA4 Property ID</th>
                    <td>
                        <input type="text" name="user_tracking_settings[ga4_property_id]" 
                               value="<?php echo esc_attr(get_option('user_tracking_settings')['ga4_property_id'] ?? ''); ?>" 
                               class="regular-text">
                        <p class="description">Định dạng: properties/XXXXXXX hoặc G-XXXXXXX</p>
                    </td>
                </tr>
                <tr>
                    <th>GA4 API Key</th>
                    <td>
                        <input type="password" name="user_tracking_settings[ga4_api_key]" 
                               value="<?php echo esc_attr(get_option('user_tracking_settings')['ga4_api_key'] ?? ''); ?>" 
                               class="regular-text">
                        <p class="description">
                            <strong>Hướng dẫn lấy API Key:</strong><br>
                            1. Truy cập <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a><br>
                            2. Chọn project tương ứng với GA4 property<br>
                            3. Vào "APIs & Services" > "Credentials"<br>
                            4. Nhấn "Create Credentials" > "API Key"<br>
                            5. Sao chép key và dán vào ô trên
                        </p>
                        <p class="description">
                            <strong>Hướng dẫn lấy Property ID:</strong><br>
                            1. Truy cập <a href="https://analytics.google.com/" target="_blank">Google Analytics</a><br>
                            2. Chọn property GA4 cần kết nối<br>
                            3. Vào "Admin" (biểu tượng bánh răng)<br>
                            4. Trong cột "Property", chọn "Data Streams"<br>
                            5. Click vào stream > Sao chép "Measurement ID" (định dạng G-XXXXXXX)
                        </p>
                    </td>
                </tr>
            </table>
            <button id="test-ga4" class="button button-secondary">
                <span class="dashicons dashicons-admin-site"></span> Test GA4 Connection
            </button>
            <div id="ga4-test-results" style="margin-top:10px;"></div>
        </div>

        <div class="card">
            <h3>Environment Check</h3>
            <?php
            $db_check = UserTracking\Database::check_schema();
            if ($db_check !== true) : ?>
                <div class="notice notice-warning">
                    <p><strong>Database cần cập nhật:</strong> Thiếu các bảng/cột sau:</p>
                    <ul>
                        <?php foreach ($db_check as $missing) : ?>
                            <li><?php echo esc_html($missing); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="user_tracking_update_db">
                        <?php wp_nonce_field('user_tracking_update_db_nonce'); ?>
                        <button type="submit" class="button button-primary">Cập nhật Database</button>
                    </form>
                </div>
            <?php else : ?>
                <div class="notice notice-success">
                    <p>Tất cả bảng và cột cần thiết đã tồn tại trong database.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>System Information</h3>
            <table class="widefat">
                <tr>
                    <th>Last Data Purge</th>
                    <td><?php echo get_option('user_tracking_last_purge', 'Never'); ?></td>
                </tr>
                <tr>
                    <th>Tracking Active Since</th>
                    <td><?php echo get_option('user_tracking_install_date', 'Unknown'); ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#test-ga4').click(function(e) {
        e.preventDefault();
        $('#ga4-test-results').html('<p><span class="spinner is-active"></span> Testing GA4 connection...</p>');
        
        $.post(ajaxurl, {
            action: 'user_tracking_test_ga4',
            property_id: $('input[name="user_tracking_settings[ga4_property_id]"]').val(),
            api_key: $('input[name="user_tracking_settings[ga4_api_key]"]').val()
        }, function(response) {
            if (response.success) {
                $('#ga4-test-results').html('<div class="notice notice-success"><p>GA4 connection successful!</p></div>');
            } else {
                $('#ga4-test-results').html('<div class="notice notice-error"><p>GA4 test failed: ' + (response.data || 'Unknown error') + '</p></div>');
            }
        }).fail(function() {
            $('#ga4-test-results').html('<div class="notice notice-error"><p>Failed to send test request</p></div>');
        });
    });
});
</script>

<script>
jQuery(document).ready(function($) {
    $('#test-email').click(function(e) {
        e.preventDefault();
        $('#test-results').html('<p><span class="spinner is-active"></span> Testing email connection...</p>');
        
        $.post(ajaxurl, {
            action: 'user_tracking_test_email',
            email: $('input[name="user_tracking_settings[alert_email]"]').val()
        }, function(response) {
            if (response.success) {
                $('#test-results').html('<div class="notice notice-success"><p>Email test successful! Check your inbox.</p></div>');
            } else {
                $('#test-results').html('<div class="notice notice-error"><p>Email test failed: ' + (response.data || 'Unknown error') + '</p></div>');
            }
        }).fail(function() {
            $('#test-results').html('<div class="notice notice-error"><p>Failed to send test request</p></div>');
        });
    });

    $('#test-telegram').click(function(e) {
        e.preventDefault();
        $('#test-results').html('<p><span class="spinner is-active"></span> Testing Telegram connection...</p>');
        
        $.post(ajaxurl, {
            action: 'user_tracking_test_telegram',
            bot_token: $('input[name="user_tracking_settings[telegram_bot_token]"]').val(),
            chat_id: $('input[name="user_tracking_settings[telegram_chat_id]"]').val()
        }, function(response) {
            if (response.success) {
                $('#test-results').html('<div class="notice notice-success"><p>Telegram test successful! Check your Telegram.</p></div>');
            } else {
                $('#test-results').html('<div class="notice notice-error"><p>Telegram test failed: ' + (response.data || 'Unknown error') + '</p></div>');
            }
        }).fail(function() {
            $('#test-results').html('<div class="notice notice-error"><p>Failed to send test request</p></div>');
        });
    });

    $('#preview-alerts').click(function(e) {
        e.preventDefault();
        $('#test-results').html(`
            <div class="notice notice-info">
                <h4>Email Alert Preview:</h4>
                <p><strong>Subject:</strong> [User Tracking] Fraud Detected</p>
                <p><strong>Content:</strong><br>
                Fraudulent activity detected:<br>
                - IP: 192.168.1.1<br>
                - User Agent: Suspicious Bot<br>  
                - Country: Unknown<br>
                - Reason: Multiple failed attempts<br>
                - Time: ${new Date().toLocaleString()}</p>
            </div>
            <div class="notice notice-info" style="margin-top:10px;">
                <h4>Telegram Alert Preview:</h4>
                <p><strong>Content:</strong><br>
                🚨 Fraud Detected 🚨<br>
                IP: 192.168.1.1<br>
                User Agent: Suspicious Bot<br>
                Country: Unknown<br>
                Reason: Multiple failed attempts<br>
                Time: ${new Date().toLocaleString()}</p>
            </div>
        `);
    });
});
</script>