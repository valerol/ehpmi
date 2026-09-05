<?php
require __DIR__ . '/spec.php';

ehpmi_html_refactor_assert_dev();
$spec = ehpmi_html_refactor_spec();
$errors = array();
$counts = array(
	'records'          => 0,
	'projects'         => 0,
	'excerpt_blocks'   => 0,
	'untitled_maps'    => 0,
	'nonlazy_maps'     => 0,
	'empty_headings'   => 0,
	'project_fieldsets'=> 0,
	'featured_alts'    => 0,
);

$rows = get_posts(
	array(
		'post_type'      => array( 'post', 'project' ),
		'post_status'    => 'publish',
		'post__in'       => $spec['content_ids'],
		'posts_per_page' => -1,
	)
);
$counts['records'] = count( $rows );

foreach ( $rows as $row ) {
	$counts['excerpt_blocks'] += substr_count( $row->post_content, '<!-- wp:post-excerpt' );
	$counts['empty_headings'] += preg_match_all( '/<h[1-6][^>]*>\s*<\/h[1-6]>/i', $row->post_content );
	if ( preg_match_all( '/<iframe\b[^>]+(?:google\.com\/maps|maps\.google\.)[^>]*>/i', $row->post_content, $iframes ) ) {
		foreach ( $iframes[0] as $iframe ) {
			if ( ! preg_match( '/\btitle="[^"]+"/i', $iframe ) ) {
				$counts['untitled_maps']++;
			}
			if ( ! preg_match( '/\bloading="lazy"/i', $iframe ) || ! preg_match( '/\breferrerpolicy="no-referrer-when-downgrade"/i', $iframe ) ) {
				$counts['nonlazy_maps']++;
			}
		}
	}

	if ( 'project' === $row->post_type ) {
		$counts['projects']++;
		$has_value = false;
		foreach ( $spec['fields'] as $field_name => $field_key ) {
			$value = (string) get_post_meta( $row->ID, $field_name, true );
			$ref   = (string) get_post_meta( $row->ID, '_' . $field_name, true );
			$has_value = $has_value || '' !== trim( wp_strip_all_tags( $value ) );
			if ( $field_key !== $ref ) {
				$errors[] = "Project {$row->ID}: invalid ACF reference for {$field_name}.";
			}
		}
		if ( ! $has_value ) {
			$errors[] = "Project {$row->ID}: no structured summary value.";
		} else {
			$counts['project_fieldsets']++;
		}
	}
}

foreach ( $spec['featured_alts'] as $attachment_id => $expected ) {
	$actual = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	if ( $expected === $actual ) {
		$counts['featured_alts']++;
	} else {
		$errors[] = "Attachment {$attachment_id}: alt text mismatch.";
	}
}

$content_1953 = (string) get_post_field( 'post_content', 1953 );
if ( str_contains( $content_1953, '<h4' ) || 2 !== substr_count( $content_1953, '<!-- wp:heading {"level":3} -->' ) ) {
	$errors[] = 'Post 1953: heading hierarchy migration mismatch.';
}
$content_1988 = (string) get_post_field( 'post_content', 1988 );
if ( ! str_contains( $content_1988, '<caption>Highest DDT concentrations identified during site assessments</caption>' ) || ! str_contains( $content_1988, '<thead>' ) ) {
	$errors[] = 'Post 1988: semantic table migration mismatch.';
}
$content_2355 = (string) get_post_field( 'post_content', 2355 );
if ( ! str_contains( $content_2355, '<caption>Workshop presentations by EHPMI members and partners</caption>' ) || preg_match( '/<(?:table|th|td)\b[^>]*\sstyle=/i', $content_2355 ) ) {
	$errors[] = 'Post 2355: semantic table migration mismatch.';
}

if ( $spec['version'] !== get_option( 'ehpmi_html_structure_migration' ) ) {
	$errors[] = 'Migration marker mismatch.';
}
if ( 41 !== $counts['records'] || 28 !== $counts['projects'] || 0 !== $counts['excerpt_blocks'] || 0 !== $counts['untitled_maps'] || 0 !== $counts['nonlazy_maps'] || 0 !== $counts['empty_headings'] || 28 !== $counts['project_fieldsets'] || 4 !== $counts['featured_alts'] ) {
	$errors[] = 'Aggregate verification counters mismatch.';
}

$report = array( 'pass' => empty( $errors ), 'version' => $spec['version'], 'counts' => $counts, 'errors' => $errors );
echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
if ( $errors ) {
	throw new RuntimeException( 'Post-migration verification failed.' );
}
