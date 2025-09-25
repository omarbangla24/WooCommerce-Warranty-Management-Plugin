<?php
if ( ! defined('ABSPATH') ) exit;

class WVS_CPT {
    private static $saving = false;

    public static function init(){
        add_action('init', [__CLASS__, 'register_cpt']);
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post_warranty', [__CLASS__, 'save_meta'], 10, 2);
    }

    public static function register_cpt(){
        register_post_type('warranty', [
            'labels' => [
                'name' => 'Warranties',
                'singular_name' => 'Warranty',
                'menu_name' => 'Warranties'
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-shield',
            'supports' => ['title'],
            'capability_type' => 'post'
        ]);
    }

    public static function add_meta_boxes(){
        add_meta_box('wvs_warranty_meta', 'Warranty Details', [__CLASS__, 'render_meta'], 'warranty', 'normal', 'default');
    }

    protected static function order_dropdown(){
        if (defined('WVS_SAFE_MODE') && WVS_SAFE_MODE){
            return '<input type="number" id="wvs_order_id" placeholder="Enter Order ID" style="min-width:200px;">';
        }
        if ( ! class_exists('WooCommerce') ) return '';
        
        $orders = wc_get_orders(['limit' => 20, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'objects']);
        $html = '<select id="wvs_order_select" style="min-width:260px;"><option value="">Select order…</option>';
        
        foreach($orders as $order){
            $name = trim($order->get_formatted_billing_full_name());
            if(!$name) $name = trim($order->get_billing_first_name().' '.$order->get_billing_last_name());
            if(!$name) $name = $order->get_billing_email();
            
            $label = sprintf('#%d — %s (%s)', 
                $order->get_id(), 
                $name ?: 'Customer', 
                $order->get_date_created() ? $order->get_date_created()->date('Y-m-d') : ''
            );
            $html .= '<option value="'.esc_attr($order->get_id()).'">'.esc_html($label).'</option>';
        }
        $html .= '</select> <button type="button" class="button" id="wvs_load_from_order">Load</button>';
        return $html;
    }

    public static function render_meta($post){
        $fields = [
            'wvs_number' => 'Warranty Number',
            'wvs_order_id' => 'Order ID',
            'wvs_product_id' => 'Product ID',
            'wvs_customer_email' => 'Customer Email',
            'wvs_customer_phone' => 'Customer Phone',
            'wvs_start_date' => 'Start Date (Y-m-d)',
            'wvs_end_date' => 'End Date (Y-m-d)'
        ];

        echo '<table class="form-table">';
        echo '<tr><th>Load From Order</th><td>'. self::order_dropdown() .' <span class="description">Pick an order to auto-fill fields.</span></td></tr>';
        echo '<tr><th><label for="wvs_product_select">Product</label></th><td><select id="wvs_product_select" style="min-width:260px;"><option value="">Select product…</option></select> <span class="description">Choosing a product auto-calculates end date if warranty months set.</span></td></tr>';
        
        foreach($fields as $key => $label){
            $val = esc_attr(get_post_meta($post->ID, $key, true));
            $cls = in_array($key, ['wvs_start_date','wvs_end_date']) ? ' class="regular-text wvs-datepick"' : ' class="regular-text"';
            
            echo '<tr><th><label for="'.$key.'">'.$label.'</label></th><td><input type="text" name="'.$key.'" id="'.$key.'"'.$cls.' value="'.$val.'">';
            if ($key === 'wvs_number') echo ' <button type="button" class="button" id="wvs_generate_number">Generate</button>';
            echo '</td></tr>';
        }
        echo '</table>';
        
        wp_nonce_field('wvs_warranty_meta','wvs_warranty_meta_nonce');
        echo '<p style="display:flex;gap:8px;align-items:center;">';
        echo '<a class="button" href="'.esc_url(WVS_Certificate::certificate_url($post->ID)).'" target="_blank">View Certificate</a> ';
        echo '<a class="button" href="'.esc_url(WVS_Invoice::invoice_url($post->ID)).'" target="_blank">View Invoice</a> ';
        echo '<button type="button" class="button button-primary" id="wvs_send_email_btn" data-id="'.intval($post->ID).'">Send Email to Customer</button>';
        echo '</p>';
    }

    public static function save_meta($post_id, $post){
        if (self::$saving) return;
        
        if ( !isset($_POST['wvs_warranty_meta_nonce']) || !wp_verify_nonce($_POST['wvs_warranty_meta_nonce'], 'wvs_warranty_meta') ) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! current_user_can('edit_post', $post_id) ) return;
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
        
        foreach(['wvs_number','wvs_order_id','wvs_product_id','wvs_customer_email','wvs_customer_phone','wvs_start_date','wvs_end_date'] as $k){
            if (isset($_POST[$k])) {
                update_post_meta($post_id, $k, sanitize_text_field($_POST[$k]));
            }
        }
        
        $num = get_post_meta($post_id, 'wvs_number', true);
        if (empty($num)){ 
            $gen = self::generate_unique_number( (int) get_post_meta($post_id,'wvs_order_id', true), 0 ); 
            update_post_meta($post_id, 'wvs_number', $gen); 
            $num = $gen; 
        }
        
        if ($num && get_post_field('post_title', $post_id) !== $num){
            self::$saving = true;
            remove_action('save_post_warranty', [__CLASS__, 'save_meta'], 10);
            wp_update_post(['ID' => $post_id, 'post_title' => $num]);
            add_action('save_post_warranty', [__CLASS__, 'save_meta'], 10, 2);
            self::$saving = false;
        }

        $start = get_post_meta($post_id, 'wvs_start_date', true);
        $end   = get_post_meta($post_id, 'wvs_end_date', true);
        $pid   = (int) get_post_meta($post_id, 'wvs_product_id', true);
        
        $needs_calc = (empty($end) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end));
        if ($needs_calc && $pid){
            $months = self::get_warranty_months($pid);
            if ($months > 0){
                $start_date = ( $start && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) ) ? $start : date('Y-m-d');
                $calc_end = date('Y-m-d', strtotime("+$months months", strtotime($start_date)));
                update_post_meta($post_id, 'wvs_end_date', $calc_end);
            }
        }
    }

    public static function get_warranty_months($product_id) {
        if (!$product_id) return 0;
        
        $product = wc_get_product($product_id);
        if (!$product) return 0;
        
        if ($product->is_type('variation')) {
            $months = (int) get_post_meta($product_id, '_wvs_warranty_months', true);
            if ($months > 0) return $months;
            
            $parent_id = $product->get_parent_id();
            if ($parent_id) {
                return (int) get_post_meta($parent_id, '_wvs_warranty_months', true);
            }
        } else {
            return (int) get_post_meta($product_id, '_wvs_warranty_months', true);
        }
        
        return 0;
    }

    public static function generate_unique_number($order_id = 0, $item_id = 0){
        $date = current_time('Ymd'); 
        $base = 'WVS-' . $date;
        
        for($i=0;$i<10;$i++){
            $rand = strtoupper(wp_generate_password(6, false, false));
            $parts = [$base]; 
            if ($order_id) $parts[] = $order_id; 
            if ($item_id) $parts[] = $item_id; 
            $parts[] = $rand;
            $candidate = implode('-', $parts);
            if ( ! self::find_by_number($candidate) ) return $candidate;
        }
        
        return $base . '-' . $order_id . '-' . $item_id . '-' . strtoupper(substr(md5(uniqid('', true)),0,8));
    }

    public static function create_warranty($args){
        $args = wp_parse_args($args, [
            'wvs_number' => '',
            'wvs_order_id' => 0,
            'wvs_product_id' => 0,
            'wvs_customer_email' => '',
            'wvs_customer_phone' => '',
            'wvs_start_date' => date('Y-m-d'),
            'wvs_end_date' => date('Y-m-d', strtotime('+12 months'))
        ]);
        
        if (empty($args['wvs_number'])) {
            $args['wvs_number'] = self::generate_unique_number($args['wvs_order_id'], 0);
        }
        
        $post_id = wp_insert_post([
            'post_type' => 'warranty',
            'post_status' => 'publish',
            'post_title' => $args['wvs_number']
        ]);
        
        if (is_wp_error($post_id)) return $post_id;
        
        foreach($args as $k => $v) {
            update_post_meta($post_id, $k, $v);
        }
        
        return $post_id;
    }

    public static function find_by_number($number){
        $q = new WP_Query([
            'post_type' => 'warranty',
            'meta_query' => [[
                'key' => 'wvs_number',
                'value' => $number,
                'compare' => '='
            ]],
            'fields' => 'ids',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'no_found_rows' => true
        ]);
        
        if ($q->have_posts()) return (int) $q->posts[0];
        return 0;
    }

    public static function find_by_phone($phone){
        $phone = sanitize_text_field($phone);
        if (empty($phone)) return [];
        
        $q = new WP_Query([
            'post_type' => 'warranty',
            'meta_query' => [[
                'key' => 'wvs_customer_phone',
                'value' => $phone,
                'compare' => '='
            ]],
            'fields' => 'ids',
            'posts_per_page' => 20,
            'post_status' => 'publish',
            'no_found_rows' => true
        ]);
        
        return $q->posts;
    }
}