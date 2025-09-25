<?php
/**
 * Plugin Name: Warranty Verification System
 * Description: Warranty management with WooCommerce, QR verification, invoice (with QR), email, rate limiting, bulk invoices, policy text, and bulk print.
 * Version: 1.3.8
 * Author: Omar Faruk
 * Author URI: https://wa.me/8801961547802
 * Text Domain: wvs
 */
if (!defined('ABSPATH')) exit;
define('WVS_VERSION', '1.3.8');
define('WVS_PATH', plugin_dir_path(__FILE__));
define('WVS_URL', plugin_dir_url(__FILE__));
// Define in wp-config.php as: define('WVS_SAFE_MODE', true); to disable heavy features if needed.
spl_autoload_register(function($class){
  if (strpos($class, 'WVS_') === 0){
    $file = WVS_PATH . 'includes/class-' . strtolower(str_replace('_','-',$class)) . '.php';
    if (file_exists($file)) require_once $file;
  }
});
register_activation_hook(__FILE__, function(){
  WVS_CPT::register_cpt();
  WVS_Verification::add_rewrite_rules();
  WVS_Certificate::add_endpoint();
  WVS_Invoice::add_endpoint();
  flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function(){ flush_rewrite_rules(); });
add_action('plugins_loaded', function(){
  WVS_CPT::init();
  WVS_Admin::init();
  WVS_Verification::init();
  WVS_Rate_Limit::init();
  WVS_Certificate::init();
  WVS_Invoice::init();
  WVS_Email::init();
  WVS_Admin_Print::init();
  WVS_Performance::init();
  if (class_exists('WooCommerce')) WVS_Woo::init();
});