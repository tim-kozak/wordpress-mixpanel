<?php

//region SET SUPER PROPERTIES each request
if (is_user_logged_in()) {
    $user = get_user_by( 'id', get_current_user_id() );
    set_super_props( $user );
}

function set_super_props( $user ) {
    $super_props = get_super_properties( $user );
    MXTracker::instance()->js_set_super_properties( $super_props );
    MXTracker::instance()->api_set_super_properties( $super_props );
}
//endregion


//region ATTRIBUTES for user profiles
function get_user_attributes ( $user ) {
    return array(
        'Login'       => $user->user_login,
        '$first_name' => $user->first_name,
        '$last_name'  => $user->last_name,
        '$email'      => $user->user_email,
        '$created'    => date( 'Y-m-d\TH:i:s', strtotime( $user->user_registered ) ),
        '$ip'         => mx_get_client_ip(),
    );
}

//endregion


//region PROPERTIES for EVENTS

function get_super_properties( $user ) {
    return array(
        'user.id'    => $user->ID,
        'user.login' => $user->user_login,
    );
}

function get_user_properties( $user ) {
    return array(
        'user.login'      => $user->user_login,
        'user.first_name' => $user->first_name,
        'user.last_name'  => $user->last_name,
        'user.email'      => $user->user_email,
        'user.created_at' => date( 'Y-m-d\TH:i:s', strtotime( $user->user_registered ) ),
    );
}

function get_registration_properties( $user ) {
    return array(
        'user.source'     => "email",
        'user.created_at' => date( 'Y-m-d\TH:i:s', strtotime( $user->user_registered ) ),
    );
}

//endregion


//region AUTH events

add_action( 'set_current_user', function(){

    $user = get_user_by( 'id', get_current_user_id() );

    //if you need to force set user on login/logout/registration
    MXTracker::instance()->set_user( $user );
    //update super props
    set_super_props( $user );

    $attrs = get_user_attributes( $user );
    MXTracker::instance()->api_set_user_attributes( $attrs );
    MXTracker::instance()->api_track_event('auth.login.success' );
}, 10, 2 );

add_action( 'clear_auth_cookie', function(){

    $user = get_user_by( 'id', get_current_user_id() );

    //if you need to force set user on login/logout/registration
    MXTracker::instance()->set_user( $user );
    //update super props
    set_super_props( $user );

    MXTracker::instance()->api_track_event('auth.logout' );
});

add_action( 'user_register', function ( $user_id ) {

    $user = get_user_by( 'id', $user_id );

    //if you need to force set user on login/logout/registration
    MXTracker::instance()->set_user( $user );
    //update super props
    set_super_props( $user );

    $props = get_registration_properties( $user );
    MXTracker::instance()->api_track_event('user.registration',$props );
}, 10, 1 );

//endregion


//region EXAMPLE event
//
//add_action('woocommerce_add_to_cart', function ($cart_item_key, $_product_id, $quantity, $variation_id, $variation, $cart_item_data) {
//    global $woocommerce;
//    $product_id = $woocommerce->cart->cart_contents[$cart_item_key]['product_id'];
//    $product = wc_get_product($product_id);
//    $cart = $woocommerce->cart;
//
//    $product_props = get_product_properties( $product );
//    $cart_props = get_cart_basic_properties( $cart );
//    MXTracker::instance()->api_track_event('product.addcart', array_merge($product_props,$cart_props) );
//});
//
//endregion
