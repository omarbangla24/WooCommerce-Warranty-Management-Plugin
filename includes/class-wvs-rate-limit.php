<?php
if ( ! defined('ABSPATH') ) exit;
class WVS_Rate_Limit {
    const WINDOW = 600; const MAX_ATTEMPTS = 20;
    public static function init(){}
    public static function check(){
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        $key = 'wvs_rl_' . md5($ip);
        $data = get_transient($key);
        if (!$data){ set_transient($key, 1, self::WINDOW); return true; }
        $data = (int)$data;
        if ($data >= self::MAX_ATTEMPTS) return false;
        set_transient($key, $data+1, self::WINDOW); return true;
    }
}