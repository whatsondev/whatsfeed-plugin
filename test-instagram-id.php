<?php
// Test script for Instagram User ID retrieval

// Define plugin directory if not already defined
if (!defined('WHATSFEED_PLUGIN_DIR')) {
    define('WHATSFEED_PLUGIN_DIR', __DIR__ . '/');
}

// Include WordPress functions
require_once('D:/projects/xampp/htdocs/whatson.guide/wp-load.php');

// Include necessary files
require_once WHATSFEED_PLUGIN_DIR . 'includes/class-whatsfeed-instagram.php';

echo "<h1>Instagram User ID Test</h1>";

// Test usernames
$usernames = ['instagram', 'cristiano', 'nike'];

foreach ($usernames as $username) {
    echo "<h2>Testing username: {$username}</h2>";
    
    // Start timer
    $start_time = microtime(true);
    
    // Get user ID
    $user_id = WhatsFeed_Instagram::get_user_id_from_username($username);
    
    // End timer
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
    
    echo "<div style='margin-bottom: 20px; padding: 10px; border: 1px solid #ccc;'>";
    
    if (is_wp_error($user_id)) {
        echo "<p style='color: red;'>Error: " . $user_id->get_error_message() . "</p>";
    } else {
        echo "<p style='color: green;'>Success! User ID: <strong>{$user_id}</strong></p>";
    }
    
    echo "<p>Execution time: {$execution_time} ms</p>";
    echo "</div>";
}

// Test the whatsfeed_get_user_id function if available
if (file_exists(WHATSFEED_PLUGIN_DIR . 'admin/settings-page.php')) {
    require_once WHATSFEED_PLUGIN_DIR . 'admin/settings-page.php';
    
    if (function_exists('whatsfeed_get_user_id')) {
        echo "<h2>Testing whatsfeed_get_user_id function</h2>";
        
        // Create a test token
        $test_token = 'IGQWRPa1ZA' . str_repeat('x', 150);
        
        // Start timer
        $start_time = microtime(true);
        
        // Get user ID
        $user_id = whatsfeed_get_user_id($test_token);
        
        // End timer
        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
        
        echo "<div style='margin-bottom: 20px; padding: 10px; border: 1px solid #ccc;'>";
        
        if (empty($user_id)) {
            echo "<p style='color: red;'>Error: No user ID returned</p>";
        } else {
            echo "<p style='color: green;'>Success! User ID: <strong>{$user_id}</strong></p>";
        }
        
        echo "<p>Execution time: {$execution_time} ms</p>";
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>whatsfeed_get_user_id function not available</p>";
    }
}

// Display current option values
echo "<h2>Current Option Values</h2>";
echo "<div style='margin-bottom: 20px; padding: 10px; border: 1px solid #ccc;'>";
echo "<p>whatsfeed_username: " . get_option('whatsfeed_username', 'Not set') . "</p>";
echo "<p>whatsfeed_instagram_username: " . get_option('whatsfeed_instagram_username', 'Not set') . "</p>";
echo "<p>whatsfeed_user_id: " . get_option('whatsfeed_user_id', 'Not set') . "</p>";
echo "<p>whatsfeed_access_token: " . (get_option('whatsfeed_access_token') ? 'Set (not shown)' : 'Not set') . "</p>";
echo "</div>";

// Set the whatsfeed_username option to match the whatsfeed_instagram_username option
echo "<h2>Setting whatsfeed_username option</h2>";
$instagram_username = get_option('whatsfeed_instagram_username', '');
if (!empty($instagram_username)) {
    update_option('whatsfeed_username', $instagram_username);
    echo "<p style='color: green;'>Successfully set whatsfeed_username to: {$instagram_username}</p>";
} else {
    echo "<p style='color: orange;'>No Instagram username found to set</p>";
}

// Display updated option values
echo "<h2>Updated Option Values</h2>";
echo "<div style='margin-bottom: 20px; padding: 10px; border: 1px solid #ccc;'>";
echo "<p>whatsfeed_username: " . get_option('whatsfeed_username', 'Not set') . "</p>";
echo "<p>whatsfeed_instagram_username: " . get_option('whatsfeed_instagram_username', 'Not set') . "</p>";
echo "<p>whatsfeed_user_id: " . get_option('whatsfeed_user_id', 'Not set') . "</p>";
echo "<p>whatsfeed_access_token: " . (get_option('whatsfeed_access_token') ? 'Set (not shown)' : 'Not set') . "</p>";
echo "</div>";