<?php
require __DIR__ . '/spec.php';

ehpmi_html_refactor_assert_dev();
$spec = ehpmi_html_refactor_spec();
$rows = get_posts(
	array(
		'post_type'      => array( 'post', 'project' ),
		'post_status'    => 'publish',
		'post__in'       => $spec['content_ids'],
		'posts_per_page' => -1,
	)
);

$report = array(
	'boundary' => array( 'home' => get_option( 'home' ), 'database' => DB_NAME ),
	'counts'   => array( 'records' => count( $rows ), 'projects' => 0, 'excerpt_blocks' => 0, 'map_iframes' => 0, 'empty_headings' => 0 ),
	'projects' => array(),
);

foreach ( $rows as $row ) {
	$report['counts']['excerpt_blocks'] += substr_count( $row->post_content, '<!-- wp:post-excerpt' );
	$report['counts']['map_iframes']    += preg_match_all( '/<iframe[^>]+(?:google\.com\/maps|maps\.google\.)/i', $row->post_content );
	$report['counts']['empty_headings'] += preg_match_all( '/<h[1-6][^>]*>\s*<\/h[1-6]>/i', $row->post_content );

	if ( 'project' === $row->post_type ) {
		$report['counts']['projects']++;
		$parsed = ehpmi_html_refactor_parse_excerpt( $row->post_excerpt );
		$report['projects'][] = array(
			'id'      => $row->ID,
			'intro'   => '' !== trim( wp_strip_all_tags( $parsed['project_intro'] ) ),
			'facts'   => count( array_filter( array_diff_key( $parsed, array( 'project_intro' => true ) ) ) ),
			'preview' => $parsed,
		);
	}
}

$report['pass'] = 41 === $report['counts']['records']
	&& 28 === $report['counts']['projects']
	&& 39 === $report['counts']['excerpt_blocks']
	&& 17 === $report['counts']['map_iframes']
	&& 2 === $report['counts']['empty_headings'];

echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
if ( ! $report['pass'] ) {
	throw new RuntimeException( 'Pre-migration audit did not match the approved snapshot.' );
}
