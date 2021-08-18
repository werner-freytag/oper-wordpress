<?php
/** Requiere the JWT library. */

use Firebase\JWT\JWT;

function my_awesome_func(WP_REST_Request $request)
{

    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $request->get_param('token');;
    $redurl = $request->get_param('redurl');
    $token = validate_token_my(false);
    if ($token instanceof WP_Error) {
        wp_redirect(home_url() . '/' . $redurl);
        exit;
    }
    wp_set_auth_cookie($token->data->user->id, true);
    wp_redirect(home_url() . '/' . $redurl);
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('sosu/v1', '/auth/(?P<token>\S+)', array(
        'methods' => 'GET',
        'callback' => 'my_awesome_func',
    ));
});

function sosu_get_downloads(WP_REST_Request $request)
{

    // $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $request->get_param('token');;
    // $redurl = $request->get_param('redurl');
    $token = validate_token_my(false);
    if (is_wp_error($token)) {
        // wp_redirect(home_url() . '/' . $redurl);
        exit;
    }
    $userId = $token->data->user->id;
    // wp_set_auth_cookie($token->data->user->id, true);
    // wp_redirect(home_url() . '/' . $redurl);
    // $woocommerce->get('customers/2/downloads');

    // curl https://example.com/wp-json/wc/v1/customers/2/downloads \
    //    -u consumer_key:consumer_secret

    $args = ['headers' => [
        'Authorization' => 'Basic ck_dca5001d2900808af21af4d5a4780f96cdec7e49 cs_30359b3be6b6ace501041d5f779717575b8ca461',
    ]];

    $result = wp_remote_get(home_url() . '/wp-json/wc/v1/customers/'.$userId.'/downloads' , $args);

    exit;
}

// add_action('rest_api_init', function () {
//     register_rest_route('sosu/v1', 'downloads', array(
//         'methods' => 'GET',
//         'callback' => 'sosu_get_downloads',
//     ));
// });

function sosu_extend_user_json($data, $user)
{
    $data['user_id'] = $user->ID;
    return $data;
}

add_filter('jwt_auth_token_before_dispatch', 'sosu_extend_user_json', 10, 2);











/**
 * Main validation function, this function try to get the Autentication
 * headers and decoded.
 *
 * @param bool $output
 *
 * @return WP_Error | Object | Array
 */
function validate_token_my($output = true)
{
    /*
     * Looking for the HTTP_AUTHORIZATION header, if not present just
     * return the user.
     */
    $auth = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : false;
    /* Double check for different auth header string (server dependent) */
    if (!$auth) {
        $auth = isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : false;
    }

    if (!$auth) {
        return new WP_Error(
            'jwt_auth_no_auth_header',
            'Authorization header not found.',
            array(
                'status' => 403,
            )
        );
    }

    /*
     * The HTTP_AUTHORIZATION is present verify the format
     * if the format is wrong return the user.
     */
    list($token) = sscanf($auth, 'Bearer %s');
    if (!$token) {
        return new WP_Error(
            'jwt_auth_bad_auth_header',
            'Authorization header malformed.',
            array(
                'status' => 403,
            )
        );
    }

    /** Get the Secret Key */
    $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;
    if (!$secret_key) {
        return new WP_Error(
            'jwt_auth_bad_config',
            'JWT is not configurated properly, please contact the admin',
            array(
                'status' => 403,
            )
        );
    }

    /** Try to decode the token */
    try {
        $token = JWT::decode($token, $secret_key, array('HS256'));
        /** The Token is decoded now validate the iss */
        if ($token->iss != get_bloginfo('url')) {
            /** The iss do not match, return error */
            return new WP_Error(
                'jwt_auth_bad_iss',
                'The iss do not match with this server',
                array(
                    'status' => 403,
                )
            );
        }
        /** So far so good, validate the user id in the token */
        if (!isset($token->data->user->id)) {
            /** No user id in the token, abort!! */
            return new WP_Error(
                'jwt_auth_bad_request',
                'User ID not found in the token',
                array(
                    'status' => 403,
                )
            );
        }
        /** Everything looks good return the decoded token if the $output is false */
        if (!$output) {
            return $token;
        }
        /** If the output is true return an answer to the request to show it */
        return array(
            'code' => 'jwt_auth_valid_token',
            'data' => array(
                'status' => 200,
            ),
        );
    } catch (Exception $e) {
        /** Something is wrong trying to decode the token, send back the error */
        return new WP_Error(
            'jwt_auth_invalid_token',
            $e->getMessage(),
            array(
                'status' => 403,
            )
        );
    }
}