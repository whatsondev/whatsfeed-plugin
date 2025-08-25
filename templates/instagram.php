<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Check if we have feed data
if (empty($feed)) {
    echo '<div class="whatsfeed-error">No Instagram posts found</div>';
    return;
}

// Get attributes
$columns = isset($atts['columns']) ? intval($atts['columns']) : 3;
$layout = isset($atts['layout']) ? sanitize_text_field($atts['layout']) : 'grid';
$show_profile = isset($atts['show_profile']) && $atts['show_profile'] === 'yes';
$show_bio = isset($atts['show_bio']) && $atts['show_bio'] === 'yes';
$show_header = isset($atts['show_header']) && $atts['show_header'] === 'yes';
$show_follow_button = isset($atts['show_follow_button']) && $atts['show_follow_button'] === 'yes';

// Container classes
$container_class = 'whatsfeed-instagram-container';
$container_class .= ' whatsfeed-layout-' . esc_attr($layout);
?>

<div class="<?php echo esc_attr($container_class); ?>">
    <?php if ($show_header && $show_profile) : ?>
    <div class="whatsfeed-profile-header">
        <div class="whatsfeed-profile-info">
            <div class="whatsfeed-profile-name">
                <a href="<?php echo esc_url($profile_url); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html($username); ?>
                </a>
            </div>
            
            <?php if ($show_bio && !empty($profile_bio)) : ?>
            <div class="whatsfeed-profile-bio">
                <?php echo esc_html($profile_bio); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($show_follow_button) : ?>
            <div class="whatsfeed-follow-button">
                <a href="<?php echo esc_url($profile_url); ?>" target="_blank" rel="noopener noreferrer" class="whatsfeed-button">
                    Follow
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($layout === 'grid') : ?>
    <div class="whatsfeed-grid columns-<?php echo esc_attr($columns); ?>">
        <?php foreach ($feed as $item) : 
            $is_video = isset($item['media_type']) && $item['media_type'] === 'VIDEO';
            $media_url = $item['media_url'] ?? '';
            $permalink = $item['permalink'] ?? '#';
            $caption = $item['caption'] ?? '';
            $thumbnail_url = $item['thumbnail_url'] ?? $media_url;
        ?>
        <div class="whatsfeed-item instagram">
            <div class="whatsfeed-media">
                
                <a href="<?php echo esc_url($permalink); ?>" target="_blank" rel="noopener noreferrer">
                    <?php if ($is_video) : ?>
                    <div class="whatsfeed-video-indicator"></div>
                    <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(wp_trim_words($caption, 10, '...')); ?>" loading="lazy">
                    <?php else : ?>
                    <img src="<?php echo esc_url($media_url); ?>" alt="<?php echo esc_attr(wp_trim_words($caption, 10, '...')); ?>" loading="lazy">
                    <?php endif; ?>
                </a>
            </div>
            
            <?php if (!empty($caption)) : ?>
            <div class="whatsfeed-caption">
                <p><?php echo esc_html(wp_trim_words($caption, 15, '...')); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else : // Carousel layout ?>
    <div class="whatsfeed-carousel-container">
        <div class="whatsfeed-carousel swiper">
            <div class="swiper-wrapper">
                <?php foreach ($feed as $item) : 
                    $is_video = isset($item['media_type']) && $item['media_type'] === 'VIDEO';
                    $media_url = $item['media_url'] ?? '';
                    $permalink = $item['permalink'] ?? '#';
                    $caption = $item['caption'] ?? '';
                    $thumbnail_url = $item['thumbnail_url'] ?? $media_url;
                ?>
                <div class="swiper-slide">
                    <div class="whatsfeed-item instagram">
                        <div class="whatsfeed-media">
                            
                            <a href="<?php echo esc_url($permalink); ?>" target="_blank" rel="noopener noreferrer">
                                <?php if ($is_video) : ?>
                                <div class="whatsfeed-video-indicator"></div>
                                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(wp_trim_words($caption, 10, '...')); ?>" loading="lazy">
                                <?php else : ?>
                                <img src="<?php echo esc_url($media_url); ?>" alt="<?php echo esc_attr(wp_trim_words($caption, 10, '...')); ?>" loading="lazy">
                                <?php endif; ?>
                            </a>
                        </div>
                        
                        <?php if (!empty($caption)) : ?>
                        <div class="whatsfeed-caption">
                            <p><?php echo esc_html(wp_trim_words($caption, 15, '...')); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($layout === 'carousel') : ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new Swiper('.whatsfeed-carousel', {
            slidesPerView: 1,
            spaceBetween: 10,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                640: {
                    slidesPerView: 2,
                    spaceBetween: 20,
                },
                768: {
                    slidesPerView: 3,
                    spaceBetween: 30,
                },
                1024: {
                    slidesPerView: 3,
                    spaceBetween: 30,
                },
            }
        });
    });
</script>
<?php endif; ?>