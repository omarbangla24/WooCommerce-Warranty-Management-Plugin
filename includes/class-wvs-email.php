<?php
if ( ! defined('ABSPATH') ) exit;
class WVS_Email {
    public static function init(){}
    public static function send_warranty_email($warranty_id){
        $email = get_post_meta($warranty_id, 'wvs_customer_email', true);
        $number = get_post_meta($warranty_id, 'wvs_number', true);
        if ( ! $email || ! is_email($email) ) return 'No valid customer email on warranty.';
        $cert_url = WVS_Certificate::certificate_url($warranty_id);
        $inv_url  = WVS_Invoice::invoice_url($warranty_id);
        $subject = sprintf(__('Your Warranty Certificate #%s','wvs'), $number);
        $company = get_option('wvs_company_info'); $footer  = get_option('wvs_footer_text');
        $message = '<p>Dear Customer,</p><p>Thank you for your purchase. Your warranty details are below:</p>';
        $message .= '<p><strong>Warranty Number:</strong> ' . esc_html($number) . '</p>';
        $message .= '<p><a href="'.esc_url($cert_url).'">View Warranty Certificate</a></p>';
        $message .= '<p><a href="'.esc_url($inv_url).'">View Invoice</a></p>';
        if ($company){ $message .= '<hr><p style="white-space:pre-wrap;">'.esc_html($company).'</p>'; }
        if ($footer){ $message .= '<p>'.esc_html($footer).'</p>'; }
        add_filter('wp_mail_content_type', function(){ return 'text/html'; });
        $sent = wp_mail($email, $subject, $message);
        remove_filter('wp_mail_content_type', '__return_false');
        return $sent ? true : 'wp_mail returned false';
    }
}