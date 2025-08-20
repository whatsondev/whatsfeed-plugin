<?php if ( ! empty($data) ) : ?>
    <?php if ( $atts['layout'] === 'grid' ) : ?>
        <div class="whatsfeed-grid columns-<?php echo esc_attr( $atts['columns'] ?? 3 ); ?>">
            <?php foreach ( $data as $item ) : ?>
                <?php 
                $source = $atts['source'] ?? 'instagram';
                $is_video = isset($item['media_type']) && $item['media_type'] === 'VIDEO';
                $is_tiktok_video = $source === 'tiktok';
                $media_url = $item['media_url'] ?? $item['cover_image_url'] ?? '';
                $video_url = $item['video_url'] ?? '';
                $permalink = $item['permalink'] ?? '#';
                $caption = $item['caption'] ?? '';
                ?>
                <div class="whatsfeed-item <?php echo esc_attr($source); ?>">
                    <div class="whatsfeed-media">
                        <span class="whatsfeed-source <?php echo esc_attr($source); ?>">
                            <?php echo esc_html(ucfirst($source)); ?>
                        </span>
                        
                        <?php if ( $atts['open_in_popup'] === 'yes' ) : ?>
                            <a href="<?php echo esc_url($media_url); ?>" 
                               class="whatsfeed-popup-link" 
                               data-caption="<?php echo esc_attr($caption); ?>" 
                               <?php if ($is_video || $is_tiktok_video) : ?>
                               data-video="<?php echo esc_url($video_url); ?>"
                               <?php endif; ?>>
                        <?php else : ?>
                            <a href="<?php echo esc_url($permalink); ?>" target="_blank" rel="nofollow">
                        <?php endif; ?>
                            <img src="<?php echo esc_url($media_url); ?>" alt="<?php echo esc_attr($caption); ?>" loading="lazy">
                            
                            <?php if ($is_video || $is_tiktok_video) : ?>
                                <div class="whatsfeed-play-btn">▶</div>
                            <?php endif; ?>
                        </a>
                    </div>
                    
                    <?php if ( $atts['show_captions'] === 'yes' && !empty($caption) ) : ?>
                        <div class="whatsfeed-caption">
                            <?php echo esc_html(wp_trim_words($caption, 15)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ( $atts['open_in_popup'] === 'yes' ) : ?>
            <div class="whatsfeed-popup" id="whatsfeed-popup" style="display:none;">
                <div class="whatsfeed-popup-overlay"></div>
                <div class="whatsfeed-popup-content">
                    <button class="whatsfeed-popup-close">&times;</button>
                    <img src="" alt="" id="whatsfeed-popup-img">
                    <div class="whatsfeed-popup-caption" id="whatsfeed-popup-caption"></div>
                </div>
            </div>
        <?php endif; ?>
        
    <?php elseif ( $atts['layout'] === 'carousel' ) : ?>
        <div class="whatsfeed-carousel">
            <div class="swiper">
                <div class="swiper-wrapper">
                    <?php foreach ( $data as $item ) : ?>
                        <?php 
                        $source = $atts['source'] ?? 'instagram';
                        $is_video = isset($item['media_type']) && $item['media_type'] === 'VIDEO';
                        $is_tiktok_video = $source === 'tiktok';
                        $media_url = $item['media_url'] ?? $item['cover_image_url'] ?? '';
                        $video_url = $item['video_url'] ?? '';
                        $permalink = $item['permalink'] ?? '#';
                        $caption = $item['caption'] ?? '';
                        ?>
                        <div class="swiper-slide">
                            <div class="whatsfeed-item <?php echo esc_attr($source); ?>">
                                <div class="whatsfeed-media">
                                    <span class="whatsfeed-source <?php echo esc_attr($source); ?>">
                                        <?php echo esc_html(ucfirst($source)); ?>
                                    </span>
                                    
                                    <?php if ( $atts['open_in_popup'] === 'yes' ) : ?>
                                        <a href="<?php echo esc_url($media_url); ?>" 
                                           class="whatsfeed-popup-link" 
                                           data-caption="<?php echo esc_attr($caption); ?>" 
                                           <?php if ($is_video || $is_tiktok_video) : ?>
                                           data-video="<?php echo esc_url($video_url); ?>"
                                           <?php endif; ?>>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url($permalink); ?>" target="_blank" rel="nofollow">
                                    <?php endif; ?>
                                        <img src="<?php echo esc_url($media_url); ?>" alt="<?php echo esc_attr($caption); ?>" loading="lazy">
                                        
                                        <?php if ($is_video || $is_tiktok_video) : ?>
                                            <div class="whatsfeed-play-btn">▶</div>
                                        <?php endif; ?>
                                    </a>
                                </div>
                                
                                <?php if ( $atts['show_captions'] === 'yes' && !empty($caption) ) : ?>
                                    <div class="whatsfeed-caption">
                                        <?php echo esc_html(wp_trim_words($caption, 15)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <!-- Navigation -->
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
        
        <?php if ( $atts['open_in_popup'] === 'yes' ) : ?>
            <div class="whatsfeed-popup" id="whatsfeed-popup" style="display:none;">
                <div class="whatsfeed-popup-overlay"></div>
                <div class="whatsfeed-popup-content">
                    <button class="whatsfeed-popup-close">&times;</button>
                    <img src="" alt="" id="whatsfeed-popup-img">
                    <div class="whatsfeed-popup-caption" id="whatsfeed-popup-caption"></div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
<?php else : ?>
    <p>No feed found.</p>
<?php endif; ?>
