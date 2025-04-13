<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\admin_check.php

/**
 * Admin Access Control
 * 
 * This file verifies if the current user has administrative access permissions.
 * Include this file at the top of all admin pages to restrict access.
 */

session_start();

// Function to check if user has admin permissions
function hasAdminAccess()
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        return false;
    }

    $allowed_roles = ['admin', 'staff', 'dentist', 'assistant', 'intern'];
    return in_array($_SESSION['role'], $allowed_roles);
}

// Redirect non-admin users to the appropriate page
if (!hasAdminAccess()) {
    // Check if user is logged in but not an admin
    if (isset($_SESSION['user_id'])) {
        // User is logged in but doesn't have admin rights
        header("Location:../../old/userdashboard/dashboard.php");
    } else {
        // User is not logged in at all
        header("Location: ../login/login.php");
    }
    exit();
}
?>