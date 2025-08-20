<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode('instagram_feed', 'whatsfeed_shortcode_handler');
function whatsfeed_shortcode_handler($atts) {
    $default_limit = get_option('whatsfeed_default_limit', 6);
    $grid_columns = get_option('whatsfeed_grid_columns', 3);

    $atts = shortcode_atts([
        'limit' => $default_limit,
        'columns' => $grid_columns,
        'layout' => 'grid', // grid or carousel
        'show_captions' => 'yes',
        'open_in_popup' => 'yes'
    ], $atts);

    $token = get_option('whatsfeed_access_token');
    $user_id = get_option('whatsfeed_user_id');

    if (empty($token) || empty($user_id)) {
        return '<div class="whatsfeed-error"><p>⚠️ Please configure your Instagram credentials in <a href="' . admin_url('admin.php?page=whatsfeed-settings') . '"><strong>WhatsFeed Settings</strong></a>.</p></div>';
    }

    $cache_key = 'whatsfeed_cache_' . md5($user_id . $atts['limit']);
    $cached = get_transient($cache_key);

    if ($cached === false) {
        $url = "https://graph.facebook.com/v21.0/$user_id/media?" . http_build_query([
            'fields' => 'id,caption,media_type,media_url,permalink,timestamp,username,thumbnail_url',
            'access_token' => $token,
            'limit' => max(50, intval($atts['limit'])) // Get more than needed for flexibility
        ]);

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'User-Agent' => 'WhatsFeed-Plugin/' . WHATSFEED_VERSION
            ]
        ]);

        if (is_wp_error($response)) {
            return '<div class="whatsfeed-error"><p>⚠️ Error fetching Instagram feed: ' . esc_html($response->get_error_message()) . '</p></div>';
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return '<div class="whatsfeed-error"><p>⚠️ Instagram API returned error code: ' . esc_html($response_code) . '</p></div>';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['data'])) {
            if (isset($body['error'])) {
                return '<div class="whatsfeed-error"><p>⚠️ Instagram API Error: ' . esc_html($body['error']['message']) . '</p></div>';
            }
            return '<div class="whatsfeed-error"><p>⚠️ No Instagram posts found.</p></div>';
        }

        $data = $body['data'];
        
        // Cache for 1 hour
        set_transient($cache_key, $data, HOUR_IN_SECONDS);
    } else {
        $data = $cached;
    }

    // Apply shortcode limit
    $data = array_slice($data, 0, intval($atts['limit']));

    if (empty($data)) {
        return '<div class="whatsfeed-error"><p>⚠️ No posts to display.</p></div>';
    }

    // Generate the feed HTML
    return whatsfeed_generate_feed_html($data, $atts);
}

function whatsfeed_generate_feed_html($posts, $atts) {
    $layout = $atts['layout'];
    $columns = intval($atts['columns']);
    $show_captions = $atts['show_captions'] === 'yes';
    $open_in_popup = $atts['open_in_popup'] === 'yes';
    
    $unique_id = 'whatsfeed-' . uniqid();
    
    ob_start();
    
    if ($layout === 'carousel') {
        echo whatsfeed_render_carousel($posts, $unique_id, $show_captions, $open_in_popup);
    } else {
        echo whatsfeed_render_grid($posts, $unique_id, $columns, $show_captions, $open_in_popup);
    }
    
    return ob_get_clean();
}

function whatsfeed_render_grid($posts, $unique_id, $columns, $show_captions, $open_in_popup) {
    $html = '<div class="whatsfeed-grid whatsfeed-cols-' . $columns . '" id="' . $unique_id . '">';
    
    foreach ($posts as $post) {
        $media_url = $post['media_type'] === 'VIDEO' && !empty($post['thumbnail_url']) 
                    ? $post['thumbnail_url'] 
                    : $post['media_url'];
        
        $caption = !empty($post['caption']) ? wp_trim_words($post['caption'], 15) : '';
        $permalink = $post['permalink'] ?? '#';
        
        $html .= '<div class="whatsfeed-item">';
        $html .= '  <div class="whatsfeed-media">';
        
        if ($open_in_popup) {
            $html .= '<a href="' . esc_url($media_url) . '" class="whatsfeed-popup-link" data-caption="' . esc_attr($caption) . '">';
        } else {
            $html .= '<a href="' . esc_url($permalink) . '" target="_blank" rel="noopener">';
        }
        
        $html .= '<img src="' . esc_url($media_url) . '" alt="' . esc_attr($caption) . '" loading="lazy" />';
        
        if ($post['media_type'] === 'VIDEO') {
            $html .= '<div class="whatsfeed-play-btn">▶</div>';
        }
        
        $html .= '</a>';
        $html .= '  </div>';
        
        if ($show_captions && !empty($caption)) {
            $html .= '  <div class="whatsfeed-caption">' . esc_html($caption) . '</div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    // Add popup HTML if needed
    if ($open_in_popup) {
        $html .= whatsfeed_popup_html();
    }
    
    return $html;
}

function whatsfeed_render_carousel($posts, $unique_id, $show_captions, $open_in_popup) {
    $html = '<div class="swiper whatsfeed-carousel" id="' . $unique_id . '">';
    $html .= '  <div class="swiper-wrapper">';
    
    foreach ($posts as $post) {
        $media_url = $post['media_type'] === 'VIDEO' && !empty($post['thumbnail_url']) 
                    ? $post['thumbnail_url'] 
                    : $post['media_url'];
        
        $caption = !empty($post['caption']) ? wp_trim_words($post['caption'], 20) : '';
        $permalink = $post['permalink'] ?? '#';
        
        $html .= '<div class="swiper-slide">';
        $html .= '  <div class="whatsfeed-item">';
        $html .= '    <div class="whatsfeed-media">';
        
        if ($open_in_popup) {
            $html .= '<a href="' . esc_url($media_url) . '" class="whatsfeed-popup-link" data-caption="' . esc_attr($caption) . '">';
        } else {
            $html .= '<a href="' . esc_url($permalink) . '" target="_blank" rel="noopener">';
        }
        
        $html .= '<img src="' . esc_url($media_url) . '" alt="' . esc_attr($caption) . '" loading="lazy" />';
        
        if ($post['media_type'] === 'VIDEO') {
            $html .= '<div class="whatsfeed-play-btn">▶</div>';
        }
        
        $html .= '</a>';
        $html .= '    </div>';
        
        if ($show_captions && !empty($caption)) {
            $html .= '    <div class="whatsfeed-caption">' . esc_html($caption) . '</div>';
        }
        
        $html .= '  </div>';
        $html .= '</div>';
    }
    
    $html .= '  </div>';
    $html .= '  <div class="swiper-pagination"></div>';
    $html .= '  <div class="swiper-button-next"></div>';
    $html .= '  <div class="swiper-button-prev"></div>';
    $html .= '</div>';
    
    // Add popup HTML if needed
    if ($open_in_popup) {
        $html .= whatsfeed_popup_html();
    }
    
    return $html;
}

function whatsfeed_popup_html() {
    return '
    <div class="whatsfeed-popup" id="whatsfeed-popup" style="display:none;">
        <div class="whatsfeed-popup-overlay"></div>
        <div class="whatsfeed-popup-content">
            <button class="whatsfeed-popup-close">&times;</button>
            <img src="" alt="" id="whatsfeed-popup-img">
            <div class="whatsfeed-popup-caption" id="whatsfeed-popup-caption"></div>
        </div>
    </div>';
}

// Add AJAX endpoint for clearing cache
add_action('wp_ajax_whatsfeed_clear_cache', 'whatsfeed_clear_cache_ajax');
function whatsfeed_clear_cache_ajax() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'whatsfeed_clear_cache')) {
        wp_die('Invalid nonce');
    }
    
    global $wpdb;
    $deleted = $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_whatsfeed_cache_%' 
         OR option_name LIKE '_transient_timeout_whatsfeed_cache_%'"
    );

    wp_send_json_success(['deleted' => $deleted]);
}