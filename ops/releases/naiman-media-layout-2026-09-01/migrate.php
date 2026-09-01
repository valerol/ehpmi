<?php
/**
 * Apply the approved media layout and meaningful alternative text to the
 * Naiman remediation project. Run through WP-CLI; pass `apply` to write.
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
$post_id           = 761;

if ( $migration_version === get_option( 'ehpmi_naiman_media_layout_version' ) ) {
    WP_CLI::success( 'Naiman media layout is already migrated; no changes made.' );
    return;
}

$alts = array(
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

$pair_styles = array(
    '771,1176'  => 'is-style-ehpmi-photo-pair-4x3',
    '1179,777'  => 'is-style-ehpmi-photo-pair-3x2',
    '775,767'   => 'is-style-ehpmi-photo-pair-4x3',
    '769,770'   => 'is-style-ehpmi-photo-pair-4x3',
    '766,765'   => 'is-style-ehpmi-photo-pair-4x3',
    '764,768'   => 'is-style-ehpmi-photo-pair-4x3',
    '1182,1184' => 'is-style-ehpmi-photo-pair-3x4',
);

$expected_ids = array_keys( $alts );

$set_tag_class = static function ( $html, $tag, $class_name ) {
    if ( ! is_string( $html ) || '' === $html ) {
        return $html;
    }

    $processor = new WP_HTML_Tag_Processor( $html );
    if ( ! $processor->next_tag( array( 'tag_name' => $tag ) ) ) {
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

$set_image_alt = static function ( $html, $attachment_id, $alt ) {
    if ( ! is_string( $html ) || '' === $html ) {
        return $html;
    }

    $processor = new WP_HTML_Tag_Processor( $html );
    while ( $processor->next_tag( array( 'tag_name' => 'IMG' ) ) ) {
        $classes = ' ' . (string) $processor->get_attribute( 'class' ) . ' ';
        if ( false !== strpos( $classes, ' wp-image-' . $attachment_id . ' ' ) ) {
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
        if ( 'core/image' === $block['blockName'] && ! empty( $block['attrs']['id'] ) ) {
            $ids[] = (int) $block['attrs']['id'];
        }
        if ( ! empty( $block['innerBlocks'] ) ) {
            $ids = array_merge( $ids, $collect_image_ids( $block['innerBlocks'] ) );
        }
    }
    return $ids;
};

$stats = array(
    'image_blocks_updated' => 0,
    'pair_styles_updated'  => 0,
    'map_widened'          => 0,
    'map_caption_added'    => 0,
    'attachment_alts'      => 0,
);

$transform = static function ( $blocks ) use ( &$transform, &$stats, $alts, $pair_styles, $collect_image_ids, $set_tag_class, $set_image_alt, $set_block_html ) {
    foreach ( $blocks as &$block ) {
        if ( ! empty( $block['innerBlocks'] ) ) {
            $block['innerBlocks'] = $transform( $block['innerBlocks'] );
        }

        if ( 'core/image' === $block['blockName'] && ! empty( $block['attrs']['id'] ) ) {
            $attachment_id = (int) $block['attrs']['id'];
            if ( isset( $alts[ $attachment_id ] ) ) {
                $block = $set_block_html(
                    $block,
                    static function ( $html ) use ( $attachment_id, $alts, $set_image_alt ) {
                        return $set_image_alt( $html, $attachment_id, $alts[ $attachment_id ] );
                    }
                );
                ++$stats['image_blocks_updated'];
            }

            if ( 774 === $attachment_id ) {
                $block['attrs']['className'] = 'is-style-ehpmi-wide';
                $block = $set_block_html(
                    $block,
                    static function ( $html ) use ( $set_tag_class ) {
                        return $set_tag_class( $html, 'FIGURE', 'is-style-ehpmi-wide' );
                    }
                );
                ++$stats['map_widened'];

                $caption = 'Mercury concentrations measured in topsoil along the irrigation channel in Naiman, 2017.';
                $add_caption = static function ( $html ) use ( $caption ) {
                    if ( ! is_string( $html ) || false === stripos( $html, '</figure>' ) || false !== strpos( $html, 'wp-element-caption' ) ) {
                        return $html;
                    }
                    return preg_replace(
                        '/<\/figure>\s*$/i',
                        '<figcaption class="wp-element-caption">' . esc_html( $caption ) . '</figcaption></figure>',
                        $html,
                        1
                    );
                };
                $block = $set_block_html( $block, $add_caption );
                ++$stats['map_caption_added'];
            }
        }

        if ( 'core/columns' === $block['blockName'] ) {
            $key = implode( ',', $collect_image_ids( $block['innerBlocks'] ) );
            if ( isset( $pair_styles[ $key ] ) ) {
                $style                      = $pair_styles[ $key ];
                $block['attrs']['className'] = $style;
                $block = $set_block_html(
                    $block,
                    static function ( $html ) use ( $style, $set_tag_class ) {
                        return $set_tag_class( $html, 'DIV', $style );
                    }
                );
                ++$stats['pair_styles_updated'];
            }
        }
    }
    unset( $block );
    return $blocks;
};

$post = get_post( $post_id );
if ( ! $post || 'project' !== $post->post_type || 'remediation-of-the-irrigation-channel-contaminated-with-mercury-in-naiman-kyrgyzstan' !== $post->post_name ) {
    WP_CLI::error( 'Expected Naiman project post 761 was not found.' );
}

$before_blocks = parse_blocks( $post->post_content );
$before_ids    = $collect_image_ids( $before_blocks );
if ( $expected_ids !== $before_ids ) {
    WP_CLI::error( 'Naiman image sequence differs from the accepted audit: ' . wp_json_encode( $before_ids ) );
}

$after_blocks  = $transform( $before_blocks );
$after_ids     = $collect_image_ids( $after_blocks );
$after_content = serialize_blocks( $after_blocks );

if ( $before_ids !== $after_ids ) {
    WP_CLI::error( 'Attachment sequence changed during migration.' );
}
if ( 15 !== $stats['image_blocks_updated'] || 7 !== $stats['pair_styles_updated'] || 1 !== $stats['map_widened'] || 1 !== $stats['map_caption_added'] ) {
    WP_CLI::error( 'Unexpected transformation counts: ' . wp_json_encode( $stats ) );
}

if ( ! $apply ) {
    WP_CLI::success( 'Dry run; no changes made: ' . wp_json_encode( $stats ) );
    return;
}

global $wpdb;
$wpdb->query( 'START TRANSACTION' );

try {
    $updated = wp_update_post(
        array(
            'ID'           => $post_id,
            'post_content' => $after_content,
        ),
        true
    );
    if ( is_wp_error( $updated ) ) {
        throw new RuntimeException( $updated->get_error_message() );
    }

    foreach ( $alts as $attachment_id => $alt ) {
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
        ++$stats['attachment_alts'];
    }

    update_option( 'ehpmi_naiman_media_layout_version', $migration_version, false );
    $wpdb->query( 'COMMIT' );
} catch ( Throwable $error ) {
    $wpdb->query( 'ROLLBACK' );
    WP_CLI::error( 'Naiman media migration rolled back: ' . $error->getMessage() );
}

clean_post_cache( $post_id );
foreach ( array_keys( $alts ) as $attachment_id ) {
    clean_post_cache( $attachment_id );
}

WP_CLI::success( 'Naiman media migration applied: ' . wp_json_encode( $stats ) );
