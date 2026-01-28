/**
 * Zakat Calculator - Vue.js Application
 * Version: 1.0.0
 */

(function() {
    'use strict';
    
    // Wait for DOM and Vue to be ready
    document.addEventListener('DOMContentLoaded', function() {
        const appElement = document.getElementById('zcwc-calculator-app');
        
        if (!appElement) {
            return;
        }
        
        if (typeof Vue === 'undefined') {
            console.error('Vue.js is not loaded');
            appElement.innerHTML = '<p style="color: red;">Error: Vue.js failed to load.</p>';
            return;
        }
        
        const { createApp } = Vue;
        
        // Main Vue Application
        const app = createApp({
            data() {
                return {
                    // Settings from WordPress
                    settings: window.zcwcData.settings,
                    translations: window.zcwcData.translations,
                    currency: window.zcwcData.currency,
                    currencySymbol: window.zcwcData.currencySymbol,
                    
                    // User-entered prices
                    prices: {
                        goldPrice: 0,
                        silverPrice: 0
                    },
                    
                    // Nisab method
                    nisabMethod: 'silver', // gold or silver
                    
                    // Calculator state
                    assets: {
                        cash: 0,
                        goldIncluded: false,
                        goldGrams: 0,
                        silverIncluded: false,
                        silverGrams: 0,
                        investmentsIncluded: false,
                        investmentValue: 0,
                        receivablesIncluded: false,
                        receivablesValue: 0,
                        businessIncluded: false,
                        businessValue: 0
                    },
                    
                    debts: {
                        immediate: 0
                    },
                    
                    heldForYear: false,
                    loading: false
                }
            },
            
            computed: {
                // Calculate gold value from grams
                calculatedGoldValue() {
                    if (!this.assets.goldIncluded || !this.assets.goldGrams || !this.prices.goldPrice) {
                        return 0;
                    }
                    return this.assets.goldGrams * this.prices.goldPrice;
                },
                
                // Calculate silver value from grams
                calculatedSilverValue() {
                    if (!this.assets.silverIncluded || !this.assets.silverGrams || !this.prices.silverPrice) {
                        return 0;
                    }
                    return this.assets.silverGrams * this.prices.silverPrice;
                },
                
                // Total assets
                totalAssets() {
                    let total = parseFloat(this.assets.cash) || 0;
                    
                    if (this.assets.goldIncluded) {
                        total += this.calculatedGoldValue;
                    }
                    
                    if (this.assets.silverIncluded) {
                        total += this.calculatedSilverValue;
                    }
                    
                    if (this.assets.investmentsIncluded) {
                        total += parseFloat(this.assets.investmentValue) || 0;
                    }
                    
                    if (this.assets.receivablesIncluded) {
                        total += parseFloat(this.assets.receivablesValue) || 0;
                    }
                    
                    if (this.assets.businessIncluded) {
                        total += parseFloat(this.assets.businessValue) || 0;
                    }
                    
                    return total;
                },
                
                // Total debts
                totalDebts() {
                    return parseFloat(this.debts.immediate) || 0;
                },
                
                // Net wealth
                netWealth() {
                    return this.totalAssets - this.totalDebts;
                },
                
                // Nisab threshold
                nisabThreshold() {
                    const goldNisabGrams = this.settings.goldNisabGrams || 87.48;
                    const silverNisabGrams = this.settings.silverNisabGrams || 612.36;
                    
                    if (this.nisabMethod === 'gold' && this.prices.goldPrice > 0) {
                        return goldNisabGrams * this.prices.goldPrice;
                    } else if (this.nisabMethod === 'silver' && this.prices.silverPrice > 0) {
                        return silverNisabGrams * this.prices.silverPrice;
                    }
                    
                    return 0;
                },
                
                // Check if above nisab
                isAboveNisab() {
                    return this.nisabThreshold > 0 && this.netWealth >= this.nisabThreshold;
                },
                
                // Calculate Zakat (2.5%)
                zakatAmount() {
                    if (!this.isAboveNisab || !this.heldForYear) {
                        return 0;
                    }
                    return this.netWealth * 0.025;
                }
            },
            
            methods: {
                // Format currency
                formatCurrency(value) {
                    return this.currencySymbol + parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                },
                
                // Reset calculator
                resetCalculator() {
                    this.prices = {
                        goldPrice: 0,
                        silverPrice: 0
                    };
                    this.nisabMethod = 'silver';
                    this.assets = {
                        cash: 0,
                        goldIncluded: false,
                        goldGrams: 0,
                        silverIncluded: false,
                        silverGrams: 0,
                        investmentsIncluded: false,
                        investmentValue: 0,
                        receivablesIncluded: false,
                        receivablesValue: 0,
                        businessIncluded: false,
                        businessValue: 0
                    };
                    this.debts = { immediate: 0 };
                    this.heldForYear = false;
                },
                
                // Add to WooCommerce cart
                addToCart() {
                    if (!this.zakatAmount || this.zakatAmount <= 0) {
                        alert('Please calculate your Zakat first.');
                        return;
                    }
                    
                    if (this.nisabThreshold <= 0) {
                        alert('Please enter current gold or silver price.');
                        return;
                    }
                    
                    this.loading = true;
                    
                    // AJAX request to add to cart
                    jQuery.ajax({
                        url: window.zcwcData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'zcwc_add_to_cart',
                            nonce: window.zcwcData.nonce,
                            amount: this.zakatAmount
                        },
                        success: (response) => {
                            this.loading = false;
                            if (response.success) {
                                // Redirect to cart
                                window.location.href = response.data.cart_url;
                            } else {
                                alert(response.data.message || 'Failed to add to cart.');
                            }
                        },
                        error: () => {
                            this.loading = false;
                            alert('An error occurred. Please try again.');
                        }
                    });
                }
            },
            
            mounted() {
                console.log('Zakat Calculator loaded');
            }
        });
        
        app.mount('#zcwc-calculator-app');
    });
})();
