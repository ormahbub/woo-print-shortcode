<?php
/**
 * Display positions handler
 */

class WPS_Display_Positions {

    private $available_positions = [];

    public function __construct() {
        $this->available_positions = [
            'after_price' => [
                'label' => 'Directly After Price',
                'hook' => 'woocommerce_get_price_html',
                'priority' => 10,
                'callback' => 'display_after_price',
                'is_filter' => true
            ],
            'after_title' => [
                'label' => 'Directly After Title',
                'hook' => 'woocommerce_single_product_summary',
                'priority' => 6,
                'callback' => 'display_after_title',
                'is_filter' => false
            ],
            'after_rating' => [
                'label' => 'Directly After Rating',
                'hook' => 'woocommerce_single_product_summary',
                'priority' => 11,
                'callback' => 'display_after_rating',
                'is_filter' => false
            ],
            'before_add_to_cart' => [
                'label' => 'Before Add to Cart Form',
                'hook' => 'woocommerce_before_add_to_cart_form',
                'priority' => 10,
                'callback' => 'display_before_add_to_cart',
                'is_filter' => false
            ],
            'after_add_to_cart' => [
                'label' => 'After Add to Cart Form',
                'hook' => 'woocommerce_after_add_to_cart_form',
                'priority' => 10,
                'callback' => 'display_after_add_to_cart',
                'is_filter' => false
            ],
            'after_meta' => [
                'label' => 'After Product Meta',
                'hook' => 'woocommerce_product_meta_end',
                'priority' => 10,
                'callback' => 'display_after_meta',
                'is_filter' => false
            ],
            'after_description' => [
                'label' => 'After Description',
                'hook' => 'woocommerce_after_single_product_summary',
                'priority' => 5,
                'callback' => 'display_after_description',
                'is_filter' => false
            ],
            'before_product_summary' => [
                'label' => 'Before Product Summary',
                'hook' => 'woocommerce_before_single_product_summary',
                'priority' => 30,
                'callback' => 'display_before_product_summary',
                'is_filter' => false
            ],
            'after_product_summary' => [
                'label' => 'After Product Summary',
                'hook' => 'woocommerce_after_single_product_summary',
                'priority' => 15,
                'callback' => 'display_after_product_summary',
                'is_filter' => false
            ],
            'inside_short_description' => [
                'label' => 'Inside Short Description',
                'hook' => 'woocommerce_short_description',
                'priority' => 20,
                'callback' => 'display_inside_short_description',
                'is_filter' => false
            ]
        ];
    }

    /**
     * Get all available positions
     */
    public function get_positions() {
        return $this->available_positions;
    }

    /**
     * Add shortcode to specific position
     */
    public function add_shortcode_to_position($shortcode, $position_key, $rule_id) {
        if (!isset($this->available_positions[$position_key])) return;
        
        $position = $this->available_positions[$position_key];
        
        if ($position_key === 'after_price') {
            // Special handling for price filter - WITH SINGLE PRODUCT CHECK
            add_filter($position['hook'], function($price) use ($shortcode, $rule_id, $position_key) {
                if (is_product() && is_single()) { // Double check for single product
                    $content = do_shortcode($shortcode);
                    if (!empty($content)) {
                        $price .= '<div class="wps-display wps-position-' . esc_attr($position_key) . ' wps-rule-' . esc_attr($rule_id) . '" style="margin-top: 10px;">' . $content . '</div>';
                    }
                }
                return $price;
            }, $position['priority'], 1);
        } else {
            // Regular action hooks - WITH SINGLE PRODUCT CHECK
            add_action($position['hook'], function() use ($shortcode, $rule_id, $position_key) {
                if (is_product() && is_single()) { // Double check for single product
                    $content = do_shortcode($shortcode);
                    if (!empty($content)) {
                        echo '<div class="wps-display wps-position-' . esc_attr($position_key) . ' wps-rule-' . esc_attr($rule_id) . '">' . $content . '</div>';
                    }
                }
            }, $position['priority']);
        }
    }

    /**
     * Individual display methods for reference
     */
    
    // After price (using filter)
    public static function display_after_price($price, $shortcode) {
        if (is_product() && is_single()) {
            $content = do_shortcode($shortcode);
            if (!empty($content)) {
                $price .= '<div class="wps-after-price">' . $content . '</div>';
            }
        }
        return $price;
    }
    
    // After title (using action)
    public static function display_after_title($shortcode) {
        if (is_product() && is_single()) {
            $content = do_shortcode($shortcode);
            if (!empty($content)) {
                echo '<div class="wps-after-title">' . $content . '</div>';
            }
        }
    }
    
    // After rating (using action)
    public static function display_after_rating($shortcode) {
        if (is_product() && is_single()) {
            $content = do_shortcode($shortcode);
            if (!empty($content)) {
                echo '<div class="wps-after-rating">' . $content . '</div>';
            }
        }
    }
    
    // Before add to cart
    public static function display_before_add_to_cart($shortcode) {
        if (is_product() && is_single()) {
            $content = do_shortcode($shortcode);
            if (!empty($content)) {
                echo '<div class="wps-before-add-to-cart">' . $content . '</div>';
            }
        }
    }
    
    // After add to cart
    public static function display_after_add_to_cart($shortcode) {
        if (is_product() && is_single()) {
            $content = do_shortcode($shortcode);
            if (!empty($content)) {
                echo '<div class="wps-after-add-to-cart">' . $content . '</div>';
            }
        }
    }
    
    // After meta
    public static function display_after_meta($shortcode) {
        if (is_product() && is_single()) {
            $content = do_shortcode($shortcode);
            if (!empty($content)) {
                echo '<div class="wps-after-meta">' . $content . '</div>';
            }
        }
    }
    
    // After description
    public static function display_after_description($shortcode) {
        if (is_product() && is_single()) {
            $content = do_shortcode($shortcode);
            if (!empty($content)) {
                echo '<div class="wps-after-description">' . $content . '</div>';
            }
        }
    }
    
    // Before product summary
    public static function display_before_product_summary($shortcode) {
        if (is_product() && is_single()) {
            $content = do_shortcode($shortcode);
            if (!empty($content)) {
                echo '<div class="wps-before-summary">' . $content . '</div>';
            }
        }
    }
    
    // After product summary
    public static function display_after_product_summary($shortcode) {
        if (is_product() && is_single()) {
            $content = do_shortcode($shortcode);
            if (!empty($content)) {
                echo '<div class="wps-after-summary">' . $content . '</div>';
            }
        }
    }
    
    // Inside short description
    public static function display_inside_short_description($shortcode) {
        if (is_product() && is_single()) {
            $content = do_shortcode($shortcode);
            if (!empty($content)) {
                echo '<div class="wps-inside-short-description">' . $content . '</div>';
            }
        }
    }
}