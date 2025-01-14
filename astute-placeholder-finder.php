<?php
/*
Plugin Name: Astute Placeholder Finder
Description: Comprehensive search for Lorem Ipsum text and placeholder (#) links across all content types
Version: 1.5
Author: Astute Communications
*/

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

class Placeholder_Content_Finder {
    public function __construct() {
        // Hook into WordPress admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Placeholder Content Finder', 
            'Placeholder Finder', 
            'manage_options', 
            'placeholder-content-finder', 
            array($this, 'render_admin_page'),
            'dashicons-search',
            99
        );
    }

    public function render_admin_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Process search if form is submitted
        $results = isset($_POST['find_placeholders']) ? $this->find_placeholders() : null;

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field('placeholder_finder_action', 'placeholder_finder_nonce'); ?>
                <input type="submit" name="find_placeholders" value="Find Placeholder Content" class="button button-primary">
            </form>

            <?php if ($results): ?>
                <div class="placeholder-results">
                    <h2>Search Results</h2>
                    
                    <?php if (!empty($results['blocks'])): ?>
                        <h3>Gutenberg Blocks with Placeholder Content</h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Post ID</th>
                                    <th>Post Title</th>
                                    <th>Post Status</th>
                                    <th>Block Type</th>
                                    <th>Placeholder Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results['blocks'] as $block_result): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url(get_edit_post_link($block_result['post_id'])); ?>" 
                                               target="_blank">
                                                <?php echo esc_html($block_result['post_id']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo esc_html($block_result['post_title']); ?></td>
                                        <td><?php echo esc_html($block_result['post_status']); ?></td>
                                        <td><?php echo esc_html($block_result['block_type']); ?></td>
                                        <td><?php echo esc_html($block_result['type']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <?php if (!empty($results['posts'])): ?>
                        <h3>Posts with Placeholder Content</h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Post ID</th>
                                    <th>Post Title</th>
                                    <th>Post Status</th>
                                    <th>Placeholder Type</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results['posts'] as $post): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url(get_edit_post_link($post['id'])); ?>" 
                                               target="_blank">
                                                <?php echo esc_html($post['id']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo esc_html($post['title']); ?></td>
                                        <td><?php echo esc_html($post['post_status']); ?></td>
                                        <td><?php echo esc_html($post['type']); ?></td>
                                        <td><?php echo esc_html($post['details']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No posts with placeholder content found.</p>
                    <?php endif; ?>

                    <?php if (!empty($results['postmeta'])): ?>
                        <h3>Post Meta with Placeholder Content</h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Post ID</th>
                                    <th>Post Title</th>
                                    <th>Post Status</th>
                                    <th>Meta Key</th>
                                    <th>Placeholder Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results['postmeta'] as $meta_result): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url(get_edit_post_link($meta_result['post_id'])); ?>" 
                                               target="_blank">
                                                <?php echo esc_html($meta_result['post_id']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo esc_html($meta_result['post_title']); ?></td>
                                        <td><?php echo esc_html($meta_result['post_status']); ?></td>
                                        <td><?php echo esc_html($meta_result['meta_key']); ?></td>
                                        <td><?php echo esc_html($meta_result['type']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <?php if (!empty($results['acf'])): ?>
                        <h3>ACF Fields with Placeholder Content</h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Post ID</th>
                                    <th>Post Title</th>
                                    <th>Post Status</th>
                                    <th>ACF Field</th>
                                    <th>Placeholder Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results['acf'] as $acf_result): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url(get_edit_post_link($acf_result['post_id'])); ?>" 
                                               target="_blank">
                                                <?php echo esc_html($acf_result['post_id']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo esc_html($acf_result['post_title']); ?></td>
                                        <td><?php echo esc_html($acf_result['post_status']); ?></td>
                                        <td><?php echo esc_html($acf_result['field_name']); ?></td>
                                        <td><?php echo esc_html($acf_result['type']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No ACF fields with placeholder content found.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

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
                        ? implode(' ', $block['innerContent']) 
                        : $block['innerContent'];
                    
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
                        // Convert to string for searching
                        $string_value = is_array($value) || is_object($value) 
                            ? wp_json_encode($value) 
                            : (string) $value;

                        // Check for Lorem Ipsum
                        if (preg_match('/\blorem ipsum\b/i', $string_value)) {
                            $results['acf'][] = array(
                                'post_id' => get_the_ID(),
                                'post_title' => get_the_title(),
                                'post_status' => get_post_status(),
                                'field_name' => $field_name,
                                'type' => 'Lorem Ipsum'
                            );
                        }

                        // Check for placeholder links
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

// Initialize the plugin
function initialize_placeholder_finder() {
    new Placeholder_Content_Finder();
}
add_action('plugins_loaded', 'initialize_placeholder_finder');