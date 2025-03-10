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
        
        // Add some basic styling
        add_action('admin_head', array($this, 'add_admin_styles'));
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
    
    public function add_admin_styles() {
        echo '<style>
            .placeholder-indicator {
                display: inline-block;
                padding: 3px 8px;
                margin: 2px;
                border-radius: 3px;
                color: #fff;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .placeholder-indicator.lorem {
                background-color: #d63638;
            }
            .placeholder-indicator.link {
                background-color: #2271b1;
            }
            .placeholder-indicator.phone {
                background-color: #8c5e58;
            }
            .placeholder-indicator.youtube {
                background-color: #ff0000;
            }
            .placeholder-indicator.image {
                background-color: #2ea2cc;
            }
        </style>';
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
            
            <div class="notice notice-info">
                <p>This tool identifies pages with placeholder content, showing which types of placeholder content each page contains.</p>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('placeholder_finder_action', 'placeholder_finder_nonce'); ?>
                <input type="submit" name="find_placeholders" value="Find Placeholder Content" class="button button-primary">
            </form>

            <?php if ($results): ?>
                <div class="placeholder-results">
                    <h2>Pages with Placeholder Content</h2>
                    
                    <?php if (empty($results)): ?>
                        <div class="notice notice-success">
                            <p>No placeholder content found!</p>
                        </div>
                    <?php else: ?>
                        <p>Found <?php echo count($results); ?> pages with placeholder content.</p>
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Page Title</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Placeholder Types</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $page): ?>
                                    <tr>
                                        <td><?php echo esc_html($page['post_id']); ?></td>
                                        <td>
                                            <strong>
                                                <a href="<?php echo esc_url($page['edit_link']); ?>" target="_blank">
                                                    <?php echo esc_html($page['post_title']); ?>
                                                </a>
                                            </strong>
                                        </td>
                                        <td><?php echo esc_html($page['post_type']); ?></td>
                                        <td><?php echo esc_html($page['post_status']); ?></td>
                                        <td>
                                            <?php if ($page['placeholder_types']['lorem_ipsum']): ?>
                                                <span class="placeholder-indicator lorem">Lorem Ipsum</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($page['placeholder_types']['placeholder_link']): ?>
                                                <span class="placeholder-indicator link">Placeholder Link</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($page['placeholder_types']['placeholder_phone']): ?>
                                                <span class="placeholder-indicator phone">Phone 000.000.0000</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($page['placeholder_types']['youtube_url']): ?>
                                                <span class="placeholder-indicator youtube">YouTube URL</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($page['placeholder_types']['placeholder_image']): ?>
                                                <span class="placeholder-indicator image">Placeholder Image</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}