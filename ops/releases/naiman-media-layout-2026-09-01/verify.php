<?php
/** Read-only release verification for the Naiman media layout. */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "Run this file through WP-CLI.\n" );
    exit( 1 );
}

if ( 'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) ) || 'nykvymmy_ehpmidev' !== DB_NAME ) {
    WP_CLI::error( 'Verification target is not the EHPMI dev site and dev database.' );
}

$post_id = 761;
$expected_alts = array(
    771  => 'Landscape near Naiman showing a dark mound of mercury-contaminated waste.',
    1176 => 'Garmin GPS receiver beside silvery mercury droplets in exposed soil.',
    1179 => 'EHPMI project staff discussing mercury contamination with Naiman residents.',
    777  => 'Naiman residents and project team gathered in a village street.',
    774  => 'Map of mercury concentrations in topsoil along the irrigation channel in Naiman, Kyrgyzstan.',
    775  => 'Open roadside irrigation channel running through Naiman.',
    767  => 'Excavator loading contaminated soil into a dump truck beside the irrigation channel.',
    769  => 'Silvery mercury droplets visible in excavated soil.',
    770  => 'Workers excavating contaminated soil from the roadside irrigation channel.',
    766  => 'Excavator loading removed soil into a dump truck.',
    765  => 'Workers removing contaminated soil beside an excavator and dump truck.',
    764  => 'Dump truck unloading removed soil at the disposal site.',
    768  => 'Dump truck beside piles of removed soil at the disposal site.',
    1182 => 'Field researcher inspecting a well in Naiman as a child looks on.',
    1184 => 'Project team measuring mercury levels in soil beside the irrigation channel in Naiman.',
);

$post = get_post( $post_id );
if ( ! $post ) {
    WP_CLI::error( 'Project 761 is missing.' );
}

$blocks = parse_blocks( $post->post_content );
$styles = array();
$ids    = array();
$html_alts = array();

$walk = static function ( $items ) use ( &$walk, &$styles, &$ids, &$html_alts ) {
    foreach ( $items as $block ) {
        if ( 'core/columns' === $block['blockName'] && ! empty( $block['attrs']['className'] ) ) {
            $styles[] = $block['attrs']['className'];
        }
        if ( 'core/image' === $block['blockName'] && ! empty( $block['attrs']['id'] ) ) {
            $id = (int) $block['attrs']['id'];
            $ids[] = $id;
            $processor = new WP_HTML_Tag_Processor( $block['innerHTML'] );
            if ( $processor->next_tag( array( 'tag_name' => 'IMG' ) ) ) {
                $html_alts[ $id ] = (string) $processor->get_attribute( 'alt' );
            }
        }
        if ( ! empty( $block['innerBlocks'] ) ) {
            $walk( $block['innerBlocks'] );
        }
    }
};
$walk( $blocks );

$expected_styles = array(
    'is-style-ehpmi-photo-pair-4x3',
    'is-style-ehpmi-photo-pair-3x2',
    'is-style-ehpmi-photo-pair-4x3',
    'is-style-ehpmi-photo-pair-4x3',
    'is-style-ehpmi-photo-pair-4x3',
    'is-style-ehpmi-photo-pair-4x3',
    'is-style-ehpmi-photo-pair-3x4',
);

if ( array_keys( $expected_alts ) !== $ids ) {
    WP_CLI::error( 'Attachment order changed: ' . wp_json_encode( $ids ) );
}
if ( $expected_styles !== $styles ) {
    WP_CLI::error( 'Pair styles differ: ' . wp_json_encode( $styles ) );
}
if ( false === strpos( $post->post_content, '<!-- wp:image {"id":774,"sizeSlug":"large","linkDestination":"none","className":"is-style-ehpmi-wide"} -->' ) ) {
    WP_CLI::error( 'Map image is not stored as the EHPMI wide style.' );
}
if ( false === strpos( $post->post_content, 'Mercury concentrations measured in topsoil along the irrigation channel in Naiman, 2017.' ) ) {
    WP_CLI::error( 'Map caption is missing.' );
}

foreach ( $expected_alts as $attachment_id => $alt ) {
    if ( $alt !== get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) {
        WP_CLI::error( 'Attachment alt differs for ' . $attachment_id . '.' );
    }
    if ( ! isset( $html_alts[ $attachment_id ] ) || $alt !== $html_alts[ $attachment_id ] ) {
        WP_CLI::error( 'Block HTML alt differs for ' . $attachment_id . '.' );
    }
}

if ( '2026-09-01-v1' !== get_option( 'ehpmi_naiman_media_layout_version' ) ) {
    WP_CLI::error( 'Migration marker is missing.' );
}

$registry = WP_Block_Styles_Registry::get_instance();
foreach ( array( 'ehpmi-photo-pair-4x3', 'ehpmi-photo-pair-3x2', 'ehpmi-photo-pair-3x4' ) as $style_name ) {
    if ( ! $registry->is_registered( 'core/columns', $style_name ) ) {
        WP_CLI::error( 'Editor style is not registered: ' . $style_name );
    }
}

WP_CLI::success(
    'Verified Naiman media layout: ' . wp_json_encode(
        array(
            'post_id'          => $post_id,
            'images'           => count( $ids ),
            'meaningful_alts'  => count( $html_alts ),
            'styled_pairs'     => count( $styles ),
            'theme_version'    => wp_get_theme()->get( 'Version' ),
            'migration_marker' => get_option( 'ehpmi_naiman_media_layout_version' ),
        )
    )
);
