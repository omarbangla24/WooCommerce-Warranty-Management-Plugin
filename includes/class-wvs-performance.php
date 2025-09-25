<?php
if ( ! defined('ABSPATH') ) exit;
class WVS_Performance {
    public static function init(){
        add_action('pre_get_posts', [__CLASS__, 'cap_admin_warranty_queries'], 1);
    }
    public static function cap_admin_warranty_queries($q){
        if ( ! is_admin() ) return;
        /* allow also AJAX-backed list requests */
        $pt = $q->get('post_type');
        $is_warranty = false;
        if (is_string($pt) && $pt === 'warranty') $is_warranty = true;
        if (is_array($pt) && in_array('warranty', $pt, true)) $is_warranty = true;
        // Also catch list table where post_type comes via GET
        if (!$is_warranty && isset($_GET['post_type']) && $_GET['post_type'] === 'warranty') $is_warranty = true;

        if ( ! $is_warranty ) return;

        $ppp = (int) $q->get('posts_per_page');
        if ($ppp === -1 || $ppp > 200 || $ppp === 0){
            $q->set('posts_per_page', 50);
        }

        // Avoid heavy caches on admin list views
        $q->set('no_found_rows', true);
        $q->set('update_post_meta_cache', false);
        $q->set('update_post_term_cache', false);

        // If someone sets 'fields' to something weird, let WP handle it; we keep defaults for list table rendering.

        // Optional debug
        if (defined('WVS_DEBUG_QUERIES') && WVS_DEBUG_QUERIES){
            $args = $q->query_vars;
            if (function_exists('error_log')){
                error_log('[WVS] pre_get_posts clamp: posts_per_page=' . $q->get('posts_per_page'));
                error_log('[WVS] args: ' . wp_json_encode($args));
            }
        }
    }
}