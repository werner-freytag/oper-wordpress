<?php
add_action('rest_api_init', function () {
    register_rest_route('sosu/v1', 'check_order', array(
        'methods' => 'GET',
        'callback' => 'sosu_check_order',
    ));
});

function sosu_check_order(WP_REST_Request $request)
{
    // $order = wc_get_order( 9709 );
    // $order_data = $order->get_data();
    // return $order_data;
    return "";
}