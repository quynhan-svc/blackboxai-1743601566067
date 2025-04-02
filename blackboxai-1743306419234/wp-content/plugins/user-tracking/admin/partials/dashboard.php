<div class="wrap user-tracking-dashboard">
    <h1>User Tracking Dashboard</h1>
    
    <div class="loading-overlay" style="display:none;">
        <div class="spinner is-active"></div>
        <p>Loading data...</p>
    </div>

    <div class="dashboard-content">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Sessions</h3>
                <p><?php echo number_format($stats['total_sessions']); ?></p>
            </div>
            <div class="stat-card">
                <h3>Today's Sessions</h3>
                <p><?php echo number_format($stats['today_sessions']); ?></p>
            </div>
            <div class="stat-card">
                <h3>Fraud Attempts</h3>
                <p><?php echo number_format($stats['fraud_attempts']); ?></p>
            </div>
        </div>

        <div class="chart-container" style="max-height: 600px;">
            <h2>Sessions Over Time (Last 30 Days)</h2>
            <div style="height: 500px;">
                <canvas id="sessionsChart"></canvas>
            </div>
        </div>

        <div class="top-countries">
            <h2>Top Countries</h2>
            <ul>
                <?php foreach ($stats['top_countries'] as $country): ?>
                    <li>
                        <span class="country-name"><?php echo esc_html($country->country ?: 'Unknown'); ?></span>
                        <span class="country-count"><?php echo number_format($country->count); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Show loading indicator
    $('.loading-overlay').show();
    $('.dashboard-content').hide();
    
    // Load data asynchronously with timeout
    var loadData = $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'user_tracking_load_dashboard'
        },
        timeout: 10000, // 10 seconds timeout
        success: function(response) {
            if (response.success && response.data) {
                // Update dashboard content
                $('.loading-overlay').hide();
                
                if (response.data.stats.total_sessions > 0) {
                    $('.dashboard-content').show();
                    if(response.data.chart_data) {
                        updateChart(response.data.chart_data);
                    }
                } else {
                    $('.dashboard-content').html(
                        '<div class="notice notice-info">' +
                        '<p>Chưa có dữ liệu tracking nào. Plugin sẽ tự động thu thập dữ liệu khi có người dùng truy cập website.</p>' +
                        '</div>'
                    ).show();
                }
            }
        },
        error: function(xhr, status, error) {
            $('.loading-overlay').hide();
            $('.dashboard-content').html(
                '<div class="notice notice-info">' +
                '<p>Chưa có dữ liệu tracking nào. Plugin sẽ tự động thu thập dữ liệu khi có người dùng truy cập website.</p>' +
                '</div>'
            ).show();
        }
    });

    function updateChart(data) {
        const ctx = document.getElementById('sessionsChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Sessions',
                    data: data.values,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
});
</script>
