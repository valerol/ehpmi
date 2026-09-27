<?php
/**
 * Stage 1: rename the Member/Partner Additional image ACF meta key on dev.
 *
 * Run from the verified dev WordPress root with WP-CLI:
 *   wp eval-file /absolute/path/to/migrate.php
 *   EHPMI_STAGE1_MODE=apply wp eval-file /absolute/path/to/migrate.php
 *   EHPMI_STAGE1_MODE=rollback-dry-run wp eval-file /absolute/path/to/migrate.php
 *   EHPMI_STAGE1_MODE=rollback wp eval-file /absolute/path/to/migrate.php
 *
 * Default mode is read-only. Apply and rollback are guarded, transactional,
 * and only change ten pre-inventoried postmeta.meta_key values.
 */

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WP_CLI' ) ) {
    fwrite( STDERR, "Run through WP-CLI in the EHPMI dev WordPress root.\n" );
    exit( 1 );
}

if (
    '/home2/nykvymmy/dev.ehpmi.org' !== realpath( ABSPATH ) ||
    'nykvymmy_ehpmidev' !== DB_NAME ||
    'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) )
) {
    WP_CLI::error( 'Refusing to run outside the verified EHPMI dev root and database.' );
}

$mode = getenv( 'EHPMI_STAGE1_MODE' ) ?: 'dry-run';
if ( ! in_array( $mode, array( 'dry-run', 'apply', 'rollback-dry-run', 'rollback' ), true ) ) {
    WP_CLI::error( 'Unknown mode.' );
}

global $wpdb;

// meta_id => [post_id, legacy key, corrected key, exact stored value].
$expected = array(
    3633 => array( 116, '_additonal_image', '_additional_image', 'field_6576b42d8de48' ),
    3632 => array( 116, 'additonal_image', 'additional_image', '' ),
    3104 => array( 117, '_additonal_image', '_additional_image', 'field_6576b42d8de48' ),
    3103 => array( 117, 'additonal_image', 'additional_image', '' ),
    3635 => array( 118, '_additonal_image', '_additional_image', 'field_6576b42d8de48' ),
    3634 => array( 118, 'additonal_image', 'additional_image', '' ),
    3093 => array( 1493, '_additonal_image', '_additional_image', 'field_6576b42d8de48' ),
    3092 => array( 1493, 'additonal_image', 'additional_image', '1498' ),
    3623 => array( 1735, '_additonal_image', '_additional_image', 'field_6576b42d8de48' ),
    3622 => array( 1735, 'additonal_image', 'additional_image', '' ),
);

$read_state = static function () use ( $wpdb, $expected ) {
    $ids          = implode( ',', array_map( 'intval', array_keys( $expected ) ) );
    $rows         = $wpdb->get_results( "SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_id IN ({$ids})", ARRAY_A );
    $actual       = array();
    $legacy_count = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
         JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE p.post_type IN ('member', 'partner')
           AND pm.meta_key IN ('additonal_image', '_additonal_image')"
    );
    $new_count    = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
         JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE p.post_type IN ('member', 'partner')
           AND pm.meta_key IN ('additional_image', '_additional_image')"
    );

    foreach ( $rows as $row ) {
        $actual[ (int) $row['meta_id'] ] = $row;
    }

    return array( $actual, $legacy_count, $new_count );
};

$assert_state = static function ( $wanted ) use ( $read_state, $expected, $wpdb ) {
    list( $actual, $legacy_count, $new_count ) = $read_state();
    $legacy_expected = 'legacy' === $wanted ? 10 : 0;
    $new_expected    = 'corrected' === $wanted ? 10 : 0;

    if ( 10 !== count( $actual ) || $legacy_expected !== $legacy_count || $new_expected !== $new_count ) {
        throw new RuntimeException( 'Unexpected meta row counts: located=' . count( $actual ) . ', legacy=' . $legacy_count . ', corrected=' . $new_count );
    }

    foreach ( $expected as $meta_id => $item ) {
        if ( ! isset( $actual[ $meta_id ] ) ) {
            throw new RuntimeException( 'Missing meta_id ' . $meta_id );
        }
        $row  = $actual[ $meta_id ];
        $post = get_post( $item[0] );
        $key  = 'legacy' === $wanted ? $item[1] : $item[2];
        if (
            ! $post || 'member' !== $post->post_type ||
            (int) $row['post_id'] !== $item[0] ||
            $row['meta_key'] !== $key ||
            $row['meta_value'] !== $item[3]
        ) {
            throw new RuntimeException( 'Unexpected post or meta value for meta_id ' . $meta_id );
        }
    }

    if ( $wpdb->last_error ) {
        throw new RuntimeException( 'Database read failed: ' . $wpdb->last_error );
    }
};

$rollback = str_starts_with( $mode, 'rollback' );
$before   = $rollback ? 'corrected' : 'legacy';
$after    = $rollback ? 'legacy' : 'corrected';

try {
    $assert_state( $before );
} catch ( Throwable $error ) {
    WP_CLI::error( 'Precondition failed: ' . $error->getMessage() );
}

if ( 'dry-run' === $mode || 'rollback-dry-run' === $mode ) {
    WP_CLI::success( $mode . ': exact 5 Member value rows and 5 companion rows match; expected changes=10, nonempty image ID=1498 on Member 1493; no writes.' );
    return;
}

if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
    WP_CLI::error( 'Could not start transaction.' );
}

try {
    foreach ( $expected as $meta_id => $item ) {
        $from    = $rollback ? $item[2] : $item[1];
        $to      = $rollback ? $item[1] : $item[2];
        $changed = $wpdb->update(
            $wpdb->postmeta,
            array( 'meta_key' => $to ),
            array(
                'meta_id'    => $meta_id,
                'post_id'    => $item[0],
                'meta_key'   => $from,
                'meta_value' => $item[3],
            ),
            array( '%s' ),
            array( '%d', '%d', '%s', '%s' )
        );
        if ( 1 !== $changed ) {
            throw new RuntimeException( 'Expected one changed row for meta_id ' . $meta_id . ', got ' . var_export( $changed, true ) );
        }
    }
    $assert_state( $after );
    if ( false === $wpdb->query( 'COMMIT' ) ) {
        throw new RuntimeException( 'Commit failed.' );
    }
} catch ( Throwable $error ) {
    $wpdb->query( 'ROLLBACK' );
    WP_CLI::error( 'Transaction rolled back: ' . $error->getMessage() );
}

foreach ( array( 116, 117, 118, 1493, 1735 ) as $post_id ) {
    wp_cache_delete( $post_id, 'post_meta' );
    clean_post_cache( $post_id );
}

WP_CLI::success( $mode . ': renamed exactly 10 meta keys; five values and five ACF companion references verified.' );
