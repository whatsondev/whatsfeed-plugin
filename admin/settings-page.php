<?php
// Add settings menu
add_action('admin_menu', function() {
    add_menu_page(
        'WhatsFeed Settings',
        'WhatsFeed',
        'manage_options',
        'whatsfeed-settings',
        'whatsfeed_settings_page_html',
        'dashicons-instagram'
    );
});

// Register options
add_action('admin_init', function() {
    register_setting('whatsfeed_options', 'whatsfeed_access_token');
    register_setting('whatsfeed_options', 'whatsfeed_user_id');
});

function whatsfeed_settings_page_html() {
    ?>
    <div class="wrap">
        <h1>WhatsFeed – Instagram Settings</h1>
        <p>Paste your Instagram <b>Access Token</b> and <b>User ID</b> below.</p>
        <form method="post" action="options.php">
            <?php settings_fields('whatsfeed_options'); ?>
            <?php do_settings_sections('whatsfeed_options'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">Instagram Access Token</th>
                    <td>
                        <input type="text" name="whatsfeed_access_token"
                               value="<?php echo esc_attr(get_option('whatsfeed_access_token')); ?>"
                               class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Instagram User ID</th>
                    <td>
                        <input type="text" name="whatsfeed_user_id"
                               value="<?php echo esc_attr(get_option('whatsfeed_user_id')); ?>"
                               class="regular-text" />
                    </td>
                </tr>
            </table>

            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}
