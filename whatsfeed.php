<?php
/**
 * Plugin Name: WhatsFeed – Instagram Feed
 * Description: Simple Instagram Feed plugin (Paste Token Method) with caching & styling.
 * Version: 1.0
 * Author: WhatsOn Agency
 * Author URI: https://whatson.agency
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Enqueue styles
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'whatsfeed-style',
        plugin_dir_url(__FILE__) . 'assets/css/whatsfeed.css',
        [],
        '1.0'
    );
});

// Load Admin Settings
require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php';

// Load Shortcode
require_once plugin_dir_path(__FILE__) . 'public/display-feed.php';
