<?php
/**
 * Stage 2: migrate Staff excerpts to structured positions, add the accepted
 * Vietnam country role, and preserve the current directory order.
 *
 * Run from the verified dev WordPress root with WP-CLI:
 *   wp eval-file /absolute/path/to/migrate.php
 *   EHPMI_STAGE2_MODE=apply wp eval-file /absolute/path/to/migrate.php
 *   EHPMI_STAGE2_MODE=rollback-dry-run wp eval-file /absolute/path/to/migrate.php
 *   EHPMI_STAGE2_MODE=rollback wp eval-file /absolute/path/to/migrate.php
 *
 * Dry-run is the default. Apply and rollback are guarded and transactional.
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

$mode = getenv( 'EHPMI_STAGE2_MODE' ) ?: 'dry-run';
if ( ! in_array( $mode, array( 'dry-run', 'apply', 'rollback-dry-run', 'rollback' ), true ) ) {
    WP_CLI::error( 'Unknown mode.' );
}

global $wpdb;

// ID => [base64-encoded exact excerpt/position, retained directory menu_order].
$positions = array(
    188  => array( 'UHJlc2lkZW50', 10 ),
    176  => array( 'RGlyZWN0b3Igb2YgT3BlcmF0aW9ucyAmIEZpbmFuY2U=', 20 ),
    257  => array( 'QXdhcmVuZXNzIGFuZCBFZHVjYXRpb24gRXhwZXJ0', 30 ),
    166  => array( 'QXJtZW5pYSBDb3VudHJ5IENvb3JkaW5hdG9yLCBEaXJlY3RvciBvZiBSZXNlYXJjaA==', 40 ),
    757  => array( 'QXJtZW5pYSBFbnZpcm9ubWVudGFsIE1vbml0b3JpbmcgT2ZmaWNlcg==', 50 ),
    551  => array( 'V2F0ZXIgUHJvZ3JhbXMgQWR2aXNvcg==', 60 ),
    236  => array( 'VGFqaWtpc3RhbiBQcm9qZWN0IEFzc29jaWF0ZQ==', 70 ),
    193  => array( 'QXplcmJhaWphbiBDb3VudHJ5IENvb3JkaW5hdG9y', 80 ),
    911  => array( 'QXplcmJhaWphbiBQcm9ncmFtIEFzc29jaWF0ZQ==', 90 ),
    261  => array( 'R2VvcmdpYSBDb3VudHJ5IENvb3JkaW5hdG9y', 100 ),
    249  => array( 'R2VvcmdpYSBQcm9ncmFtIEFzc29jaWF0ZQ==', 110 ),
    246  => array( 'R2VvcmdpYSBQcm9ncmFtIE9mZmljZXI=', 120 ),
    252  => array( 'R2VvcmdpYSBQcm9ncmFtIENvbW11bmljYXRpb25z', 130 ),
    234  => array( 'S2F6YWtoc3RhbiBDb3VudHJ5IENvb3JkaW5hdG9y', 140 ),
    240  => array( 'S2F6YWtoc3RhbiBQcm9qZWN0IEFzc29jaWF0ZSA=', 150 ),
    183  => array( 'S3lyZ3l6c3RhbiBDb3VudHJ5IENvb3JkaW5hdG9yLCBEaXJlY3RvciBvZiBDb21tdW5pY2F0aW9ucw==', 160 ),
    2344 => array( 'QXNzaXN0YW5jZSBpbiBwcm9qZWN0IGFuZCBwcm9ncmFtIG1hbmFnZW1lbnQgYW5kIHJlcG9ydGluZw==', 170 ),
    243  => array( 'TW9uZ29saWEgQ291bnRyeSBDb29yZGluYXRvcg==', 180 ),
    197  => array( 'VGFqaWtpc3RhbiBDb3VudHJ5IENvb3JkaW5hdG9yLCBQT1BzL1Blc3RpY2lkZXMgUHJvZ3JhbSBEaXJlY3Rvcg==', 190 ),
    1516 => array( 'VmlldG5hbSBDb3VudHJ5IENvb3JkaW5hdG9y', 200 ),
);

$relationship_hashes = array(
    '327:leader'  => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    '327:team'    => '0e253b8f09d61b89c07df14945dbc5b1227cef3f4344fb60b1e41747b900b395',
    '327:member'  => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    '656:leader'  => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    '656:team'    => '891bcde0a64e23c4f4505dd46cc768c0924d13fe4215b3a47bd6eae0996ec765',
    '656:member'  => 'c1748242d7432934ba435e162f069df017f983855d808b1bd4d687f6bbc67239',
    '347:leader'  => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    '347:team'    => '73a38112c5d79dc7ebc31ff6618a0085f2cd134e5d412ab0be8d9c8f5b0437bc',
    '347:member'  => 'cad728e7ed51f50181a37546eef62f31b2a0a6c2d8f1ad6ca33d4daa55c76036',
    '355:leader'  => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    '355:team'    => 'eb7fbb3b20c8ea9c0da34718d86e1123876cd6286f5bfd978934c951696a32a1',
    '355:member'  => '4d72507246b07aac5a8e3e1ce85f6b5a331fb0b2c9e6f82e10b7d9172036abb1',
    '362:leader'  => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    '362:team'    => '7f430beafdd84bdd936c3ca7adb59e45a56f85bd6beb08e715ce2bb6550822b5',
    '362:member'  => 'bd6b1f82f738809e196cf018d58861a96c3cff21aad2ad2a71b63f5095a7661a',
    '374:leader'  => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    '374:team'    => 'b248617485a952064dedbc6b92c1ed9c01e6f5bc2260d1782849cb0463d16ba3',
    '374:member'  => 'b5827f36033b5f9b947e4b6c8360f609fba10063545611e9411bd6f4c541e4b4',
    '378:leader'  => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    '378:team'    => 'c4159ad3e89706cf118fd76bf5dd50763e6e2921df378c77c61b822524eaad13',
    '378:member'  => '2b38bd4782907bbf71b06dbf32dab56ad50fd2b0398f7308ffbaf347ef007837',
    '647:leader'  => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    '647:team'    => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    '647:member'  => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    '1647:leader' => '17c7f8a2457a4b725d6d5f6b2e2ac67bd74933d9633436bd2756a6920ef5a705',
    '1647:team'   => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
    '1647:member' => '7909261ae48f73b14ac8af2a53862c96c69ba2cbdbe609f61c2c01bb9e2115b7',
);

$organization_rows = array();
foreach ( array( 176, 183, 193, 197, 234, 257, 261, 551, 757, 1516, 2344 ) as $staff_id ) {
    $organization_rows[ $staff_id . ':_organization' ] = 'field_634161e581dc4';
    $organization_rows[ $staff_id . ':organization' ]  = 183 === $staff_id ? '116' : '';
}

$assert_relationships = static function () use ( $wpdb, $relationship_hashes, $organization_rows, $positions ) {
    $rows = $wpdb->get_results(
        "SELECT p.ID, pm.meta_key, pm.meta_value
         FROM {$wpdb->posts} p
         JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
         WHERE p.post_parent = 17 AND p.post_type = 'page'
           AND pm.meta_key IN ('leader','team','member')
         ORDER BY p.menu_order, p.ID, FIELD(pm.meta_key,'leader','team','member')",
        ARRAY_A
    );
    $actual = array();
    foreach ( $rows as $row ) {
        $actual[ (int) $row['ID'] . ':' . $row['meta_key'] ] = hash( 'sha256', $row['meta_value'] );
    }
    if ( $actual !== $relationship_hashes ) {
        throw new RuntimeException( 'Country leader/team/member relationship bytes differ from the frozen inventory.' );
    }

    $ids      = implode( ',', array_map( 'intval', array_keys( $positions ) ) );
    $org_rows = $wpdb->get_results(
        "SELECT post_id, meta_key, meta_value
         FROM {$wpdb->postmeta}
         WHERE post_id IN ({$ids}) AND meta_key IN ('organization','_organization')
         ORDER BY post_id, meta_key",
        ARRAY_A
    );
    $org_actual = array();
    foreach ( $org_rows as $row ) {
        $org_actual[ (int) $row['post_id'] . ':' . $row['meta_key'] ] = $row['meta_value'];
    }
    if ( $org_actual !== $organization_rows ) {
        throw new RuntimeException( 'Staff Member-organization relationship bytes differ from the frozen inventory.' );
    }
};

$assert_state = static function ( $wanted ) use ( $wpdb, $positions, $assert_relationships ) {
    $rows = $wpdb->get_results(
        "SELECT ID, post_status, post_excerpt, menu_order
         FROM {$wpdb->posts}
         WHERE post_type = 'staff_member'
         ORDER BY ID",
        ARRAY_A
    );
    if ( 20 !== count( $rows ) ) {
        throw new RuntimeException( 'Expected exactly 20 Staff records.' );
    }

    $actual_ids = array_map( 'intval', array_column( $rows, 'ID' ) );
    $wanted_ids = array_keys( $positions );
    sort( $wanted_ids );
    if ( $actual_ids !== $wanted_ids ) {
        throw new RuntimeException( 'Staff ID set differs from the frozen inventory.' );
    }

    foreach ( $rows as $row ) {
        $id       = (int) $row['ID'];
        $position = base64_decode( $positions[ $id ][0], true );
        $order    = 'migrated' === $wanted ? $positions[ $id ][1] : 0;
        if (
            'publish' !== $row['post_status'] ||
            false === $position ||
            $row['post_excerpt'] !== $position ||
            (int) $row['menu_order'] !== $order
        ) {
            throw new RuntimeException( 'Unexpected status, excerpt, or menu_order for Staff ' . $id );
        }
    }

    $ids       = implode( ',', array_map( 'intval', array_keys( $positions ) ) );
    $meta_rows = $wpdb->get_results(
        "SELECT post_id, meta_key, meta_value
         FROM {$wpdb->postmeta}
         WHERE post_id IN ({$ids})
           AND meta_key IN ('position','_position','country_role','_country_role')
         ORDER BY post_id, meta_key",
        ARRAY_A
    );

    if ( 'baseline' === $wanted ) {
        if ( array() !== $meta_rows ) {
            throw new RuntimeException( 'Structured Staff metadata already exists.' );
        }
    } else {
        if ( 42 !== count( $meta_rows ) ) {
            throw new RuntimeException( 'Expected exactly 42 Stage 2 metadata rows.' );
        }
        $actual_meta = array();
        foreach ( $meta_rows as $row ) {
            $actual_meta[ (int) $row['post_id'] . ':' . $row['meta_key'] ] = $row['meta_value'];
        }
        foreach ( $positions as $id => $definition ) {
            $position = base64_decode( $definition[0], true );
            if (
                $actual_meta[ $id . ':position' ] !== $position ||
                $actual_meta[ $id . ':_position' ] !== 'field_ehpmi_staff_position'
            ) {
                throw new RuntimeException( 'Unexpected structured position metadata for Staff ' . $id );
            }
        }
        if (
            'The Country Coordinator' !== $actual_meta['1516:country_role'] ||
            'field_ehpmi_country_role' !== $actual_meta['1516:_country_role']
        ) {
            throw new RuntimeException( 'Unexpected Vietnam country-role metadata.' );
        }
    }

    $assert_relationships();
    if ( '' !== (string) $wpdb->last_error ) {
        throw new RuntimeException( 'Database read failed: ' . $wpdb->last_error );
    }
};

$rollback = str_starts_with( $mode, 'rollback' );
$before   = $rollback ? 'migrated' : 'baseline';
$after    = $rollback ? 'baseline' : 'migrated';

try {
    $assert_state( $before );
} catch ( Throwable $error ) {
    WP_CLI::error( 'Precondition failed: ' . $error->getMessage() );
}

if ( 'dry-run' === $mode || 'rollback-dry-run' === $mode ) {
    WP_CLI::success(
        $mode . ': exact 20 excerpts, directory order, 27 Country relationships, and 22 organization rows match; expected writes=62; no writes.'
    );
    return;
}

if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
    WP_CLI::error( 'Could not start transaction.' );
}

try {
    foreach ( $positions as $id => $definition ) {
        $position = base64_decode( $definition[0], true );
        $order    = (int) $definition[1];

        if ( $rollback ) {
            foreach (
                array(
                    'position'  => $position,
                    '_position' => 'field_ehpmi_staff_position',
                ) as $key => $value
            ) {
                $deleted = $wpdb->delete(
                    $wpdb->postmeta,
                    array( 'post_id' => $id, 'meta_key' => $key, 'meta_value' => $value ),
                    array( '%d', '%s', '%s' )
                );
                if ( 1 !== $deleted ) {
                    throw new RuntimeException( 'Expected one deleted ' . $key . ' row for Staff ' . $id );
                }
            }
        } else {
            foreach (
                array(
                    'position'  => $position,
                    '_position' => 'field_ehpmi_staff_position',
                ) as $key => $value
            ) {
                $inserted = $wpdb->insert(
                    $wpdb->postmeta,
                    array( 'post_id' => $id, 'meta_key' => $key, 'meta_value' => $value ),
                    array( '%d', '%s', '%s' )
                );
                if ( 1 !== $inserted ) {
                    throw new RuntimeException( 'Expected one inserted ' . $key . ' row for Staff ' . $id );
                }
            }
        }

        $changed = $wpdb->update(
            $wpdb->posts,
            array( 'menu_order' => $rollback ? 0 : $order ),
            array(
                'ID'           => $id,
                'post_type'    => 'staff_member',
                'post_status'  => 'publish',
                'post_excerpt' => $position,
                'menu_order'   => $rollback ? $order : 0,
            ),
            array( '%d' ),
            array( '%d', '%s', '%s', '%s', '%d' )
        );
        if ( 1 !== $changed ) {
            throw new RuntimeException( 'Expected one menu_order update for Staff ' . $id );
        }
    }

    foreach (
        array(
            'country_role'  => 'The Country Coordinator',
            '_country_role' => 'field_ehpmi_country_role',
        ) as $key => $value
    ) {
        if ( $rollback ) {
            $changed = $wpdb->delete(
                $wpdb->postmeta,
                array( 'post_id' => 1516, 'meta_key' => $key, 'meta_value' => $value ),
                array( '%d', '%s', '%s' )
            );
        } else {
            $changed = $wpdb->insert(
                $wpdb->postmeta,
                array( 'post_id' => 1516, 'meta_key' => $key, 'meta_value' => $value ),
                array( '%d', '%s', '%s' )
            );
        }
        if ( 1 !== $changed ) {
            throw new RuntimeException( 'Expected one changed ' . $key . ' row for Staff 1516.' );
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

foreach ( array_keys( $positions ) as $post_id ) {
    wp_cache_delete( $post_id, 'post_meta' );
    clean_post_cache( $post_id );
}

WP_CLI::success(
    $mode . ': 20 positions, 20 ACF companions, one country role pair, and 20 directory-order values changed; excerpts and relationships preserved.'
);
