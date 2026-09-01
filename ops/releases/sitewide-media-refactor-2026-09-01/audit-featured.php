<?php
/** Read-only featured-image audit for the site-wide media release. */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "Run this file through WP-CLI.\n" );
    exit( 1 );
}
if ( 'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) ) || 'nykvymmy_ehpmidev' !== DB_NAME ) {
    WP_CLI::error( 'Audit target is not the EHPMI dev site and dev database.' );
}

$spec = require __DIR__ . '/spec.php';
$rows = array();
foreach ( $spec['post_ids'] as $post_id ) {
    $thumbnail_id = get_post_thumbnail_id( $post_id );
    if ( ! $thumbnail_id ) {
        continue;
    }
    $rows[] = array(
        'post_id' => $post_id,
        'title' => get_the_title( $post_id ),
        'url' => get_permalink( $post_id ),
        'thumbnail_id' => (int) $thumbnail_id,
        'alt' => (string) get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ),
        'filename' => (string) get_post_meta( $thumbnail_id, '_wp_attached_file', true ),
    );
}
echo wp_json_encode( $rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
