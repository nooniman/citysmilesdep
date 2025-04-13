<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/auth0-config.php';

echo "<h1>Auth0 State Test</h1>";

// Display all cookies for debugging
echo "<h2>Current Cookies:</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

// Check if we have a cookie secret
if (!isset($config['cookie_secret'])) {
    echo "<p style='color:red'>⚠️ No cookie_secret defined in auth0-config.php - Auth0 will fail!</p>";
} else {
    echo "<p style='color:green'>✓ Cookie secret properly defined.</p>";
}

try {
    $auth0 = new \Auth0\SDK\Auth0([
        'strategy' => 'webapp',
        'domain' => $config['domain'],
        'clientId' => $config['client_id'],
        'clientSecret' => $config['client_secret'],
        'redirectUri' => $config['redirect_uri'],
        'cookieSecret' => $config['cookie_secret']
    ]);
    
    echo "<p style='color:green'>✓ Auth0 SDK initialized successfully.</p>";
    
    // Test cookie functionality
    $testCookie = 'auth0_test_' . time();
    setcookie($testCookie, 'test_value', time() + 3600, '/');
    
    echo "<p>Set test cookie: {$testCookie}</p>";
    echo "<p>Refresh this page to see if the cookie appears above.</p>";
    
    // Generate login URL with debug parameter
    $loginUrl = $auth0->login();
    
    echo "<p>Login URL: <a href='" . htmlspecialchars($loginUrl) . "'>" . htmlspecialchars($loginUrl) . "</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p style='color:red'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>