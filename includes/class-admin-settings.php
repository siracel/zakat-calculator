<?php
/**
 * Admin Settings Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZCWC_Admin_Settings {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Add admin menu
     */
    public function add_menu() {
        add_options_page(
            __('Zakat Calculator Settings', 'zakat-calculator-wc'),
            __('Zakat Calculator', 'zakat-calculator-wc'),
            'manage_options',
            'zakat-calculator-wc',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings
        register_setting('zcwc_settings_group', 'zcwc_product_id', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0
        ));
        
        register_setting('zcwc_settings_group', 'zcwc_gold_nisab_grams', array(
            'type' => 'number',
            'sanitize_callback' => array($this, 'sanitize_price'),
            'default' => 87.48
        ));
        
        register_setting('zcwc_settings_group', 'zcwc_silver_nisab_grams', array(
            'type' => 'number',
            'sanitize_callback' => array($this, 'sanitize_price'),
            'default' => 612.36
        ));
        
        // Add settings sections
        add_settings_section(
            'zcwc_general_section',
            __('General Settings', 'zakat-calculator-wc'),
            array($this, 'general_section_callback'),
            'zakat-calculator-wc'
        );
        
        add_settings_section(
            'zcwc_nisab_section',
            __('Nisab Thresholds', 'zakat-calculator-wc'),
            array($this, 'nisab_section_callback'),
            'zakat-calculator-wc'
        );
        
        // Add settings fields
        add_settings_field(
            'zcwc_product_id',
            __('WooCommerce Product ID', 'zakat-calculator-wc'),
            array($this, 'product_id_field'),
            'zakat-calculator-wc',
            'zcwc_general_section'
        );
        
        add_settings_field(
            'zcwc_gold_nisab_grams',
            __('Gold Nisab (grams)', 'zakat-calculator-wc'),
            array($this, 'gold_nisab_field'),
            'zakat-calculator-wc',
            'zcwc_nisab_section'
        );
        
        add_settings_field(
            'zcwc_silver_nisab_grams',
            __('Silver Nisab (grams)', 'zakat-calculator-wc'),
            array($this, 'silver_nisab_field'),
            'zakat-calculator-wc',
            'zcwc_nisab_section'
        );
    }
    
    /**
     * Section callbacks
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__('Configure the WooCommerce product for Zakat donations. Users will enter current prices directly in the calculator.', 'zakat-calculator-wc') . '</p>';
    }
    
    public function nisab_section_callback() {
        echo '<p>' . esc_html__('Standard nisab thresholds based on Islamic law. Gold: 87.48g (7.5 tola), Silver: 612.36g (52.5 tola). You can adjust these if needed.', 'zakat-calculator-wc') . '</p>';
    }
    
    /**
     * Field callbacks
     */
    public function product_id_field() {
        $value = get_option('zcwc_product_id', 0);
        ?>
        <input type="number" 
               name="zcwc_product_id" 
               id="zcwc_product_id" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" 
               min="0" 
               step="1">
        <p class="description">
            <?php esc_html_e('Enter the WooCommerce Product ID for Zakat donations. This product should have a price of 0 (zero).', 'zakat-calculator-wc'); ?>
            <br>
            <?php 
            if ($value > 0 && function_exists('wc_get_product')) {
                $product = wc_get_product($value);
                if ($product) {
                    echo sprintf(
                        /* translators: %1$s: product name, %2$s: edit link */
                        esc_html__('Current product: %1$s - %2$s', 'zakat-calculator-wc'),
                        '<strong>' . esc_html($product->get_name()) . '</strong>',
                        '<a href="' . esc_url(get_edit_post_link($value)) . '" target="_blank">' . esc_html__('Edit Product', 'zakat-calculator-wc') . '</a>'
                    );
                } else {
                    echo '<span style="color: #dc3232;">' . esc_html__('⚠ Product not found!', 'zakat-calculator-wc') . '</span>';
                }
            }
            ?>
        </p>
        <?php
    }
    
    public function gold_nisab_field() {
        $value = get_option('zcwc_gold_nisab_grams', 87.48);
        ?>
        <input type="number" 
               name="zcwc_gold_nisab_grams" 
               id="zcwc_gold_nisab_grams" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" 
               min="0" 
               step="0.01">
        <span class="description">grams</span>
        <p class="description">
            <?php esc_html_e('Standard: 87.48 grams (7.5 tola). This value is used to calculate the Nisab threshold when Gold method is selected.', 'zakat-calculator-wc'); ?>
        </p>
        <?php
    }
    
    public function silver_nisab_field() {
        $value = get_option('zcwc_silver_nisab_grams', 612.36);
        ?>
        <input type="number" 
               name="zcwc_silver_nisab_grams" 
               id="zcwc_silver_nisab_grams" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" 
               min="0" 
               step="0.01">
        <span class="description">grams</span>
        <p class="description">
            <?php esc_html_e('Standard: 612.36 grams (52.5 tola). This value is used to calculate the Nisab threshold when Silver method is selected.', 'zakat-calculator-wc'); ?>
        </p>
        <?php
    }
    
    /**
     * Sanitize callbacks
     */
    public function sanitize_price($value) {
        return floatval($value);
    }
    
    public function sanitize_nisab_method($value) {
        $valid = array('gold', 'silver');
        return in_array($value, $valid) ? $value : 'silver';
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        $screen = get_current_screen();
        if ($screen->id !== 'settings_page_zakat-calculator-wc') {
            return;
        }
        
        $product_id = get_option('zcwc_product_id', 0);
        
        if (empty($product_id)) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_html_e('Zakat Calculator Setup Required', 'zakat-calculator-wc'); ?></strong><br>
                    <?php esc_html_e('Please set the WooCommerce Product ID for Zakat donations.', 'zakat-calculator-wc'); ?>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Settings page HTML
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if settings were saved
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'zcwc_messages',
                'zcwc_message',
                __('Settings Saved', 'zakat-calculator-wc'),
                'updated'
            );
        }
        
        settings_errors('zcwc_messages');
        ?>
        <div class="wrap zcwc-admin-wrapper">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="zcwc-settings-header" style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #2ecc71;">
                <h2 style="margin-top: 0;"><?php esc_html_e('Quick Setup Guide', 'zakat-calculator-wc'); ?></h2>
                <ol style="line-height: 1.8;">
                    <li><?php esc_html_e('Create a WooCommerce product for Zakat donations (set price to 0)', 'zakat-calculator-wc'); ?></li>
                    <li><?php esc_html_e('Enter the Product ID below', 'zakat-calculator-wc'); ?></li>
                    <li><?php esc_html_e('Add shortcode to any page:', 'zakat-calculator-wc'); ?> <code>[zakat_calculator]</code></li>
                    <li><?php esc_html_e('Users will enter current gold/silver prices directly in the calculator', 'zakat-calculator-wc'); ?></li>
                </ol>
            </div>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('zcwc_settings_group');
                do_settings_sections('zakat-calculator-wc');
                submit_button(__('Save Settings', 'zakat-calculator-wc'));
                ?>
            </form>
            
            <div class="zcwc-info-box" style="background: #f0f0f1; padding: 15px; margin-top: 20px;">
                <h3><?php esc_html_e('Need Help?', 'zakat-calculator-wc'); ?></h3>
                <p><?php esc_html_e('For support and documentation, visit:', 'zakat-calculator-wc'); ?> 
                    <a href="https://tebilisim.com" target="_blank">tebilisim.com</a>
                </p>
            </div>
        </div>
        <?php
    }
}
