<div class="wrap">
    <h1>Fraud Detection Logs</h1>

    <div class="fraud-logs-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                    <th>Reason</th>
                    <th>Blocked</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fraud_logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html(date('Y-m-d H:i', strtotime($log->created_at))); ?></td>
                        <td><?php echo esc_html($log->ip_address); ?></td>
                        <td><?php echo esc_html(substr($log->user_agent, 0, 50)); ?></td>
                        <td><?php echo esc_html($log->reason); ?></td>
                        <td><?php echo $log->is_blocked ? 'Yes' : 'No'; ?></td>
                        <td>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_block_status">
                                <input type="hidden" name="log_id" value="<?php echo $log->log_id; ?>">
                                <input type="hidden" name="current_status" value="<?php echo $log->is_blocked; ?>">
                                <?php wp_nonce_field('toggle_block_status_nonce'); ?>
                                <button type="submit" class="button button-small">
                                    <?php echo $log->is_blocked ? 'Unblock' : 'Block'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="fraud-actions">
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="export_fraud_logs">
            <?php wp_nonce_field('export_fraud_logs_nonce'); ?>
            <button type="submit" class="button button-primary">Export to CSV</button>
        </form>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-left:10px;">
            <input type="hidden" name="action" value="clear_fraud_logs">
            <?php wp_nonce_field('clear_fraud_logs_nonce'); ?>
            <button type="submit" class="button button-danger">Clear All Logs</button>
        </form>
    </div>
</div>