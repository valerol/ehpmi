<?php
/**
 * Replace one/two-item Gallery blocks with the accepted EHPMI media grammar.
 *
 * Dry run:
 *   wp eval-file /absolute/path/to/migrate.php
 * Apply on dev only:
 *   wp eval-file /absolute/path/to/migrate.php apply
 */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "Run this file through WP-CLI.\n" );
    exit( 1 );
}

if ( 'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) ) || 'nykvymmy_ehpmidev' !== DB_NAME ) {
    WP_CLI::error( 'Refusing to run outside the EHPMI dev site and dev database.' );
}

$apply             = isset( $args ) && in_array( 'apply', $args, true );
$migration_version = '2026-09-01-v1';

if ( $migration_version === get_option( 'ehpmi_media_layout_migration_version' ) ) {
    WP_CLI::success( 'Media layout is already migrated; no changes made.' );
    return;
}

$make_column = static function ( $image_block ) {
    return array(
        'blockName'    => 'core/column',
        'attrs'        => array(),
        'innerBlocks'  => array( $image_block ),
        'innerHTML'    => '<div class="wp-block-column"></div>',
        'innerContent' => array( '<div class="wp-block-column">', null, '</div>' ),
    );
};

$make_pair = static function ( $images ) use ( $make_column ) {
    return array(
        'blockName'    => 'core/columns',
        'attrs'        => array( 'className' => 'is-style-ehpmi-image-pair' ),
        'innerBlocks'  => array_map( $make_column, $images ),
        'innerHTML'    => '<div class="wp-block-columns is-style-ehpmi-image-pair"></div>',
        'innerContent' => array(
            '<div class="wp-block-columns is-style-ehpmi-image-pair">',
            null,
            null,
            '</div>',
        ),
    );
};

$stats = array(
    'posts_scanned'            => 0,
    'posts_changed'            => 0,
    'single_gallery_to_image'  => 0,
    'two_gallery_to_pair'      => 0,
    'three_plus_gallery_kept'  => 0,
    'unsupported_gallery_kept' => 0,
);

$transform = static function ( $blocks ) use ( &$transform, &$stats, $make_pair ) {
    $result = array();

    foreach ( $blocks as $block ) {
        if ( 'core/gallery' === $block['blockName'] ) {
            $images = array_values(
                array_filter(
                    $block['innerBlocks'],
                    static function ( $inner ) {
                        return 'core/image' === $inner['blockName'];
                    }
                )
            );

            if ( count( $images ) !== count( $block['innerBlocks'] ) ) {
                ++$stats['unsupported_gallery_kept'];
                $result[] = $block;
                continue;
            }

            if ( 1 === count( $images ) ) {
                ++$stats['single_gallery_to_image'];
                $result[] = $images[0];
                continue;
            }

            if ( 2 === count( $images ) ) {
                ++$stats['two_gallery_to_pair'];
                $result[] = $make_pair( $images );
                continue;
            }

            ++$stats['three_plus_gallery_kept'];
            $result[] = $block;
            continue;
        }

        if ( ! empty( $block['innerBlocks'] ) ) {
            $block['innerBlocks'] = $transform( $block['innerBlocks'] );
        }

        $result[] = $block;
    }

    return $result;
};

$collect_image_ids = static function ( $blocks ) use ( &$collect_image_ids ) {
    $ids = array();
    foreach ( $blocks as $block ) {
        if ( 'core/image' === $block['blockName'] && ! empty( $block['attrs']['id'] ) ) {
            $ids[] = (int) $block['attrs']['id'];
        }
        if ( ! empty( $block['innerBlocks'] ) ) {
            $ids = array_merge( $ids, $collect_image_ids( $block['innerBlocks'] ) );
        }
    }
    return $ids;
};

$posts = get_posts(
    array(
        'post_type'      => array( 'post', 'page', 'project', 'staff_member', 'member', 'partner', 'testimonial' ),
        'post_status'    => array( 'publish', 'draft', 'private' ),
        'posts_per_page' => -1,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    )
);

$updates = array();
foreach ( $posts as $post ) {
    ++$stats['posts_scanned'];
    $before_blocks = parse_blocks( $post->post_content );
    $before_ids    = $collect_image_ids( $before_blocks );
    $after_blocks  = $transform( $before_blocks );
    $after_ids     = $collect_image_ids( $after_blocks );
    $after_content = serialize_blocks( $after_blocks );

    if ( $before_ids !== $after_ids ) {
        WP_CLI::error( 'Attachment sequence changed while transforming post ' . $post->ID . '.' );
    }

    if ( $after_content !== $post->post_content ) {
        ++$stats['posts_changed'];
        $updates[ $post->ID ] = $after_content;
    }
}

if ( 145 !== $stats['single_gallery_to_image'] + $stats['two_gallery_to_pair'] + $stats['three_plus_gallery_kept'] + $stats['unsupported_gallery_kept'] ) {
    WP_CLI::error( 'Expected to inspect exactly 145 Gallery blocks: ' . wp_json_encode( $stats ) );
}
if ( 56 !== $stats['single_gallery_to_image'] || 87 !== $stats['two_gallery_to_pair'] || 2 !== $stats['three_plus_gallery_kept'] ) {
    WP_CLI::error( 'Gallery distribution differs from the accepted audit: ' . wp_json_encode( $stats ) );
}
if ( $stats['unsupported_gallery_kept'] ) {
    WP_CLI::error( 'Unsupported Gallery content found: ' . wp_json_encode( $stats ) );
}

if ( ! $apply ) {
    WP_CLI::success( 'Dry run; no changes made: ' . wp_json_encode( $stats ) );
    return;
}

global $wpdb;
$wpdb->query( 'START TRANSACTION' );

try {
    foreach ( $updates as $post_id => $post_content ) {
        $updated = wp_update_post(
            array(
                'ID'           => $post_id,
                'post_content' => $post_content,
            ),
            true
        );
        if ( is_wp_error( $updated ) ) {
            throw new RuntimeException( 'Could not update post ' . $post_id . ': ' . $updated->get_error_message() );
        }
    }

    update_option( 'ehpmi_media_layout_migration_version', $migration_version, false );
    $wpdb->query( 'COMMIT' );
} catch ( Throwable $error ) {
    $wpdb->query( 'ROLLBACK' );
    WP_CLI::error( 'Media layout migration rolled back: ' . $error->getMessage() );
}

foreach ( array_keys( $updates ) as $post_id ) {
    clean_post_cache( $post_id );
}

WP_CLI::success( 'Media layout migration applied: ' . wp_json_encode( $stats ) );
