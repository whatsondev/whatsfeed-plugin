<?php
/**
 * Plugin Name: WhatsFeed – Social Media Feed
 * Description: Simple Instagram and TikTok Feed plugin with caching & styling.
 * Version: 1.3
 * Author: WhatsOn Agency
 * Author URI: https://whatson.agency
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Define plugin constants
define('WHATSFEED_VERSION', '1.2');
define('WHATSFEED_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WHATSFEED_PLUGIN_URL', plugin_dir_url(__FILE__));

// Enqueue frontend styles & scripts
add_action('wp_enqueue_scripts', 'whatsfeed_enqueue_assets');
function whatsfeed_enqueue_assets() {
    // Swiper CSS + JS
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0');
    wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true);

    // Plugin CSS
    $css_file = WHATSFEED_PLUGIN_DIR . 'assets/css/whatsfeed.css';
    if (file_exists($css_file)) {
        wp_enqueue_style(
            'whatsfeed-style',
            WHATSFEED_PLUGIN_URL . 'assets/css/whatsfeed.css',
            [],
            filemtime($css_file)
        );
    }

    // Plugin JS (depends on Swiper)
    $js_file = WHATSFEED_PLUGIN_DIR . 'assets/js/whatsfeed.js';
    if (file_exists($js_file)) {
        wp_enqueue_script(
            'whatsfeed-js',
            WHATSFEED_PLUGIN_URL . 'assets/js/whatsfeed.js',
            ['swiper-js'],
            filemtime($js_file),
            true
        );
    }
}

// Plugin activation hook
register_activation_hook(__FILE__, 'whatsfeed_activate');
function whatsfeed_activate() {
    // Set default options
    if (!get_option('whatsfeed_default_limit')) {
        update_option('whatsfeed_default_limit', 6);
    }
    if (!get_option('whatsfeed_grid_columns')) {
        update_option('whatsfeed_grid_columns', 3);
    }
    
    // Create necessary directories
    $upload_dir = wp_upload_dir();
    $whatsfeed_dir = $upload_dir['basedir'] . '/whatsfeed-cache';
    if (!file_exists($whatsfeed_dir)) {
        wp_mkdir_p($whatsfeed_dir);
    }
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'whatsfeed_deactivate');
function whatsfeed_deactivate() {
    // Clear all cached data
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_whatsfeed_cache_%' OR option_name LIKE '_transient_timeout_whatsfeed_cache_%'");
}

// Load required files
require_once WHATSFEED_PLUGIN_DIR . 'includes/class-whatsfeed-cache.php';
require_once WHATSFEED_PLUGIN_DIR . 'includes/class-whatsfeed-instagram.php';
require_once WHATSFEED_PLUGIN_DIR . 'includes/class-whatsfeed-tiktok.php';
require_once WHATSFEED_PLUGIN_DIR . 'includes/class-whatsfeed-shortcode.php';
require_once WHATSFEED_PLUGIN_DIR . 'includes/class-whatsfeed-auth-helper.php';
require_once WHATSFEED_PLUGIN_DIR . 'includes/class-whatsfeed-ajax.php';
require_once WHATSFEED_PLUGIN_DIR . 'admin/settings-page.php';

// Initialize shortcode
$whatsfeed_shortcode = new WhatsFeed_Shortcode();

// Register username-only Instagram shortcode
add_shortcode('whatsfeed_instagram_username', [$whatsfeed_shortcode, 'instagram_username_shortcode']);

// Legacy shortcode support
add_shortcode('instagram_feed', 'whatsfeed_legacy_shortcode_handler');
function whatsfeed_legacy_shortcode_handler($atts) {
    $atts = shortcode_atts([
        'limit' => get_option('whatsfeed_default_limit', 6),
        'columns' => get_option('whatsfeed_grid_columns', 3),
        'layout' => 'grid',
        'show_captions' => 'yes',
        'open_in_popup' => 'yes',
        'source' => 'instagram'
    ], $atts);
    
    return do_shortcode('[whatsfeed source="instagram" limit="' . $atts['limit'] . '" layout="' . $atts['layout'] . '" columns="' . $atts['columns'] . '" show_captions="' . $atts['show_captions'] . '" open_in_popup="' . $atts['open_in_popup'] . '"]');
}

// Add admin notices for missing configuration
add_action('admin_notices', 'whatsfeed_admin_notices');
function whatsfeed_admin_notices() {
    $screen = get_current_screen();
    if ($screen->id !== 'toplevel_page_whatsfeed-settings') {
        return;
    }
    
    $instagram_token = get_option('whatsfeed_access_token');
    $instagram_user_id = get_option('whatsfeed_user_id');
    $tiktok_token = get_option('whatsfeed_tiktok_access_token');
    $tiktok_open_id = get_option('whatsfeed_tiktok_open_id');
    $default_source = get_option('whatsfeed_default_source', 'instagram');
    
    $notices = [];
    
    if ($default_source === 'instagram' && (empty($instagram_token) || empty($instagram_user_id))) {
        $notices[] = '<div class="notice notice-warning"><p><strong>WhatsFeed:</strong> Please configure your Instagram credentials to display Instagram feeds. <a href="' . admin_url('admin.php?page=whatsfeed-settings') . '#instagram-tab">Go to Instagram Settings</a></p></div>';
    }
    
    if ($default_source === 'tiktok' && (empty($tiktok_token) || empty($tiktok_open_id))) {
        $notices[] = '<div class="notice notice-warning"><p><strong>WhatsFeed:</strong> Please configure your TikTok credentials to display TikTok feeds. <a href="' . admin_url('admin.php?page=whatsfeed-settings') . '#tiktok-tab">Go to TikTok Settings</a></p></div>';
    }
    
    foreach ($notices as $notice) {
        echo $notice;
    }
}

// Add settings link to plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'whatsfeed_settings_link');
function whatsfeed_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=whatsfeed-settings') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}