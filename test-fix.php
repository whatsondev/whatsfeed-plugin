<?php
// Load WordPress
require_once('d:/projects/xampp/htdocs/whatson.guide/wp-load.php');

// Set the Instagram username
$instagram_username = 'jarin_rahi';
echo "Testing Instagram API fix for username: '{$instagram_username}'\n\n";

// Include the Instagram class
require_once(plugin_dir_path(__FILE__) . 'includes/class-whatsfeed-instagram.php');

// Check current settings
$current_username = get_option('whatsfeed_instagram_username', '');
echo "Current Instagram username setting: '{$current_username}'\n";

$access_token = get_option('whatsfeed_access_token', '');
echo "Access token exists: " . (!empty($access_token) ? 'Yes' : 'No') . "\n";

// Clear cache
$cache_key = 'whatsfeed_instagram_' . md5($instagram_username . '_12');
delete_transient($cache_key);
echo "Cleared cache for username '{$instagram_username}'\n";

// Test the fetch_by_username method directly
echo "\nTesting fetch_by_username method directly...\n";
$feed = WhatsFeed_Instagram::fetch_by_username($instagram_username, 12);

// Check if the feed was fetched successfully
if (is_wp_error($feed)) {
    echo "Error: " . $feed->get_error_message() . "\n";
    echo "Error code: " . $feed->get_error_code() . "\n";
} else {
    echo "Success! Feed fetched with " . count($feed) . " posts.\n";
    
    // Display the first post details
    if (!empty($feed)) {
        echo "\nFirst post details:\n";
        $first_post = $feed[0];
        echo "- Type: " . $first_post['type'] . "\n";
        echo "- URL: " . $first_post['permalink'] . "\n";
        echo "- Caption: " . (isset($first_post['caption']) ? substr($first_post['caption'], 0, 100) . '...' : 'No caption') . "\n";
        echo "- Timestamp: " . $first_post['timestamp'] . "\n";
        echo "- Media URL: " . $first_post['media_url'] . "\n";
    }
}

echo "\nTest completed.\n";