<?php
/** Requiere the JWT library. */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function my_awesome_func(WP_REST_Request $request)
{

    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $request->get_param('token');;
    $redurl = $request->get_param('redurl');
    $token = validate_token_my($request, $request->get_header( 'Authorization' ));
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
        'permission_callback' => '__return_true',
    ));
});

function sosu_get_downloads(WP_REST_Request $request)
{

    // $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $request->get_param('token');;
    // $redurl = $request->get_param('redurl');
    // $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $request->get_param('token');
    // $redurl = $request->get_param('redurl');

    $token = validate_token_my($request, $request->get_header( 'Authorization' ));
    // header( 'Access-Control-Allow-Origin: *' );
    // return $token;
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


    return wc_get_customer_available_downloads($userId);
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('sosu/v1', 'downloads', array(
        'methods' => 'GET',
        'callback' => 'sosu_get_downloads',
        'permission_callback' => '__return_true',
    ));
});

function sosu_extend_user_json($data, $user)
{
    $data['user_id'] = $user->ID;
    return $data;
}

add_filter('jwt_auth_token_before_dispatch', 'sosu_extend_user_json', 10, 2);

function get_algorithm_my() {
    $algorithm = apply_filters( 'jwt_auth_algorithm', 'HS256' );
    $supported_algorithms = [ 'HS256', 'HS384', 'HS512', 'RS256', 'RS384', 'RS512', 'ES256', 'ES384', 'ES512', 'PS256', 'PS384', 'PS512' ];
    if ( ! in_array( $algorithm, $supported_algorithms ) ) {
        return false;
    }

    return $algorithm;
}

/**
 * Main validation function, this function try to get the Authentication
 * headers and decoded.
 *
 * @param bool $output
 *
 * @return WP_Error | Object | Array
 */
function validate_token_my( WP_REST_Request $request, $custom_token = false ) {
    /*
     * Looking for the Authorization header
     *
     * There is two ways to get the authorization token
     *  1. via WP_REST_Request
     *  2. via custom_token, we get this for all the other API requests
     *
     * The get_header( 'Authorization' ) checks for the header in the following order:
     * 1. HTTP_AUTHORIZATION
     * 2. REDIRECT_HTTP_AUTHORIZATION
     *
     * @see https://core.trac.wordpress.org/ticket/47077
     */

    $auth_header = $custom_token ?: $request->get_header( 'Authorization' );

    if ( ! $auth_header ) {
        return new WP_Error(
            'jwt_auth_no_auth_header',
            'Authorization header not found.',
            [
                'status' => 403,
            ]
        );
    }

    /*
     * Extract the authorization header
     */
    [ $token ] = sscanf( $auth_header, 'Bearer %s' );

    /**
     * if the format is not valid return an error.
     */
    if ( ! $token ) {
        return new WP_Error(
            'jwt_auth_bad_auth_header',
            'Authorization header malformed.',
            [
                'status' => 403,
            ]
        );
    }

    /** Get the Secret Key */
    $secret_key = defined( 'JWT_AUTH_SECRET_KEY' ) ? JWT_AUTH_SECRET_KEY : false;
    if ( ! $secret_key ) {
        return new WP_Error(
            'jwt_auth_bad_config',
            'JWT is not configured properly, please contact the admin',
            [
                'status' => 403,
            ]
        );
    }

    /** Try to decode the token */
    try {
        $algorithm = get_algorithm_my();
        if ( $algorithm === false ) {
            return new WP_Error(
                'jwt_auth_unsupported_algorithm',
                __( 'Algorithm not supported, see https://www.rfc-editor.org/rfc/rfc7518#section-3', 'wp-api-jwt-auth' ),
                [
                    'status' => 403,
                ]
            );
        }

        $token = JWT::decode( $token, new Key( $secret_key, $algorithm ) );

        /** The Token is decoded now validate the iss */
        if ( $token->iss !== get_bloginfo( 'url' ) ) {
            /** The iss do not match, return error */
            return new WP_Error(
                'jwt_auth_bad_iss',
                'The iss do not match with this server',
                [
                    'status' => 403,
                ]
            );
        }

        /** So far so good, validate the user id in the token */
        if ( ! isset( $token->data->user->id ) ) {
            /** No user id in the token, abort!! */
            return new WP_Error(
                'jwt_auth_bad_request',
                'User ID not found in the token',
                [
                    'status' => 403,
                ]
            );
        }

        /** Everything looks good return the decoded token if we are using the custom_token */
        if ( $custom_token ) {
            return $token;
        }

        /** This is for the /toke/validate endpoint*/
        return [
            'code' => 'jwt_auth_valid_token',
            'data' => [
                'status' => 200,
            ],
        ];
    } catch ( Exception $e ) {
        /** Something were wrong trying to decode the token, send back the error */
        return new WP_Error(
            'jwt_auth_invalid_token',
            $e->getMessage(),
            [
                'status' => 403,
            ]
        );
    }
}
