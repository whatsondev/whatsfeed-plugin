<?php if ( ! empty($data) ) : ?>
    <?php if ( $atts['layout'] === 'grid' ) : ?>
        <div class="whatsfeed-grid">
            <?php foreach ( $data as $item ) : ?>
                <div class="whatsfeed-item">
                    <a href="<?php echo esc_url( $item['permalink'] ?? '#' ); ?>" target="_blank" rel="nofollow">
                        <img src="<?php echo esc_url( $item['media_url'] ?? $item['cover_image_url'] ?? '' ); ?>" alt="">
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ( $atts['layout'] === 'carousel' ) : ?>
        <div class="whatsfeed-carousel">
            <div class="swiper">
                <div class="swiper-wrapper">
                    <?php foreach ( $data as $item ) : ?>
                        <div class="swiper-slide">
                            <a href="<?php echo esc_url( $item['permalink'] ?? '#' ); ?>" target="_blank" rel="nofollow">
                                <img src="<?php echo esc_url( $item['media_url'] ?? $item['cover_image_url'] ?? '' ); ?>" alt="">
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <!-- Navigation -->
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    <?php endif; ?>
<?php else : ?>
    <p>No feed found.</p>
<?php endif; ?>
