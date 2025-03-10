<?php
// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

trait Placeholder_Content_Search {
    public function find_placeholders() {
        global $wpdb;
        $results = array(
            'posts' => array(),
            'blocks' => array(),
            'postmeta' => array(),
            'acf' => array(),
            'phone_numbers' => array(), // Added for phone numbers
            'youtube_urls' => array(),  // Added for YouTube URLs
            'placeholder_images' => array(), // Added for placeholder images
        );

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

            // Skip revisions (additional check)
            if (get_post_type() === 'revision') {
                continue;
            }

            // Search in Gutenberg blocks
            if (has_blocks($post_content)) {
                $blocks = parse_blocks($post_content);
                
                foreach ($blocks as $block) {
                    // Convert block to string for searching
                    $block_content = is_array($block['innerContent']) 
                        ? implode(' ', array_filter($block['innerContent'])) 
                        : (string)$block['innerContent'];
                    
                    // Search for Lorem Ipsum in block
                    if (preg_match('/\blorem ipsum\b/i', $block_content)) {
                        $results['blocks'][] = array(
                            'post_id' => $post_id,
                            'post_title' => $post_title,
                            'post_status' => $post_status,
                            'block_type' => $block['blockName'] ?: 'Unknown Block',
                            'type' => 'Lorem Ipsum'
                        );
                    }

                    // Search for placeholder links in block
                    if (strpos($block_content, 'href="#"') !== false) {
                        $results['blocks'][] = array(
                            'post_id' => $post_id,
                            'post_title' => $post_title,
                            'post_status' => $post_status,
                            'block_type' => $block['blockName'] ?: 'Unknown Block',
                            'type' => 'Placeholder Link'
                        );
                    }
                    
                    // Search for placeholder phone numbers in block
                    if (preg_match('/\b(\d{3}[\.\-\s]?\d{3}[\.\-\s]?\d{4}|\(\d{3}\)[\s\.\-]?\d{3}[\.\-\s]?\d{4})\b/', $block_content, $matches)) {
                        $results['phone_numbers'][] = array(
                            'post_id' => $post_id,
                            'post_title' => $post_title,
                            'post_status' => $post_status,
                            'location' => 'Block: ' . ($block['blockName'] ?: 'Unknown Block'),
                            'number' => $matches[0],
                            'type' => 'Placeholder Phone Number'
                        );
                    }
                    
                    // Search for YouTube URLs in block
                    if (preg_match_all('/(https?:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/.+/', $block_content, $matches)) {
                        foreach ($matches[0] as $youtube_url) {
                            $results['youtube_urls'][] = array(
                                'post_id' => $post_id,
                                'post_title' => $post_title,
                                'post_status' => $post_status,
                                'location' => 'Block: ' . ($block['blockName'] ?: 'Unknown Block'),
                                'url' => $youtube_url,
                                'type' => 'YouTube URL'
                            );
                        }
                    }
                    
                    // Search for images with "placeholder" in the filename
                    if (preg_match_all('/<img[^>]+src=[\'"]([^\'"]+placeholder[^\'"]+)[\'"][^>]*>/i', $block_content, $matches)) {
                        foreach ($matches[1] as $image_src) {
                            $results['placeholder_images'][] = array(
                                'post_id' => $post_id,
                                'post_title' => $post_title,
                                'post_status' => $post_status,
                                'location' => 'Block: ' . ($block['blockName'] ?: 'Unknown Block'),
                                'src' => $image_src,
                                'type' => 'Placeholder Image'
                            );
                        }
                    }
                }
            }

            // Search in post content and excerpt (legacy/non-block content)
            // Check for Lorem Ipsum in content
            if (preg_match('/\blorem ipsum\b/i', $post_content)) {
                $results['posts'][] = array(
                    'id' => $post_id,
                    'title' => $post_title,
                    'post_status' => $post_status,
                    'type' => 'Lorem Ipsum',
                    'details' => 'Found in post content'
                );
            }

            // Check for placeholder links in content
            if (strpos($post_content, 'href="#"') !== false) {
                $results['posts'][] = array(
                    'id' => $post_id,
                    'title' => $post_title,
                    'post_status' => $post_status,
                    'type' => 'Placeholder Link',
                    'details' => 'Found placeholder link href="#"'
                );
            }
            
            // Check for placeholder phone numbers in content
            if (preg_match_all('/\b(\d{3}[\.\-\s]?\d{3}[\.\-\s]?\d{4}|\(\d{3}\)[\s\.\-]?\d{3}[\.\-\s]?\d{4})\b/', $post_content, $matches)) {
                foreach ($matches[0] as $phone_number) {
                    $results['phone_numbers'][] = array(
                        'post_id' => $post_id,
                        'post_title' => $post_title,
                        'post_status' => $post_status,
                        'location' => 'Post Content',
                        'number' => $phone_number,
                        'type' => 'Placeholder Phone Number'
                    );
                }
            }
            
            // Check for YouTube URLs in content
            if (preg_match_all('/(https?:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/.+/', $post_content, $matches)) {
                foreach ($matches[0] as $youtube_url) {
                    $results['youtube_urls'][] = array(
                        'post_id' => $post_id,
                        'post_title' => $post_title,
                        'post_status' => $post_status,
                        'location' => 'Post Content',
                        'url' => $youtube_url,
                        'type' => 'YouTube URL'
                    );
                }
            }
            
            // Check for images with "placeholder" in the filename
            if (preg_match_all('/<img[^>]+src=[\'"]([^\'"]+placeholder[^\'"]+)[\'"][^>]*>/i', $post_content, $matches)) {
                foreach ($matches[1] as $image_src) {
                    $results['placeholder_images'][] = array(
                        'post_id' => $post_id,
                        'post_title' => $post_title,
                        'post_status' => $post_status,
                        'location' => 'Post Content',
                        'src' => $image_src,
                        'type' => 'Placeholder Image'
                    );
                }
            }
        }
        wp_reset_postdata();

        // Search in post meta, excluding meta from revisions
        $meta_query = $wpdb->prepare(
            "SELECT pm.post_id, p.post_title, p.post_status, pm.meta_key, pm.meta_value 
             FROM {$wpdb->postmeta} pm 
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE p.post_type != 'revision' AND 
             (pm.meta_value REGEXP %s OR pm.meta_value LIKE %s)",
            '[[:<:]]lorem ipsum[[:>:]]',
            '%href="#"%'
        );
        $meta_results = $wpdb->get_results($meta_query, ARRAY_A);

        foreach ($meta_results as $meta) {
            // Convert meta value to string
            $string_value = is_serialized($meta['meta_value']) 
                ? wp_json_encode(maybe_unserialize($meta['meta_value'])) 
                : $meta['meta_value'];

            // Check for Lorem Ipsum
            if (preg_match('/\blorem ipsum\b/i', $string_value)) {
                $results['postmeta'][] = array(
                    'post_id' => $meta['post_id'],
                    'post_title' => $meta['post_title'],
                    'post_status' => $meta['post_status'],
                    'meta_key' => $meta['meta_key'],
                    'type' => 'Lorem Ipsum'
                );
            }

            // Check for placeholder links
            if (strpos($string_value, 'href="#"') !== false) {
                $results['postmeta'][] = array(
                    'post_id' => $meta['post_id'],
                    'post_title' => $meta['post_title'],
                    'post_status' => $meta['post_status'],
                    'meta_key' => $meta['meta_key'],
                    'type' => 'Placeholder Link'
                );
            }
            
            // Check for placeholder phone numbers in meta
            if (preg_match_all('/\b(\d{3}[\.\-\s]?\d{3}[\.\-\s]?\d{4}|\(\d{3}\)[\s\.\-]?\d{3}[\.\-\s]?\d{4})\b/', $string_value, $matches)) {
                foreach ($matches[0] as $phone_number) {
                    $results['phone_numbers'][] = array(
                        'post_id' => $meta['post_id'],
                        'post_title' => $meta['post_title'],
                        'post_status' => $meta['post_status'],
                        'location' => 'Meta: ' . $meta['meta_key'],
                        'number' => $phone_number,
                        'type' => 'Placeholder Phone Number'
                    );
                }
            }
            
            // Check for YouTube URLs in meta
            if (preg_match_all('/(https?:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/.+/', $string_value, $matches)) {
                foreach ($matches[0] as $youtube_url) {
                    $results['youtube_urls'][] = array(
                        'post_id' => $meta['post_id'],
                        'post_title' => $meta['post_title'],
                        'post_status' => $meta['post_status'],
                        'location' => 'Meta: ' . $meta['meta_key'],
                        'url' => $youtube_url,
                        'type' => 'YouTube URL'
                    );
                }
            }
            
            // Check for images with "placeholder" in the filename
            if (preg_match_all('/<img[^>]+src=[\'"]([^\'"]+placeholder[^\'"]+)[\'"][^>]*>/i', $string_value, $matches)) {
                foreach ($matches[1] as $image_src) {
                    $results['placeholder_images'][] = array(
                        'post_id' => $meta['post_id'],
                        'post_title' => $meta['post_title'],
                        'post_status' => $meta['post_status'],
                        'location' => 'Meta: ' . $meta['meta_key'],
                        'src' => $image_src,
                        'type' => 'Placeholder Image'
                    );
                }
            }
        }

        // Check ACF fields if Advanced Custom Fields is active
        if (function_exists('get_fields')) {
            $acf_query = new WP_Query(array(
                'posts_per_page' => -1,
                'post_type' => 'any',
                'post_status' => 'any',
                'post__not_in' => wp_get_post_revisions(), // Exclude revisions
            ));

            while ($acf_query->have_posts()) {
                $acf_query->the_post();
                
                // Skip revisions (additional check)
                if (get_post_type() === 'revision') {
                    continue;
                }
                
                $fields = get_fields(get_the_ID());

                if ($fields) {
                    foreach ($fields as $field_name => $value) {
                        // Skip if value is complex type or null
                        if ($value === null || is_array($value) || is_object($value)) {
                            continue;
                        }

                        // Convert to string for searching
                        $string_value = (string) $value;

                        // Check for Lorem Ipsum in text values
                        if (preg_match('/\blorem ipsum\b/i', $string_value)) {
                            $results['acf'][] = array(
                                'post_id' => get_the_ID(),
                                'post_title' => get_the_title(),
                                'post_status' => get_post_status(),
                                'field_name' => $field_name,
                                'type' => 'Lorem Ipsum'
                            );
                        }

                        // Check for placeholder links in text values
                        if (strpos($string_value, 'href="#"') !== false) {
                            $results['acf'][] = array(
                                'post_id' => get_the_ID(),
                                'post_title' => get_the_title(),
                                'post_status' => get_post_status(),
                                'field_name' => $field_name,
                                'type' => 'Placeholder Link'
                            );
                        }
                        
                        // Check for placeholder phone numbers in ACF fields
                        if (preg_match_all('/\b(\d{3}[\.\-\s]?\d{3}[\.\-\s]?\d{4}|\(\d{3}\)[\s\.\-]?\d{3}[\.\-\s]?\d{4})\b/', $string_value, $matches)) {
                            foreach ($matches[0] as $phone_number) {
                                $results['phone_numbers'][] = array(
                                    'post_id' => get_the_ID(),
                                    'post_title' => get_the_title(),
                                    'post_status' => get_post_status(),
                                    'location' => 'ACF: ' . $field_name,
                                    'number' => $phone_number,
                                    'type' => 'Placeholder Phone Number'
                                );
                            }
                        }
                        
                        // Check for YouTube URLs in ACF fields
                        if (preg_match_all('/(https?:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/.+/', $string_value, $matches)) {
                            foreach ($matches[0] as $youtube_url) {
                                $results['youtube_urls'][] = array(
                                    'post_id' => get_the_ID(),
                                    'post_title' => get_the_title(),
                                    'post_status' => get_post_status(),
                                    'location' => 'ACF: ' . $field_name,
                                    'url' => $youtube_url,
                                    'type' => 'YouTube URL'
                                );
                            }
                        }
                        
                        // Check for images with "placeholder" in the filename
                        if (preg_match_all('/<img[^>]+src=[\'"]([^\'"]+placeholder[^\'"]+)[\'"][^>]*>/i', $string_value, $matches)) {
                            foreach ($matches[1] as $image_src) {
                                $results['placeholder_images'][] = array(
                                    'post_id' => get_the_ID(),
                                    'post_title' => get_the_title(),
                                    'post_status' => get_post_status(),
                                    'location' => 'ACF: ' . $field_name,
                                    'src' => $image_src,
                                    'type' => 'Placeholder Image'
                                );
                            }
                        }
                    }
                }
            }
            wp_reset_postdata();
        }

        return $results;
    }
}