<?php
add_filter('rest_authentication_errors', function ($result) {

    if ('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
        return $result;
    }

    if (strpos($_SERVER['REQUEST_URI'], 'jwt-auth') != false) {
        return $result;
    }

    if (strpos($_SERVER['REQUEST_URI'], 'sosu') != false) {
        return $result;
    }

    if (strpos($_SERVER['REQUEST_URI'], '3460?_embed') != false) {
        return $result;
    }

    // if (strpos($_SERVER['REQUEST_URI'], 'wp_json') == false) {
    //     return $result;
    // }

    // If a previous authentication check was applied,
    // pass that result along without modification.
    if (true === $result || is_wp_error($result)) {
        return $result;
    }

    // No authentication has been performed yet.
    // Return an error if user is not logged in.
    if (!is_user_logged_in()) {
        return new WP_Error(
            'rest_not_logged_in',
            __('You are not currently logged in.'),
            array('status' => 401)
        );
    }

    // User authorized. Checking roles
    $user = wp_get_current_user();
    if (in_array('administrator', (array)$user->roles) || in_array('paywall_access', (array)$user->roles)) {
        return $result;
    }

    // If used has no autorized roles. Block.
    return new WP_Error(
        'Rest_not_allowed',
        __('You have no valid authorization.'),
        array('status' => 403)
    );
});
