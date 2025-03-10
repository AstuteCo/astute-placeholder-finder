<?php
// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

trait Placeholder_Content_Search {
    public function find_placeholders() {
        global $wpdb;
        
        // Initialize the results structure to track pages with placeholders
        $results = array();
        
        // Get all posts, excluding revisions
        $posts_query = new WP_Query(array(
            'posts_per_page' => -1,
            'post_type' => 'any',
            'post_status' => 'any',
            'post__not_in' => wp_get_post_revisions(), // Exclude revisions
        ));

        while ($posts_query->have_posts()) {
            $posts_query->the_post();
            $post_id = get_the_ID();
            $post_title = get_the_title();
            $post_content = get_the_content();
            $post_status = get_post_status();
            $post_type = get_post_type();
            $edit_link = get_edit_post_link($post_id);

            // Skip revisions (additional check)
            if ($post_type === 'revision') {
                continue;
            }
            
            // Initialize placeholder types for this page
            $placeholder_types = array(
                'lorem_ipsum' => false,
                'placeholder_link' => false,
                'placeholder_phone' => false,
                'youtube_url' => false,
                'placeholder_image' => false
            );
            
            // Check post content for placeholders
            // Look for Lorem Ipsum
            if (preg_match('/\blorem ipsum\b/i', $post_content)) {
                $placeholder_types['lorem_ipsum'] = true;
            }
            
            // Look for placeholder links
            if (strpos($post_content, 'href="#"') !== false) {
                $placeholder_types['placeholder_link'] = true;
            }
            
            // Look for specific phone number format: 000.000.0000
            if (preg_match('/\b000\.000\.0000\b/', $post_content) || 
                preg_match('/\b000\-000\-0000\b/', $post_content) || 
                preg_match('/\b\(000\)[\s\.\-]?000[\.\-]?0000\b/', $post_content)) {
                $placeholder_types['placeholder_phone'] = true;
            }
            
            // Look for YouTube URLs 
            if (preg_match('/(https?:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/.+/', $post_content)) {
                $placeholder_types['youtube_url'] = true;
            }
            
            // Look for images with "placeholder" in the filename
            if (preg_match('/<img[^>]+src=[\'"][^\'"]*placeholder[^\'"]*[\'"][^>]*>/i', $post_content)) {
                $placeholder_types['placeholder_image'] = true;
            }
            
            // Check Gutenberg blocks if present
            if (has_blocks($post_content)) {
                $blocks = parse_blocks($post_content);
                
                foreach ($blocks as $block) {
                    // Convert block to string for searching
                    $block_content = is_array($block['innerContent']) 
                        ? implode(' ', array_filter($block['innerContent'])) 
                        : (string)$block['innerContent'];
                    
                    // Check for placeholders in blocks
                    if (preg_match('/\blorem ipsum\b/i', $block_content)) {
                        $placeholder_types['lorem_ipsum'] = true;
                    }
                    
                    if (strpos($block_content, 'href="#"') !== false) {
                        $placeholder_types['placeholder_link'] = true;
                    }
                    
                    if (preg_match('/\b000\.000\.0000\b/', $block_content) || 
                        preg_match('/\b000\-000\-0000\b/', $block_content) || 
                        preg_match('/\b\(000\)[\s\.\-]?000[\.\-]?0000\b/', $block_content)) {
                        $placeholder_types['placeholder_phone'] = true;
                    }
                    
                    if (preg_match('/(https?:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/.+/', $block_content)) {
                        $placeholder_types['youtube_url'] = true;
                    }
                    
                    if (preg_match('/<img[^>]+src=[\'"][^\'"]*placeholder[^\'"]*[\'"][^>]*>/i', $block_content)) {
                        $placeholder_types['placeholder_image'] = true;
                    }
                }
            }
            
            // Check post meta for placeholders
            $post_meta = get_post_meta($post_id);
            if (!empty($post_meta)) {
                foreach ($post_meta as $meta_key => $meta_values) {
                    foreach ($meta_values as $meta_value) {
                        // Convert value to string for searching
                        $string_value = is_serialized($meta_value) 
                            ? wp_json_encode(maybe_unserialize($meta_value)) 
                            : (string)$meta_value;
                        
                        // Check for placeholders in meta values
                        if (preg_match('/\blorem ipsum\b/i', $string_value)) {
                            $placeholder_types['lorem_ipsum'] = true;
                        }
                        
                        if (strpos($string_value, 'href="#"') !== false) {
                            $placeholder_types['placeholder_link'] = true;
                        }
                        
                        if (preg_match('/\b000\.000\.0000\b/', $string_value) ||
                            preg_match('/\b000\-000\-0000\b/', $string_value) ||
                            preg_match('/\b\(000\)[\s\.\-]?000[\.\-]?0000\b/', $string_value)) {
                            $placeholder_types['placeholder_phone'] = true;
                        }
                        
                        if (preg_match('/(https?:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/.+/', $string_value)) {
                            $placeholder_types['youtube_url'] = true;
                        }
                        
                        if (preg_match('/<img[^>]+src=[\'"][^\'"]*placeholder[^\'"]*[\'"][^>]*>/i', $string_value)) {
                            $placeholder_types['placeholder_image'] = true;
                        }
                    }
                }
            }
            
            // Check ACF fields if available
            if (function_exists('get_fields')) {
                $acf_fields = get_fields($post_id);
                if ($acf_fields) {
                    foreach ($acf_fields as $field_name => $value) {
                        // Skip complex types or null values
                        if ($value === null || is_array($value) || is_object($value)) {
                            continue;
                        }
                        
                        // Convert to string for searching
                        $string_value = (string) $value;
                        
                        // Check for placeholders in ACF field values
                        if (preg_match('/\blorem ipsum\b/i', $string_value)) {
                            $placeholder_types['lorem_ipsum'] = true;
                        }
                        
                        if (strpos($string_value, 'href="#"') !== false) {
                            $placeholder_types['placeholder_link'] = true;
                        }
                        
                        if (preg_match('/\b000\.000\.0000\b/', $string_value) ||
                            preg_match('/\b000\-000\-0000\b/', $string_value) ||
                            preg_match('/\b\(000\)[\s\.\-]?000[\.\-]?0000\b/', $string_value)) {
                            $placeholder_types['placeholder_phone'] = true;
                        }
                        
                        if (preg_match('/(https?:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/.+/', $string_value)) {
                            $placeholder_types['youtube_url'] = true;
                        }
                        
                        if (preg_match('/<img[^>]+src=[\'"][^\'"]*placeholder[^\'"]*[\'"][^>]*>/i', $string_value)) {
                            $placeholder_types['placeholder_image'] = true;
                        }
                    }
                }
            }
            
            // Add to results only if at least one placeholder type is found
            if (in_array(true, $placeholder_types)) {
                $results[] = array(
                    'post_id' => $post_id,
                    'post_title' => $post_title,
                    'post_type' => $post_type,
                    'post_status' => $post_status,
                    'edit_link' => $edit_link,
                    'placeholder_types' => $placeholder_types
                );
            }
        }
        wp_reset_postdata();
        
        return $results;
    }
}