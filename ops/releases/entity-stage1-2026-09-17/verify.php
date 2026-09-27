<?php
/** Read-only Stage 1 verifier, run with WP-CLI from the dev document root. */
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WP_CLI' ) ) {
    exit( 1 );
}
if ( '/home2/nykvymmy/dev.ehpmi.org' !== realpath( ABSPATH ) || 'nykvymmy_ehpmidev' !== DB_NAME ) {
    WP_CLI::error( 'Wrong installation.' );
}

global $wpdb;
$failures = array();
$check = static function ( $condition, $label ) use ( &$failures ) {
    if ( ! $condition ) {
        $failures[] = $label;
    }
};

$records = $wpdb->get_results(
    "SELECT ID, post_type, post_status, post_content FROM {$wpdb->posts}
     WHERE post_type IN ('member', 'partner') AND post_status = 'publish' ORDER BY ID",
    ARRAY_A
);
$ids = array_map( 'intval', array_column( $records, 'ID' ) );
$check( $ids === array( 103, 116, 117, 118, 119, 1455, 1493, 1735 ), 'record IDs/statuses' );

$fields = array();
foreach ( array( 'member', 'partner' ) as $type ) {
    $groups = function_exists( 'acf_get_field_groups' ) ? acf_get_field_groups( array( 'post_type' => $type ) ) : array();
    foreach ( $groups as $group ) {
        if ( 'group_6336c6465a962' === $group['key'] ) {
            $fields[ $type ] = array_column( acf_get_fields( $group ), 'name' );
            break;
        }
    }
    $check( isset( $fields[ $type ] ) && in_array( 'url', $fields[ $type ], true ) && in_array( 'additional_image', $fields[ $type ], true ), $type . ' ACF fields' );
}

$data = array();
foreach ( $records as $record ) {
    $id = (int) $record['ID'];
    $thumb = (int) get_post_thumbnail_id( $id );
    $extra = get_post_meta( $id, 'additional_image', true );
    $check( $thumb > 0 && 'attachment' === get_post_type( $thumb ), 'logo ' . $id );
    if ( '' !== $extra ) {
        $check( 'attachment' === get_post_type( (int) $extra ), 'additional image ' . $id );
    }
    $data[ $id ] = array(
        'type'         => $record['post_type'],
        'url'          => get_post_meta( $id, 'url', true ),
        'logo_id'      => $thumb,
        'additional_id'=> $extra,
        'body_sha256'  => hash( 'sha256', $record['post_content'] ),
    );
}
$expected_data = array(
    103  => array( 'type' => 'member',  'url' => '',                    'logo_id' => 151,  'additional_id' => '',     'body_sha256' => 'ac7013419491be44cde48985fcfe7dd8882029022da1244d4580632ad5444c23' ),
    116  => array( 'type' => 'member',  'url' => 'http://ekois.net/',   'logo_id' => 613,  'additional_id' => '',     'body_sha256' => '138b3c6d428766e3aefbf7cae57a3acc6e4b248a327ea6de4061480dba1f0f04' ),
    117  => array( 'type' => 'member',  'url' => 'https://flagman.tj/', 'logo_id' => 612,  'additional_id' => '',     'body_sha256' => '702b1fb817661e55e0e8b12aeb630209c367ff3c045e7b6057001014a159a54e' ),
    118  => array( 'type' => 'member',  'url' => '',                    'logo_id' => 148,  'additional_id' => '',     'body_sha256' => 'bcc7161b7d0135cea1f7cea76d38373a7623eb18ef903615dc0ce78708a4365d' ),
    119  => array( 'type' => 'member',  'url' => '',                    'logo_id' => 147,  'additional_id' => '',     'body_sha256' => '5da47c0ca75d623166419994e1cd89ce4869c9e61b748caf66937b438f380e34' ),
    1455 => array( 'type' => 'partner', 'url' => '',                    'logo_id' => 1456, 'additional_id' => '',     'body_sha256' => 'd0058174bf8b3b67d51ef5f806294bedd8a467f78588d72380bdfc7fe7bea14f' ),
    1493 => array( 'type' => 'member',  'url' => '',                    'logo_id' => 1494, 'additional_id' => '1498', 'body_sha256' => '2de756a2505b9fb84ef7c737ee2b0bdeb763fedde9aadc928540eaab15455090' ),
    1735 => array( 'type' => 'member',  'url' => 'https://tuqay.az/',    'logo_id' => 1737, 'additional_id' => '',     'body_sha256' => '9e6df71c61a9e5dabd608df33fbf9f8aca1b3f0ea55eb40368d2dcc1dd458251' ),
);
$check( $data === $expected_data, 'preserved record data' );

$check( $data[1493]['additional_id'] === '1498', 'Member 1493 additional image' );
$check( $data[1455]['type'] === 'partner' && $data[1455]['logo_id'] === 1456, 'Partner logo' );
$check( count( array_filter( $data, static function ( $row ) { return '' !== $row['url']; } ) ) === 3, 'three preserved URLs' );

$meta_counts = $wpdb->get_results(
    "SELECT pm.meta_key, COUNT(*) AS n FROM {$wpdb->postmeta} pm
     JOIN {$wpdb->posts} p ON p.ID = pm.post_id
     WHERE p.post_type IN ('member','partner')
       AND pm.meta_key IN ('additonal_image','_additonal_image','additional_image','_additional_image')
     GROUP BY pm.meta_key ORDER BY pm.meta_key",
    ARRAY_A
);
$check( $meta_counts === array(
    array( 'meta_key' => '_additional_image', 'n' => '6' ),
    array( 'meta_key' => 'additional_image', 'n' => '6' ),
), 'corrected metadata only' );

$types = array();
foreach ( array( 'member', 'partner' ) as $type ) {
    $object = get_post_type_object( $type );
    $types[ $type ] = array(
        'public'             => (bool) $object->public,
        'publicly_queryable' => (bool) $object->publicly_queryable,
        'archive'            => (bool) $object->has_archive,
        'rest'               => (bool) $object->show_in_rest,
    );
    $check( ! in_array( true, $types[ $type ], true ), $type . ' public exposure' );
}

$check( '' === (string) $wpdb->last_error, 'database read error' );
$report = array( 'result' => $failures ? 'FAIL' : 'PASS', 'failures' => $failures,
    'acf_fields' => $fields, 'meta_counts' => $meta_counts, 'types' => $types, 'records' => $data );
WP_CLI::line( 'STAGE1_VERIFY_JSON=' . wp_json_encode( $report, JSON_UNESCAPED_SLASHES ) );
if ( $failures ) {
    WP_CLI::error( 'Stage 1 verifier failed.' );
}
