<?php
/**
 * Plugin Name: Woo Print Shortcode
 * Description: Create custom HTML shortcodes and display them on WooCommerce product pages based on categories.
 * Version: 2.0
 * Author: Gemini AI
 * Text Domain: woo-print-shortcode
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WooPrintShortcode {

    public function __construct() {
        // Initialization
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_init', [ $this, 'settings_init' ] );
        add_action( 'init', [ $this, 'register_custom_shortcodes' ] );
        add_action( 'wp', [ $this, 'execute_display_rules' ] );
    }

    /**
     * 1. MENU REGISTRATION
     */
    public function register_menus() {
        // Main Parent Menu
        add_menu_page(
            'Woo Print Shortcode',
            'Woo Print',
            'manage_options',
            'woo-print-main',
            [ $this, 'render_creator_page' ], // Default to creator
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
     * 2. SETTINGS REGISTRATION (For Display Rules)
     */
    public function settings_init() {
        register_setting( 'wps_display_group', 'wds_settings' );

        add_settings_section( 'wds_section', 'Placement Settings', null, 'woo-print-display' );
        add_settings_field( 'wds_shortcode', 'Shortcode to Display', [ $this, 'render_shortcode_field' ], 'woo-print-display', 'wds_section' );
        add_settings_field( 'wds_category', 'Target Category', [ $this, 'render_category_field' ], 'woo-print-display', 'wds_section' );
        add_settings_field( 'wds_position', 'Hook Position', [ $this, 'render_position_field' ], 'woo-print-display', 'wds_section' );
    }

    /**
     * 3. SHORTCODE CREATOR LOGIC (HTML to Shortcode)
     */
    public function render_creator_page() {
        // Save logic
        if ( isset($_POST['save_shortcode']) && check_admin_referer('wps_save_action') ) {
            $name = sanitize_title($_POST['shortcode_name']);
            $html = wp_kses_post($_POST['html_content']);
            
            if ($name && $html) {
                $shortcodes = get_option('h2s_stored_shortcodes', []);
                $shortcodes[$name] = $html;
                update_option('h2s_stored_shortcodes', $shortcodes);
                echo '<div class="updated"><p>Shortcode <code>[' . $name . ']</code> is now active!</p></div>';
            }
        }

        // Delete logic
        if ( isset($_GET['delete']) ) {
            $shortcodes = get_option('h2s_stored_shortcodes', []);
            unset($shortcodes[$_GET['delete']]);
            update_option('h2s_stored_shortcodes', $shortcodes);
            echo '<div class="notice notice-info is-dismissible"><p>Shortcode removed.</p></div>';
        }

        $shortcodes = get_option('h2s_stored_shortcodes', []);
        ?>
        <div class="wrap">
            <h1>Shortcode Creator</h1>
            <p class="description">Turn any HTML snippet into a shortcode you can use anywhere.</p>
            
            <form method="post" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-top: 20px;">
                <?php wp_nonce_field('wps_save_action'); ?>
                <table class="form-table">
                    <tr>
                        <th><label>Shortcode Name</label></th>
                        <td><input type="text" name="shortcode_name" placeholder="e.g. shipping-notice" class="regular-text" required>
                        <p class="description">This will be used as [name]</p></td>
                    </tr>
                    <tr>
                        <th><label>HTML Content</label></th>
                        <td><textarea name="html_content" rows="8" cols="50" class="large-text" required placeholder="<div class='my-style'>...</div>"></textarea></td>
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
                        <tr><td colspan="3">No custom shortcodes found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($shortcodes as $name => $content): ?>
                            <tr>
                                <td><code>[<?php echo $name; ?>]</code></td>
                                <td><code><?php echo esc_html(wp_trim_words($content, 15)); ?></code></td>
                                <td><a href="?page=woo-print-main&delete=<?php echo $name; ?>" class="button button-link-delete">Delete</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * 4. DISPLAY RULES LOGIC (Woo Display)
     */
    public function render_display_rules_page() {
        ?>
        <div class="wrap">
            <h1>Display Rules</h1>
            <p class="description">Choose which shortcode to display automatically on product pages.</p>
            <form action='options.php' method='post' style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-top: 20px;">
                <?php
                settings_fields( 'wps_display_group' );
                do_settings_sections( 'woo-print-display' );
                submit_button('Save Placement Rules');
                ?>
            </form>
        </div>
        <?php
    }

    // Field Renders
    public function render_shortcode_field() {
        $options = get_option( 'wds_settings' );
        $val = isset($options['wds_shortcode']) ? $options['wds_shortcode'] : '';
        echo "<input type='text' name='wds_settings[wds_shortcode]' value='" . esc_attr($val) . "' class='regular-text' placeholder='[your-shortcode]'>";
    }

    public function render_category_field() {
        $options = get_option( 'wds_settings' );
        $selected_cat = isset($options['wds_category']) ? $options['wds_category'] : '';
        $categories = get_terms( ['taxonomy' => 'product_cat', 'hide_empty' => false] );

        echo "<select name='wds_settings[wds_category]'>";
        echo '<option value="all">-- All Categories --</option>';
        foreach ( $categories as $category ) {
            $selected = ($selected_cat == $category->slug) ? 'selected' : '';
            echo "<option value='{$category->slug}' $selected>{$category->name}</option>";
        }
        echo "</select>";
    }

    public function render_position_field() {
        $options = get_option( 'wds_settings' );
        $val = isset($options['wds_position']) ? $options['wds_position'] : '';
        $hooks = [
            'woocommerce_single_product_summary'       => 'Summary (After Title/Price)',
            'woocommerce_before_add_to_cart_form'      => 'Before Add to Cart',
            'woocommerce_after_add_to_cart_form'       => 'After Add to Cart',
            'woocommerce_product_meta_end'             => 'After Product Meta (SKU)',
            'woocommerce_after_single_product_summary' => 'After Tabs/Description',
        ];
        echo "<select name='wds_settings[wds_position]'>";
        foreach ($hooks as $hook => $label) {
            $selected = ($val == $hook) ? 'selected' : '';
            echo "<option value='$hook' $selected>$label</option>";
        }
        echo "</select>";
    }

    /**
     * 5. FRONT-END EXECUTION
     */
    public function register_custom_shortcodes() {
        $shortcodes = get_option('h2s_stored_shortcodes', []);
        foreach ($shortcodes as $tag => $html) {
            add_shortcode($tag, function() use ($html) {
                return $html;
            });
        }
    }

    public function execute_display_rules() {
        if ( is_product() ) {
            $options   = get_option( 'wds_settings' );
            $shortcode = isset($options['wds_shortcode']) ? $options['wds_shortcode'] : '';
            $category  = isset($options['wds_category']) ? $options['wds_category'] : 'all';
            $position  = isset($options['wds_position']) ? $options['wds_position'] : 'woocommerce_single_product_summary';

            if ( empty( $shortcode ) ) return;

            if ( $category === 'all' || has_term( $category, 'product_cat' ) ) {
                add_action( $position, function() use ($shortcode) {
                    echo '<div class="wps-injected-content" style="margin: 15px 0;">' . do_shortcode($shortcode) . '</div>';
                }, 25 );
            }
        }
    }
}

new WooPrintShortcode();