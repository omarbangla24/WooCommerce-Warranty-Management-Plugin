<?php
if ( ! defined('ABSPATH') ) exit;
class WVS_Invoice {
    public static function init(){
        add_action('init', [__CLASS__, 'add_endpoint']);
        add_action('template_redirect', [__CLASS__, 'route']);
    }
    public static function add_endpoint(){
        add_rewrite_rule('^warranty-invoice/([0-9]+)/?$', 'index.php?wvs_invoice=$matches[1]', 'top');
        add_filter('query_vars', function($vars){ $vars[]='wvs_invoice'; return $vars; });
    }
    public static function route(){
        $id = get_query_var('wvs_invoice'); if ($id){ status_header(200); self::render((int)$id); exit; }
    }
    public static function invoice_url($post_id){ return home_url('/warranty-invoice/' . intval($post_id)); }
    public static function prepare_invoice_data($post_id){
        $order_id = (int)get_post_meta($post_id,'wvs_order_id',true);
        $product_id = (int)get_post_meta($post_id,'wvs_product_id',true);
        $number = get_post_meta($post_id,'wvs_number',true);
        $order = $order_id ? wc_get_order($order_id) : false;
        $product = $product_id ? wc_get_product($product_id) : false;
        $items = []; $order_date=''; $billing_name=''; $billing_email=''; $billing_address=''; $billing_phone=''; $total=''; $currency='';
        if ($order){
            $order_date = $order->get_date_created() ? $order->get_date_created()->date('Y-m-d') : '';
            $billing_name = trim($order->get_formatted_billing_full_name()); if(!$billing_name) $billing_name = $order->get_billing_first_name().' '.$order->get_billing_last_name();
            $billing_email = $order->get_billing_email(); 
            $billing_phone = $order->get_billing_phone();
            $billing_address = WC()->countries->get_formatted_address($order->get_address('billing'), ', ');
            $currency = $order->get_currency(); $total = wc_price($order->get_total());
            foreach($order->get_items() as $item_id=>$item){ $p=$item->get_product(); if(!$p) continue;
                $items[]=['name'=>$p->get_name(),'qty'=>$item->get_quantity(),'unit'=>wc_price(wc_get_price_to_display($p)),'is_target'=>($p->get_id()==$product_id)];
            }
        } elseif ($product){
            $items[]=['name'=>$product->get_name(),'qty'=>1,'unit'=>wc_price(wc_get_price_to_display($product)),'is_target'=>true];
        }
        $company = nl2br(esc_html(get_option('wvs_company_info'))); $footer = esc_html(get_option('wvs_footer_text')); $logo=(int)get_option('wvs_invoice_logo_id')?wp_get_attachment_image_url((int)get_option('wvs_invoice_logo_id'),'medium'):'';
        $verify = WVS_Verification::verify_code($number); $verify_url = $verify ? $verify['verify_url'] : '';

        /* WVS 1.3.7: runtime end-date fallback */
        $end_meta = get_post_meta($post_id, 'wvs_end_date', true);
        $start_meta = get_post_meta($post_id, 'wvs_start_date', true);
        $pid_meta = (int) get_post_meta($post_id, 'wvs_product_id', true);
        $needs_calc = (empty($end_meta) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_meta));
        if ($needs_calc && $pid_meta){
            $months = (int) get_post_meta($pid_meta, '_wvs_warranty_months', true);
            if ($months > 0){
                $base = $start_meta && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_meta) ? $start_meta : (isset($order_date) && $order_date ? $order_date : date('Y-m-d'));
                $calc_end = date('Y-m-d', strtotime('+' . $months . ' months', strtotime($base)));
                // backfill but do not trigger save_post
                update_post_meta($post_id, 'wvs_end_date', $calc_end);
            }
        }

        return compact('post_id','order_id','product_id','number','order_date','billing_name','billing_email','billing_address','billing_phone','total','currency','items','company','footer','logo','verify_url');
    }
    public static function render($post_id){
        $data = self::prepare_invoice_data($post_id); extract($data);
        $template = locate_template('wvs/invoice.php'); if (!$template) $template = WVS_PATH . 'templates/invoice.php'; include $template;
    }
    public static function render_block($post_id){
        $data = self::prepare_invoice_data($post_id); extract($data);
        $template = locate_template('wvs/invoice-block.php'); if (!$template) $template = WVS_PATH . 'templates/invoice-block.php'; include $template;
    }
}