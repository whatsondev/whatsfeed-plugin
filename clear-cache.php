<?php
// Load WordPress
require_once('d:/projects/xampp/htdocs/whatson.guide/wp-load.php');

// Username to clear cache for
$username = 'jarin_rahi';
$cache_key = 'whatsfeed_instagram_' . md5($username . '_12');

// Delete the specific cache
$deleted = delete_transient($cache_key);
echo "Deleted cache for username '{$username}': " . ($deleted ? 'Yes' : 'No') . "\n";

// Also clear any other whatsfeed_instagram_ transients
$like = 'whatsfeed_instagram_%';
global $wpdb;
$count = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_{$like}' OR option_name LIKE '_transient_timeout_{$like}'");
echo "Cleared {$count} whatsfeed_instagram_ transients\n";

// Clear token-based cache as well
$token_cache_key = 'whatsfeed_instagram_token_%';
$token_count = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_{$token_cache_key}' OR option_name LIKE '_transient_timeout_{$token_cache_key}'");
echo "Cleared {$token_count} token-based transients\n";