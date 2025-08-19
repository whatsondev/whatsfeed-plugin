<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WhatsFeed_Shortcode {

    public function __construct() {
        add_shortcode( 'whatsfeed', [ $this, 'render_feed' ] );
    }

    public function render_feed( $atts ) {
        $atts = shortcode_atts( [
            'source' => 'instagram', // instagram | tiktok
            'limit'  => 6,
            'layout' => 'grid',      // grid | carousel
        ], $atts );

        $data = ( $atts['source'] === 'instagram' )
            ? WhatsFeed_Instagram::fetch_feed( $atts['limit'] )
            : WhatsFeed_TikTok::fetch_feed( $atts['limit'] );

        ob_start();
        include WHATSFEED_PATH . 'templates/feed-grid.php';
        return ob_get_clean();
    }
}
