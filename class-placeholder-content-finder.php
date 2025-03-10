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
            .filter-options {
                margin: 15px 0;
                padding: 15px;
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .filter-options h3 {
                margin-top: 0;
            }
            .filter-options .filter-item {
                display: inline-block;
                margin-right: 20px;
            }
        </style>';
    }

    public function render_admin_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get filter options
        $filter_options = array(
            'lorem_ipsum' => isset($_POST['filter_lorem_ipsum']),
            'placeholder_link' => isset($_POST['filter_placeholder_link']),
            'placeholder_phone' => isset($_POST['filter_placeholder_phone']),
            'youtube_url' => isset($_POST['filter_youtube_url']),
            'placeholder_image' => isset($_POST['filter_placeholder_image'])
        );
        
        // Check if any filter is active
        $is_filtered = in_array(true, $filter_options);
        
        // Process search if form is submitted
        $results = isset($_POST['find_placeholders']) ? $this->find_placeholders() : null;
        
        // Apply filters if needed
        if ($results && $is_filtered) {
            $results = array_filter($results, function($page) use ($filter_options) {
                foreach ($filter_options as $type => $is_active) {
                    if ($is_active && $page['placeholder_types'][$type]) {
                        return true;
                    }
                }
                // If we're filtering and none of the active filters match, exclude this page
                return false;
            });
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-info">
                <p>This tool identifies pages with placeholder content, showing which types of placeholder content each page contains.</p>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('placeholder_finder_action', 'placeholder_finder_nonce'); ?>
                
                <div class="filter-options">
                    <h3>Filter by Placeholder Type</h3>
                    <p>Select which types of placeholders to search for:</p>
                    
                    <div class="filter-item">
                        <label>
                            <input type="checkbox" name="filter_lorem_ipsum" value="1" 
                                <?php checked(isset($_POST['filter_lorem_ipsum'])); ?>>
                            Lorem Ipsum
                        </label>
                    </div>
                    
                    <div class="filter-item">
                        <label>
                            <input type="checkbox" name="filter_placeholder_link" value="1"
                                <?php checked(isset($_POST['filter_placeholder_link'])); ?>>
                            Placeholder Links
                        </label>
                    </div>
                    
                    <div class="filter-item">
                        <label>
                            <input type="checkbox" name="filter_placeholder_phone" value="1"
                                <?php checked(isset($_POST['filter_placeholder_phone'])); ?>>
                            Phone Numbers (000.000.0000)
                        </label>
                    </div>
                    
                    <div class="filter-item">
                        <label>
                            <input type="checkbox" name="filter_youtube_url" value="1"
                                <?php checked(isset($_POST['filter_youtube_url'])); ?>>
                            YouTube URLs
                        </label>
                    </div>
                    
                    <div class="filter-item">
                        <label>
                            <input type="checkbox" name="filter_placeholder_image" value="1"
                                <?php checked(isset($_POST['filter_placeholder_image'])); ?>>
                            Placeholder Images
                        </label>
                    </div>
                    
                    <p class="description">
                        <?php if ($is_filtered): ?>
                            <em>Showing only pages with selected placeholder types. </em>
                        <?php else: ?>
                            <em>If no types are selected, all placeholder types will be searched.</em>
                        <?php endif; ?>
                    </p>
                </div>
                
                <input type="submit" name="find_placeholders" value="Find Placeholder Content" class="button button-primary">
            </form>

            <?php if ($results !== null): ?>
                <div class="placeholder-results">
                    <h2>Pages with Placeholder Content</h2>
                    
                    <?php if (empty($results)): ?>
                        <div class="notice notice-success">
                            <p>
                                <?php if ($is_filtered): ?>
                                    No pages found with the selected placeholder types.
                                <?php else: ?>
                                    No placeholder content found!
                                <?php endif; ?>
                            </p>
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