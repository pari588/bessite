<?php
/**
 * HRMS Base Route - Redirect to login or home
 * This file handles /hrms/ base URL
 */

// Check if function exists (loaded from inc file)
if (function_exists('isHRMSLoggedIn') && isHRMSLoggedIn()) {
    header('Location: ' . SITEURL . '/hrms/home/');
    exit;
} else {
    header('Location: ' . SITEURL . '/hrms/login/');
    exit;
}
?>
