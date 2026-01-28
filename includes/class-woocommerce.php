<?php
/**
 * WooCommerce Integration Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZCWC_WooCommerce {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX handler for adding to cart
        add_action('wp_ajax_zcwc_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_nopriv_zcwc_add_to_cart', array($this, 'ajax_add_to_cart'));
        
        // Set custom price in cart
        add_action('woocommerce_before_calculate_totals', array($this, 'set_custom_price'), 10, 1);
        
        // Display custom item meta in cart
        add_filter('woocommerce_get_item_data', array($this, 'display_custom_item_data'), 10, 2);
        
        // Save custom item meta to order
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_custom_order_item_meta'), 10, 4);
    }
    
    /**
     * AJAX handler to add Zakat to cart
     */
    public function ajax_add_to_cart() {
        // Verify nonce
        if (!check_ajax_referer('zcwc_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'zakat-calculator-wc')
            ));
        }
        
        // Get amount
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        
        if ($amount <= 0) {
            wp_send_json_error(array(
                'message' => __('Invalid Zakat amount.', 'zakat-calculator-wc')
            ));
        }
        
        // Get product ID from settings
        $product_id = get_option('zcwc_product_id', 0);
        
        if (empty($product_id)) {
            wp_send_json_error(array(
                'message' => __('Zakat product is not configured.', 'zakat-calculator-wc')
            ));
        }
        
        // Check if product exists
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_send_json_error(array(
                'message' => __('Zakat product not found.', 'zakat-calculator-wc')
            ));
        }
        
        // Clear cart (optional - remove if you want to keep other items)
        // WC()->cart->empty_cart();
        
        // Add to cart with custom data
        $cart_item_data = array(
            'zakat_amount' => $amount,
            'zakat_donation' => true,
            'unique_key' => md5(microtime() . rand()) // Prevent grouping
        );
        
        $cart_item_key = WC()->cart->add_to_cart(
            $product_id,
            1, // quantity
            0, // variation_id
            array(), // variation
            $cart_item_data
        );
        
        if ($cart_item_key) {
            wp_send_json_success(array(
                'message' => __('Zakat added to cart successfully.', 'zakat-calculator-wc'),
                'cart_url' => wc_get_cart_url()
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to add Zakat to cart.', 'zakat-calculator-wc')
            ));
        }
    }
    
    /**
     * Set custom price for Zakat items in cart
     */
    public function set_custom_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        // Loop through cart items
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            // Check if this is a Zakat donation
            if (isset($cart_item['zakat_donation']) && isset($cart_item['zakat_amount'])) {
                $zakat_amount = floatval($cart_item['zakat_amount']);
                
                // Set the custom price
                $cart_item['data']->set_price($zakat_amount);
            }
        }
    }
    
    /**
     * Display custom item data in cart
     */
    public function display_custom_item_data($item_data, $cart_item) {
        if (isset($cart_item['zakat_donation'])) {
            $item_data[] = array(
                'name' => __('Type', 'zakat-calculator-wc'),
                'value' => __('Zakat Donation', 'zakat-calculator-wc')
            );
        }
        
        return $item_data;
    }
    
    /**
     * Save custom order item meta
     */
    public function save_custom_order_item_meta($item, $cart_item_key, $values, $order) {
        if (isset($values['zakat_donation']) && isset($values['zakat_amount'])) {
            $item->add_meta_data(__('Donation Type', 'zakat-calculator-wc'), __('Zakat', 'zakat-calculator-wc'), true);
            $item->add_meta_data(__('Calculated Amount', 'zakat-calculator-wc'), wc_price($values['zakat_amount']), true);
        }
    }
}
