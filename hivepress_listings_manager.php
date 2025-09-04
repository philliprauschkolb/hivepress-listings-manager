<?php
/**
 * Plugin Name: HivePress User Listings Manager
 * Description: Allows manual setting of available listings count for users from WordPress admin area
 * Version: 1.0.0
 * Author: Just some guy
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HivePressListingsManager {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add user profile fields
        add_action('show_user_profile', array($this, 'add_user_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_user_profile_fields'));
        
        // Save user profile fields
        add_action('personal_options_update', array($this, 'save_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_profile_fields'));
        
        // Add listings column to users list
        add_filter('manage_users_columns', array($this, 'add_listings_column'));
        add_filter('manage_users_custom_column', array($this, 'show_listings_column_content'), 10, 3);
        
        // Add AJAX handler for bulk operations
        add_action('wp_ajax_update_user_listings', array($this, 'ajax_update_user_listings'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'users.php',
            'Manage User Listings',
            'User Listings',
            'manage_options',
            'hivepress-listings-manager',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Get user's current listings count
     */
    public function get_user_listings_count($user_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT comment_karma FROM {$wpdb->comments} WHERE user_id = %d LIMIT 1",
            $user_id
        ));
        
        return $count !== null ? intval($count) : 0;
    }
    
    /**
     * Set user's listings count
     */
    public function set_user_listings_count($user_id, $count) {
        global $wpdb;
        
        // Check if user has a record in comments table
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT comment_ID FROM {$wpdb->comments} WHERE user_id = %d LIMIT 1",
            $user_id
        ));
        
        if ($existing) {
            // Update existing record
            $result = $wpdb->update(
                $wpdb->comments,
                array('comment_karma' => intval($count)),
                array('user_id' => $user_id),
                array('%d'),
                array('%d')
            );
        } else {
            // Create new record
            $result = $wpdb->insert(
                $wpdb->comments,
                array(
                    'comment_post_ID' => 0,
                    'comment_author' => get_userdata($user_id)->display_name,
                    'comment_author_email' => get_userdata($user_id)->user_email,
                    'comment_date' => current_time('mysql'),
                    'comment_date_gmt' => current_time('mysql', 1),
                    'comment_content' => 'HivePress listings count record',
                    'comment_approved' => '1',
                    'comment_type' => 'hivepress_listings',
                    'user_id' => $user_id,
                    'comment_karma' => intval($count)
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d')
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Add user profile fields
     */
    public function add_user_profile_fields($user) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $listings_count = $this->get_user_listings_count($user->ID);
        ?>
        <h3>HivePress Listings</h3>
        <table class="form-table">
            <tr>
                <th><label for="hivepress_listings_count">Available Listings</label></th>
                <td>
                    <input type="number" 
                           name="hivepress_listings_count" 
                           id="hivepress_listings_count" 
                           value="<?php echo esc_attr($listings_count); ?>" 
                           class="regular-text" 
                           min="0" />
                    <p class="description">Set the number of listings this user can create.</p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save user profile fields
     */
    public function save_user_profile_fields($user_id) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['hivepress_listings_count'])) {
            $count = intval($_POST['hivepress_listings_count']);
            $this->set_user_listings_count($user_id, $count);
        }
    }
    
    /**
     * Add listings column to users list
     */
    public function add_listings_column($columns) {
        $columns['hivepress_listings'] = 'Available Listings';
        return $columns;
    }
    
    /**
     * Show listings column content
     */
    public function show_listings_column_content($value, $column_name, $user_id) {
        if ($column_name === 'hivepress_listings') {
            $count = $this->get_user_listings_count($user_id);
            return '<span class="listings-count" data-user-id="' . $user_id . '">' . $count . '</span>';
        }
        return $value;
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        if (isset($_POST['bulk_update']) && wp_verify_nonce($_POST['_wpnonce'], 'bulk_update_listings')) {
            $this->handle_bulk_update();
        }
        
        $users = get_users();
        ?>
        <div class="wrap">
            <h1>Manage User Listings</h1>
            
            <div class="notice notice-info">
                <p><strong>Bulk Update:</strong> Set the same number of listings for multiple users at once.</p>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('bulk_update_listings'); ?>
                
                <div style="margin: 20px 0;">
                    <label for="bulk_listings_count">Set listings count:</label>
                    <input type="number" id="bulk_listings_count" name="bulk_listings_count" value="0" min="0" style="width: 100px;">
                    <input type="submit" name="bulk_update" class="button" value="Apply to Selected Users">
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="select-all-users">
                            </td>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Current Listings Count</th>
                            <th>Quick Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" name="selected_users[]" value="<?php echo $user->ID; ?>">
                            </th>
                            <td>
                                <strong><?php echo esc_html($user->display_name); ?></strong><br>
                                <small>ID: <?php echo $user->ID; ?></small>
                            </td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td><?php echo esc_html(implode(', ', $user->roles)); ?></td>
                            <td>
                                <span id="listings-count-<?php echo $user->ID; ?>">
                                    <?php echo $this->get_user_listings_count($user->ID); ?>
                                </span>
                            </td>
                            <td>
                                <input type="number" 
                                       id="quick-update-<?php echo $user->ID; ?>" 
                                       value="<?php echo $this->get_user_listings_count($user->ID); ?>" 
                                       min="0" 
                                       style="width: 80px;">
                                <button type="button" 
                                        class="button quick-update-btn" 
                                        data-user-id="<?php echo $user->ID; ?>">
                                    Update
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Select all functionality
            $('#select-all-users').change(function() {
                $('input[name="selected_users[]"]').prop('checked', this.checked);
            });
            
            // Quick update functionality
            $('.quick-update-btn').click(function() {
                var userId = $(this).data('user-id');
                var newCount = $('#quick-update-' + userId).val();
                var button = $(this);
                
                button.prop('disabled', true).text('Updating...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'update_user_listings',
                        user_id: userId,
                        count: newCount,
                        nonce: '<?php echo wp_create_nonce('update_user_listings'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#listings-count-' + userId).text(newCount);
                            button.prop('disabled', false).text('Update');
                            
                            // Show success message briefly
                            button.text('Updated!').addClass('button-primary');
                            setTimeout(function() {
                                button.text('Update').removeClass('button-primary');
                            }, 2000);
                        } else {
                            alert('Error updating listings count: ' + response.data);
                            button.prop('disabled', false).text('Update');
                        }
                    },
                    error: function() {
                        alert('An error occurred while updating.');
                        button.prop('disabled', false).text('Update');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Handle bulk update
     */
    private function handle_bulk_update() {
        if (!isset($_POST['selected_users']) || !isset($_POST['bulk_listings_count'])) {
            return;
        }
        
        $selected_users = array_map('intval', $_POST['selected_users']);
        $bulk_count = intval($_POST['bulk_listings_count']);
        
        $updated = 0;
        foreach ($selected_users as $user_id) {
            if ($this->set_user_listings_count($user_id, $bulk_count)) {
                $updated++;
            }
        }
        
        echo '<div class="notice notice-success"><p>Updated ' . $updated . ' users with ' . $bulk_count . ' listings each.</p></div>';
    }
    
    /**
     * AJAX handler for quick updates
     */
    public function ajax_update_user_listings() {
        if (!wp_verify_nonce($_POST['nonce'], 'update_user_listings') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $user_id = intval($_POST['user_id']);
        $count = intval($_POST['count']);
        
        if ($this->set_user_listings_count($user_id, $count)) {
            wp_send_json_success('Listings count updated successfully.');
        } else {
            wp_send_json_error('Failed to update listings count.');
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook === 'users_page_hivepress-listings-manager') {
            wp_enqueue_script('jquery');
        }
    }
}

// Initialize the plugin
new HivePressListingsManager();

// Add activation hook
register_activation_hook(__FILE__, function() {
    // Plugin activation logic if needed
});

// Add deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Plugin deactivation logic if needed
});
?>