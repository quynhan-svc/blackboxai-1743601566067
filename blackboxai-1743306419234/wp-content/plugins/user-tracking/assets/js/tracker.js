document.addEventListener('DOMContentLoaded', function() {
    // Collect device information
    const deviceInfo = {
        browser: getBrowser(),
        os: getOS(),
        screenWidth: window.screen.width,
        screenHeight: window.screen.height,
        deviceType: getDeviceType()
    };

    // Get geolocation from IP
    fetch('https://ipapi.co/json/')
        .then(response => response.json())
        .then(geoData => {
            const trackingData = {
                action: 'user_tracking_log',
                security: userTracking.nonce,
                country: geoData.country_name,
                city: geoData.city,
                user_agent: navigator.userAgent,
                device_info: deviceInfo,
                referrer: document.referrer,
                page_url: window.location.href
            };

            // Send initial page view
            sendTrackingData(trackingData);

            // Track time on page
            let startTime = new Date();
            window.addEventListener('beforeunload', function() {
                trackingData.time_on_page = Math.round((new Date() - startTime) / 1000);
                sendTrackingData(trackingData);
            });
        });

    function sendTrackingData(data) {
        navigator.sendBeacon(userTracking.ajaxurl + '?' + new URLSearchParams(data));
    }

    // Helper functions for device detection
    function getBrowser() {
        const userAgent = navigator.userAgent;
        if (userAgent.includes('Firefox')) return 'Firefox';
        if (userAgent.includes('SamsungBrowser')) return 'Samsung Browser';
        if (userAgent.includes('Opera') || userAgent.includes('OPR')) return 'Opera';
        if (userAgent.includes('Trident')) return 'IE';
        if (userAgent.includes('Edge')) return 'Edge';
        if (userAgent.includes('Chrome')) return 'Chrome';
        if (userAgent.includes('Safari')) return 'Safari';
        return 'Unknown';
    }

    function getOS() {
        const userAgent = navigator.userAgent;
        if (userAgent.includes('Android')) return 'Android';
        if (userAgent.includes('iPhone') || userAgent.includes('iPad')) return 'iOS';
        if (userAgent.includes('Windows')) return 'Windows';
        if (userAgent.includes('Mac')) return 'MacOS';
        if (userAgent.includes('Linux')) return 'Linux';
        return 'Unknown';
    }

    function getDeviceType() {
        const userAgent = navigator.userAgent;
        if (/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i.test(userAgent)) {
            return 'Tablet';
        }
        if (/Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/.test(userAgent)) {
            return 'Mobile';
        }
        return 'Desktop';
    }
});