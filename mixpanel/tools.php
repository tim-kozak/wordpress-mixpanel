<?php

//some script after which all scrips will be included
define("HEAD_SCRIPT_HANDLER", "main");

//region TOOLS -----------

function mx_is_ok() {
    $is_attachment = preg_match("/\b(\.jpg|\.JPG|\.png|\.PNG|\.gif|\.GIF)\b/", $_SERVER['REQUEST_URI']);
    $is_404 = is_404();
    $is_doing_cron = defined( 'DOING_CRON' );

    return !$is_404 && !$is_attachment && !$is_doing_cron;
}

function mx_get_client_ip() {
    return isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
}

function mx_add_inline_script_to_head($data, $priority = 1) {
    add_action('wp_enqueue_scripts', function () use ($data) {
        wp_add_inline_script( HEAD_SCRIPT_HANDLER, $data, 'after' );
    }, 99 + $priority );
}

function mx_string( $string ){
    if ($string && strlen($string)) {
        return $string;
    }
    return 'Unknown';
}

//endregion
