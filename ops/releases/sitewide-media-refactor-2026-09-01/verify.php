<?php
/** Read-only verification for the site-wide EHPMI media refactor. */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "Run this file through WP-CLI.\n" );
    exit( 1 );
}
if ( 'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) ) || 'nykvymmy_ehpmidev' !== DB_NAME ) {
    WP_CLI::error( 'Verification target is not the EHPMI dev site and dev database.' );
}

$spec = require __DIR__ . '/spec.php';
if ( $spec['version'] !== get_option( 'ehpmi_sitewide_media_refactor_version' ) ) {
    WP_CLI::error( 'Migration marker is missing or differs.' );
}

$path_from_src = static function ( $src ) {
    $path = wp_parse_url( html_entity_decode( (string) $src ), PHP_URL_PATH );
    return is_string( $path ) ? rawurldecode( $path ) : '';
};
$collect_image_ids = static function ( $blocks ) use ( &$collect_image_ids ) {
    $ids = array();
    foreach ( $blocks as $block ) {
        if ( 'core/image' === $block['blockName'] ) {
            $ids[] = empty( $block['attrs']['id'] ) ? 0 : (int) $block['attrs']['id'];
        }
        if ( ! empty( $block['innerBlocks'] ) ) {
            $ids = array_merge( $ids, $collect_image_ids( $block['innerBlocks'] ) );
        }
    }
    return $ids;
};

$stats = array(
    'records' => 0,
    'images' => 0,
    'meaningful_block_alts' => 0,
    'unique_attachment_ids' => 0,
    'attachment_meta_alts' => 0,
    'pair_styles' => 0,
    'wide_images' => 0,
);
$attachment_ids = array();
$seen_pair_specs = array();
$seen_wide_ids = array();
$seen_wide_legacy = array();
$seen_required_alts = array();
$seen_legacy_alts = array();

$walk = static function ( $blocks, $post_id ) use (
    &$walk, &$stats, &$attachment_ids, &$seen_pair_specs, &$seen_wide_ids, &$seen_wide_legacy,
    &$seen_required_alts, &$seen_legacy_alts, $spec, $collect_image_ids, $path_from_src
) {
    foreach ( $blocks as $block ) {
        if ( 'core/media-text' === $block['blockName'] && ! empty( $block['attrs']['mediaId'] ) ) {
            ++$stats['images'];
            $id = (int) $block['attrs']['mediaId'];
            $processor = new WP_HTML_Tag_Processor( $block['innerHTML'] );
            $alt = '';
            if ( $processor->next_tag( array('tag_name' => 'IMG') ) ) {
                $alt = (string) $processor->get_attribute( 'alt' );
            }
            if ( '' === trim( $alt ) ) {
                WP_CLI::error( 'Empty media-text alt remains in post ' . $post_id . ' for attachment ' . $id . '.' );
            }
            ++$stats['meaningful_block_alts'];
            $attachment_ids[ $id ] = true;
            if ( isset( $spec['media_text_alts'][ $id ] ) ) {
                if ( $spec['media_text_alts'][ $id ] !== $alt ) {
                    WP_CLI::error( 'Media-text block alt differs for attachment ' . $id . '.' );
                }
                $seen_required_alts[ 'media-' . $id ] = true;
            }
        }
        if ( 'core/image' === $block['blockName'] ) {
            ++$stats['images'];
            $id = empty( $block['attrs']['id'] ) ? 0 : (int) $block['attrs']['id'];
            $processor = new WP_HTML_Tag_Processor( $block['innerHTML'] );
            $alt = '';
            $path = '';
            if ( $processor->next_tag( array('tag_name' => 'IMG') ) ) {
                $alt  = (string) $processor->get_attribute( 'alt' );
                $path = $path_from_src( $processor->get_attribute( 'src' ) );
            }
            if ( '' === trim( $alt ) ) {
                WP_CLI::error( 'Empty block alt remains in post ' . $post_id . ' for ' . ( $id ? 'attachment ' . $id : $path ) . '.' );
            }
            ++$stats['meaningful_block_alts'];
            if ( $id ) {
                $attachment_ids[ $id ] = true;
                if ( isset( $spec['attachment_alts'][ $id ] ) ) {
                    if ( $spec['attachment_alts'][ $id ] !== $alt ) {
                        WP_CLI::error( 'Block alt differs for attachment ' . $id . '.' );
                    }
                    $seen_required_alts[ $id ] = true;
                }
                if ( in_array( $id, $spec['wide_attachment_ids'], true ) ) {
                    if ( false === strpos( (string) ($block['attrs']['className'] ?? ''), 'is-style-ehpmi-wide' ) ) {
                        WP_CLI::error( 'Wide image style is missing for attachment ' . $id . '.' );
                    }
                    $seen_wide_ids[ $id ] = true;
                    ++$stats['wide_images'];
                }
            } elseif ( isset( $spec['legacy_alts'][ $post_id ][ $path ] ) ) {
                if ( $spec['legacy_alts'][ $post_id ][ $path ] !== $alt ) {
                    WP_CLI::error( 'Legacy block alt differs for ' . $path . '.' );
                }
                $seen_legacy_alts[ $post_id . '|' . $path ] = true;
            }
            if ( ! $id && isset( $spec['wide_legacy_paths'][ $post_id ] ) && in_array( $path, $spec['wide_legacy_paths'][ $post_id ], true ) ) {
                if ( false === strpos( (string) ($block['attrs']['className'] ?? ''), 'is-style-ehpmi-wide' ) ) {
                    WP_CLI::error( 'Wide legacy image style is missing for ' . $path . '.' );
                }
                $seen_wide_legacy[ $post_id . '|' . $path ] = true;
                ++$stats['wide_images'];
            }
        }

        if ( 'core/columns' === $block['blockName'] && isset( $spec['pair_styles'][ $post_id ] ) ) {
            $key = implode( ',', $collect_image_ids( $block['innerBlocks'] ) );
            foreach ( $spec['pair_styles'][ $post_id ] as $index => $pair_spec ) {
                $seen_key = $post_id . '|' . $index;
                if ( isset( $seen_pair_specs[ $seen_key ] ) || $key !== $pair_spec[0] ) {
                    continue;
                }
                if ( false === strpos( (string) ($block['attrs']['className'] ?? ''), $pair_spec[1] ) ) {
                    WP_CLI::error( 'Pair style differs in post ' . $post_id . ' for pair ' . $key . '.' );
                }
                $seen_pair_specs[ $seen_key ] = true;
                ++$stats['pair_styles'];
                break;
            }
        }
        if ( ! empty( $block['innerBlocks'] ) ) {
            $walk( $block['innerBlocks'], $post_id );
        }
    }
};

foreach ( $spec['post_ids'] as $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || 'publish' !== $post->post_status ) {
        WP_CLI::error( 'Expected published content record is missing: ' . $post_id . '.' );
    }
    ++$stats['records'];
    $walk( parse_blocks( $post->post_content ), $post_id );
}

foreach ( $attachment_ids as $attachment_id => $_true ) {
    if ( '' === trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ) {
        WP_CLI::error( 'Empty attachment metadata alt remains for ' . $attachment_id . '.' );
    }
    ++$stats['attachment_meta_alts'];
}
$stats['unique_attachment_ids'] = count( $attachment_ids );

foreach ( $spec['attachment_alts'] as $attachment_id => $alt ) {
    if ( $alt !== get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) {
        WP_CLI::error( 'Attachment metadata alt differs for ' . $attachment_id . '.' );
    }
}
foreach ( $spec['attachment_alt_sync'] as $attachment_id => $alt ) {
    if ( $alt !== get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) {
        WP_CLI::error( 'Synchronized attachment metadata alt differs for ' . $attachment_id . '.' );
    }
}
foreach ( $spec['media_text_alts'] as $attachment_id => $alt ) {
    if ( $alt !== get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) {
        WP_CLI::error( 'Media-text attachment metadata alt differs for ' . $attachment_id . '.' );
    }
}
foreach ( $spec['featured_alts'] as $attachment_id => $alt ) {
    if ( $alt !== get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) {
        WP_CLI::error( 'Featured attachment metadata alt differs for ' . $attachment_id . '.' );
    }
}

$expected = array(
    'records' => 44,
    'images' => 270,
    'meaningful_block_alts' => 270,
    'unique_attachment_ids' => 258,
    'attachment_meta_alts' => 258,
    'pair_styles' => 81,
    'wide_images' => 30,
);
if ( $expected !== $stats || count( $seen_required_alts ) !== 129 || count( $seen_legacy_alts ) !== 12 ||
    count( $seen_pair_specs ) !== 81 || count( $seen_wide_ids ) !== 23 || count( $seen_wide_legacy ) !== 7 ) {
    WP_CLI::error( 'Verification counts differ: ' . wp_json_encode( $stats ) );
}

$registry = WP_Block_Styles_Registry::get_instance();
foreach ( array('ehpmi-photo-pair-4x3', 'ehpmi-photo-pair-3x2', 'ehpmi-photo-pair-3x4') as $style_name ) {
    if ( ! $registry->is_registered( 'core/columns', $style_name ) ) {
        WP_CLI::error( 'Editor style is not registered: ' . $style_name );
    }
}

WP_CLI::success( 'Verified site-wide media refactor: ' . wp_json_encode( $stats ) );
