document.addEventListener("DOMContentLoaded", function() {
    // Initialize Swiper for carousels
    if (document.querySelector(".whatsfeed-carousel .swiper")) {
        new Swiper(".whatsfeed-carousel .swiper", {
            slidesPerView: 3,
            spaceBetween: 20,
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            breakpoints: {
                768: { slidesPerView: 2 },
                480: { slidesPerView: 1 }
            }
        });
    }
    
    // Handle popup functionality
    const popupLinks = document.querySelectorAll('.whatsfeed-popup-link');
    const popup = document.getElementById('whatsfeed-popup');
    
    if (popupLinks.length > 0 && popup) {
        // Open popup when clicking on a feed item
        popupLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const imgSrc = this.getAttribute('href');
                const caption = this.getAttribute('data-caption');
                const videoSrc = this.getAttribute('data-video');
                
                const popupImg = document.getElementById('whatsfeed-popup-img');
                const popupCaption = document.getElementById('whatsfeed-popup-caption');
                
                // Clear previous content
                if (popupImg) popupImg.style.display = 'none';
                
                // Remove any existing video
                const existingVideo = popup.querySelector('video');
                if (existingVideo) existingVideo.remove();
                
                // Set content based on media type
                if (videoSrc) {
                    // Create video element for TikTok videos
                    const video = document.createElement('video');
                    video.src = videoSrc;
                    video.controls = true;
                    video.autoplay = true;
                    video.className = 'whatsfeed-popup-video';
                    video.style.maxWidth = '100%';
                    video.style.maxHeight = '80vh';
                    
                    // Insert video before caption
                    popupCaption.parentNode.insertBefore(video, popupCaption);
                } else {
                    // Show image
                    popupImg.src = imgSrc;
                    popupImg.style.display = 'block';
                }
                
                // Set caption
                if (popupCaption) popupCaption.textContent = caption || '';
                
                // Show popup
                popup.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            });
        });
        
        // Close popup when clicking on close button or overlay
        const closeBtn = popup.querySelector('.whatsfeed-popup-close');
        const overlay = popup.querySelector('.whatsfeed-popup-overlay');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', closePopup);
        }
        
        if (overlay) {
            overlay.addEventListener('click', closePopup);
        }
        
        // Close popup when pressing Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && popup.style.display === 'flex') {
                closePopup();
            }
        });
        
        function closePopup() {
            popup.style.display = 'none';
            document.body.style.overflow = ''; // Restore scrolling
            
            // Stop video if playing
            const video = popup.querySelector('video');
            if (video) video.pause();
        }
    }
});
