<?php
/**
 * Read-only verification for the EHPMI media-layout migration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "Run this file through WP-CLI.\n" );
    exit( 1 );
}

if ( 'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) ) || 'nykvymmy_ehpmidev' !== DB_NAME ) {
    WP_CLI::error( 'Refusing to verify outside the EHPMI dev site and dev database.' );
}

$counts = array(
    'image_blocks'       => 0,
    'gallery_blocks'     => 0,
    'small_galleries'    => 0,
    'three_galleries'    => 0,
    'image_pair_columns' => 0,
);
$image_ids = array();

$walk = static function ( $blocks ) use ( &$walk, &$counts, &$image_ids ) {
    foreach ( $blocks as $block ) {
        $attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

        if ( 'core/image' === $block['blockName'] ) {
            ++$counts['image_blocks'];
            if ( ! empty( $attrs['id'] ) ) {
                $image_ids[] = (int) $attrs['id'];
            }
        }

        if ( 'core/gallery' === $block['blockName'] ) {
            ++$counts['gallery_blocks'];
            $images = array_filter(
                $block['innerBlocks'],
                static function ( $inner ) {
                    return 'core/image' === $inner['blockName'];
                }
            );
            if ( count( $images ) < 3 ) {
                ++$counts['small_galleries'];
            }
            if ( 3 === count( $images ) ) {
                ++$counts['three_galleries'];
            }
        }

        if ( 'core/columns' === $block['blockName'] && 'is-style-ehpmi-image-pair' === ( $attrs['className'] ?? '' ) ) {
            ++$counts['image_pair_columns'];
        }

        if ( ! empty( $block['innerBlocks'] ) ) {
            $walk( $block['innerBlocks'] );
        }
    }
};

$posts = get_posts(
    array(
        'post_type'      => array( 'post', 'page', 'project', 'staff_member', 'member', 'partner', 'testimonial' ),
        'post_status'    => array( 'publish', 'draft', 'private' ),
        'posts_per_page' => -1,
    )
);
foreach ( $posts as $post ) {
    $walk( parse_blocks( $post->post_content ) );
}

$failures = array();
$check = static function ( $condition, $message ) use ( &$failures ) {
    if ( ! $condition ) {
        $failures[] = $message;
    }
};

$sizes   = apply_filters(
    'intermediate_image_sizes_advanced',
    array(
        'thumbnail'  => array(),
        'medium'     => array(),
        'medium_large' => array(),
        'large'      => array(),
        '1536x1536'  => array(),
        '2048x2048'  => array(),
    )
);

$check( '2026-09-01-v1' === get_option( 'ehpmi_media_layout_migration_version' ), 'Migration marker is missing.' );
$check( 260 === $counts['image_blocks'], 'Expected 260 Image blocks: ' . wp_json_encode( $counts ) );
$check( 2 === $counts['gallery_blocks'], 'Only two real Gallery blocks should remain: ' . wp_json_encode( $counts ) );
$check( 0 === $counts['small_galleries'], 'One/two-item Gallery blocks remain.' );
$check( 2 === $counts['three_galleries'], 'Expected two three-image galleries.' );
$check( 87 === $counts['image_pair_columns'], 'Expected 87 EHPMI image pairs.' );
$check( ! isset( $sizes['1536x1536'], $sizes['2048x2048'] ), 'Redundant 1536/2048 image sizes remain enabled.' );
$check( array() === apply_filters( 'image_editor_output_format', array() ), 'Runtime format conversion must remain disabled.' );
$check( 82 === apply_filters( 'wp_editor_set_quality', 86, 'image/webp' ), 'WebP quality is not 82.' );
$check( defined( 'EHPMI_CORE_VERSION' ) && '1.1.0' === EHPMI_CORE_VERSION, 'EHPMI Core 1.1.0 is not active.' );
$check( '1.5' === wp_get_theme()->get( 'Version' ), 'EHPMI theme 1.5 is not active.' );
$check( 467 === (int) wp_count_posts( 'attachment' )->inherit, 'Attachment count changed.' );

global $wpdb;
$check( 172 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}newsletter" ), 'Newsletter subscriber count changed.' );
$check( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}newsletter_sent" ), 'Newsletter sent table changed.' );

if ( $failures ) {
    WP_CLI::error( implode( "\n", $failures ) );
}

WP_CLI::success(
    'Verified media layout: ' . wp_json_encode(
        array(
            'counts'                 => $counts,
            'referenced_image_ids'   => count( array_unique( $image_ids ) ),
            'runtime_format_conversion' => false,
            'webp_quality'           => apply_filters( 'wp_editor_set_quality', 86, 'image/webp' ),
        )
    )
);
