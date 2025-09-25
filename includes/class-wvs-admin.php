<?php
if ( ! defined('ABSPATH') ) exit;

class WVS_Admin {
    public static function init(){
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue']);
        add_action('wp_ajax_wvs_order_details', [__CLASS__, 'ajax_order_details']);
        add_action('wp_ajax_wvs_send_warranty_email', [__CLASS__, 'ajax_send_email']);
    }

    public static function enqueue($hook){
        if (strpos($hook, 'warranty_page_wvs-invoice-settings') !== false){
            wp_enqueue_media();
            wp_enqueue_style('wvs-admin', WVS_URL . 'assets/css/admin.css', [], WVS_VERSION);
            wp_enqueue_script('wvs-admin', WVS_URL . 'assets/js/admin.js', ['jquery'], WVS_VERSION, true);
        }
        
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->post_type === 'warranty'){
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style('wvs-datepicker', WVS_URL . 'assets/css/datepicker.css', [], WVS_VERSION);
            wp_enqueue_script('wvs-warranty-meta', WVS_URL . 'assets/js/warranty-meta.js', ['jquery','jquery-ui-datepicker'], WVS_VERSION, true);
            wp_localize_script('wvs-warranty-meta', 'WVS_META', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wvs_order_nonce'),
                'send_nonce' => wp_create_nonce('wvs_send_email_nonce')
            ]);
        }
    }

    public static function menu(){
        add_submenu_page(
            'edit.php?post_type=warranty',
            'Invoice Settings',
            'Invoice Settings',
            'manage_options',
            'wvs-invoice-settings',
            [__CLASS__,'settings_page']
        );
    }

    public static function register_settings(){
        register_setting('wvs_invoice', 'wvs_invoice_logo_id');
        register_setting('wvs_invoice', 'wvs_company_info');
        register_setting('wvs_invoice', 'wvs_footer_text');
        register_setting('wvs_invoice', 'wvs_policy_text');
    }

    public static function settings_page(){
        ?>
        <div class="wrap"><h1>Invoice Settings</h1>
        <form method="post" action="options.php"><?php settings_fields('wvs_invoice'); ?>
        <table class="form-table">
        <tr><th>Company Logo</th><td><?php $logo_id=(int)get_option('wvs_invoice_logo_id'); $src=$logo_id?wp_get_attachment_image_url($logo_id,'medium'):''; ?>
            <div><img id="wvs-logo-preview" src="<?php echo esc_url($src); ?>" style="max-width:200px;<?php echo $src?'':'display:none;'; ?>" /></div>
            <input type="hidden" id="wvs_invoice_logo_id" name="wvs_invoice_logo_id" value="<?php echo esc_attr($logo_id); ?>">
            <button type="button" class="button" id="wvs-upload-logo">Upload/Select Logo</button>
            <button type="button" class="button" id="wvs-remove-logo">Remove</button>
        </td></tr>
        <tr><th>Company Information</th><td><textarea name="wvs_company_info" rows="6" class="large-text"><?php echo esc_textarea(get_option('wvs_company_info')); ?></textarea></td></tr>
        <tr><th>Footer Text</th><td><input type="text" name="wvs_footer_text" class="regular-text" value="<?php echo esc_attr(get_option('wvs_footer_text')); ?>"></td></tr>
        <tr><th>Warranty Policy (shown on invoice)</th><td><textarea name="wvs_policy_text" rows="6" class="large-text" placeholder="Enter warranty/return policy text..."><?php echo esc_textarea(get_option('wvs_policy_text')); ?></textarea></td></tr>
        </table><?php submit_button(); ?></form></div><?php
    }

    public static function ajax_order_details(){
        check_ajax_referer('wvs_order_nonce', 'nonce');
        if ( ! current_user_can('edit_posts') ) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }
        if ( ! class_exists('WooCommerce') ) {
            wp_send_json_error(['message' => 'WooCommerce not active'], 400);
        }
        
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        if ( ! $order_id ) {
            wp_send_json_error(['message' => 'Missing order_id'], 400);
        }
        
        $order = wc_get_order($order_id);
        if ( ! $order ) {
            wp_send_json_error(['message' => 'Order not found'], 404);
        }
        
        $items = [];
        foreach($order->get_items() as $item_id => $item){
            $product = $item->get_product();
            if (!$product) continue;
            
            $warranty_months = 0;
            if ($product->is_type('variation')) {
                $warranty_months = (int) get_post_meta($product->get_id(), '_wvs_warranty_months', true);
                if ($warranty_months <= 0) {
                    $parent_id = $product->get_parent_id();
                    if ($parent_id) {
                        $warranty_months = (int) get_post_meta($parent_id, '_wvs_warranty_months', true);
                    }
                }
            } else {
                $warranty_months = (int) $product->get_meta('_wvs_warranty_months');
            }
            
            $items[] = [
                'item_id' => $item_id,
                'product_id' => $product->get_id(),
                'product_name' => $product->get_name(),
                'warranty_months' => $warranty_months,
                'qty' => $item->get_quantity(),
                'price' => wc_get_price_to_display($product)
            ];
        }
        
        $name = trim($order->get_formatted_billing_full_name());
        if (!$name) $name = trim($order->get_billing_first_name().' '.$order->get_billing_last_name());
        if (!$name) $name = $order->get_billing_email();
        
        $data = [
            'order_id' => $order->get_id(),
            'order_date' => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d') : current_time('Y-m-d'),
            'billing_email' => $order->get_billing_email(),
            'billing_phone' => $order->get_billing_phone(),
            'billing_name' => $name,
            'items' => $items,
            'status' => $order->get_status(),
            'total' => wc_price($order->get_total()),
            'currency' => get_woocommerce_currency_symbol($order->get_currency())
        ];
        
        wp_send_json_success($data);
    }

    public static function ajax_send_email(){
        check_ajax_referer('wvs_send_email_nonce', 'nonce');
        if ( ! current_user_can('edit_posts') ) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }
        
        $wid = isset($_POST['warranty_id']) ? absint($_POST['warranty_id']) : 0;
        if (!$wid) {
            wp_send_json_error(['message'=>'Missing warranty_id'], 400);
        }
        
        $ok = WVS_Email::send_warranty_email($wid);
        if ($ok === true) {
            wp_send_json_success(['message'=>'Email sent']);
        } else {
            wp_send_json_error(['message'=> is_string($ok) ? $ok : 'Failed to send']);
        }
    }
}