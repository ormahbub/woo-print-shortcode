<?php
/**
 * Plugin Name: Woo Print Shortcode
 * Description: Create custom HTML shortcodes and display them on WooCommerce product pages with precise positioning.
 * Version: 3.1
 * Author: Gemini AI
 * Text Domain: woo-print-shortcode
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Include necessary files
require_once plugin_dir_path( __FILE__ ) . 'includes/display-positions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/rules-manager.php';

class WooPrintShortcode {

    private $rules_manager;

    public function __construct() {
        $this->rules_manager = new WPS_Rules_Manager();
        
        // Initialization
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'init', [ $this, 'register_custom_shortcodes' ] );
        add_action( 'wp', [ $this, 'execute_display_rules' ] );
        
        // Add admin assets
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // Enqueue frontend styles for shortcode display
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_styles' ] );
    }

    /**
     * Register admin menu
     */
    public function register_menus() {
        // Main Parent Menu
        add_menu_page(
            'Woo Print Shortcode',
            'Woo Print',
            'manage_options',
            'woo-print-main',
            [ $this, 'render_creator_page' ],
            'dashicons-media-code',
            58
        );

        // Submenu: HTML Creator
        add_submenu_page(
            'woo-print-main',
            'Shortcode Creator',
            'Shortcode Creator',
            'manage_options',
            'woo-print-main',
            [ $this, 'render_creator_page' ]
        );

        // Submenu: Display Rules
        add_submenu_page(
            'woo-print-main',
            'Display Rules',
            'Display Rules',
            'manage_options',
            'woo-print-display',
            [ $this, 'render_display_rules_page' ]
        );
    }

    /**
     * Shortcode Creator Page
     */
    public function render_creator_page() {
        // Save logic
        if ( isset($_POST['save_shortcode']) && check_admin_referer('wps_save_action') ) {
            $name = sanitize_title($_POST['shortcode_name']);
            $html = wp_kses_post($_POST['html_content']);
            
            if ($name && $html) {
                $shortcodes = get_option('wps_stored_shortcodes', []);
                $shortcodes[$name] = $html;
                update_option('wps_stored_shortcodes', $shortcodes);
                echo '<div class="updated"><p>Shortcode <code>[' . esc_html($name) . ']</code> is now active!</p></div>';
            }
        }

        // Delete logic
        if ( isset($_GET['delete']) ) {
            $shortcodes = get_option('wps_stored_shortcodes', []);
            $delete_name = sanitize_title($_GET['delete']);
            unset($shortcodes[$delete_name]);
            update_option('wps_stored_shortcodes', $shortcodes);
            echo '<div class="notice notice-info is-dismissible"><p>Shortcode removed.</p></div>';
        }

        $shortcodes = get_option('wps_stored_shortcodes', []);
        ?>
        <div class="wrap">
            <h1>Shortcode Creator</h1>
            <p class="description">Turn any HTML snippet into a shortcode you can use anywhere.</p>
            
            <form method="post" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-top: 20px;">
                <?php wp_nonce_field('wps_save_action'); ?>
                <table class="form-table">
                    <tr>
                        <th><label>Shortcode Name</label></th>
                        <td>
                            <input type="text" name="shortcode_name" placeholder="e.g. shipping-notice" class="regular-text" required>
                            <p class="description">This will be used as [name]</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>HTML Content</label></th>
                        <td>
                            <textarea name="html_content" rows="8" cols="50" class="large-text" required placeholder="<div class='my-style'>...</div>"></textarea>
                            <p class="description">You can use any valid HTML and CSS here.</p>
                        </td>
                    </tr>
                </table>
                <input type="submit" name="save_shortcode" class="button button-primary" value="Generate Shortcode">
            </form>

            <h2 style="margin-top:40px;">Your Custom Shortcodes</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="20%">Shortcode</th>
                        <th>HTML Content Preview</th>
                        <th width="10%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($shortcodes)): ?>
                        <tr><td colspan="3">No custom shortcodes found. Create your first one above!</td></tr>
                    <?php else: ?>
                        <?php foreach ($shortcodes as $name => $content): ?>
                            <tr>
                                <td><code>[<?php echo esc_html($name); ?>]</code></td>
                                <td><code><?php echo esc_html(wp_trim_words($content, 15)); ?></code></td>
                                <td>
                                    <a href="?page=woo-print-main&delete=<?php echo esc_attr($name); ?>" 
                                       class="button button-link-delete" 
                                       onclick="return confirm('Are you sure you want to delete this shortcode?')">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Display Rules Page
     */
    public function render_display_rules_page() {
        $this->rules_manager->render_display_rules_page();
    }

    /**
     * Register all custom shortcodes
     */
    public function register_custom_shortcodes() {
        $shortcodes = get_option('wps_stored_shortcodes', []);
        foreach ($shortcodes as $tag => $html) {
            add_shortcode($tag, function($atts = [], $content = null) use ($tag, $html) { // FIXED: Added $tag parameter
                $attributes = shortcode_atts([], $atts, $tag);
                // Check if we're on a single product page when shortcode is used manually
                if ( is_product() ) {
                    return $html;
                }
                return ''; // Return empty string if not on single product page
            });
        }
    }

    /**
     * Execute display rules on frontend
     */
    public function execute_display_rules() {
        if ( !is_product() ) return; // Only on single product pages
        
        $rules = get_option('wps_display_rules', []);
        
        foreach ($rules as $rule_id => $rule) {
            if ( empty($rule['shortcode']) || empty($rule['position']) ) continue;
            
            // Skip disabled rules
            if ( isset($rule['enabled']) && !$rule['enabled'] ) continue;
            
            // Check category condition
            $show = false;
            if ($rule['category'] === 'all') {
                $show = true;
            } else {
                $show = has_term($rule['category'], 'product_cat');
            }
            
            if ($show) {
                $display_handler = new WPS_Display_Positions();
                $display_handler->add_shortcode_to_position(
                    $rule['shortcode'],
                    $rule['position'],
                    $rule_id
                );
            }
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'woo-print') === false) return;
        
        wp_enqueue_script('wps-admin', plugins_url('assets/js/admin.js', __FILE__), ['jquery'], '1.0', true);
        wp_enqueue_style('wps-admin', plugins_url('assets/css/admin.css', __FILE__), [], '1.0');
    }

    /**
     * Enqueue frontend styles
     */
    public function enqueue_frontend_styles() {
        if ( is_product() ) {
            wp_enqueue_style( 
                'wps-frontend', 
                plugins_url( 'assets/css/frontend.css', __FILE__ ), 
                [], 
                '1.0' 
            );
        }
    }
}

new WooPrintShortcode();