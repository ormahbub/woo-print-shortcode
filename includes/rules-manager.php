<?php
/**
 * Rules manager for handling multiple display rules
 */

class WPS_Rules_Manager {

    private $display_positions;

    public function __construct() {
        $this->display_positions = new WPS_Display_Positions();
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('wps_display_group', 'wps_display_rules', [$this, 'sanitize_rules']);
    }

    /**
     * Sanitize rules
     */
    public function sanitize_rules($input) {
        $sanitized = [];
        
        if (!is_array($input)) return [];
        
        foreach ($input as $key => $rule) {
            $sanitized[$key] = [
                'shortcode' => sanitize_text_field($rule['shortcode']),
                'category' => sanitize_text_field($rule['category']),
                'position' => sanitize_text_field($rule['position']),
                'enabled' => isset($rule['enabled']) ? 1 : 0
            ];
        }
        
        return $sanitized;
    }

    /**
     * Render display rules page
     */
    public function render_display_rules_page() {
        // Handle actions
        $this->handle_actions();
        
        $rules = get_option('wps_display_rules', []);
        $shortcodes = get_option('wps_stored_shortcodes', []);
        $positions = $this->display_positions->get_positions();
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'name'
        ]);
        ?>
        <div class="wrap">
            <h1>Display Rules</h1>
            <p class="description">Set up multiple rules to display shortcodes in specific positions on product pages.</p>
            
            <form method="post" action="options.php" id="wps-rules-form">
                <?php settings_fields('wps_display_group'); ?>
                
                <div id="wps-rules-container" style="margin-top: 20px;">
                    <?php if (empty($rules)): ?>
                        <?php $this->render_rule_template(0, $shortcodes, $positions, $categories); ?>
                    <?php else: ?>
                        <?php foreach ($rules as $index => $rule): ?>
                            <?php $this->render_rule_template($index, $shortcodes, $positions, $categories, $rule); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div style="margin: 20px 0;">
                    <button type="button" id="wps-add-rule" class="button button-secondary">
                        <span class="dashicons dashicons-plus"></span> Add New Rule
                    </button>
                </div>
                
                <?php submit_button('Save All Rules'); ?>
            </form>
        </div>
        
        <script type="text/html" id="wps-rule-template">
            <?php $this->render_rule_template('__INDEX__', $shortcodes, $positions, $categories); ?>
        </script>
        <?php
    }

    /**
     * Render individual rule template
     */
    private function render_rule_template($index, $shortcodes, $positions, $categories, $rule = null) {
        $rule_id = is_numeric($index) ? $index : '__INDEX__';
        $is_template = $rule_id === '__INDEX__';
        
        $shortcode_val = $is_template ? '' : (isset($rule['shortcode']) ? $rule['shortcode'] : '');
        $category_val = $is_template ? '' : (isset($rule['category']) ? $rule['category'] : 'all');
        $position_val = $is_template ? '' : (isset($rule['position']) ? $rule['position'] : '');
        $enabled_val = $is_template ? 'checked' : (isset($rule['enabled']) && $rule['enabled'] ? 'checked' : '');
        ?>
        <div class="wps-rule" data-index="<?php echo esc_attr($rule_id); ?>" 
             style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px; border-radius: 4px;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0;">Rule #<span class="wps-rule-number"><?php echo ($is_template ? '__INDEX__' : ($index + 1)); ?></span></h3>
                <?php if (!$is_template): ?>
                    <button type="button" class="wps-remove-rule button button-link-delete" style="color: #dc3232;">
                        <span class="dashicons dashicons-trash"></span> Remove Rule
                    </button>
                <?php endif; ?>
            </div>
            
            <table class="form-table">
                <tr>
                    <th width="20%">
                        <label>Shortcode</label>
                    </th>
                    <td>
                        <select name="wps_display_rules[<?php echo esc_attr($rule_id); ?>][shortcode]" class="regular-text" required>
                            <option value="">-- Select Shortcode --</option>
                            <?php foreach ($shortcodes as $name => $content): ?>
                                <option value="[<?php echo esc_attr($name); ?>]" <?php selected($shortcode_val, '[' . $name . ']'); ?>>
                                    [<?php echo esc_html($name); ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($shortcodes)): ?>
                            <p class="description" style="color: #d63638;">
                                No shortcodes found. Please create shortcodes first in the "Shortcode Creator" tab.
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th>
                        <label>Target Category</label>
                    </th>
                    <td>
                        <select name="wps_display_rules[<?php echo esc_attr($rule_id); ?>][category]" class="regular-text">
                            <option value="all" <?php selected($category_val, 'all'); ?>>-- All Categories --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($category_val, $category->slug); ?>>
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Leave as "All Categories" to display on all products</p>
                    </td>
                </tr>
                
                <tr>
                    <th>
                        <label>Display Position</label>
                    </th>
                    <td>
                        <select name="wps_display_rules[<?php echo esc_attr($rule_id); ?>][position]" class="regular-text" required>
                            <option value="">-- Select Position --</option>
                            <?php foreach ($positions as $key => $position): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($position_val, $key); ?>>
                                    <?php echo esc_html($position['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Choose where the shortcode should appear</p>
                    </td>
                </tr>
                
                <tr>
                    <th>
                        <label>Status</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wps_display_rules[<?php echo esc_attr($rule_id); ?>][enabled]" value="1" <?php echo $enabled_val; ?>>
                            Enable this rule
                        </label>
                    </td>
                </tr>
            </table>
            
            <input type="hidden" name="wps_display_rules[<?php echo esc_attr($rule_id); ?>][id]" value="<?php echo esc_attr($rule_id); ?>">
        </div>
        <?php
    }

    /**
     * Handle form actions
     */
    private function handle_actions() {
        if (isset($_POST['wps_display_rules'])) {
            // Rules are saved via settings API
            return;
        }
        
        // Handle AJAX actions if needed
        if (isset($_GET['action']) && $_GET['action'] === 'wps_delete_rule' && isset($_GET['rule_id'])) {
            if (!current_user_can('manage_options')) return;
            
            $rules = get_option('wps_display_rules', []);
            $rule_id = sanitize_key($_GET['rule_id']);
            
            if (isset($rules[$rule_id])) {
                unset($rules[$rule_id]);
                update_option('wps_display_rules', $rules);
                
                wp_redirect(admin_url('admin.php?page=woo-print-display&deleted=1'));
                exit;
            }
        }
    }
}