<?php
// Load WordPress
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
require_once($wp_load_path);

// Include the WhatsFeed Instagram class
require_once('includes/class-whatsfeed-instagram.php');

echo "Testing Instagram API for username: 'jarin_rahi'\n\n";

$username = 'jarin_rahi';

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
    exit;
}

$response_code = wp_remote_retrieve_response_code($response);
$body = wp_remote_retrieve_body($response);

echo "API v1 endpoint response code: {$response_code}\n";

if ($response_code !== 200) {
    echo "API v1 endpoint error: Non-200 response code\n";
    exit;
}

// Print the raw response for inspection
echo "\nRaw API response (first 1000 characters):\n";
echo substr($body, 0, 1000) . "...\n\n";

$data = json_decode($body, true);

if (empty($data)) {
    echo "API v1 endpoint error: Could not decode JSON response\n";
    exit;
}

echo "JSON structure:\n";
print_r(array_keys($data));

if (!isset($data['data']) || !isset($data['data']['user'])) {
    echo "\nAPI v1 endpoint error: Missing 'data' or 'user' in response\n";
    if (isset($data['data'])) {
        echo "\nData keys available:\n";
        print_r(array_keys($data['data']));
    }
    exit;
}

$user_data = $data['data']['user'];

echo "\nUser data available:\n";
print_r(array_keys($user_data));

if (!isset($user_data['edge_owner_to_timeline_media'])) {
    echo "\nAPI v1 endpoint error: Missing 'edge_owner_to_timeline_media' in user data\n";
    exit;
}

$timeline_media = $user_data['edge_owner_to_timeline_media'];

echo "\nTimeline media data available:\n";
print_r(array_keys($timeline_media));

if (!isset($timeline_media['edges'])) {
    echo "\nAPI v1 endpoint error: Missing 'edges' in timeline media data\n";
    exit;
}

$post_count = count($timeline_media['edges']);

echo "\nFound {$post_count} posts from API v1 endpoint\n";

if ($post_count > 0) {
    echo "First post details:\n";
    $first_post = $timeline_media['edges'][0]['node'];
    echo "- ID: " . $first_post['id'] . "\n";
    echo "- Type: " . ($first_post['is_video'] ? 'video' : 'image') . "\n";
    echo "- Caption: " . (isset($first_post['edge_media_to_caption']['edges'][0]['node']['text']) ? 
        substr($first_post['edge_media_to_caption']['edges'][0]['node']['text'], 0, 50) . "..." : 'No caption') . "\n";
} else {
    echo "\nNo posts found for this username.\n";
    
    // Check if the user exists but has no posts
    if (isset($user_data['username'])) {
        echo "\nUser exists but has no posts.\n";
        echo "Username: " . $user_data['username'] . "\n";
        echo "Full name: " . $user_data['full_name'] . "\n";
        echo "Biography: " . $user_data['biography'] . "\n";
        echo "Is private: " . ($user_data['is_private'] ? 'Yes' : 'No') . "\n";
        echo "Is verified: " . ($user_data['is_verified'] ? 'Yes' : 'No') . "\n";
        echo "Profile pic URL: " . $user_data['profile_pic_url'] . "\n";
    }
}

echo "\nTest completed.\n";