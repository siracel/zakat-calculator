<?php
/**
 * Calculator Logic Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZCWC_Calculator {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX handlers will be added here
    }
}
