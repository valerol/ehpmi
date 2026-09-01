<?php
/** Read-only inventory for the sitewide editorial-media follow-up. */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "Run through WP-CLI.\n" );
    exit( 1 );
}

if ( 'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) ) || 'nykvymmy_ehpmidev' !== DB_NAME ) {
    WP_CLI::error( 'Refusing to audit outside EHPMI dev.' );
}

$read_image_html = static function ( $html ) {
    $processor = new WP_HTML_Tag_Processor( $html );
    if ( ! $processor->next_tag( array( 'tag_name' => 'IMG' ) ) ) {
        return array( 'alt' => '', 'src' => '', 'width' => 0, 'height' => 0 );
    }
    return array(
        'alt'    => (string) $processor->get_attribute( 'alt' ),
        'src'    => (string) $processor->get_attribute( 'src' ),
        'width'  => (int) $processor->get_attribute( 'width' ),
        'height' => (int) $processor->get_attribute( 'height' ),
    );
};

$image_data = static function ( $block, $context ) use ( $read_image_html ) {
    $id       = isset( $block['attrs']['id'] ) ? (int) $block['attrs']['id'] : 0;
    $metadata = $id ? wp_get_attachment_metadata( $id ) : array();
    $html     = $read_image_html( $block['innerHTML'] );
    return array(
        'id'             => $id,
        'filename'       => $id ? get_post_meta( $id, '_wp_attached_file', true ) : '',
        'title'          => $id ? get_the_title( $id ) : '',
        'width'          => isset( $metadata['width'] ) ? (int) $metadata['width'] : $html['width'],
        'height'         => isset( $metadata['height'] ) ? (int) $metadata['height'] : $html['height'],
        'full_url'       => $id ? wp_get_attachment_url( $id ) : $html['src'],
        'large_url'      => $id ? wp_get_attachment_image_url( $id, 'large' ) : '',
        'block_alt'      => $html['alt'],
        'attachment_alt' => $id ? (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) : '',
        'size_slug'      => isset( $block['attrs']['sizeSlug'] ) ? $block['attrs']['sizeSlug'] : '',
        'image_style'    => isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '',
        'context'        => $context,
    );
};

$records = array();
$posts   = get_posts(
    array(
        'post_type'      => array( 'post', 'page', 'project', 'staff_member', 'member', 'partner', 'testimonial' ),
        'post_status'    => array( 'publish', 'draft', 'private' ),
        'posts_per_page' => -1,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    )
);

foreach ( $posts as $post ) {
    if ( 761 === (int) $post->ID ) {
        continue;
    }

    $images = array();
    $pairs  = array();

    $walk = static function ( $blocks, $context = 'standalone' ) use ( &$walk, &$images, &$pairs, $image_data ) {
        foreach ( $blocks as $block ) {
            if ( 'core/image' === $block['blockName'] ) {
                $images[] = $image_data( $block, $context );
                continue;
            }

            if ( 'core/columns' === $block['blockName'] ) {
                $pair_images = array();
                $collect = static function ( $children ) use ( &$collect, &$pair_images ) {
                    foreach ( $children as $child ) {
                        if ( 'core/image' === $child['blockName'] ) {
                            $pair_images[] = $child;
                        }
                        if ( ! empty( $child['innerBlocks'] ) ) {
                            $collect( $child['innerBlocks'] );
                        }
                    }
                };
                $collect( $block['innerBlocks'] );
                if ( 2 === count( $pair_images ) ) {
                    $ids = array_map(
                        static function ( $image ) {
                            return isset( $image['attrs']['id'] ) ? (int) $image['attrs']['id'] : 0;
                        },
                        $pair_images
                    );
                    $pairs[] = array(
                        'ids'           => $ids,
                        'current_style' => isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '',
                    );
                    foreach ( $pair_images as $image ) {
                        $images[] = $image_data( $image, 'pair:' . implode( ',', $ids ) );
                    }
                    continue;
                }
            }

            $child_context = 'core/gallery' === $block['blockName'] ? 'gallery' : $context;
            if ( ! empty( $block['innerBlocks'] ) ) {
                $walk( $block['innerBlocks'], $child_context );
            }
        }
    };

    $walk( parse_blocks( $post->post_content ) );
    if ( ! $images ) {
        continue;
    }

    $records[] = array(
        'post_id'     => (int) $post->ID,
        'post_type'   => $post->post_type,
        'post_status' => $post->post_status,
        'slug'        => $post->post_name,
        'title'       => html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
        'url'         => get_permalink( $post ),
        'images'      => $images,
        'pairs'       => $pairs,
    );
}

WP_CLI::line( wp_json_encode( $records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
