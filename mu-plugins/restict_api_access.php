<?php
# Checks if a string ends in a string
function endsWith($haystack, $needle)
{
    return substr($haystack, -strlen($needle)) === $needle;
}

add_filter('rest_authentication_errors', function ($result) {

    if ('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
        return $result;
    }

    if (strpos($_SERVER['REQUEST_URI'], 'jwt-auth') != false) {
        return $result;
    }

    if (strpos($_SERVER['REQUEST_URI'], 'paypal') != false) {
        return $result;
    }

    if (strpos($_SERVER['REQUEST_URI'], 'wc_stripe') != false) {
        return $result;
    }

    if (strpos($_SERVER['REQUEST_URI'], 'sosu') != false) {
        return $result;
    }

    if (strpos($_SERVER['REQUEST_URI'], 'wp/v2/categories') != false) {
        return $result;
    }

    if (strpos($_SERVER['REQUEST_URI'], 'wc/v1/customers') != false) {
        return $result;
    }

    if (strpos($_SERVER['REQUEST_URI'], 'v2/posts') != false) {
        return $result;
    }

    if (strpos($_SERVER['REQUEST_URI'], '3460?_embed') != false) {
        return $result;
    }

    if (strpos($_SERVER['REQUEST_URI'], 'wp-mail-smtp') != false) {
        return $result;
    }

    if (endsWith($_SERVER['REQUEST_URI'], '/wp-json')) {
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
    if (checkUserRightsToViewContent()) {
        return $result;
    }

    // If used has no autorized roles. Block.
    return new WP_Error(
        'Rest_not_allowed',
        __('You have no valid authorization.'),
        array('status' => 403)
    );
});

/**
 * @return bool
 */
function checkUserRightsToViewContent()
{
    $user = wp_get_current_user();
    return in_array('administrator', (array)$user->roles) || in_array('editor', (array)$user->roles) || in_array('paywall_access', (array)$user->roles);
}

function checkSlug($data)
{
    switch ($data->data['slug']) {
        case 'aktuelle-ausgabe':
        case 'aktuelle-ausgabe-app ':
        case 'liebe-leserin-lieber-leser':
        case 'editorial':
        case 'editorial-2':
        case 'impressum':
        case 'banner-container':
        case 'datenschutzerklaerung':
        case 'datenschutzerklaerung-app':
        case 'e-paper-text-in-app':
        case 'login-text-app':
        case 'account-text-app':
        case 'oper-oktober-2021-2':
            return true;
    }
    return false;
}

function post_restrict_content_user_json($data, $post, $context)
{
    if (!checkUserRightsToViewContent() && !checkSlug($data)) {
        if (isset($data->data['content'])) {
            $data->data['content']['rendered'] = '';
        }
    }
    return $data;
}

add_filter('rest_prepare_post', 'post_restrict_content_user_json', 10, 3);

/*add_filter('woocommerce_rest_check_permissions',
    function ($permission, $context, $object_id, $post_type) {
        if ($context !== 'read') {
            return $permission;
        }

        // $post_type_object = get_post_type_object($post_type);
//
        // if ($object_id) {
        //     return current_user_can($post_type_object->cap->read_post, $object_id);
        // }
//
        // return $post_type_object->has_archive;
        return true;

    }, 10, 4);*/
