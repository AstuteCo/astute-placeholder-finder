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
            'acf' => array()
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
                    }
                }
            }
            wp_reset_postdata();
        }

        return $results;
    }
}