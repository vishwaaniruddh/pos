<?php
// Centralized base URL and asset URL resolver for POS
// Handles local subfolder development (/ss/pos/) and production (/pos/)

if (!function_exists('get_pos_base_url')) {
    function get_pos_base_url()
    {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = __DIR__;

        // Any request for srishringarr.com or on production server path is strictly /pos/
        if (strpos($host, 'srishringarr.com') !== false || strpos($dir, 'u464193275') !== false) {
            return '/pos/';
        }

        $is_local = (
            strpos($host, 'localhost') !== false ||
            strpos($host, '127.0.0.1') !== false ||
            $host === '::1' ||
            php_sapi_name() === 'cli'
        );

        if ($is_local && (strpos($uri, '/ss/pos') !== false || strpos($script, '/ss/pos') !== false || strpos($dir, 'ss' . DIRECTORY_SEPARATOR . 'pos') !== false)) {
            return '/ss/pos/';
        }
        return '/pos/';
    }
}

if (!function_exists('get_pos_asset_url')) {
    function get_pos_asset_url()
    {
        return get_pos_base_url();
    }
}
