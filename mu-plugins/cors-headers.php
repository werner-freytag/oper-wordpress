<?php

function initCors( $value ) {
  $origin_url = 'capacitor://localhost';

  // Check if production environment or not
  // if (ENVIRONMENT === 'production') {
  //   $origin_url = 'SOME APP STUFF';
  // }

  header( 'Access-Control-Allow-Origin: *' );
  // header( 'Access-Control-Allow-Origin: http://localhost:4200/*' );
  // header( 'Access-Control-Allow-Origin: ' . $origin_url );
  header( 'Access-Control-Allow-Methods: GET, POST' );
  header( 'Access-Control-Allow-Credentials: true' );

    // add X-Cache-Variant
    header( 'Access-Control-Allow-Headers: Authorization, X-WP-Nonce, Content-Disposition, Content-MD5, Content-Type, X-Cache-Variant');

    return $value;
}

add_action( 'rest_api_init', function() {

  remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
  add_filter( 'rest_pre_serve_request', 'initCors');
}, 15 );

// function add_cors_http_header(){
//     header("Access-Control-Allow-Origin: *");
// }
// add_action('init','add_cors_http_header');

function add_cors_http_header(){
    header("Access-Control-Allow-Origin: *");
}
add_action('init','add_cors_http_header');
