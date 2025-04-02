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