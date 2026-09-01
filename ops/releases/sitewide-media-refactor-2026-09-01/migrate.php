<?php
/** Apply the approved media-layout and alternative-text refactor. Use WP-CLI; pass `apply` to write. */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "Run this file through WP-CLI.\n" );
    exit( 1 );
}

if ( 'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) ) || 'nykvymmy_ehpmidev' !== DB_NAME ) {
    WP_CLI::error( 'Refusing to run outside the EHPMI dev site and dev database.' );
}

$spec  = require __DIR__ . '/spec.php';
$apply = isset( $args ) && in_array( 'apply', $args, true );

if ( $spec['base_version'] === get_option( 'ehpmi_sitewide_media_refactor_version' ) || $spec['version'] === get_option( 'ehpmi_sitewide_media_refactor_version' ) ) {
    WP_CLI::success( 'Site-wide media refactor is already applied; no changes made.' );
    return;
}

$path_from_src = static function ( $src ) {
    $path = wp_parse_url( html_entity_decode( (string) $src ), PHP_URL_PATH );
    return is_string( $path ) ? rawurldecode( $path ) : '';
};

$read_image = static function ( $html ) use ( $path_from_src ) {
    $result = array('alt' => null, 'path' => '');
    if ( ! is_string( $html ) || '' === $html ) {
        return $result;
    }
    $processor = new WP_HTML_Tag_Processor( $html );
    if ( $processor->next_tag( array('tag_name' => 'IMG') ) ) {
        $result['alt']  = $processor->get_attribute( 'alt' );
        $result['path'] = $path_from_src( $processor->get_attribute( 'src' ) );
    }
    return $result;
};

$set_tag_class = static function ( $html, $tag, $class_name ) {
    if ( ! is_string( $html ) || '' === $html ) {
        return $html;
    }
    $processor = new WP_HTML_Tag_Processor( $html );
    if ( ! $processor->next_tag( array('tag_name' => $tag) ) ) {
        return $html;
    }
    $classes = preg_split( '/\s+/', (string) $processor->get_attribute( 'class' ), -1, PREG_SPLIT_NO_EMPTY );
    $classes = array_values(
        array_filter(
            $classes,
            static function ( $class ) {
                return 0 !== strpos( $class, 'is-style-ehpmi-' );
            }
        )
    );
    $classes[] = $class_name;
    $processor->set_attribute( 'class', implode( ' ', array_unique( $classes ) ) );
    return $processor->get_updated_html();
};

$set_image_alt = static function ( $html, $attachment_id, $path, $alt ) use ( $path_from_src ) {
    if ( ! is_string( $html ) || '' === $html ) {
        return $html;
    }
    $processor = new WP_HTML_Tag_Processor( $html );
    while ( $processor->next_tag( array('tag_name' => 'IMG') ) ) {
        $matches = false;
        if ( $attachment_id ) {
            $classes = ' ' . (string) $processor->get_attribute( 'class' ) . ' ';
            $matches = false !== strpos( $classes, ' wp-image-' . $attachment_id . ' ' );
        } else {
            $matches = $path === $path_from_src( $processor->get_attribute( 'src' ) );
        }
        if ( $matches ) {
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
    'posts_changed'           => 0,
    'attachment_block_alts'   => 0,
    'legacy_block_alts'       => 0,
    'pair_styles'             => 0,
    'wide_images'             => 0,
    'attachment_meta_alts'    => 0,
    'attachment_meta_synced'  => 0,
);
$changed_posts = array();
$seen_alt_ids  = array();
$seen_legacy   = array();
$seen_wide_ids = array();
$seen_wide_legacy = array();
$seen_pairs    = array();

$transform = static function ( $blocks, $post_id ) use (
    &$transform, &$stats, &$seen_alt_ids, &$seen_legacy, &$seen_wide_ids, &$seen_wide_legacy, &$seen_pairs,
    $spec, $collect_image_ids, $read_image, $set_tag_class, $set_image_alt, $set_block_html
) {
    foreach ( $blocks as &$block ) {
        if ( ! empty( $block['innerBlocks'] ) ) {
            $block['innerBlocks'] = $transform( $block['innerBlocks'], $post_id );
        }

        if ( 'core/image' === $block['blockName'] ) {
            $attachment_id = empty( $block['attrs']['id'] ) ? 0 : (int) $block['attrs']['id'];
            $image          = $read_image( $block['innerHTML'] );
            $alt            = null;
            $is_legacy      = false;

            if ( $attachment_id && isset( $spec['attachment_alts'][ $attachment_id ] ) ) {
                $alt = $spec['attachment_alts'][ $attachment_id ];
                if ( '' !== (string) $image['alt'] ) {
                    WP_CLI::error( 'Expected an empty block alt for attachment ' . $attachment_id . ' in post ' . $post_id . '.' );
                }
                $seen_alt_ids[ $attachment_id ] = true;
            } elseif ( ! $attachment_id && isset( $spec['legacy_alts'][ $post_id ][ $image['path'] ] ) ) {
                $alt       = $spec['legacy_alts'][ $post_id ][ $image['path'] ];
                $is_legacy = true;
                if ( '' !== (string) $image['alt'] ) {
                    WP_CLI::error( 'Expected an empty legacy block alt for ' . $image['path'] . ' in post ' . $post_id . '.' );
                }
                $seen_legacy[ $post_id . '|' . $image['path'] ] = true;
            }

            if ( null !== $alt ) {
                $block = $set_block_html(
                    $block,
                    static function ( $html ) use ( $attachment_id, $image, $alt, $set_image_alt ) {
                        return $set_image_alt( $html, $attachment_id, $image['path'], $alt );
                    }
                );
                if ( $is_legacy ) {
                    ++$stats['legacy_block_alts'];
                } else {
                    ++$stats['attachment_block_alts'];
                }
            }

            $make_wide = $attachment_id && in_array( $attachment_id, $spec['wide_attachment_ids'], true );
            if ( ! $attachment_id && isset( $spec['wide_legacy_paths'][ $post_id ] ) ) {
                $make_wide = in_array( $image['path'], $spec['wide_legacy_paths'][ $post_id ], true );
            }
            if ( $make_wide ) {
                $block['attrs']['className'] = 'is-style-ehpmi-wide';
                $block = $set_block_html(
                    $block,
                    static function ( $html ) use ( $set_tag_class ) {
                        return $set_tag_class( $html, 'FIGURE', 'is-style-ehpmi-wide' );
                    }
                );
                if ( $attachment_id ) {
                    $seen_wide_ids[ $attachment_id ] = true;
                } else {
                    $seen_wide_legacy[ $post_id . '|' . $image['path'] ] = true;
                }
                ++$stats['wide_images'];
            }
        }

        if ( 'core/columns' === $block['blockName'] && isset( $spec['pair_styles'][ $post_id ] ) ) {
            $key = implode( ',', $collect_image_ids( $block['innerBlocks'] ) );
            foreach ( $spec['pair_styles'][ $post_id ] as $index => $pair_spec ) {
                if ( $key !== $pair_spec[0] ) {
                    continue;
                }
                $seen_key = $post_id . '|' . $index;
                if ( isset( $seen_pairs[ $seen_key ] ) ) {
                    continue;
                }
                $style = $pair_spec[1];
                $block['attrs']['className'] = $style;
                $block = $set_block_html(
                    $block,
                    static function ( $html ) use ( $style, $set_tag_class ) {
                        return $set_tag_class( $html, 'DIV', $style );
                    }
                );
                $seen_pairs[ $seen_key ] = true;
                ++$stats['pair_styles'];
                break;
            }
        }
    }
    unset( $block );
    return $blocks;
};

foreach ( $spec['attachment_alts'] as $attachment_id => $alt ) {
    $expected_previous = isset( $spec['expected_previous_attachment_alts'][ $attachment_id ] )
        ? $spec['expected_previous_attachment_alts'][ $attachment_id ]
        : '';
    if ( $expected_previous !== (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) {
        WP_CLI::error( 'Attachment metadata alt differs from the accepted audit for ' . $attachment_id . '.' );
    }
}
foreach ( $spec['attachment_alt_sync'] as $attachment_id => $alt ) {
    if ( '' !== (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) {
        WP_CLI::error( 'Expected empty attachment metadata alt before synchronization for ' . $attachment_id . '.' );
    }
}

$post_updates = array();
foreach ( $spec['post_ids'] as $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || 'publish' !== $post->post_status ) {
        WP_CLI::error( 'Expected published content record is missing: ' . $post_id . '.' );
    }
    $before = $post->post_content;
    $after  = serialize_blocks( $transform( parse_blocks( $before ), $post_id ) );
    if ( $before !== $after ) {
        $post_updates[ $post_id ] = $after;
    }
}

$expected_legacy = array_sum( array_map( 'count', $spec['legacy_alts'] ) );
$expected_pairs  = array_sum( array_map( 'count', $spec['pair_styles'] ) );
$expected_wide_legacy = array_sum( array_map( 'count', $spec['wide_legacy_paths'] ) );

if ( count( $seen_alt_ids ) !== count( $spec['attachment_alts'] ) ||
    count( $seen_legacy ) !== $expected_legacy ||
    count( $seen_pairs ) !== $expected_pairs ||
    count( $seen_wide_ids ) !== count( $spec['wide_attachment_ids'] ) ||
    count( $seen_wide_legacy ) !== $expected_wide_legacy ) {
    WP_CLI::error(
        'The current content does not match the accepted audit: ' . wp_json_encode(
            array(
                'alt_ids' => count( $seen_alt_ids ), 'legacy_alts' => count( $seen_legacy ),
                'pairs' => count( $seen_pairs ), 'wide_ids' => count( $seen_wide_ids ),
                'wide_legacy' => count( $seen_wide_legacy ),
            )
        )
    );
}

$stats['posts_changed'] = count( $post_updates );
if ( 112 !== $stats['attachment_block_alts'] || 12 !== $stats['legacy_block_alts'] ||
    81 !== $stats['pair_styles'] || 30 !== $stats['wide_images'] ) {
    WP_CLI::error( 'Unexpected transformation counts: ' . wp_json_encode( $stats ) );
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
        $changed_posts[] = $post_id;
    }
    foreach ( $spec['attachment_alts'] as $attachment_id => $alt ) {
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
        ++$stats['attachment_meta_alts'];
    }
    foreach ( $spec['attachment_alt_sync'] as $attachment_id => $alt ) {
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
        ++$stats['attachment_meta_synced'];
    }
    update_option( 'ehpmi_sitewide_media_refactor_version', $spec['base_version'], false );
    $wpdb->query( 'COMMIT' );
} catch ( Throwable $error ) {
    $wpdb->query( 'ROLLBACK' );
    WP_CLI::error( 'Site-wide media refactor rolled back: ' . $error->getMessage() );
}

foreach ( $changed_posts as $post_id ) {
    clean_post_cache( $post_id );
}
foreach ( array_unique( array_merge( array_keys( $spec['attachment_alts'] ), array_keys( $spec['attachment_alt_sync'] ) ) ) as $attachment_id ) {
    clean_post_cache( $attachment_id );
}

WP_CLI::success( 'Site-wide media refactor applied: ' . wp_json_encode( $stats ) );
