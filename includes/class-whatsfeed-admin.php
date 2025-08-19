<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WhatsFeed_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_menu() {
        add_options_page(
            'WhatsFeed Settings',
            'WhatsFeed',
            'manage_options',
            'whatsfeed',
            [ $this, 'settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'whatsfeed_settings', 'whatsfeed_instagram_token' );
        register_setting( 'whatsfeed_settings', 'whatsfeed_tiktok_token' );
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>WhatsFeed Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'whatsfeed_settings' ); ?>
                <?php do_settings_sections( 'whatsfeed_settings' ); ?>
                
                <h2>Instagram</h2>
                <input type="text" name="whatsfeed_instagram_token" 
                       value="<?php echo esc_attr( get_option('whatsfeed_instagram_token') ); ?>" 
                       placeholder="Enter Instagram Access Token" class="regular-text">
                
                <h2>TikTok</h2>
                <input type="text" name="whatsfeed_tiktok_token" 
                       value="<?php echo esc_attr( get_option('whatsfeed_tiktok_token') ); ?>" 
                       placeholder="Enter TikTok Access Token" class="regular-text">
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
