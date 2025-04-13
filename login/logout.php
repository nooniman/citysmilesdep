<?php
require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/auth0-config.php';

session_start();

// Check if the user logged in via Auth0
$auth0_logout = isset($_SESSION['auth0_user']);

// Destroy session
session_destroy();

if ($auth0_logout) {
    try {
        $auth0 = new \Auth0\SDK\Auth0([
            'strategy' => 'webapp',
            'domain' => $config['domain'],
            'clientId' => $config['client_id'],
            'clientSecret' => $config['client_secret'],
            'redirectUri' => $config['redirect_uri'],
            'cookieSecret' => $config['cookie_secret']
        ]);

        // FIXED: Updated logout method parameters
        // First parameter is the return URI as a string (not an array)
        $returnTo = 'http://localhost/CitySmilesRepo/old/login/login.php';
        $logout_url = $auth0->logout($returnTo);

        header('Location: ' . $logout_url);
        exit;
    } catch (Exception $e) {
        // If Auth0 logout fails, fall back to regular logout
        header("Location: login.php");
        exit;
    }
} else {
    // Regular logout
    header("Location: login.php");
    exit;
}
?>