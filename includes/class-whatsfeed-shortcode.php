<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WhatsFeed_Shortcode {

    public function __construct() {
        add_shortcode( 'whatsfeed', [ $this, 'render_feed' ] );
        add_shortcode( 'whatsfeed_instagram_username', [ $this, 'instagram_username_shortcode' ] );
        add_shortcode( 'instagram_feed', [ $this, 'instagram_feed_from_settings' ] );
        add_shortcode( 'whatsfeed_tiktok_username', [ $this, 'tiktok_username_shortcode' ] );
        add_shortcode( 'tiktok_feed', [ $this, 'tiktok_feed_from_settings' ] );
    }

    public function render_feed( $atts ) {
        $atts = shortcode_atts( [
            'source' => 'instagram', // instagram | tiktok | both
            'limit'  => 6,
            'layout' => 'grid',      // grid | carousel
            'columns' => 3,
            'open_in_popup' => 'yes',
            'show_captions' => 'yes',
        ], $atts );

        // Handle 'both' source type or specific source
        if ($atts['source'] === 'both') {
            // Try to get Instagram data
            $instagram_username = get_option('whatsfeed_instagram_username', '');
            
            // If username is empty, try the other option name
            if (empty($instagram_username)) {
                $instagram_username = get_option('whatsfeed_username', '');
                // If we found a username in the other option, update the primary option
                if (!empty($instagram_username)) {
                    update_option('whatsfeed_instagram_username', $instagram_username);
                    error_log('Updated whatsfeed_instagram_username from whatsfeed_username: ' . $instagram_username);
                }
            }
            
            if (!empty($instagram_username)) {
                $instagram_data = WhatsFeed_Instagram::fetch_by_username($instagram_username, $atts['limit']);
            } else {
                $instagram_data = WhatsFeed_Instagram::fetch_feed_with_token($atts['limit']);
            }
            
            // Try to get TikTok data
            $tiktok_data = WhatsFeed_TikTok::fetch_feed($atts['limit']);
            
            // Combine data if both are available and not errors
            if (!is_wp_error($instagram_data) && !is_wp_error($tiktok_data) && !empty($instagram_data) && !empty($tiktok_data)) {
                $data = array_merge($instagram_data, $tiktok_data);
                // Limit the combined results to the requested limit
                $data = array_slice($data, 0, $atts['limit']);
            } elseif (!is_wp_error($instagram_data) && !empty($instagram_data)) {
                // Only Instagram data is available
                $data = $instagram_data;
            } elseif (!is_wp_error($tiktok_data) && !empty($tiktok_data)) {
                // Only TikTok data is available
                $data = $tiktok_data;
            } else {
                // Both failed, return the Instagram error (or empty array if both are just empty)
                $data = is_wp_error($instagram_data) ? $instagram_data : [];
            }
        } else if ($atts['source'] === 'instagram') {
            // Use Instagram source
            $instagram_username = get_option('whatsfeed_instagram_username', '');
            
            // If username is empty, try the other option name
            if (empty($instagram_username)) {
                $instagram_username = get_option('whatsfeed_username', '');
                // If we found a username in the other option, update the primary option
                if (!empty($instagram_username)) {
                    update_option('whatsfeed_instagram_username', $instagram_username);
                    error_log('Updated whatsfeed_instagram_username from whatsfeed_username: ' . $instagram_username);
                }
            }
            
            if (!empty($instagram_username)) {
                $data = WhatsFeed_Instagram::fetch_by_username($instagram_username, $atts['limit']);
            } else {
                $data = WhatsFeed_Instagram::fetch_feed_with_token($atts['limit']);
            }
        } else {
            // Use TikTok source
            $data = WhatsFeed_TikTok::fetch_feed($atts['limit']);
        }

        ob_start();
        include WHATSFEED_PLUGIN_DIR . 'templates/feed-grid.php';
        return ob_get_clean();
    }

    /**
     * Instagram username shortcode handler
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function instagram_username_shortcode($atts) {
        // Get username from options, checking both option names
        $username = get_option('whatsfeed_instagram_username', '');
        
        // If username is empty, try the other option name
        if (empty($username)) {
            $username = get_option('whatsfeed_username', '');
            // If we found a username in the other option, update the primary option
            if (!empty($username)) {
                update_option('whatsfeed_instagram_username', $username);
                error_log('Updated whatsfeed_instagram_username from whatsfeed_username: ' . $username);
            }
        }
        
        $atts = shortcode_atts(array(
            'username' => $username,
            'limit' => 6,
            'columns' => 3,
            'layout' => 'grid',
            'show_profile' => 'yes',
            'show_bio' => 'yes',
            'show_header' => 'yes',
            'show_follow_button' => 'yes',
            'cache_time' => HOUR_IN_SECONDS,
        ), $atts, 'whatsfeed_instagram_username');
        
        if (empty($atts['username'])) {
            return '<div class="whatsfeed-error">Instagram username is required. Please provide a username in the shortcode or set it in the WhatsFeed settings.</div>';
        }
        
        // Get feed data using username
        $feed = WhatsFeed_Instagram::fetch_by_username($atts['username'], $atts['limit']);
        
        if (is_wp_error($feed)) {
            return '<div class="whatsfeed-error">' . esc_html($feed->get_error_message()) . '</div>';
        }
        
        if (empty($feed)) {
            return '<div class="whatsfeed-error">No Instagram posts found</div>';
        }
        
        // Get template
        ob_start();
        $this->get_template('instagram', array(
            'feed' => $feed,
            'atts' => $atts,
            'username' => $atts['username'],
            'profile_picture' => '',  // Not available with username-only approach
            'profile_name' => $atts['username'],
            'profile_bio' => '',      // Not available with username-only approach
            'profile_url' => 'https://www.instagram.com/' . $atts['username'] . '/',
        ));
        return ob_get_clean();
    }
    
    /**
     * Simple Instagram feed shortcode that uses the username from settings
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function instagram_feed_from_settings($atts) {
        $username = get_option('whatsfeed_instagram_username', '');
        
        if (empty($username)) {
            return '<div class="whatsfeed-error">Instagram username is not set in WhatsFeed settings. Please configure it in the admin dashboard.</div>';
        }
        
        // Merge with the username from settings
        $atts['username'] = $username;
        
        // Use the existing username shortcode handler
        return $this->instagram_username_shortcode($atts);
    }
    
    /**
     * TikTok username shortcode handler
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function tiktok_username_shortcode($atts) {
        $atts = shortcode_atts(array(
            'username' => get_option('whatsfeed_tiktok_username', ''),
            'limit' => 6,
            'columns' => 3,
            'layout' => 'grid',
            'show_profile' => 'yes',
            'show_bio' => 'yes',
            'show_header' => 'yes',
            'show_follow_button' => 'yes',
            'cache_time' => HOUR_IN_SECONDS,
        ), $atts, 'whatsfeed_tiktok_username');
        
        if (empty($atts['username'])) {
            return '<div class="whatsfeed-error">TikTok username is required. Please provide a username in the shortcode or set it in the WhatsFeed settings.</div>';
        }
        
        // Get feed data using username
        $feed = WhatsFeed_TikTok::fetch_by_username($atts['username'], $atts['limit']);
        
        if (is_wp_error($feed)) {
            return '<div class="whatsfeed-error">' . esc_html($feed->get_error_message()) . '</div>';
        }
        
        if (empty($feed)) {
            return '<div class="whatsfeed-error">No TikTok videos found</div>';
        }
        
        // Get template
        ob_start();
        $this->get_template('feed-grid', array(
            'data' => $feed,
            'atts' => $atts,
            'source' => 'tiktok',
        ));
        return ob_get_clean();
    }
    
    /**
     * Simple TikTok feed shortcode that uses the username from settings
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function tiktok_feed_from_settings($atts) {
        $username = get_option('whatsfeed_tiktok_username', '');
        
        if (empty($username)) {
            return '<div class="whatsfeed-error">TikTok username is not set in WhatsFeed settings. Please configure it in the admin dashboard.</div>';
        }
        
        // Merge with the username from settings
        $atts['username'] = $username;
        
        // Use the existing username shortcode handler
        return $this->tiktok_username_shortcode($atts);
    }
    
    /**
     * Get template for feed display
     * 
     * @param string $template_name Template name without extension
     * @param array $args Arguments to pass to the template
     * @return void
     */
    private function get_template($template_name, $args = []) {
        // Extract args to make them available in the template
        if (!empty($args)) {
            extract($args);
        }
        
        $template_file = WHATSFEED_PLUGIN_DIR . 'templates/' . $template_name . '.php';
        
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<div class="whatsfeed-error">Template not found: ' . esc_html($template_name) . '</div>';
        }
    }
}
