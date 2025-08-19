<?php
/**
 * Plugin Name: WhatsFeed – Instagram & TikTok Feed Fetcher
 * Description: Lightweight plugin to fetch and display Instagram & TikTok feeds with grid or carousel layout.
 * Version: 1.0.0
 * Author: WhatsOn
 * Author URI: https://whatson.agency/
 * Text Domain: whatsfeed
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Enqueue frontend CSS/JS
add_action( 'wp_enqueue_scripts', function() {
    // CSS
    wp_enqueue_style( 'whatsfeed-css', WHATSFEED_URL . 'assets/css/whatsfeed.css', [], '1.0.0' );
    wp_enqueue_style( 'swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0' );

    // JS
    wp_enqueue_script( 'swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true );
    wp_enqueue_script( 'whatsfeed-js', WHATSFEED_URL . 'assets/js/whatsfeed.js', [ 'swiper-js' ], '1.0.0', true );
});

// Define constants
define( 'WHATSFEED_PATH', plugin_dir_path( __FILE__ ) );
define( 'WHATSFEED_URL', plugin_dir_url( __FILE__ ) );

// Includes
require_once WHATSFEED_PATH . 'includes/class-whatsfeed-admin.php';
require_once WHATSFEED_PATH . 'includes/class-whatsfeed-cache.php';
require_once WHATSFEED_PATH . 'includes/class-whatsfeed-instagram.php';
require_once WHATSFEED_PATH . 'includes/class-whatsfeed-tiktok.php';
require_once WHATSFEED_PATH . 'includes/class-whatsfeed-shortcode.php';

// Init
add_action( 'plugins_loaded', function() {
    new WhatsFeed_Admin();
    new WhatsFeed_Shortcode();
});
