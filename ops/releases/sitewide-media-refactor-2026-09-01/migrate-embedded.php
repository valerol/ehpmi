<?php
/** Complete the release by updating media-text and featured-image alternatives. */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "Run this file through WP-CLI.\n" );
    exit( 1 );
}
if ( 'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) ) || 'nykvymmy_ehpmidev' !== DB_NAME ) {
    WP_CLI::error( 'Refusing to run outside the EHPMI dev site and dev database.' );
}

$spec  = require __DIR__ . '/spec.php';
$apply = isset( $args ) && in_array( 'apply', $args, true );
$marker = get_option( 'ehpmi_sitewide_media_refactor_version' );
if ( $spec['version'] === $marker ) {
    WP_CLI::success( 'Embedded and featured media alternatives are already migrated; no changes made.' );
    return;
}
if ( $spec['base_version'] !== $marker ) {
    WP_CLI::error( 'The base site-wide media migration marker is missing.' );
}

$set_image_alt = static function ( $html, $attachment_id, $alt ) {
    if ( ! is_string( $html ) || '' === $html ) {
        return $html;
    }
    $processor = new WP_HTML_Tag_Processor( $html );
    while ( $processor->next_tag( array('tag_name' => 'IMG') ) ) {
        $classes = ' ' . (string) $processor->get_attribute( 'class' ) . ' ';
        if ( false !== strpos( $classes, ' wp-image-' . $attachment_id . ' ' ) ) {
            if ( '' !== (string) $processor->get_attribute( 'alt' ) ) {
                WP_CLI::error( 'Expected an empty media-text alt for attachment ' . $attachment_id . '.' );
            }
            $processor->set_attribute( 'alt', $alt );
        }
    }
    return $processor->get_updated_html();
};
$set_block_html = static function ( $block, $callback ) {
    $block['innerHTML'] = $callback( $block['innerHTML'] );
    foreach ( $block['innerContent'] as $index => $fragment ) {
        if ( is_string( $fragment ) ) {
            $block['innerContent'][ $index ] = $callback( $fragment );
        }
    }
    return $block;
};

$stats = array(
    'posts_changed' => 0,
    'media_text_block_alts' => 0,
    'attachment_meta_alts' => 0,
    'featured_meta_alts' => 0,
);
$seen = array();
$transform = static function ( $blocks ) use ( &$transform, &$stats, &$seen, $spec, $set_image_alt, $set_block_html ) {
    foreach ( $blocks as &$block ) {
        if ( ! empty( $block['innerBlocks'] ) ) {
            $block['innerBlocks'] = $transform( $block['innerBlocks'] );
        }
        if ( 'core/media-text' !== $block['blockName'] || empty( $block['attrs']['mediaId'] ) ) {
            continue;
        }
        $attachment_id = (int) $block['attrs']['mediaId'];
        if ( ! isset( $spec['media_text_alts'][ $attachment_id ] ) ) {
            continue;
        }
        $alt = $spec['media_text_alts'][ $attachment_id ];
        $block = $set_block_html(
            $block,
            static function ( $html ) use ( $attachment_id, $alt, $set_image_alt ) {
                return $set_image_alt( $html, $attachment_id, $alt );
            }
        );
        $seen[ $attachment_id ] = true;
        ++$stats['media_text_block_alts'];
    }
    unset( $block );
    return $blocks;
};

foreach ( $spec['media_text_alts'] as $attachment_id => $alt ) {
    $expected_previous = isset( $spec['expected_previous_media_attachment_alts'][ $attachment_id ] )
        ? $spec['expected_previous_media_attachment_alts'][ $attachment_id ]
        : '';
    if ( $expected_previous !== (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) {
        WP_CLI::error( 'Media-text attachment alt differs from the accepted audit for ' . $attachment_id . '.' );
    }
}
foreach ( $spec['featured_alts'] as $attachment_id => $alt ) {
    if ( '' !== (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) {
        WP_CLI::error( 'Featured attachment alt differs from the accepted audit for ' . $attachment_id . '.' );
    }
}

$post_updates = array();
foreach ( $spec['post_ids'] as $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || 'publish' !== $post->post_status ) {
        WP_CLI::error( 'Expected published content record is missing: ' . $post_id . '.' );
    }
    $before = $post->post_content;
    $after  = serialize_blocks( $transform( parse_blocks( $before ) ) );
    if ( $before !== $after ) {
        $post_updates[ $post_id ] = $after;
    }
}
$stats['posts_changed'] = count( $post_updates );
if ( 17 !== count( $seen ) || 17 !== $stats['media_text_block_alts'] || 11 !== $stats['posts_changed'] ) {
    WP_CLI::error( 'Unexpected embedded-media transformation counts: ' . wp_json_encode( $stats ) );
}

if ( ! $apply ) {
    WP_CLI::success( 'Dry run; no changes made: ' . wp_json_encode( $stats ) );
    return;
}

global $wpdb;
$wpdb->query( 'START TRANSACTION' );
try {
    foreach ( $post_updates as $post_id => $content ) {
        $updated = wp_update_post( array('ID' => $post_id, 'post_content' => $content), true );
        if ( is_wp_error( $updated ) ) {
            throw new RuntimeException( 'Post ' . $post_id . ': ' . $updated->get_error_message() );
        }
    }
    foreach ( $spec['media_text_alts'] as $attachment_id => $alt ) {
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
        ++$stats['attachment_meta_alts'];
    }
    foreach ( $spec['featured_alts'] as $attachment_id => $alt ) {
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
        ++$stats['featured_meta_alts'];
    }
    update_option( 'ehpmi_sitewide_media_refactor_version', $spec['version'], false );
    $wpdb->query( 'COMMIT' );
} catch ( Throwable $error ) {
    $wpdb->query( 'ROLLBACK' );
    WP_CLI::error( 'Embedded-media migration rolled back: ' . $error->getMessage() );
}

foreach ( array_keys( $post_updates ) as $post_id ) {
    clean_post_cache( $post_id );
}
foreach ( array_unique( array_merge( array_keys( $spec['media_text_alts'] ), array_keys( $spec['featured_alts'] ) ) ) as $attachment_id ) {
    clean_post_cache( $attachment_id );
}

WP_CLI::success( 'Embedded and featured media migration applied: ' . wp_json_encode( $stats ) );
