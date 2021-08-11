<?php
// https://wordpress.stackexchange.com/questions/231137/wp-rest-api-how-to-get-featured-image

function post_featured_image_json( $data, $post, $context ) {
    $featured_image_id = $data->data['featured_media']; // get featured image id
    $featured_image_url = wp_get_attachment_image_src( $featured_image_id, 'original' ); // get url of the original size

    if( $featured_image_url ) {
        $data->data['featured_image_url'] = $featured_image_url[0];
    }

    if (isset($data->data['categories']) && is_array($data->data['categories'])){
        $data->data['categories_name'] = array();
        foreach ($data->data['categories'] as $key => $value) {
            array_push($data->data['categories_name'], array('name' => get_the_category_by_ID($value), 'id' => $value));
        }
    }

    return $data;
}
add_filter( 'rest_prepare_post', 'post_featured_image_json', 10, 3 );
