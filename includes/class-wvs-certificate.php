<?php
if ( ! defined('ABSPATH') ) exit;
class WVS_Certificate {
    public static function init(){
        add_action('init', [__CLASS__, 'add_endpoint']);
        add_action('template_redirect', [__CLASS__, 'route']);
    }
    public static function add_endpoint(){
        add_rewrite_rule('^warranty-certificate/([0-9]+)/?$', 'index.php?wvs_cert=$matches[1]', 'top');
        add_filter('query_vars', function($vars){ $vars[]='wvs_cert'; return $vars; });
    }
    public static function route(){
        $id = get_query_var('wvs_cert');
        if ($id){ status_header(200); self::render((int)$id); exit; }
    }
    public static function certificate_url($post_id){ return home_url('/warranty-certificate/' . intval($post_id)); }
    protected static function qr_url($data){ return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . rawurlencode($data); }
    protected static function company_logo_src(){ $id=(int)get_option('wvs_invoice_logo_id'); return $id ? wp_get_attachment_image_url($id,'medium') : ''; }
    public static function render($post_id){
        $number = get_post_meta($post_id, 'wvs_number', true);
        $data = WVS_Verification::verify_code($number);

        /* WVS 1.3.7: runtime end-date fallback */
        $end_meta = get_post_meta($data['id'], 'wvs_end_date', true);
        $start_meta = get_post_meta($data['id'], 'wvs_start_date', true);
        $pid_meta = (int) get_post_meta($data['id'], 'wvs_product_id', true);
        $needs_calc = (empty($end_meta) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_meta));
        if ($needs_calc && $pid_meta){
            $months = (int) get_post_meta($pid_meta, '_wvs_warranty_months', true);
            if ($months > 0){
                $base = $start_meta && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_meta) ? $start_meta : (isset($order_date) && $order_date ? $order_date : date('Y-m-d'));
                $calc_end = date('Y-m-d', strtotime('+' . $months . ' months', strtotime($base)));
                // backfill but do not trigger save_post
                update_post_meta($data['id'], 'wvs_end_date', $calc_end);
            }
        }
     if (!$data) wp_die('Invalid certificate.');
        $verify_url = $data['verify_url']; $qr = self::qr_url($verify_url); $logo=self::company_logo_src();
        $company = nl2br(esc_html(get_option('wvs_company_info'))); $footer = esc_html(get_option('wvs_footer_text'));
        $template = locate_template('wvs/certificate.php'); if (!$template) $template = WVS_PATH . 'templates/certificate.php'; include $template;
    }
}