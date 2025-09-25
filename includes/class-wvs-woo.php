<?php
if ( ! defined('ABSPATH') ) exit;

class WVS_Woo {
    public static function init(){
        if (defined('WVS_SAFE_MODE') && WVS_SAFE_MODE){
            add_action('woocommerce_product_options_general_product_data', [__CLASS__, 'product_field']);
            add_action('woocommerce_admin_process_product_object', [__CLASS__, 'save_product_field']);
            add_action('woocommerce_product_after_variable_attributes', [__CLASS__, 'variation_field'], 10, 3);
            add_action('woocommerce_save_product_variation', [__CLASS__, 'save_variation_field'], 10, 2);
            add_action('woocommerce_admin_order_data_after_billing_address', [__CLASS__, 'order_meta_box']);
            return;
        }
        
        add_action('woocommerce_product_options_general_product_data', [__CLASS__, 'product_field']);
        add_action('woocommerce_admin_process_product_object', [__CLASS__, 'save_product_field']);
        add_action('woocommerce_product_after_variable_attributes', [__CLASS__, 'variation_field'], 10, 3);
        add_action('woocommerce_save_product_variation', [__CLASS__, 'save_variation_field'], 10, 2);
        add_action('woocommerce_order_status_completed', [__CLASS__, 'on_order_completed']);
        add_action('woocommerce_admin_order_data_after_billing_address', [__CLASS__, 'order_meta_box']);
    }

    public static function product_field(){
        echo '<div class="options_group">';
        woocommerce_wp_text_input([
            'id' => '_wvs_warranty_months',
            'label' => __('Warranty Period (months)', 'wvs'),
            'type' => 'number',
            'desc_tip' => true,
            'description' => __('Number of months for product warranty. This will apply to all variations if no specific variation warranty is set.', 'wvs'),
            'custom_attributes' => ['min' => '0', 'step' => '1']
        ]);
        echo '</div>';
    }

    public static function save_product_field($product){
        $months = isset($_POST['_wvs_warranty_months']) ? absint($_POST['_wvs_warranty_months']) : 0;
        $product->update_meta_data('_wvs_warranty_months', $months);
    }

    public static function variation_field($loop, $variation_data, $variation){
        $variation_id = $variation->ID;
        $warranty_months = get_post_meta($variation_id, '_wvs_warranty_months', true);
        
        echo '<div class="form-row form-row-full">';
        woocommerce_wp_text_input([
            'id' => '_wvs_warranty_months_' . $loop,
            'name' => '_wvs_warranty_months[' . $loop . ']',
            'label' => __('Warranty Period (months)', 'wvs'),
            'type' => 'number',
            'value' => $warranty_months,
            'desc_tip' => true,
            'description' => __('Number of months for this variation warranty. Leave empty to use parent product warranty.', 'wvs'),
            'custom_attributes' => ['min' => '0', 'step' => '1']
        ]);
        echo '</div>';
    }

    public static function save_variation_field($variation_id, $loop){
        if (isset($_POST['_wvs_warranty_months'][$loop])) {
            $warranty_months = absint($_POST['_wvs_warranty_months'][$loop]);
            update_post_meta($variation_id, '_wvs_warranty_months', $warranty_months);
        }
    }

    public static function on_order_completed($order_id){
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $email = $order->get_billing_email();
        $phone = $order->get_billing_phone();
        
        foreach($order->get_items() as $item_id => $item){
            $product = $item->get_product();
            if (!$product) continue;
            
            $months = 0;
            if ($product->is_type('variation')) {
                $months = (int) get_post_meta($product->get_id(), '_wvs_warranty_months', true);
                if ($months <= 0) {
                    $parent_id = $product->get_parent_id();
                    if ($parent_id) {
                        $months = (int) get_post_meta($parent_id, '_wvs_warranty_months', true);
                    }
                }
            } else {
                $months = (int) $product->get_meta('_wvs_warranty_months');
            }
            
            if ($months > 0){
                $start = current_time('Y-m-d');
                $end = date('Y-m-d', strtotime("+$months months", strtotime($start)));
                $number = WVS_CPT::generate_unique_number($order_id, $item_id);
                
                $w_id = WVS_CPT::create_warranty([
                    'wvs_number' => $number,
                    'wvs_order_id' => $order_id,
                    'wvs_product_id' => $product->get_id(),
                    'wvs_customer_email' => $email,
                    'wvs_customer_phone' => $phone,
                    'wvs_start_date' => $start,
                    'wvs_end_date' => $end
                ]);
                
                if (!is_wp_error($w_id)) {
                    WVS_Email::send_warranty_email($w_id);
                }
            }
        }
    }

    public static function order_meta_box($order){
        echo '<div class="order_data_column"><h4>Warranty Information</h4><ul>';
        $q = new WP_Query([
            'post_type' => 'warranty',
            'posts_per_page' => 50,
            'fields' => 'ids',
            'post_status' => 'publish',
            'no_found_rows' => true,
            'meta_query' => [[
                'key' => 'wvs_order_id',
                'value' => $order->get_id(),
                'compare' => '='
            ]]
        ]);
        
        if ($q->have_posts()){
            foreach($q->posts as $wid){
                $num = get_post_meta($wid, 'wvs_number', true);
                $end = get_post_meta($wid, 'wvs_end_date', true);
                echo '<li><a href="'.esc_url(WVS_Certificate::certificate_url($wid)).'" target="_blank">'.esc_html($num).'</a> (ends '.$end.')</li>';
            }
        } else { 
            echo '<li>No warranties</li>'; 
        }
        echo '</ul></div>';
    }
}