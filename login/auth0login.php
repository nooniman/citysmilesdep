<?php
// Prevent any output before headers
ob_start();

// Include required files
require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/auth0-config.php';

try {
    // Simplified Auth0 configuration
    $auth0 = new \Auth0\SDK\Auth0([
        'domain' => $config['domain'],
        'clientId' => $config['client_id'],
        'clientSecret' => $config['client_secret'],
        'redirectUri' => $config['redirect_uri'],
        'cookieSecret' => $config['cookie_secret']
    ]);

    // Generate login URL
    $loginUrl = $auth0->login();
    
    // Redirect to Auth0
    ob_end_clean(); // Clear any output
    header('Location: ' . $loginUrl);
    exit;
    
} catch (Exception $e) {
    ob_end_clean(); // Clear output buffer on error
    echo "<h2>Auth0 Error:</h2>";
    echo "<p style='color:red'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "<p><a href='login.php'>Back to login</a></p>";
}
?>