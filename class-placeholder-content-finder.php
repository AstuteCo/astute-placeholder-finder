<?php
// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Include the trait
require_once plugin_dir_path(__FILE__) . 'trait-placeholder-content-search.php';

class Placeholder_Content_Finder {
    use Placeholder_Content_Search;

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
                    
                    <?php $this->render_blocks_table($results); ?>
                    <?php $this->render_posts_table($results); ?>
                    <?php $this->render_postmeta_table($results); ?>
                    <?php $this->render_acf_table($results); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_blocks_table($results) {
        if (!empty($results['blocks'])): ?>
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
        <?php endif;
    }

    private function render_posts_table($results) {
        if (!empty($results['posts'])): ?>
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
        <?php endif;
    }

    private function render_postmeta_table($results) {
        if (!empty($results['postmeta'])): ?>
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
        <?php endif;
    }

    private function render_acf_table($results) {
        if (!empty($results['acf'])): ?>
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
        <?php endif;
    }
}