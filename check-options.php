<?php
// Load WordPress
require_once('d:/projects/xampp/htdocs/whatson.guide/wp-load.php');

// Check Instagram username option
$instagram_username = get_option('whatsfeed_instagram_username', '');
echo "Current Instagram username setting: '{$instagram_username}'\n";

// Check if the option exists at all
global $wpdb;
$option_exists = $wpdb->get_var("SELECT option_id FROM {$wpdb->options} WHERE option_name = 'whatsfeed_instagram_username'");
echo "Option exists in database: " . ($option_exists ? "Yes (ID: {$option_exists})" : "No") . "\n";

// List all whatsfeed options
echo "\nAll WhatsFeed options:\n";
$like = $wpdb->esc_like('whatsfeed_') . '%';
$options = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE '{$like}'");

foreach ($options as $option) {
    echo "{$option->option_name}: {$option->option_value}\n";
}