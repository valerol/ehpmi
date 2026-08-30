<?php
/**
 * Dev-only EHPMI content-model migration.
 *
 * Run from the WordPress document root:
 *   wp eval-file /absolute/path/to/migrate.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "Run this file through WP-CLI.\n" );
    exit( 1 );
}

if ( 'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) ) || 'nykvymmy_ehpmidev' !== DB_NAME ) {
    WP_CLI::error( 'Refusing to run outside the EHPMI dev site and dev database.' );
}

global $wpdb;

$post_type_counts = static function () use ( $wpdb ) {
    $rows = $wpdb->get_results(
        "SELECT post_type, COUNT(*) AS total
         FROM {$wpdb->posts}
         WHERE post_type IN ('member', 'partner', 'partner2')
         GROUP BY post_type",
        OBJECT_K
    );

    return array(
        'member'   => isset( $rows['member'] ) ? (int) $rows['member']->total : 0,
        'partner'  => isset( $rows['partner'] ) ? (int) $rows['partner']->total : 0,
        'partner2' => isset( $rows['partner2'] ) ? (int) $rows['partner2']->total : 0,
    );
};

$meta_counts = static function () use ( $wpdb ) {
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT meta_key, COUNT(*) AS total
             FROM {$wpdb->postmeta}
             WHERE meta_key IN ('member', '_member', 'partner', '_partner')
               AND (meta_key IN ('member', 'partner')
                    OR meta_value = %s)
             GROUP BY meta_key",
            'field_634109faf9126'
        ),
        OBJECT_K
    );

    $counts = array();
    foreach ( array( 'member', '_member', 'partner', '_partner' ) as $key ) {
        $counts[ $key ] = isset( $rows[ $key ] ) ? (int) $rows[ $key ]->total : 0;
    }

    return $counts;
};

$before_posts = $post_type_counts();
$before_meta  = $meta_counts();

if (
    7 === $before_posts['member'] &&
    1 === $before_posts['partner'] &&
    0 === $before_posts['partner2'] &&
    0 === $before_meta['partner'] &&
    0 === $before_meta['_partner'] &&
    9 === $before_meta['member'] &&
    9 === $before_meta['_member']
) {
    WP_CLI::success( 'Content model is already migrated; no changes made.' );
    return;
}

if (
    0 !== $before_posts['member'] ||
    7 !== $before_posts['partner'] ||
    1 !== $before_posts['partner2'] ||
    9 !== $before_meta['partner'] ||
    9 !== $before_meta['_partner'] ||
    0 !== $before_meta['member'] ||
    0 !== $before_meta['_member']
) {
    WP_CLI::error(
        'Unexpected pre-migration state: posts=' . wp_json_encode( $before_posts ) .
        '; country_meta=' . wp_json_encode( $before_meta )
    );
}

$wpdb->query( 'START TRANSACTION' );

try {
    $members_changed = $wpdb->query(
        "UPDATE {$wpdb->posts} SET post_type = 'member' WHERE post_type = 'partner'"
    );
    $partners_changed = $wpdb->query(
        "UPDATE {$wpdb->posts} SET post_type = 'partner' WHERE post_type = 'partner2'"
    );
    $member_values_changed = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->postmeta} AS value_row
             INNER JOIN {$wpdb->postmeta} AS ref_row
                ON ref_row.post_id = value_row.post_id
               AND ref_row.meta_key = '_partner'
               AND ref_row.meta_value = %s
             SET value_row.meta_key = 'member'
             WHERE value_row.meta_key = 'partner'",
            'field_634109faf9126'
        )
    );
    $member_refs_changed = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->postmeta}
             SET meta_key = '_member'
             WHERE meta_key = '_partner' AND meta_value = %s",
            'field_634109faf9126'
        )
    );

    if (
        7 !== $members_changed ||
        1 !== $partners_changed ||
        9 !== $member_values_changed ||
        9 !== $member_refs_changed
    ) {
        throw new RuntimeException(
            'Unexpected affected rows: ' . wp_json_encode(
                array(
                    'members'      => $members_changed,
                    'partners'     => $partners_changed,
                    'member_meta'  => $member_values_changed,
                    'member_refs'  => $member_refs_changed,
                )
            )
        );
    }

    $wpdb->query( 'COMMIT' );
} catch ( Throwable $error ) {
    $wpdb->query( 'ROLLBACK' );
    WP_CLI::error( 'Migration rolled back: ' . $error->getMessage() );
}

clean_post_cache( 103 );
clean_post_cache( 116 );
clean_post_cache( 117 );
clean_post_cache( 118 );
clean_post_cache( 119 );
clean_post_cache( 1455 );
clean_post_cache( 1493 );
clean_post_cache( 1735 );

$after_posts = $post_type_counts();
$after_meta  = $meta_counts();

if (
    7 !== $after_posts['member'] ||
    1 !== $after_posts['partner'] ||
    0 !== $after_posts['partner2'] ||
    9 !== $after_meta['member'] ||
    9 !== $after_meta['_member'] ||
    0 !== $after_meta['partner'] ||
    0 !== $after_meta['_partner']
) {
    WP_CLI::error(
        'Post-commit verification failed: posts=' . wp_json_encode( $after_posts ) .
        '; country_meta=' . wp_json_encode( $after_meta )
    );
}

WP_CLI::success(
    'Migrated content model: posts=' . wp_json_encode( $after_posts ) .
    '; country_meta=' . wp_json_encode( $after_meta )
);
