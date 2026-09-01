<?php
/** Read-only audit of images embedded by core/media-text blocks. */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "Run this file through WP-CLI.\n" );
    exit( 1 );
}
if ( 'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) ) || 'nykvymmy_ehpmidev' !== DB_NAME ) {
    WP_CLI::error( 'Audit target is not the EHPMI dev site and dev database.' );
}

$spec = require __DIR__ . '/spec.php';
$rows = array();
$walk = static function ( $blocks, $post_id ) use ( &$walk, &$rows ) {
    foreach ( $blocks as $block ) {
        if ( 'core/media-text' === $block['blockName'] && false !== stripos( $block['innerHTML'], '<img' ) ) {
            $processor = new WP_HTML_Tag_Processor( $block['innerHTML'] );
            while ( $processor->next_tag( array('tag_name' => 'IMG') ) ) {
                $classes = (string) $processor->get_attribute( 'class' );
                preg_match( '/(?:^|\\s)wp-image-(\\d+)(?:\\s|$)/', $classes, $match );
                $id = isset( $match[1] ) ? (int) $match[1] : (int) ($block['attrs']['mediaId'] ?? 0);
                $rows[] = array(
                    'post_id' => $post_id,
                    'attrs' => $block['attrs'],
                    'id' => $id,
                    'src' => (string) $processor->get_attribute( 'src' ),
                    'filename' => $id ? (string) get_post_meta( $id, '_wp_attached_file', true ) : '',
                    'block_alt' => (string) $processor->get_attribute( 'alt' ),
                    'attachment_alt' => $id ? (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) : '',
                );
            }
        }
        if ( ! empty( $block['innerBlocks'] ) ) {
            $walk( $block['innerBlocks'], $post_id );
        }
    }
};
foreach ( $spec['post_ids'] as $post_id ) {
    $post = get_post( $post_id );
    if ( $post ) {
        $walk( parse_blocks( $post->post_content ), $post_id );
    }
}
echo wp_json_encode( $rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
