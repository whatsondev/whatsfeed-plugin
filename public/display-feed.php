<?php
add_shortcode('instagram_feed', function($atts) {
    $default_limit = get_option('whatsfeed_default_limit', 6);
    $grid_columns    = get_option('whatsfeed_grid_columns', 3);

    $atts = shortcode_atts([
        'limit' => $default_limit, // default comes from admin setting
        'columns' => $grid_columns
    ], $atts);

     $token   = get_option('whatsfeed_access_token');
    $user_id = get_option('whatsfeed_user_id');

    if (empty($token) || empty($user_id)) {
        return "<p>Please set your Instagram Access Token & User ID in <b>WhatsFeed settings</b>.</p>";
    }

    $cache_key = 'whatsfeed_cache_' . $user_id;
    $cached    = get_transient($cache_key);

    if ($cached) {
        $data = $cached;
    } else {
        $url = "https://graph.facebook.com/v21.0/$user_id/media?fields=id,caption,media_type,media_url,permalink,timestamp,username&access_token=$token&limit=50";
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return "<p>⚠️ Error fetching Instagram feed.</p>";
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['data'])) {
            return "<p>⚠️ No posts found.</p>";
        }

        $data = $body['data'];
        set_transient($cache_key, $data, HOUR_IN_SECONDS);
    }

    $posts = array_slice($data, 0, intval($atts['limit']));

    // Feed output with column class
    $output = '<div class="whatsfeed-grid columns-' . intval($atts['columns']) . '">';
    foreach ($posts as $post) {
        if ($post['media_type'] == 'IMAGE' || $post['media_type'] == 'CAROUSEL_ALBUM') {
            $output .= '<a href="' . esc_url($post['permalink']) . '" target="_blank" class="whatsfeed-item">
                          <img src="' . esc_url($post['media_url']) . '" alt="" />
                        </a>';
        } elseif ($post['media_type'] == 'VIDEO') {
            $output .= '<a href="' . esc_url($post['permalink']) . '" target="_blank" class="whatsfeed-item">
                          <video muted loop playsinline>
                              <source src="' . esc_url($post['media_url']) . '" type="video/mp4">
                          </video>
                        </a>';
        }
    }
    $output .= '</div>';

    return $output;
});
