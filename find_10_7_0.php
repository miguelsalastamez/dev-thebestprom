<?php
require 'wp-load.php';
global $wpdb;

// Search wp_options
$options = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_value LIKE '%10.7.0%' LIMIT 10");
echo "OPTIONS:\n";
foreach($options as $opt) {
    echo $opt->option_name . "\n";
}

// Search posts (for code snippets)
$posts = $wpdb->get_results("SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE post_content LIKE '%10.7.0%' LIMIT 10");
echo "POSTS:\n";
foreach($posts as $post) {
    echo "ID: " . $post->ID . " - Title: " . $post->post_title . "\n";
}
