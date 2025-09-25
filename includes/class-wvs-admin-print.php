<?php
if ( ! defined('ABSPATH') ) exit;
class WVS_Admin_Print {
    public static function init(){
        add_filter('bulk_actions-edit-warranty', [__CLASS__, 'register_bulk']);
        add_filter('handle_bulk_actions-edit-warranty', [__CLASS__, 'handle_bulk'], 10, 3);
        add_action('admin_init', [__CLASS__, 'maybe_render_bulk']);
    }
    public static function register_bulk($actions){
        $actions['wvs_print_invoices'] = __('Print Invoices','wvs');
        return $actions;
    }
    public static function handle_bulk($redirect, $doaction, $post_ids){
        if ($doaction === 'wvs_print_invoices'){
            $url = add_query_arg(['post_type'=>'warranty','wvs_print_invoices'=>'1','ids'=>implode(',', array_map('intval',$post_ids))], admin_url('edit.php'));
            wp_redirect($url); exit;
        }
        return $redirect;
    }
    public static function maybe_render_bulk(){
        if ( ! is_admin() ) return;
        if ( ! isset($_GET['wvs_print_invoices']) ) return;
        if ( ! current_user_can('manage_options') ) wp_die('Unauthorized');
        $ids = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));
        ?><!DOCTYPE html><html><head><meta charset="utf-8"><title>Bulk Invoices</title><meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
          body{font-family:Arial,Helvetica,sans-serif;background:#fff;margin:0;padding:10px}
          .invoice-block{max-width:900px;margin:0 auto 10px auto;border:1px solid #eee;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.04)}
          @media print{
            body{background:#fff;padding:0}
            .invoice-block{box-shadow:none;border:0;margin:0 0 8mm 0}
            .invoice-block{break-inside: avoid; page-break-inside: avoid; max-height: 48vh; overflow: visible;}
          }
        </style></head><body>
        <?php foreach($ids as $wid){ WVS_Invoice::render_block($wid); } ?>
        <script>window.onload=function(){window.print();};</script>
        </body></html><?php
        exit;
    }
}