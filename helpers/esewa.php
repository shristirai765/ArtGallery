<?php
// eSewa Configuration for Monet Art Gallery

// Test/Sandbox Credentials
define('ESEWA_MERCHANT_CODE', 'EPAYTEST');
define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');
define('ESEWA_TEST_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form');

// Live Credentials (uncomment when going live)
// define('ESEWA_MERCHANT_CODE', 'YOUR_LIVE_MERCHANT_CODE');
// define('ESEWA_SECRET_KEY', 'YOUR_LIVE_SECRET_KEY');
// define('ESEWA_TEST_URL', 'https://epay.esewa.com.np/api/epay/main/v2/form');

// Base URL for callbacks
define('BASE_URL', 'http://localhost/monets_atelier');
?>