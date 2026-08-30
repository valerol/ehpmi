<?php
/**
 * Read-only verification for the EHPMI content-model migration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "Run this file through WP-CLI.\n" );
    exit( 1 );
}

if ( 'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) ) || 'nykvymmy_ehpmidev' !== DB_NAME ) {
    WP_CLI::error( 'Refusing to verify outside the EHPMI dev site and dev database.' );
}

global $wpdb;

$failures = array();
$check = static function ( $condition, $message ) use ( &$failures ) {
    if ( ! $condition ) {
        $failures[] = $message;
    }
};

$member_count   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'member'" );
$partner_count  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'partner'" );
$partner2_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'partner2'" );

$check( 7 === $member_count, 'Expected 7 member records.' );
$check( 1 === $partner_count, 'Expected 1 partner record.' );
$check( 0 === $partner2_count, 'Expected no partner2 records.' );

$member_type  = get_post_type_object( 'member' );
$partner_type = get_post_type_object( 'partner' );
$material_type = get_post_type_object( 'material' );

$check( $member_type && $member_type->show_ui, 'Member admin UI is unavailable.' );
$check( $partner_type && $partner_type->show_ui, 'Partner admin UI is unavailable.' );
$check( $member_type && ! $member_type->publicly_queryable, 'Member records remain publicly queryable.' );
$check( $partner_type && ! $partner_type->publicly_queryable, 'Partner records remain publicly queryable.' );
$check( $material_type && ! post_type_supports( 'material', 'editor' ), 'Material editor support remains enabled.' );
$check( $material_type && post_type_supports( 'material', 'thumbnail' ), 'Material thumbnails were not preserved.' );

$member_meta = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'member'" );
$member_refs = (int) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_member' AND meta_value = %s",
        'field_634109faf9126'
    )
);
$old_member_meta = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'partner'" );
$old_member_refs = (int) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_partner' AND meta_value = %s",
        'field_634109faf9126'
    )
);

$check( 9 === $member_meta, 'Expected 9 country member values.' );
$check( 9 === $member_refs, 'Expected 9 country member ACF references.' );
$check( 0 === $old_member_meta, 'Old country partner values remain.' );
$check( 0 === $old_member_refs, 'Old country partner ACF references remain.' );

$materials = get_posts(
    array(
        'post_type'      => 'material',
        'post_status'    => 'any',
        'posts_per_page' => -1,
    )
);
$materials_with_file = 0;
$materials_with_thumbnail = 0;
foreach ( $materials as $material ) {
    if ( get_post_meta( $material->ID, 'file', true ) ) {
        ++$materials_with_file;
    }
    if ( has_post_thumbnail( $material ) ) {
        ++$materials_with_thumbnail;
    }
}

$check( 19 === count( $materials ), 'Expected 19 materials.' );
$check( 19 === $materials_with_file, 'Not every material has a file.' );
$check( 19 === $materials_with_thumbnail, 'Not every material retained its thumbnail.' );

if ( function_exists( 'acf_get_field_groups' ) && function_exists( 'acf_get_fields' ) ) {
    $groups = acf_get_field_groups();
    $groups_by_key = array();
    foreach ( $groups as $group ) {
        $groups_by_key[ $group['key'] ] = $group;
    }

    $member_group = isset( $groups_by_key['group_6336c6465a962'] ) ? $groups_by_key['group_6336c6465a962'] : null;
    $country_group = isset( $groups_by_key['group_633fd14d6b279'] ) ? $groups_by_key['group_633fd14d6b279'] : null;

    $check( $member_group && 'Member fields' === $member_group['title'], 'Member ACF group title is incorrect.' );
    $check( $member_group && isset( $member_group['local'] ) && 'json' === $member_group['local'], 'Member ACF group is not loaded from JSON.' );
    $check( $country_group && isset( $country_group['local'] ) && 'json' === $country_group['local'], 'Country ACF group is not loaded from JSON.' );

    if ( $country_group ) {
        $country_fields = acf_get_fields( $country_group );
        $member_field = null;
        foreach ( $country_fields as $field ) {
            if ( 'field_634109faf9126' === $field['key'] ) {
                $member_field = $field;
                break;
            }
        }
        $check( $member_field && 'member' === $member_field['name'], 'Country ACF member field name is incorrect.' );
        $check( $member_field && array( 'member' ) === $member_field['post_type'], 'Country ACF member field targets the wrong post type.' );
    }
} else {
    $failures[] = 'ACF API is unavailable.';
}

$newsletter_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}newsletter" );
$newsletter_sent  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}newsletter_sent" );
$check( 172 === $newsletter_count, 'Newsletter subscriber count changed.' );
$check( 0 === $newsletter_sent, 'Newsletter sent table is no longer empty.' );

if ( $failures ) {
    WP_CLI::error( implode( "\n", $failures ) );
}

WP_CLI::success(
    sprintf(
        'Verified member=%d, partner=%d, material=%d, country_member_meta=%d, newsletter=%d.',
        $member_count,
        $partner_count,
        count( $materials ),
        $member_meta,
        $newsletter_count
    )
);
