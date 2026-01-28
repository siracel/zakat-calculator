<?php
/**
 * Plugin Name: Zakat Calculator for WooCommerce
 * Plugin URI: https://tebilisim.com
 * Description: Advanced Zakat calculator with WooCommerce payment integration. Calculate Zakat obligations and donate directly.
 * Version: 1.0.1
 * Author: TE Bilişim
 * Author URI: https://tebilisim.com
 * Text Domain: zakat-calculator-wc
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ZCWC_VERSION', '1.0.1');
define('ZCWC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ZCWC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ZCWC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Zakat_Calculator_WC {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->includes();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Load plugin textdomain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Check WooCommerce is active
        add_action('admin_init', array($this, 'check_woocommerce'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . ZCWC_PLUGIN_BASENAME, array($this, 'add_action_links'));
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Admin settings
        require_once ZCWC_PLUGIN_DIR . 'includes/class-admin-settings.php';
        
        // Calculator logic
        require_once ZCWC_PLUGIN_DIR . 'includes/class-calculator.php';
        
        // WooCommerce integration
        require_once ZCWC_PLUGIN_DIR . 'includes/class-woocommerce.php';
        
        // Shortcode handler
        require_once ZCWC_PLUGIN_DIR . 'includes/class-shortcode.php';
        
        // Initialize classes
        if (is_admin()) {
            ZCWC_Admin_Settings::get_instance();
        }
        
        ZCWC_Calculator::get_instance();
        ZCWC_WooCommerce::get_instance();
        ZCWC_Shortcode::get_instance();
    }
    
    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'zakat-calculator-wc',
            false,
            dirname(ZCWC_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Check if WooCommerce is active
     */
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            deactivate_plugins(ZCWC_PLUGIN_BASENAME);
        }
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php esc_html_e('Zakat Calculator for WooCommerce requires WooCommerce to be installed and active.', 'zakat-calculator-wc'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Add settings link to plugin actions
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=zakat-calculator-wc') . '">' . __('Settings', 'zakat-calculator-wc') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only load on pages with shortcode
        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'zakat_calculator')) {
            return;
        }
        
        // Vue.js 3 from CDN
        wp_enqueue_script(
            'vue',
            'https://unpkg.com/vue@3/dist/vue.global.prod.js',
            array(),
            '3.3.4',
            true
        );
        
        // Calculator JS
        wp_enqueue_script(
            'zcwc-calculator',
            ZCWC_PLUGIN_URL . 'assets/js/calculator.js',
            array('vue', 'jquery'),
            ZCWC_VERSION,
            true
        );
        
        // Calculator CSS
        wp_enqueue_style(
            'zcwc-calculator',
            ZCWC_PLUGIN_URL . 'assets/css/calculator.css',
            array(),
            ZCWC_VERSION
        );
        
        // Localize script
        wp_localize_script('zcwc-calculator', 'zcwcData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zcwc_nonce'),
            'currency' => get_woocommerce_currency(),
            'currencySymbol' => get_woocommerce_currency_symbol(),
            'settings' => $this->get_frontend_settings(),
            'translations' => $this->get_translations(),
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only on plugin settings page
        if ('settings_page_zakat-calculator-wc' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'zcwc-admin',
            ZCWC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ZCWC_VERSION
        );
        
        wp_enqueue_script(
            'zcwc-admin',
            ZCWC_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            ZCWC_VERSION,
            true
        );
    }
    
    /**
     * Get frontend settings
     */
    private function get_frontend_settings() {
        return array(
            'productId' => get_option('zcwc_product_id', 0),
            'goldNisabGrams' => get_option('zcwc_gold_nisab_grams', 87.48),
            'silverNisabGrams' => get_option('zcwc_silver_nisab_grams', 612.36),
        );
    }
    
    /**
     * Get translations for Vue app
     */
    private function get_translations() {
        return array(
            'calculator_title' => __('Zakat Calculator', 'zakat-calculator-wc'),
            'current_prices' => __('Current Prices', 'zakat-calculator-wc'),
            'prices_note' => __('Please enter current gold and silver prices per gram to calculate Nisab threshold', 'zakat-calculator-wc'),
            'gold_price_per_gram' => __('Gold Price (per gram)', 'zakat-calculator-wc'),
            'silver_price_per_gram' => __('Silver Price (per gram)', 'zakat-calculator-wc'),
            'nisab_method' => __('Nisab Calculation Method', 'zakat-calculator-wc'),
            'nisab_helper' => __('Silver method benefits more people', 'zakat-calculator-wc'),
            'recommended' => __('Recommended', 'zakat-calculator-wc'),
            'nisab_values' => __('NISAB VALUES', 'zakat-calculator-wc'),
            'based_on' => __('Based on Nisab value of', 'zakat-calculator-wc'),
            'gold' => __('Gold', 'zakat-calculator-wc'),
            'silver' => __('Silver', 'zakat-calculator-wc'),
            'your_assets' => __('Your Assets', 'zakat-calculator-wc'),
            'cash' => __('Cash (on hand & bank)', 'zakat-calculator-wc'),
            'gold_silver' => __('Gold & Silver', 'zakat-calculator-wc'),
            'gold_grams' => __('Gold (grams)', 'zakat-calculator-wc'),
            'enter_gold_price' => __('Please enter gold price above', 'zakat-calculator-wc'),
            'include_silver' => __('Include silver', 'zakat-calculator-wc'),
            'silver_grams' => __('Silver (grams)', 'zakat-calculator-wc'),
            'enter_silver_price' => __('Please enter silver price above', 'zakat-calculator-wc'),
            'investments' => __('Investments & Shares', 'zakat-calculator-wc'),
            'investment_value' => __('Investment value', 'zakat-calculator-wc'),
            'receivables' => __('Money Owed to You', 'zakat-calculator-wc'),
            'receivables_value' => __('Receivables (collectable)', 'zakat-calculator-wc'),
            'business_assets' => __('Business Assets (Stock)', 'zakat-calculator-wc'),
            'business_value' => __('Business assets value', 'zakat-calculator-wc'),
            'deductible_debts' => __('Deductible Debts', 'zakat-calculator-wc'),
            'immediate_debts' => __('Immediate debts (due now)', 'zakat-calculator-wc'),
            'summary' => __('Summary', 'zakat-calculator-wc'),
            'total_assets' => __('Total Assets', 'zakat-calculator-wc'),
            'total_debts' => __('Total Debts', 'zakat-calculator-wc'),
            'net_wealth' => __('Net Wealth', 'zakat-calculator-wc'),
            'nisab_threshold' => __('Nisab Threshold', 'zakat-calculator-wc'),
            'above_nisab' => __('Above Nisab Threshold', 'zakat-calculator-wc'),
            'below_nisab' => __('Below Nisab Threshold', 'zakat-calculator-wc'),
            'total_zakat_payable' => __('Total Zakat Payable', 'zakat-calculator-wc'),
            'held_for_year' => __('I have held this amount for at least one lunar year', 'zakat-calculator-wc'),
            'add_to_cart' => __('Add to Cart', 'zakat-calculator-wc'),
            'processing' => __('Processing...', 'zakat-calculator-wc'),
            'reset' => __('Reset', 'zakat-calculator-wc'),
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        add_option('zcwc_product_id', 0);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}

/**
 * Initialize the plugin
 */
function zcwc_init() {
    return Zakat_Calculator_WC::get_instance();
}

// Start the plugin
zcwc_init();
