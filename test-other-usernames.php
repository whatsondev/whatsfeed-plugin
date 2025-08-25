<?php
// Load WordPress
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
require_once($wp_load_path);

// Include the WhatsFeed Instagram class
require_once('includes/class-whatsfeed-instagram.php');

echo "Testing Instagram API with different usernames\n\n";

// List of popular Instagram usernames to test
$test_usernames = [
    'instagram',
    'cristiano',
    'leomessi',
    'natgeo',
    'nike'
];

foreach ($test_usernames as $username) {
    echo "\n=== Testing username: {$username} ===\n";
    
    // Clear any existing cache for this username
    $transient_key = 'whatsfeed_instagram_' . md5($username);
    delete_transient($transient_key);
    echo "Cleared cache for username '{$username}'\n";
    
    // Test the Instagram API v1 endpoint directly
    $api_url = "https://i.instagram.com/api/v1/users/web_profile_info/?username={$username}";
    
    $headers = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Accept' => '*/*',
        'Accept-Language' => 'en-US,en;q=0.5',
        'x-ig-app-id' => '936619743392459',
        'X-Requested-With' => 'XMLHttpRequest',
        'Connection' => 'keep-alive',
        'Referer' => 'https://www.instagram.com/' . $username . '/',
        'Origin' => 'https://www.instagram.com',
    ];
    
    $args = [
        'headers' => $headers,
        'timeout' => 15,
        'sslverify' => false,
    ];
    
    echo "Trying Instagram API v1 endpoint: {$api_url}\n";
    $response = wp_remote_get($api_url, $args);
    
    if (is_wp_error($response)) {
        echo "API v1 endpoint error: " . $response->get_error_message() . "\n";
        continue;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    echo "API v1 endpoint response code: {$response_code}\n";
    
    if ($response_code !== 200) {
        echo "API v1 endpoint error: Non-200 response code\n";
        continue;
    }
    
    $data = json_decode($body, true);
    
    if (empty($data) || !isset($data['data']) || !isset($data['data']['user'])) {
        echo "API v1 endpoint error: Invalid response format\n";
        continue;
    }
    
    $user_data = $data['data']['user'];
    $post_count = 0;
    
    if (isset($user_data['edge_owner_to_timeline_media']['edges'])) {
        $post_count = count($user_data['edge_owner_to_timeline_media']['edges']);
    }
    
    echo "Found {$post_count} posts from API v1 endpoint\n";
    
    if ($post_count > 0) {
        echo "First post details:\n";
        $first_post = $user_data['edge_owner_to_timeline_media']['edges'][0]['node'];
        echo "- ID: " . $first_post['id'] . "\n";
        echo "- Type: " . ($first_post['is_video'] ? 'video' : 'image') . "\n";
        echo "- Caption: " . (isset($first_post['edge_media_to_caption']['edges'][0]['node']['text']) ? 
            substr($first_post['edge_media_to_caption']['edges'][0]['node']['text'], 0, 50) . "..." : 'No caption') . "\n";
    }
}

echo "\nTest completed.\n";