<?php
// Load WordPress
require_once('../../../../wp-load.php');

// Set the username to test with
$test_username = 'armancse.rise';

// Clear any existing username options
delete_option('whatsfeed_instagram_username');
delete_option('whatsfeed_username');

// Set the username using the dashboard option name
update_option('whatsfeed_username', $test_username);

// Clear any cached data
delete_transient('whatsfeed_instagram_' . md5($test_username));

// Include the Instagram class
require_once('includes/class-whatsfeed-instagram.php');

// Check current settings
echo "<h2>Current Settings</h2>";
echo "whatsfeed_username: " . get_option('whatsfeed_username') . "<br>";
echo "whatsfeed_instagram_username: " . get_option('whatsfeed_instagram_username') . "<br>";
echo "whatsfeed_access_token: " . (get_option('whatsfeed_access_token') ? 'Set' : 'Not set') . "<br>";

// Test fetch_feed_with_priority
echo "<h2>Testing fetch_feed_with_priority</h2>";
$result = WhatsFeed_Instagram::fetch_feed_with_priority(12);

if (is_wp_error($result)) {
    echo "<p style='color: red;'>Error: " . $result->get_error_message() . "</p>";
} else {
    echo "<p style='color: green;'>Success! Found " . count($result) . " posts.</p>";
    
    if (!empty($result)) {
        echo "<h3>First Post Details:</h3>";
        echo "<pre>";
        print_r($result[0]);
        echo "</pre>";
    }
}

// Check if the username was synchronized
echo "<h2>After Test</h2>";
echo "whatsfeed_username: " . get_option('whatsfeed_username') . "<br>";
echo "whatsfeed_instagram_username: " . get_option('whatsfeed_instagram_username') . "<br>";