<?php
// Load WordPress
require_once('d:/projects/xampp/htdocs/whatson.guide/wp-load.php');

// Get the Instagram username
$instagram_username = 'jarin_rahi';
echo "Testing Instagram API for username: '{$instagram_username}'\n\n";

// Include the Instagram class
require_once(plugin_dir_path(__FILE__) . 'includes/class-whatsfeed-instagram.php');

// Test the fetch_by_username method
echo "Fetching feed using fetch_by_username method...\n";
$feed = WhatsFeed_Instagram::fetch_by_username($instagram_username, 12);

// Check if the feed was fetched successfully
if (is_wp_error($feed)) {
    echo "Error: " . $feed->get_error_message() . "\n";
} else {
    echo "Success! Feed fetched with " . count($feed) . " posts.\n";
    
    // Display the first post details
    if (!empty($feed)) {
        echo "\nFirst post details:\n";
        $first_post = $feed[0];
        echo "- Type: " . $first_post['type'] . "\n";
        echo "- URL: " . $first_post['permalink'] . "\n";
        echo "- Caption: " . (isset($first_post['caption']) ? substr($first_post['caption'], 0, 100) . '...' : 'No caption') . "\n";
        echo "- Timestamp: " . $first_post['timestamp'] . "\n";
        echo "- Media URL: " . $first_post['media_url'] . "\n";
    }
}

// Test each method individually to see which one works
echo "\n\nTesting individual API methods...\n";

// Test the JSON API endpoint
echo "\n1. Testing JSON API endpoint...\n";
$json_url = "https://www.instagram.com/{$instagram_username}/?__a=1&__d=dis";
echo "URL: {$json_url}\n";

$args = array(
    'timeout' => 15,
    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
    'headers' => array(
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Language' => 'en-US,en;q=0.9',
        'Referer' => 'https://www.instagram.com/',
    )
);

$response = wp_remote_get($json_url, $args);

if (is_wp_error($response)) {
    echo "Error: " . $response->get_error_message() . "\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    echo "Status Code: {$status_code}\n";
    echo "Response Length: " . strlen($body) . " bytes\n";
    echo "Response Preview: " . substr($body, 0, 150) . "...\n";
}

// Test the API v1 endpoint
echo "\n2. Testing API v1 endpoint...\n";
$api_v1_url = "https://i.instagram.com/api/v1/users/web_profile_info/?username={$instagram_username}";
echo "URL: {$api_v1_url}\n";

$args = array(
    'timeout' => 15,
    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
    'headers' => array(
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Language' => 'en-US,en;q=0.9',
        'Referer' => 'https://www.instagram.com/',
        'x-ig-app-id' => '936619743392459'
    )
);

$response = wp_remote_get($api_v1_url, $args);

if (is_wp_error($response)) {
    echo "Error: " . $response->get_error_message() . "\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    echo "Status Code: {$status_code}\n";
    echo "Response Length: " . strlen($body) . " bytes\n";
    echo "Response Preview: " . substr($body, 0, 150) . "...\n";
    
    // Try to decode the JSON
    $data = json_decode($body, true);
    if ($data && isset($data['data']) && isset($data['data']['user'])) {
        echo "User ID: " . $data['data']['user']['id'] . "\n";
        echo "Username: " . $data['data']['user']['username'] . "\n";
        echo "Full Name: " . $data['data']['user']['full_name'] . "\n";
        echo "Media Count: " . $data['data']['user']['edge_owner_to_timeline_media']['count'] . "\n";
    }
}

// Test the GraphQL API endpoint
echo "\n3. Testing GraphQL API endpoint...\n";
$user_id = '';

// Try to get user ID from API v1 response
if (isset($data) && isset($data['data']) && isset($data['data']['user']) && isset($data['data']['user']['id'])) {
    $user_id = $data['data']['user']['id'];
    echo "Using user ID from API v1: {$user_id}\n";
}

if (!empty($user_id)) {
    $query_hash = '8c2a529969ee035a5063f2fc8602a0fd';
    $variables = array(
        'id' => $user_id,
        'first' => 12
    );
    
    $graphql_url = "https://www.instagram.com/graphql/query/?query_hash={$query_hash}&variables=" . urlencode(json_encode($variables));
    echo "URL: {$graphql_url}\n";
    
    $args = array(
        'timeout' => 15,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        'headers' => array(
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Referer' => "https://www.instagram.com/{$instagram_username}/",
            'x-ig-app-id' => '936619743392459'
        )
    );
    
    $response = wp_remote_get($graphql_url, $args);
    
    if (is_wp_error($response)) {
        echo "Error: " . $response->get_error_message() . "\n";
    } else {
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        echo "Status Code: {$status_code}\n";
        echo "Response Length: " . strlen($body) . " bytes\n";
        echo "Response Preview: " . substr($body, 0, 150) . "...\n";
        
        // Try to decode the JSON
        $data = json_decode($body, true);
        if ($data && isset($data['data']) && isset($data['data']['user']) && isset($data['data']['user']['edge_owner_to_timeline_media'])) {
            $edges = $data['data']['user']['edge_owner_to_timeline_media']['edges'];
            echo "Posts found: " . count($edges) . "\n";
        }
    }
} else {
    echo "No user ID available for GraphQL API test.\n";
}

// Test the profile page scraping
echo "\n4. Testing profile page scraping...\n";
$profile_url = "https://www.instagram.com/{$instagram_username}/";
echo "URL: {$profile_url}\n";

$args = array(
    'timeout' => 30,
    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
    'headers' => array(
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Language' => 'en-US,en;q=0.9',
    )
);

$response = wp_remote_get($profile_url, $args);

if (is_wp_error($response)) {
    echo "Error: " . $response->get_error_message() . "\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    echo "Status Code: {$status_code}\n";
    echo "Response Length: " . strlen($body) . " bytes\n";
    
    // Check for _sharedData
    if (preg_match('/<script type="text\/javascript">window\._sharedData = (.*?);<\/script>/s', $body, $matches)) {
        echo "Found _sharedData JSON\n";
        $shared_data = json_decode($matches[1], true);
        if ($shared_data && isset($shared_data['entry_data']) && isset($shared_data['entry_data']['ProfilePage'])) {
            echo "Found ProfilePage data\n";
        }
    } else {
        echo "No _sharedData found\n";
    }
    
    // Check for __INITIAL_DATA__
    if (preg_match('/<script type="text\/javascript">window\.__INITIAL_DATA__ = (.*?);<\/script>/s', $body, $matches)) {
        echo "Found __INITIAL_DATA__ JSON\n";
    } else {
        echo "No __INITIAL_DATA__ found\n";
    }
    
    // Check for __SSR_DATA__
    if (preg_match('/<script type="text\/javascript">window\.__SSR_DATA__ = (.*?);<\/script>/s', $body, $matches)) {
        echo "Found __SSR_DATA__ JSON\n";
    } else {
        echo "No __SSR_DATA__ found\n";
    }
    
    // Check for application/json script tags
    $json_script_count = preg_match_all('/<script type="application\/json".*?>(.*?)<\/script>/s', $body, $json_matches);
    echo "Found {$json_script_count} application/json script tags\n";
}

echo "\n\nTest completed.\n";