<?php
// Load WordPress
require_once('d:/projects/xampp/htdocs/whatson.guide/wp-load.php');

// Get the current username value
$current_username = get_option('whatsfeed_instagram_username', '');
echo "Current Instagram username: '{$current_username}'\n";

// Get the whatsfeed_username value
$whatsfeed_username = get_option('whatsfeed_username', '');
echo "WhatsFeed username: '{$whatsfeed_username}'\n";

// Set the new username
$new_username = 'jarin_rahi';
echo "New username to set: '{$new_username}'\n";

// Update the Instagram username option
$result = update_option('whatsfeed_instagram_username', $new_username);
echo "Updated Instagram username to '{$new_username}': " . ($result ? 'Success' : 'Failed') . "\n";

// Verify the update
$updated_username = get_option('whatsfeed_instagram_username', '');
echo "Instagram username after update: '{$updated_username}'\n";