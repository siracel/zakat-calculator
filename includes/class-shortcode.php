<?php
/**
 * Shortcode Handler Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZCWC_Shortcode {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode('zakat_calculator', array($this, 'render_calculator'));
    }
    
    /**
     * Render calculator shortcode
     */
    public function render_calculator($atts) {
        // Check if required settings are configured
        $product_id = get_option('zcwc_product_id', 0);
        
        if (empty($product_id)) {
            if (current_user_can('manage_options')) {
                return $this->render_admin_warning();
            }
            return '<p>' . esc_html__('Zakat Calculator is not configured yet. Please contact the administrator.', 'zakat-calculator-wc') . '</p>';
        }
        
        // Parse attributes
        $atts = shortcode_atts(array(
            'theme' => 'default',
        ), $atts, 'zakat_calculator');
        
        ob_start();
        $this->render_calculator_template($atts);
        return ob_get_clean();
    }
    
    /**
     * Render calculator template
     */
    private function render_calculator_template($atts) {
        ?>
        <div id="zcwc-calculator-app" class="zcwc-theme-<?php echo esc_attr($atts['theme']); ?>">
            
            <!-- Header -->
            <div class="zcwc-header">
                <h2>{{ translations.calculator_title }}</h2>
            </div>
            
            <!-- Current Prices Input Section -->
            <div class="zcwc-prices-input">
                <div class="zcwc-prices-header">
                    <h3>{{ translations.current_prices }}</h3>
                    <div class="zcwc-info-banner">
                        <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                        </svg>
                        <span>{{ translations.prices_note }}</span>
                    </div>
                </div>
                <div class="zcwc-prices-grid">
                    <div class="zcwc-price-input-group">
                        <label>{{ translations.gold_price_per_gram }}</label>
                        <div class="zcwc-input-wrapper">
                            <span class="zcwc-input-prefix">{{ currencySymbol }}</span>
                            <input type="number" 
                                   v-model.number="prices.goldPrice" 
                                   min="0" 
                                   step="0.01" 
                                   placeholder="0.00"
                                   class="zcwc-price-input">
                            <span class="zcwc-input-suffix">/gram</span>
                        </div>
                    </div>
                    
                    <div class="zcwc-price-input-group">
                        <label>{{ translations.silver_price_per_gram }}</label>
                        <div class="zcwc-input-wrapper">
                            <span class="zcwc-input-prefix">{{ currencySymbol }}</span>
                            <input type="number" 
                                   v-model.number="prices.silverPrice" 
                                   min="0" 
                                   step="0.01" 
                                   placeholder="0.00"
                                   class="zcwc-price-input">
                            <span class="zcwc-input-suffix">/gram</span>
                        </div>
                    </div>
                    
                    <div class="zcwc-price-input-group">
                        <label>{{ translations.nisab_method }}</label>
                        <select v-model="nisabMethod" class="zcwc-select">
                            <option value="silver">{{ translations.silver }} ({{ translations.recommended }})</option>
                            <option value="gold">{{ translations.gold }}</option>
                        </select>
                        <span class="zcwc-helper-text">{{ translations.nisab_helper }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Nisab Information -->
            <div class="zcwc-nisab-info" v-if="prices.goldPrice > 0 || prices.silverPrice > 0">
                <h3>{{ translations.nisab_values }}</h3>
                <div class="zcwc-nisab-display">
                    <div class="zcwc-nisab-item" :class="{'active': nisabMethod === 'silver'}" v-if="prices.silverPrice > 0">
                        <span class="zcwc-label">{{ translations.silver }}:</span>
                        <span class="zcwc-value">{{ settings.silverNisabGrams }}g = {{ formatCurrency(settings.silverNisabGrams * prices.silverPrice) }}</span>
                    </div>
                    <div class="zcwc-nisab-item" :class="{'active': nisabMethod === 'gold'}" v-if="prices.goldPrice > 0">
                        <span class="zcwc-label">{{ translations.gold }}:</span>
                        <span class="zcwc-value">{{ settings.goldNisabGrams }}g = {{ formatCurrency(settings.goldNisabGrams * prices.goldPrice) }}</span>
                    </div>
                </div>
                <p class="zcwc-nisab-note">
                    {{ translations.based_on }} <strong>{{ nisabMethod === 'gold' ? translations.gold : translations.silver }}</strong>
                </p>
            </div>
            
            <!-- Main Calculator Grid -->
            <div class="zcwc-calculator-grid">
                
                <!-- Left Column - Assets Form -->
                <div class="zcwc-form-column">
                    
                    <div class="zcwc-section">
                        <h3>{{ translations.your_assets }}</h3>
                        
                        <!-- Cash -->
                        <div class="zcwc-field-group">
                            <label>{{ translations.cash }}</label>
                            <input type="number" 
                                   v-model.number="assets.cash" 
                                   min="0" 
                                   step="0.01" 
                                   :placeholder="currencySymbol + ' 0.00'">
                        </div>
                        
                        <!-- Gold & Silver -->
                        <div class="zcwc-toggle-section">
                            <div class="zcwc-toggle-header">
                                <label class="zcwc-toggle-label">
                                    <input type="checkbox" v-model="assets.goldIncluded">
                                    <span>{{ translations.gold_silver }}</span>
                                </label>
                            </div>
                            
                            <div class="zcwc-toggle-content" v-show="assets.goldIncluded">
                                <div class="zcwc-field-group">
                                    <label>{{ translations.gold_grams }}</label>
                                    <input type="number" 
                                           v-model.number="assets.goldGrams" 
                                           min="0" 
                                           step="0.01" 
                                           placeholder="0.00">
                                    <span class="zcwc-field-note" v-if="prices.goldPrice > 0">= {{ formatCurrency(calculatedGoldValue) }}</span>
                                    <span class="zcwc-field-note zcwc-warning" v-else>⚠ {{ translations.enter_gold_price }}</span>
                                </div>
                                
                                <label class="zcwc-sub-toggle">
                                    <input type="checkbox" v-model="assets.silverIncluded">
                                    <span>{{ translations.include_silver }}</span>
                                </label>
                                
                                <div class="zcwc-field-group" v-show="assets.silverIncluded">
                                    <label>{{ translations.silver_grams }}</label>
                                    <input type="number" 
                                           v-model.number="assets.silverGrams" 
                                           min="0" 
                                           step="0.01" 
                                           placeholder="0.00">
                                    <span class="zcwc-field-note" v-if="prices.silverPrice > 0">= {{ formatCurrency(calculatedSilverValue) }}</span>
                                    <span class="zcwc-field-note zcwc-warning" v-else>⚠ {{ translations.enter_silver_price }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Investments -->
                        <div class="zcwc-toggle-section">
                            <div class="zcwc-toggle-header">
                                <label class="zcwc-toggle-label">
                                    <input type="checkbox" v-model="assets.investmentsIncluded">
                                    <span>{{ translations.investments }}</span>
                                </label>
                            </div>
                            
                            <div class="zcwc-toggle-content" v-show="assets.investmentsIncluded">
                                <div class="zcwc-field-group">
                                    <label>{{ translations.investment_value }}</label>
                                    <input type="number" 
                                           v-model.number="assets.investmentValue" 
                                           min="0" 
                                           step="0.01" 
                                           :placeholder="currencySymbol + ' 0.00'">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Receivables -->
                        <div class="zcwc-toggle-section">
                            <div class="zcwc-toggle-header">
                                <label class="zcwc-toggle-label">
                                    <input type="checkbox" v-model="assets.receivablesIncluded">
                                    <span>{{ translations.receivables }}</span>
                                </label>
                            </div>
                            
                            <div class="zcwc-toggle-content" v-show="assets.receivablesIncluded">
                                <div class="zcwc-field-group">
                                    <label>{{ translations.receivables_value }}</label>
                                    <input type="number" 
                                           v-model.number="assets.receivablesValue" 
                                           min="0" 
                                           step="0.01" 
                                           :placeholder="currencySymbol + ' 0.00'">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Business Assets -->
                        <div class="zcwc-toggle-section">
                            <div class="zcwc-toggle-header">
                                <label class="zcwc-toggle-label">
                                    <input type="checkbox" v-model="assets.businessIncluded">
                                    <span>{{ translations.business_assets }}</span>
                                </label>
                            </div>
                            
                            <div class="zcwc-toggle-content" v-show="assets.businessIncluded">
                                <div class="zcwc-field-group">
                                    <label>{{ translations.business_value }}</label>
                                    <input type="number" 
                                           v-model.number="assets.businessValue" 
                                           min="0" 
                                           step="0.01" 
                                           :placeholder="currencySymbol + ' 0.00'">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Deductible Debts -->
                    <div class="zcwc-section zcwc-debts-section">
                        <h3>{{ translations.deductible_debts }}</h3>
                        
                        <div class="zcwc-field-group">
                            <label>{{ translations.immediate_debts }}</label>
                            <input type="number" 
                                   v-model.number="debts.immediate" 
                                   min="0" 
                                   step="0.01" 
                                   :placeholder="currencySymbol + ' 0.00'">
                        </div>
                    </div>
                    
                </div>
                
                <!-- Right Column - Summary -->
                <div class="zcwc-summary-column">
                    <div class="zcwc-summary-box">
                        <h3>{{ translations.summary }}</h3>
                        
                        <div class="zcwc-summary-row">
                            <span>{{ translations.total_assets }}</span>
                            <strong>{{ formatCurrency(totalAssets) }}</strong>
                        </div>
                        
                        <div class="zcwc-summary-row zcwc-negative">
                            <span>{{ translations.total_debts }}</span>
                            <strong>-{{ formatCurrency(totalDebts) }}</strong>
                        </div>
                        
                        <div class="zcwc-summary-row zcwc-divider">
                            <span>{{ translations.net_wealth }}</span>
                            <strong>{{ formatCurrency(netWealth) }}</strong>
                        </div>
                        
                        <div class="zcwc-summary-row zcwc-info" v-if="nisabThreshold > 0">
                            <span>{{ translations.nisab_threshold }}</span>
                            <span>{{ formatCurrency(nisabThreshold) }}</span>
                        </div>
                        
                        <div class="zcwc-nisab-status" v-if="nisabThreshold > 0" :class="{'above-nisab': isAboveNisab, 'below-nisab': !isAboveNisab}">
                            <p v-if="isAboveNisab">✓ {{ translations.above_nisab }}</p>
                            <p v-else>✗ {{ translations.below_nisab }}</p>
                        </div>
                        
                        <div class="zcwc-held-checkbox">
                            <label>
                                <input type="checkbox" v-model="heldForYear">
                                <span>{{ translations.held_for_year }}</span>
                            </label>
                        </div>
                        
                        <div class="zcwc-zakat-result">
                            <div class="zcwc-zakat-label">{{ translations.total_zakat_payable }}</div>
                            <div class="zcwc-zakat-amount">{{ formatCurrency(zakatAmount) }}</div>
                        </div>
                        
                        <div class="zcwc-actions">
                            <button class="zcwc-btn zcwc-btn-primary" 
                                    @click="addToCart" 
                                    :disabled="!zakatAmount || zakatAmount <= 0 || loading">
                                <span v-if="!loading">{{ translations.add_to_cart }}</span>
                                <span v-else>{{ translations.processing }}</span>
                            </button>
                            <button class="zcwc-btn zcwc-btn-secondary" @click="resetCalculator">
                                {{ translations.reset }}
                            </button>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        <?php
    }
    
    /**
     * Render admin warning
     */
    private function render_admin_warning() {
        ob_start();
        ?>
        <div class="zcwc-admin-warning" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #856404;">
                ⚠️ <?php esc_html_e('Zakat Calculator Setup Required', 'zakat-calculator-wc'); ?>
            </h3>
            <p><?php esc_html_e('Please set the WooCommerce Product ID in plugin settings.', 'zakat-calculator-wc'); ?></p>
            <p>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=zakat-calculator-wc')); ?>" 
                   class="button button-primary">
                    <?php esc_html_e('Go to Settings', 'zakat-calculator-wc'); ?>
                </a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }
}
