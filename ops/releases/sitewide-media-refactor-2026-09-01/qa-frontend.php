<?php
/** Read-only HTTP smoke test for every record covered by the release. */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "Run this file through WP-CLI.\n" );
    exit( 1 );
}
if ( 'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) ) || 'nykvymmy_ehpmidev' !== DB_NAME ) {
    WP_CLI::error( 'QA target is not the EHPMI dev site and dev database.' );
}

$spec = require __DIR__ . '/spec.php';
$rows = array();
foreach ( $spec['post_ids'] as $post_id ) {
    $url = get_permalink( $post_id );
    $response = wp_remote_get( $url, array('timeout' => 30, 'redirection' => 3) );
    if ( is_wp_error( $response ) ) {
        WP_CLI::error( 'Request failed for post ' . $post_id . ': ' . $response->get_error_message() );
    }
    $status = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );
    if ( 200 !== $status || false === stripos( $body, '<h1' ) ) {
        WP_CLI::error( 'Frontend smoke test failed for post ' . $post_id . ' with HTTP ' . $status . '.' );
    }
    $rows[] = array('post_id' => $post_id, 'status' => $status, 'bytes' => strlen( $body ), 'url' => $url);
}
WP_CLI::success( 'Frontend smoke test passed: ' . wp_json_encode( array('records' => count( $rows ), 'http_200' => count( $rows )) ) );
