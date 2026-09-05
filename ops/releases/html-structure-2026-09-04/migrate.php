<?php
require __DIR__ . '/spec.php';

ehpmi_html_refactor_assert_dev();
$spec = ehpmi_html_refactor_spec();

if ( $spec['version'] === get_option( 'ehpmi_html_structure_migration' ) ) {
	echo "Migration already applied: {$spec['version']}\n";
	return;
}

$rows = get_posts(
	array(
		'post_type'      => array( 'post', 'project' ),
		'post_status'    => 'publish',
		'post__in'       => $spec['content_ids'],
		'posts_per_page' => -1,
	)
);
if ( 41 !== count( $rows ) ) {
	throw new RuntimeException( 'Expected exactly 41 published records.' );
}

$project_count = count( array_filter( $rows, static function ( $row ) { return 'project' === $row->post_type; } ) );
if ( 28 !== $project_count ) {
	throw new RuntimeException( 'Expected exactly 28 published projects.' );
}

foreach ( $spec['project_ids'] as $project_id ) {
	foreach ( $spec['fields'] as $field_name => $field_key ) {
		if ( metadata_exists( 'post', $project_id, $field_name ) || metadata_exists( 'post', $project_id, '_' . $field_name ) ) {
			throw new RuntimeException( "Structured project metadata already exists for post {$project_id}." );
		}
	}
}

global $wpdb;
$wpdb->query( 'START TRANSACTION' );
$counters = array(
	'content_updates' => 0,
	'excerpt_blocks'  => 0,
	'map_iframes'     => 0,
	'empty_headings'  => 0,
	'heading_blocks'  => 0,
	'heading_tags'    => 0,
	'tables'          => 0,
	'project_fields'  => 0,
	'featured_alts'   => 0,
);

try {
	foreach ( $rows as $row ) {
		list( $content, $changed ) = ehpmi_html_refactor_clean_content( $row->ID, $row->post_content, $counters );
		if ( $changed ) {
			$result = wp_update_post( array( 'ID' => $row->ID, 'post_content' => $content ), true );
			if ( is_wp_error( $result ) ) {
				throw new RuntimeException( $result->get_error_message() );
			}
			$counters['content_updates']++;
		}

		if ( 'project' === $row->post_type ) {
			$parsed = ehpmi_html_refactor_parse_excerpt( $row->post_excerpt );
			if ( ! array_filter( $parsed, static function ( $value ) { return '' !== trim( wp_strip_all_tags( (string) $value ) ); } ) ) {
				throw new RuntimeException( "Project excerpt produced no structured data for post {$row->ID}." );
			}
			foreach ( $spec['fields'] as $field_name => $field_key ) {
				update_post_meta( $row->ID, $field_name, $parsed[ $field_name ] );
				update_post_meta( $row->ID, '_' . $field_name, $field_key );
				$counters['project_fields']++;
			}
			update_post_meta( $row->ID, '_ehpmi_project_facts_source_hash', hash( 'sha256', $row->post_excerpt ) );
		}
	}

	foreach ( $spec['featured_alts'] as $attachment_id => $alt ) {
		if ( 'attachment' !== get_post_type( $attachment_id ) ) {
			throw new RuntimeException( "Attachment {$attachment_id} was not found." );
		}
		$current = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
		if ( '' !== $current ) {
			throw new RuntimeException( "Attachment {$attachment_id} already has alt text." );
		}
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
		$counters['featured_alts']++;
	}

	if ( 39 !== $counters['excerpt_blocks'] || 17 !== $counters['map_iframes'] || 2 !== $counters['empty_headings'] || 2 !== $counters['heading_blocks'] || 2 !== $counters['heading_tags'] || 2 !== $counters['tables'] || 196 !== $counters['project_fields'] || 4 !== $counters['featured_alts'] ) {
		throw new RuntimeException( 'Migration counters do not match the approved snapshot: ' . wp_json_encode( $counters ) );
	}

	update_option( 'ehpmi_html_structure_migration', $spec['version'], false );
	$wpdb->query( 'COMMIT' );
	echo wp_json_encode( array( 'status' => 'committed', 'version' => $spec['version'], 'counters' => $counters ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
} catch ( Throwable $exception ) {
	$wpdb->query( 'ROLLBACK' );
	throw $exception;
}
