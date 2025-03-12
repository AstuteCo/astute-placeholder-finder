<?php
/*
Plugin Name: Astute Placeholder Finder
Description: Comprehensive search for Lorem Ipsum text and placeholder (#) links across all content types
Version: 1.8
Author: Astute Communications
*/

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Include the main plugin class
require_once plugin_dir_path(__FILE__) . 'class-placeholder-content-finder.php';

// Initialize the plugin
function astute_placeholder_finder_init() {
    new Placeholder_Content_Finder();
}
add_action('plugins_loaded', 'astute_placeholder_finder_init');