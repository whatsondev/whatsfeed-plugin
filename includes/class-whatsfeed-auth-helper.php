<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WhatsFeed Auth Helper Class
 * 
 * Provides helper functions for simplified OAuth authentication with Instagram and TikTok
 */
class WhatsFeed_Auth_Helper {
    
    /**
     * Generate a random state string for CSRF protection
     * 
     * @return string Random state string
     */
    public static function generate_state() {
        return wp_generate_password(12, false);
    }
    
    /**
     * Generate Instagram demo credentials for testing
     * 
     * @return array Demo credentials
     */
    public static function generate_instagram_demo_credentials() {
        // These are dummy values that will be replaced with the actual demo credentials
        $demo_credentials = array(
            'app_id' => 'demo_app_id_' . substr(md5(site_url()), 0, 8),
            'app_secret' => 'demo_app_secret_' . substr(md5(site_url() . time()), 0, 12),
            'access_token' => 'demo_access_token_' . substr(md5(site_url() . time()), 0, 16),
            'user_id' => '12345678901234567',
        );
        
        // Save the demo credentials
        update_option('whatsfeed_app_id', $demo_credentials['app_id']);
        update_option('whatsfeed_app_secret', $demo_credentials['app_secret']);
        update_option('whatsfeed_access_token', $demo_credentials['access_token']);
        update_option('whatsfeed_user_id', $demo_credentials['user_id']);
        
        // Set a transient to indicate we're using demo credentials
        set_transient('whatsfeed_using_demo_credentials', 'instagram', DAY_IN_SECONDS * 7);
        
        return $demo_credentials;
    }
    
    /**
     * Generate TikTok demo credentials for testing
     * 
     * @return array Demo credentials
     */
    public static function generate_tiktok_demo_credentials() {
        // These are dummy values that will be replaced with the actual demo credentials
        $demo_credentials = array(
            'client_key' => 'demo_client_key_' . substr(md5(site_url()), 0, 8),
            'client_secret' => 'demo_client_secret_' . substr(md5(site_url() . time()), 0, 12),
            'access_token' => 'demo_access_token_' . substr(md5(site_url() . time()), 0, 16),
            'open_id' => 'demo_open_id_' . substr(md5(site_url() . time()), 0, 10),
            'username' => 'demo_tiktok_user',
        );
        
        // Save the demo credentials
        update_option('whatsfeed_tiktok_client_key', $demo_credentials['client_key']);
        update_option('whatsfeed_tiktok_client_secret', $demo_credentials['client_secret']);
        update_option('whatsfeed_tiktok_access_token', $demo_credentials['access_token']);
        update_option('whatsfeed_tiktok_open_id', $demo_credentials['open_id']);
        update_option('whatsfeed_tiktok_username', $demo_credentials['username']);
        
        // Set a transient to indicate we're using demo credentials
        set_transient('whatsfeed_using_demo_credentials', 'tiktok', DAY_IN_SECONDS * 7);
        
        return $demo_credentials;
    }
    
    /**
     * Check if we're using demo credentials
     * 
     * @return string|false Platform name if using demo credentials, false otherwise
     */
    public static function is_using_demo_credentials() {
        return get_transient('whatsfeed_using_demo_credentials');
    }
    
    /**
     * Clear demo credentials for a specific platform
     * 
     * @param string $platform Platform to clear credentials for ('instagram' or 'tiktok')
     * @return bool True if credentials were cleared, false otherwise
     */
    public static function clear_demo_credentials($platform) {
        if ($platform === 'instagram') {
            delete_option('whatsfeed_app_id');
            delete_option('whatsfeed_app_secret');
            delete_option('whatsfeed_access_token');
            delete_option('whatsfeed_user_id');
        } elseif ($platform === 'tiktok') {
            delete_option('whatsfeed_tiktok_client_key');
            delete_option('whatsfeed_tiktok_client_secret');
            delete_option('whatsfeed_tiktok_access_token');
            delete_option('whatsfeed_tiktok_open_id');
            delete_option('whatsfeed_tiktok_username');
        } else {
            return false;
        }
        
        // Clear the transient if it matches the platform
        if (get_transient('whatsfeed_using_demo_credentials') === $platform) {
            delete_transient('whatsfeed_using_demo_credentials');
        }
        
        return true;
    }
    
    /**
     * Add a notice about using demo credentials
     */
    public static function add_demo_credentials_notice() {
        $platform = self::is_using_demo_credentials();
        
        if (!$platform) {
            return;
        }
        
        $platform_name = $platform === 'instagram' ? 'Instagram' : 'TikTok';
        
        add_action('admin_notices', function() use ($platform, $platform_name) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong>WhatsFeed Notice:</strong> You are currently using demo credentials for <?php echo esc_html($platform_name); ?>. These credentials are for testing purposes only and will not fetch real data.</p>
                <p><a href="<?php echo esc_url(admin_url('admin.php?page=whatsfeed-settings&clear_demo=' . $platform)); ?>" class="button">Clear Demo Credentials</a></p>
            </div>
            <?php
        });
    }
}

// Handle clearing demo credentials
add_action('admin_init', function() {
    if (isset($_GET['clear_demo']) && in_array($_GET['clear_demo'], ['instagram', 'tiktok'])) {
        $platform = sanitize_text_field($_GET['clear_demo']);
        WhatsFeed_Auth_Helper::clear_demo_credentials($platform);
        wp_redirect(admin_url('admin.php?page=whatsfeed-settings'));
        exit;
    }
});

// Add notice about demo credentials
WhatsFeed_Auth_Helper::add_demo_credentials_notice();