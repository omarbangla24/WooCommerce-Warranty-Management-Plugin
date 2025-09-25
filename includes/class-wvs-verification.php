<?php
if ( ! defined('ABSPATH') ) exit;

class WVS_Verification {
    public static function init(){
        add_action('init', [__CLASS__, 'add_rewrite_rules']);
        add_filter('query_vars', [__CLASS__, 'query_vars']);
        add_action('template_redirect', [__CLASS__, 'route']);
    }

    public static function add_rewrite_rules(){
        add_rewrite_rule('^verify/?$', 'index.php?wvs_verify=1', 'top');
        add_rewrite_rule('^verify/([^/]+)/?$', 'index.php?wvs_verify=1&wvs_code=$matches[1]', 'top');
    }

    public static function query_vars($vars){ 
        $vars[] = 'wvs_verify'; 
        $vars[] = 'wvs_code'; 
        return $vars; 
    }

    public static function route(){
        if (get_query_var('wvs_verify')){
            status_header(200);
            if ( ! WVS_Rate_Limit::check() ) {
                wp_die(__('Too many attempts. Please try again later.','wvs'));
            }
            
            $code = sanitize_text_field(get_query_var('wvs_code'));
            self::render_page($code); 
            exit;
        }
    }

    protected static function render_page($code){
        $tmpl = $code ? 'verify-result.php' : 'verify-form.php';
        $template = locate_template('wvs/'.$tmpl); 
        if (!$template) $template = WVS_PATH . 'templates/'.$tmpl;
        include $template;
    }

    public static function verify_code($code){
        if (empty($code)) return false;
        
        if (preg_match('/^[\d\+\-\s\(\)]+$/', $code)) {
            return self::verify_by_phone($code);
        }
        
        return self::verify_by_number($code);
    }

    public static function verify_by_number($number){
        $post_id = WVS_CPT::find_by_number($number);
        if (!$post_id) return false;
        
        return self::prepare_warranty_data($post_id);
    }

    public static function verify_by_phone($phone){
        $phone = sanitize_text_field($phone);
        $warranty_ids = WVS_CPT::find_by_phone($phone);
        
        if (empty($warranty_ids)) return false;
        
        $post_id = $warranty_ids[0];
        $data = self::prepare_warranty_data($post_id);
        
        if (count($warranty_ids) > 1) {
            $data['multiple_warranties'] = count($warranty_ids);
            $data['all_warranty_ids'] = $warranty_ids;
        }
        
        return $data;
    }

    protected static function prepare_warranty_data($post_id){
        $end_meta = get_post_meta($post_id, 'wvs_end_date', true);
        if (empty($end_meta) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_meta)){
            $start_meta = get_post_meta($post_id, 'wvs_start_date', true);
            $pid_meta = (int) get_post_meta($post_id, 'wvs_product_id', true);
            $months = WVS_CPT::get_warranty_months($pid_meta);
            
            if ($months > 0){
                $base = $start_meta && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_meta) ? $start_meta : date('Y-m-d');
                $calc_end = date('Y-m-d', strtotime('+' . $months . ' months', strtotime($base)));
                update_post_meta($post_id, 'wvs_end_date', $calc_end);
                $end_meta = $calc_end;
            }
        }
        
        $data = [
            'id' => $post_id,
            'number' => get_post_meta($post_id,'wvs_number',true),
            'order_id' => get_post_meta($post_id,'wvs_order_id',true),
            'product_id' => get_post_meta($post_id,'wvs_product_id',true),
            'email' => get_post_meta($post_id,'wvs_customer_email',true),
            'phone' => get_post_meta($post_id,'wvs_customer_phone',true),
            'start' => get_post_meta($post_id,'wvs_start_date',true),
            'end' => $end_meta,
            'status' => ( strtotime($end_meta) >= time() ) ? 'Active' : 'Expired',
            'verify_url' => home_url('/verify/'.urlencode(get_post_meta($post_id,'wvs_number',true)))
        ];
        
        return $data;
    }
}