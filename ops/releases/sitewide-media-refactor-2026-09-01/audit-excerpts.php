<?php
/** Read-only image audit for excerpts rendered above article content. */

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
    $post = get_post( $post_id );
    if ( ! $post || false === stripos( $post->post_excerpt, '<img' ) ) {
        continue;
    }
    $processor = new WP_HTML_Tag_Processor( $post->post_excerpt );
    $images = array();
    while ( $processor->next_tag( array('tag_name' => 'IMG') ) ) {
        $classes = (string) $processor->get_attribute( 'class' );
        preg_match( '/(?:^|\\s)wp-image-(\\d+)(?:\\s|$)/', $classes, $match );
        $images[] = array(
            'id' => isset( $match[1] ) ? (int) $match[1] : 0,
            'src' => (string) $processor->get_attribute( 'src' ),
            'alt' => (string) $processor->get_attribute( 'alt' ),
        );
    }
    $rows[] = array(
        'post_id' => $post_id,
        'title' => get_the_title( $post_id ),
        'url' => get_permalink( $post_id ),
        'images' => $images,
    );
}
echo wp_json_encode( $rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
