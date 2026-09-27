<?php
/** Read-only Stage 2 verifier, run with WP-CLI from the dev document root. */
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WP_CLI' ) ) {
    exit( 1 );
}
if (
    '/home2/nykvymmy/dev.ehpmi.org' !== realpath( ABSPATH ) ||
    'nykvymmy_ehpmidev' !== DB_NAME ||
    'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) )
) {
    WP_CLI::error( 'Wrong installation.' );
}

global $wpdb;
$failures = array();
$check    = static function ( $condition, $label ) use ( &$failures ) {
    if ( ! $condition ) {
        $failures[] = $label;
    }
};

// Exact accepted directory sequence: ID => [position b64, menu_order, body SHA-256, thumbnail ID].
$expected = array(
    188  => array( 'UHJlc2lkZW50', 10, '7a0cc257016e975bc95cf9af7dc77fe9e16746fcf4b39cc5240852388760db7a', 217 ),
    176  => array( 'RGlyZWN0b3Igb2YgT3BlcmF0aW9ucyAmIEZpbmFuY2U=', 20, '838ae81ebde2d285a888a07bc88a719f0323b3b7341f3574bf0117cee12fc74e', 214 ),
    257  => array( 'QXdhcmVuZXNzIGFuZCBFZHVjYXRpb24gRXhwZXJ0', 30, '3924fbcd242881502b6dbe69e178f667fa5380f125cf9afdd1baed93c9037829', 1882 ),
    166  => array( 'QXJtZW5pYSBDb3VudHJ5IENvb3JkaW5hdG9yLCBEaXJlY3RvciBvZiBSZXNlYXJjaA==', 40, '84ce86661a6132673ae7849165874acfd254bdb18fdd81dfc1750d676f4b05b5', 212 ),
    757  => array( 'QXJtZW5pYSBFbnZpcm9ubWVudGFsIE1vbml0b3JpbmcgT2ZmaWNlcg==', 50, '4931d05ea92c317ab537638f6bade68f605bcb78ee5c0d25284d57f610506fe2', 759 ),
    551  => array( 'V2F0ZXIgUHJvZ3JhbXMgQWR2aXNvcg==', 60, 'f0f9dd7a376879cecfa8067791fd4e2190e463d16563c0426f2d71a52e05f305', 557 ),
    236  => array( 'VGFqaWtpc3RhbiBQcm9qZWN0IEFzc29jaWF0ZQ==', 70, '8d0f13e6077dd939230dd50455d52f5e66f5ce7aea9610f9250ee26e174818e7', 290 ),
    193  => array( 'QXplcmJhaWphbiBDb3VudHJ5IENvb3JkaW5hdG9y', 80, '8b43cbd03ef9d6fbdd992c134ebb2edc5352eb906c27d03123c85a97c92bedd5', 1099 ),
    911  => array( 'QXplcmJhaWphbiBQcm9ncmFtIEFzc29jaWF0ZQ==', 90, '2ba498c986e61ee1a08de04f3a0c9e778a9770c4b1d793f9419f73cc1b6da81d', 912 ),
    261  => array( 'R2VvcmdpYSBDb3VudHJ5IENvb3JkaW5hdG9y', 100, '3aaa2237002dfe184d10b8a08ef3a43434449c67d58544f78ee8080c6d880378', 279 ),
    249  => array( 'R2VvcmdpYSBQcm9ncmFtIEFzc29jaWF0ZQ==', 110, '583d005025b99e357decb02b40392c1c66e788ae548247a297841a10d3dafe4a', 280 ),
    246  => array( 'R2VvcmdpYSBQcm9ncmFtIE9mZmljZXI=', 120, '04280dd00171c26533ce7f47ff824cca88ab62806158acfa90704abc300fab8a', 278 ),
    252  => array( 'R2VvcmdpYSBQcm9ncmFtIENvbW11bmljYXRpb25z', 130, '8a4760e97e4c5206394a1c3add64221e274b89c4c2852f5b5cf264f82cac036d', 283 ),
    234  => array( 'S2F6YWtoc3RhbiBDb3VudHJ5IENvb3JkaW5hdG9y', 140, '65b5091639b8cb3d8ff39647618a171346daf39e39a6420ebbf477575ec42092', 273 ),
    240  => array( 'S2F6YWtoc3RhbiBQcm9qZWN0IEFzc29jaWF0ZSA=', 150, '3b6e8e43cc7e51d0a8e5b0bca216a89a26c9593836e74326fad4303b30f0a7f5', 275 ),
    183  => array( 'S3lyZ3l6c3RhbiBDb3VudHJ5IENvb3JkaW5hdG9yLCBEaXJlY3RvciBvZiBDb21tdW5pY2F0aW9ucw==', 160, '26c72fa343814d53ddd0facb4a87fea628660cd5bdcb8cd89571dc2b8859930b', 216 ),
    2344 => array( 'QXNzaXN0YW5jZSBpbiBwcm9qZWN0IGFuZCBwcm9ncmFtIG1hbmFnZW1lbnQgYW5kIHJlcG9ydGluZw==', 170, '713c641e1bb4505e48dcebc6dc5394d005600e414716a054c9d49591f78130ec', 2346 ),
    243  => array( 'TW9uZ29saWEgQ291bnRyeSBDb29yZGluYXRvcg==', 180, '76cda4cddecab514a4b9a2b733d5dc52e5a55c583e15b3fece3aed0ed190d5fc', 276 ),
    197  => array( 'VGFqaWtpc3RhbiBDb3VudHJ5IENvb3JkaW5hdG9yLCBQT1BzL1Blc3RpY2lkZXMgUHJvZ3JhbSBEaXJlY3Rvcg==', 190, 'c337e82e855f7e151c9cbc7dda273ef4976d5c3a1faa809e69f3be2b527ea2a5', 221 ),
    1516 => array( 'VmlldG5hbSBDb3VudHJ5IENvb3JkaW5hdG9y', 200, 'f857bdcaf9bf14655799d279dd617e0f21a6fdb80b2d401e8ddcd35a2bb3cb05', 1518 ),
);

$rows = $wpdb->get_results(
    "SELECT ID, post_status, post_excerpt, post_content, menu_order
     FROM {$wpdb->posts}
     WHERE post_type = 'staff_member'
     ORDER BY menu_order, ID",
    ARRAY_A
);
$check( 20 === count( $rows ), '20 Staff records' );
$check( array_map( 'intval', array_column( $rows, 'ID' ) ) === array_keys( $expected ), 'directory order' );

$records = array();
foreach ( $rows as $row ) {
    $id       = (int) $row['ID'];
    $position = isset( $expected[ $id ] ) ? base64_decode( $expected[ $id ][0], true ) : false;
    $check( false !== $position, 'expected Staff ' . $id );
    if ( false === $position ) {
        continue;
    }
    $check( 'publish' === $row['post_status'], 'published Staff ' . $id );
    $check( $position === $row['post_excerpt'], 'retained excerpt ' . $id );
    $check( $position === get_post_meta( $id, 'position', true ), 'position value ' . $id );
    $check( 'field_ehpmi_staff_position' === get_post_meta( $id, '_position', true ), 'position companion ' . $id );
    $check( (int) $row['menu_order'] === $expected[ $id ][1], 'menu_order ' . $id );
    $check( hash( 'sha256', $row['post_content'] ) === $expected[ $id ][2], 'body ' . $id );
    $check( (int) get_post_thumbnail_id( $id ) === $expected[ $id ][3], 'thumbnail ' . $id );
    $records[ $id ] = array(
        'position'      => $position,
        'menu_order'    => (int) $row['menu_order'],
        'body_sha256'   => hash( 'sha256', $row['post_content'] ),
        'thumbnail_id'  => (int) get_post_thumbnail_id( $id ),
    );
}

$check( 'The Country Coordinator' === get_post_meta( 1516, 'country_role', true ), 'Vietnam country role' );
$check( 'field_ehpmi_country_role' === get_post_meta( 1516, '_country_role', true ), 'Vietnam country role companion' );

$meta_counts = $wpdb->get_results(
    "SELECT meta_key, COUNT(*) AS n
     FROM {$wpdb->postmeta} pm
     JOIN {$wpdb->posts} p ON p.ID = pm.post_id
     WHERE p.post_type = 'staff_member'
       AND pm.meta_key IN ('position','_position','country_role','_country_role')
     GROUP BY meta_key ORDER BY meta_key",
    ARRAY_A
);
$check(
    $meta_counts === array(
        array( 'meta_key' => '_country_role', 'n' => '1' ),
        array( 'meta_key' => '_position', 'n' => '20' ),
        array( 'meta_key' => 'country_role', 'n' => '1' ),
        array( 'meta_key' => 'position', 'n' => '20' ),
    ),
    'structured metadata counts'
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
$relationship_rows = $wpdb->get_results(
    "SELECT p.ID, pm.meta_key, pm.meta_value
     FROM {$wpdb->posts} p
     JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
     WHERE p.post_parent = 17 AND p.post_type = 'page'
       AND pm.meta_key IN ('leader','team','member')
     ORDER BY p.menu_order, p.ID, FIELD(pm.meta_key,'leader','team','member')",
    ARRAY_A
);
$relationship_actual = array();
foreach ( $relationship_rows as $row ) {
    $relationship_actual[ (int) $row['ID'] . ':' . $row['meta_key'] ] = hash( 'sha256', $row['meta_value'] );
}
$check( $relationship_actual === $relationship_hashes, 'Country relationship bytes' );

$organization_expected = array();
foreach ( array( 176, 183, 193, 197, 234, 257, 261, 551, 757, 1516, 2344 ) as $staff_id ) {
    $organization_expected[ $staff_id . ':_organization' ] = 'field_634161e581dc4';
    $organization_expected[ $staff_id . ':organization' ]  = 183 === $staff_id ? '116' : '';
}
$staff_ids         = implode( ',', array_map( 'intval', array_keys( $expected ) ) );
$organization_rows = $wpdb->get_results(
    "SELECT post_id, meta_key, meta_value
     FROM {$wpdb->postmeta}
     WHERE post_id IN ({$staff_ids}) AND meta_key IN ('organization','_organization')
     ORDER BY post_id, meta_key",
    ARRAY_A
);
$organization_actual = array();
foreach ( $organization_rows as $row ) {
    $organization_actual[ (int) $row['post_id'] . ':' . $row['meta_key'] ] = $row['meta_value'];
}
$check( $organization_actual === $organization_expected, 'Staff Member-organization relationship bytes' );

$acf_fields = array();
if ( function_exists( 'acf_get_field_groups' ) ) {
    foreach ( acf_get_field_groups( array( 'post_type' => 'staff_member' ) ) as $group ) {
        if ( 'group_634161e50d969' === $group['key'] ) {
            $acf_fields = array_column( acf_get_fields( $group ), 'name' );
            break;
        }
    }
}
$check( $acf_fields === array( 'position', 'country_role', 'organization' ), 'Staff ACF fields' );
$check( post_type_supports( 'staff_member', 'page-attributes' ), 'Staff Order control' );
$check( '' === (string) $wpdb->last_error, 'database read error' );

$report = array(
    'result'                  => $failures ? 'FAIL' : 'PASS',
    'failures'                => $failures,
    'directory_order'         => array_keys( $expected ),
    'records'                 => $records,
    'meta_counts'             => $meta_counts,
    'acf_fields'              => $acf_fields,
    'country_relationships'   => $relationship_actual,
    'organization_relations'  => $organization_actual,
);
WP_CLI::line( 'STAGE2_VERIFY_JSON=' . wp_json_encode( $report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
if ( $failures ) {
    WP_CLI::error( 'Stage 2 verifier failed.' );
}
