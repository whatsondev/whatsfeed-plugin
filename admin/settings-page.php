<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Add settings menu
add_action('admin_menu', 'whatsfeed_add_admin_menu');
function whatsfeed_add_admin_menu() {
    add_menu_page(
        'WhatsFeed Settings',
        'WhatsFeed',
        'manage_options',
        'whatsfeed-settings',
        'whatsfeed_settings_page_html',
        'dashicons-instagram'
    );
}

// Register options
add_action('admin_init', 'whatsfeed_register_settings');
function whatsfeed_register_settings() {
    // Instagram settings
    register_setting('whatsfeed_options', 'whatsfeed_access_token', 'sanitize_text_field');
    register_setting('whatsfeed_options', 'whatsfeed_user_id', 'sanitize_text_field');
    register_setting('whatsfeed_options', 'whatsfeed_app_id', 'sanitize_text_field');
    register_setting('whatsfeed_options', 'whatsfeed_app_secret', 'sanitize_text_field');
    
    // TikTok settings
    register_setting('whatsfeed_options', 'whatsfeed_tiktok_access_token', 'sanitize_text_field');
    register_setting('whatsfeed_options', 'whatsfeed_tiktok_open_id', 'sanitize_text_field');
    register_setting('whatsfeed_options', 'whatsfeed_tiktok_client_key', 'sanitize_text_field');
    register_setting('whatsfeed_options', 'whatsfeed_tiktok_client_secret', 'sanitize_text_field');
    register_setting('whatsfeed_options', 'whatsfeed_tiktok_username', 'sanitize_text_field');
    
    // General settings
    register_setting('whatsfeed_options', 'whatsfeed_default_limit', 'intval');
    register_setting('whatsfeed_options', 'whatsfeed_grid_columns', 'intval');
    register_setting('whatsfeed_options', 'whatsfeed_default_source', 'sanitize_text_field');
}

// Handle OAuth redirect and token exchange
add_action('admin_init', 'whatsfeed_handle_oauth');
function whatsfeed_handle_oauth() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'whatsfeed-settings') {
        return;
    }
    
    // Handle demo credential generation
    if (isset($_GET['generate_demo']) && in_array($_GET['generate_demo'], ['instagram', 'tiktok'])) {
        $platform = sanitize_text_field($_GET['generate_demo']);
        
        if ($platform === 'instagram') {
            WhatsFeed_Auth_Helper::generate_instagram_demo_credentials();
            add_settings_error('whatsfeed_messages', 'demo_credentials', '✅ Instagram demo credentials generated successfully!', 'success');
        } else if ($platform === 'tiktok') {
            WhatsFeed_Auth_Helper::generate_tiktok_demo_credentials();
            add_settings_error('whatsfeed_messages', 'demo_credentials', '✅ TikTok demo credentials generated successfully!', 'success');
        }
        
        // Redirect to remove the query parameter
        wp_redirect(admin_url('admin.php?page=whatsfeed-settings'));
        exit;
    }
    
    // Handle Instagram OAuth
    if (isset($_GET['code']) && !empty($_GET['code']) && (!isset($_GET['platform']) || $_GET['platform'] !== 'tiktok')) {
        $code = sanitize_text_field($_GET['code']);
        $client_id = get_option('whatsfeed_app_id');
        $client_secret = get_option('whatsfeed_app_secret');
        
        if (empty($client_id) || empty($client_secret)) {
            add_settings_error('whatsfeed_messages', 'missing_credentials', 'Please set your App ID and App Secret first.', 'error');
            return;
        }
        
        $redirect_uri = admin_url("admin.php?page=whatsfeed-settings");
        
        // Exchange code for short-lived access token
        $token_url = "https://graph.facebook.com/v21.0/oauth/access_token?" . http_build_query([
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'client_secret' => $client_secret,
            'code' => $code
        ]);
        
        $response = wp_remote_get($token_url, ['timeout' => 30]);
        
        if (is_wp_error($response)) {
            add_settings_error('whatsfeed_messages', 'token_error', 'Error exchanging code for token: ' . $response->get_error_message(), 'error');
            return;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            add_settings_error('whatsfeed_messages', 'api_error', 'Facebook API Error: ' . $body['error']['message'], 'error');
            return;
        }
        
        if (!empty($body['access_token'])) {
            // Extend the token to long-lived
            $long_lived_token = whatsfeed_extend_token($body['access_token']);
            
            if ($long_lived_token) {
                update_option('whatsfeed_access_token', $long_lived_token);
                
                // Get Instagram User ID
                whatsfeed_get_user_id($long_lived_token);
                
                add_settings_error('whatsfeed_messages', 'success', '✅ Instagram Connected Successfully!', 'success');
            } else {
                add_settings_error('whatsfeed_messages', 'extend_error', 'Could not extend access token.', 'error');
            }
        }
    }
    
    // Handle TikTok OAuth
    if (isset($_GET['code']) && !empty($_GET['code']) && isset($_GET['platform']) && $_GET['platform'] === 'tiktok') {
        $code = sanitize_text_field($_GET['code']);
        $client_key = get_option('whatsfeed_tiktok_client_key');
        $client_secret = get_option('whatsfeed_tiktok_client_secret');
        
        if (empty($client_key) || empty($client_secret)) {
            add_settings_error('whatsfeed_messages', 'missing_credentials', 'Please set your TikTok Client Key and Client Secret first.', 'error');
            return;
        }
        
        $redirect_uri = admin_url("admin.php?page=whatsfeed-settings&platform=tiktok");
        
        // Exchange code for TikTok access token
        $token_url = "https://open.tiktokapis.com/v2/oauth/token/";
        $token_params = array(
            'client_key' => $client_key,
            'client_secret' => $client_secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirect_uri
        );
        
        $response = wp_remote_post($token_url, array(
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'body' => $token_params,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            add_settings_error('whatsfeed_messages', 'token_error', 'Error exchanging code for TikTok token: ' . $response->get_error_message(), 'error');
            return;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            add_settings_error('whatsfeed_messages', 'api_error', 'TikTok API Error: ' . $body['error']['message'], 'error');
            return;
        }
        
        if (!empty($body['access_token']) && !empty($body['open_id'])) {
            update_option('whatsfeed_tiktok_access_token', $body['access_token']);
            update_option('whatsfeed_tiktok_open_id', $body['open_id']);
            
            // Get TikTok username if available
            if (!empty($body['username'])) {
                update_option('whatsfeed_tiktok_username', $body['username']);
            }
            
            add_settings_error('whatsfeed_messages', 'success', '✅ TikTok Connected Successfully!', 'success');
        } else {
            add_settings_error('whatsfeed_messages', 'token_error', 'Invalid response from TikTok API.', 'error');
        }
    }
}

function whatsfeed_settings_page_html() {
    // Check if form was submitted
    if (isset($_GET['settings-updated'])) {
        add_settings_error('whatsfeed_messages', 'whatsfeed_message', 'Settings Saved', 'success');
    }
    
    settings_errors('whatsfeed_messages');
    
    // Enqueue jQuery for AJAX
    wp_enqueue_script('jquery');
    
    // Instagram settings
    $app_id = get_option('whatsfeed_app_id');
    $app_secret = get_option('whatsfeed_app_secret');
    $access_token = get_option('whatsfeed_access_token');
    $user_id = get_option('whatsfeed_user_id');
    
    // TikTok settings
    $tiktok_client_key = get_option('whatsfeed_tiktok_client_key');
    $tiktok_client_secret = get_option('whatsfeed_tiktok_client_secret');
    $tiktok_access_token = get_option('whatsfeed_tiktok_access_token');
    $tiktok_open_id = get_option('whatsfeed_tiktok_open_id');
    $tiktok_username = get_option('whatsfeed_tiktok_username');
    
    // General settings
    $default_source = get_option('whatsfeed_default_source', 'instagram');
    
    // Generate OAuth URL if credentials are set
    $auth_url = '';
    if (!empty($app_id)) {
        $redirect_uri = admin_url("admin.php?page=whatsfeed-settings");
        $scope = "instagram_basic,pages_show_list";
        $auth_url = "https://www.facebook.com/v21.0/dialog/oauth?" . http_build_query([
            'client_id' => $app_id,
            'redirect_uri' => $redirect_uri,
            'scope' => $scope,
            'response_type' => 'code'
        ]);
    }
    ?>
    <div class="wrap">
        <h1>🔥 WhatsFeed – Social Media Settings</h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="#instagram-tab" class="nav-tab nav-tab-active">Instagram</a>
            <a href="#tiktok-tab" class="nav-tab">TikTok</a>
            <a href="#general-tab" class="nav-tab">General Settings</a>
        </h2>
        
        <form method="post" action="options.php">
            <?php settings_fields('whatsfeed_options'); ?>
            <?php do_settings_sections('whatsfeed_options'); ?>

            <!-- Instagram Tab -->
            <div id="instagram-tab" class="tab-content">
                <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 8px;">
                    <h2>📋 Instagram Setup Instructions</h2>
                    <ol>
                        <li><strong>Create Facebook App:</strong> Go to <a href="https://developers.facebook.com/" target="_blank">Facebook Developers</a></li>
                        <li><strong>Add Instagram Basic Display:</strong> Add the Instagram Basic Display product to your app</li>
                        <li><strong>Configure OAuth:</strong> Add this URL to Valid OAuth Redirect URIs: <code><?php echo admin_url("admin.php?page=whatsfeed-settings"); ?></code></li>
                        <li><strong>Enter Credentials:</strong> Fill in your App ID and App Secret below</li>
                        <li><strong>Connect Instagram:</strong> Click "Connect Instagram" button</li>
                    </ol>
                </div>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Facebook App ID</th>
                        <td>
                            <input type="text" name="whatsfeed_app_id"
                                   value="<?php echo esc_attr($app_id); ?>"
                                   class="regular-text" placeholder="Enter your Facebook App ID" />
                            <p class="description">Get this from your Facebook App dashboard</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Facebook App Secret</th>
                        <td>
                            <input type="password" name="whatsfeed_app_secret"
                                   value="<?php echo esc_attr($app_secret); ?>"
                                   class="regular-text" placeholder="Enter your Facebook App Secret" />
                            <p class="description">Keep this secret and secure</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Instagram Access Token</th>
                        <td>
                            <textarea name="whatsfeed_access_token" class="large-text code" rows="3" readonly><?php echo esc_textarea($access_token); ?></textarea>
                            <p class="description">This will be automatically generated when you connect Instagram</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Instagram User ID</th>
                        <td>
                            <input type="text" name="whatsfeed_user_id"
                                   value="<?php echo esc_attr($user_id); ?>" class="regular-text" readonly />
                            <p class="description">This will be automatically detected when you connect Instagram</p>
                        </td>
                    </tr>
                </table>
                
                <hr>
                
                <h2>🔗 Instagram Connection</h2>
                <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                    <?php if (empty($app_id) || empty($app_secret)): ?>
                        <p style="color: #d63384;">⚠️ Please save your App ID and App Secret first, then refresh this page to see the Connect button.</p>
                        <p>Or use the one-click setup button below to generate demo credentials:</p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=whatsfeed-settings&generate_demo=instagram')); ?>" class="button button-primary">🔑 Generate Demo Credentials</a>
                        <p class="description">This will create temporary credentials for testing purposes.</p>
                    <?php elseif (empty($access_token)): ?>
                        <p>Click the button below to connect your Instagram account:</p>
                        <a href="<?php echo esc_url($auth_url); ?>" class="button button-primary button-hero">🔗 Connect Instagram</a>
                    <?php else: ?>
                        <p style="color: #198754;">✅ <strong>Instagram Connected!</strong></p>
                        <p>Access Token: <code><?php echo substr($access_token, 0, 20) . '...'; ?></code></p>
                        <p>User ID: <code><?php echo esc_html($user_id); ?></code></p>
                        <a href="<?php echo esc_url($auth_url); ?>" class="button">🔄 Reconnect Instagram</a>
                        <button type="button" class="button" onclick="whatsfeedTestConnection('instagram')">🧪 Test Connection</button>
                        <?php if (WhatsFeed_Auth_Helper::is_using_demo_credentials() === 'instagram'): ?>
                            <p><span class="dashicons dashicons-info"></span> <em>You are using demo credentials. These are for testing only.</em></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- TikTok Tab -->
            <div id="tiktok-tab" class="tab-content" style="display: none;">
                <div style="background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 8px;">
                    <h2>📋 TikTok Setup Instructions</h2>
                    <ol>
                        <li><strong>Create TikTok Developer Account:</strong> Go to <a href="https://developers.tiktok.com/" target="_blank">TikTok for Developers</a></li>
                        <li><strong>Create a TikTok App:</strong> Create a new app in the TikTok Developer Portal</li>
                        <li><strong>Configure Redirect URI:</strong> Add this URL to your app's Redirect URI: <code><?php echo admin_url("admin.php?page=whatsfeed-settings&platform=tiktok"); ?></code></li>
                        <li><strong>Enter Credentials:</strong> Fill in your Client Key and Client Secret below</li>
                        <li><strong>Connect TikTok:</strong> Click "Connect TikTok" button</li>
                    </ol>
                </div>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">TikTok Client Key</th>
                        <td>
                            <input type="text" name="whatsfeed_tiktok_client_key"
                                   value="<?php echo esc_attr($tiktok_client_key); ?>"
                                   class="regular-text" placeholder="Enter your TikTok Client Key" />
                            <p class="description">Get this from your TikTok Developer Portal</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">TikTok Client Secret</th>
                        <td>
                            <input type="password" name="whatsfeed_tiktok_client_secret"
                                   value="<?php echo esc_attr($tiktok_client_secret); ?>"
                                   class="regular-text" placeholder="Enter your TikTok Client Secret" />
                            <p class="description">Keep this secret and secure</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">TikTok Access Token</th>
                        <td>
                            <textarea name="whatsfeed_tiktok_access_token" class="large-text code" rows="3" readonly><?php echo esc_textarea($tiktok_access_token); ?></textarea>
                            <p class="description">This will be automatically generated when you connect TikTok</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">TikTok Open ID</th>
                        <td>
                            <input type="text" name="whatsfeed_tiktok_open_id"
                                   value="<?php echo esc_attr($tiktok_open_id); ?>" class="regular-text" readonly />
                            <p class="description">This will be automatically detected when you connect TikTok</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">TikTok Username</th>
                        <td>
                            <input type="text" name="whatsfeed_tiktok_username"
                                   value="<?php echo esc_attr($tiktok_username); ?>" class="regular-text" />
                            <p class="description">Your TikTok username (used for display purposes)</p>
                        </td>
                    </tr>
                </table>
                
                <hr>
                
                <h2>🔗 TikTok Connection</h2>
                <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                    <?php if (empty($tiktok_client_key) || empty($tiktok_client_secret)): ?>
                        <p style="color: #d63384;">⚠️ Please save your Client Key and Client Secret first, then refresh this page to see the Connect button.</p>
                        <p>Or use the one-click setup button below to generate demo credentials:</p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=whatsfeed-settings&generate_demo=tiktok')); ?>" class="button button-primary">🔑 Generate Demo Credentials</a>
                        <p class="description">This will create temporary credentials for testing purposes.</p>
                    <?php elseif (empty($tiktok_access_token)): ?>
                        <p>Click the button below to connect your TikTok account:</p>
                        <a href="#" class="button button-primary button-hero" id="connect-tiktok-btn">🔗 Connect TikTok</a>
                    <?php else: ?>
                        <p style="color: #198754;">✅ <strong>TikTok Connected!</strong></p>
                        <p>Access Token: <code><?php echo substr($tiktok_access_token, 0, 20) . '...'; ?></code></p>
                        <p>Open ID: <code><?php echo esc_html($tiktok_open_id); ?></code></p>
                        <button type="button" class="button" id="reconnect-tiktok-btn">🔄 Reconnect TikTok</button>
                        <button type="button" class="button" onclick="whatsfeedTestConnection('tiktok')">🧪 Test Connection</button>
                        <?php if (WhatsFeed_Auth_Helper::is_using_demo_credentials() === 'tiktok'): ?>
                            <p><span class="dashicons dashicons-info"></span> <em>You are using demo credentials. These are for testing only.</em></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- General Settings Tab -->
            <div id="general-tab" class="tab-content" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">Default Feed Source</th>
                        <td>
                            <select name="whatsfeed_default_source">
                                <option value="instagram" <?php selected($default_source, 'instagram'); ?>>Instagram</option>
                                <option value="tiktok" <?php selected($default_source, 'tiktok'); ?>>TikTok</option>
                            </select>
                            <p class="description">Default source to use when no source is specified in shortcode.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Post Limit</th>
                        <td>
                            <input type="number" name="whatsfeed_default_limit"
                                   value="<?php echo esc_attr(get_option('whatsfeed_default_limit', 6)); ?>"
                                   class="small-text" min="1" max="50" />
                            <p class="description">Default number of posts to show if no limit is set in shortcode.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Grid Columns</th>
                        <td>
                            <select name="whatsfeed_grid_columns">
                                <?php 
                                $columns = [1, 2, 3, 4, 5, 6];
                                $saved_columns = get_option('whatsfeed_grid_columns', 3);
                                foreach ($columns as $col) {
                                    echo '<option value="' . $col . '" ' . selected($saved_columns, $col, false) . '>' . $col . ' Column' . ($col > 1 ? 's' : '') . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">Number of columns in the feed grid.</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button('Save Settings'); ?>
        </form>
        
        <hr>
        
        <h2>🔗 Instagram Connection</h2>
        <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
            <?php if (empty($app_id) || empty($app_secret)): ?>
                <p style="color: #d63384;">⚠️ Please save your App ID and App Secret first, then refresh this page to see the Connect button.</p>
            <?php elseif (empty($access_token)): ?>
                <p>Click the button below to connect your Instagram account:</p>
                <a href="<?php echo esc_url($auth_url); ?>" class="button button-primary button-hero">🔗 Connect Instagram</a>
            <?php else: ?>
                <p style="color: #198754;">✅ <strong>Instagram Connected!</strong></p>
                <p>Access Token: <code><?php echo substr($access_token, 0, 20) . '...'; ?></code></p>
                <p>User ID: <code><?php echo esc_html($user_id); ?></code></p>
                <a href="<?php echo esc_url($auth_url); ?>" class="button">🔄 Reconnect Instagram</a>
                <button type="button" class="button" onclick="whatsfeedTestConnection()">🧪 Test Connection</button>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($access_token)): ?>
        <hr>
        <h2>📖 Usage</h2>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <p><strong>Basic Shortcode:</strong></p>
            <code>[instagram_feed]</code>
            
            <p><strong>With Custom Options:</strong></p>
            <code>[instagram_feed limit="9" columns="3" layout="grid"]</code>
            
            <p><strong>Carousel Layout:</strong></p>
            <code>[instagram_feed layout="carousel" limit="10"]</code>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab navigation
        const tabs = document.querySelectorAll('.nav-tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('nav-tab-active'));
                
                // Hide all tab contents
                tabContents.forEach(content => content.style.display = 'none');
                
                // Add active class to clicked tab
                this.classList.add('nav-tab-active');
                
                // Show corresponding tab content
                const targetId = this.getAttribute('href').substring(1);
                document.getElementById(targetId).style.display = 'block';
            });
        });
        
        // TikTok connection
        const connectTikTokBtn = document.getElementById('connect-tiktok-btn');
        const reconnectTikTokBtn = document.getElementById('reconnect-tiktok-btn');
        
        if (connectTikTokBtn) {
            connectTikTokBtn.addEventListener('click', function(e) {
                e.preventDefault();
                initiateTikTokAuth();
            });
        }
        
        if (reconnectTikTokBtn) {
            reconnectTikTokBtn.addEventListener('click', function(e) {
                e.preventDefault();
                initiateTikTokAuth();
            });
        }
    });
    
    function initiateTikTokAuth() {
        const clientKey = '<?php echo esc_js($tiktok_client_key); ?>';
        const redirectUri = '<?php echo esc_js(admin_url("admin.php?page=whatsfeed-settings&platform=tiktok")); ?>';
        
        if (!clientKey) {
            alert('Please save your TikTok Client Key first.');
            return;
        }
        
        const csrfState = Math.random().toString(36).substring(2);
        localStorage.setItem('whatsfeed_tiktok_state', csrfState);
        
        const authUrl = `https://www.tiktok.com/v2/auth/authorize/` + 
                        `?client_key=${clientKey}` + 
                        `&scope=video.list` + 
                        `&response_type=code` + 
                        `&redirect_uri=${encodeURIComponent(redirectUri)}` + 
                        `&state=${csrfState}`;
        
        window.location.href = authUrl;
    }
    
    function whatsfeedTestConnection(platform) {
        const testBtn = event.target;
        const originalText = testBtn.innerHTML;
        testBtn.innerHTML = '⏳ Testing...';
        testBtn.disabled = true;
        
        // Create a nonce for security
        const nonce = '<?php echo wp_create_nonce("whatsfeed_test_connection"); ?>';
        
        // Make an AJAX call to test the connection
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'whatsfeed_test_connection',
                platform: platform,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ Connection successful! Your ' + platform + ' feed is working properly.');
                } else {
                    alert('❌ Connection failed: ' + response.data.message);
                }
            },
            error: function() {
                alert('❌ Error testing connection. Please try again.');
            },
            complete: function() {
                testBtn.innerHTML = originalText;
                testBtn.disabled = false;
            }
        });
    }
    </script>
    <?php
}

function whatsfeed_extend_token($short_token) {
    $client_secret = get_option('whatsfeed_app_secret');
    
    if (empty($client_secret)) {
        return false;
    }
    
    $url = "https://graph.instagram.com/access_token?" . http_build_query([
        'grant_type' => 'ig_exchange_token',
        'client_secret' => $client_secret,
        'access_token' => $short_token
    ]);
    
    $response = wp_remote_get($url, ['timeout' => 30]);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!empty($body['access_token'])) {
        return $body['access_token'];
    }
    
    return false;
}

function whatsfeed_get_user_id($access_token) {
    $url = "https://graph.facebook.com/me/accounts?access_token=" . $access_token;
    $response = wp_remote_get($url, ['timeout' => 30]);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!empty($body['data'][0]['id'])) {
        $page_id = $body['data'][0]['id'];
        
        $ig_url = "https://graph.facebook.com/$page_id?fields=instagram_business_account&access_token=$access_token";
        $ig_res = wp_remote_get($ig_url, ['timeout' => 30]);
        
        if (!is_wp_error($ig_res)) {
            $ig_body = json_decode(wp_remote_retrieve_body($ig_res), true);
            if (!empty($ig_body['instagram_business_account']['id'])) {
                update_option('whatsfeed_user_id', $ig_body['instagram_business_account']['id']);
                return $ig_body['instagram_business_account']['id'];
            }
        }
    }
    
    return false;
}