<?php
require __DIR__ . '/spec.php';

ehpmi_html_refactor_assert_dev();
$spec   = ehpmi_html_refactor_spec();
$errors = array();
$counts = array( 'urls' => 0, 'http_200' => 0, 'single_main' => 0, 'single_h1' => 0, 'single_article' => 0 );

foreach ( $spec['content_ids'] as $post_id ) {
	$url      = add_query_arg( 'ehpmi_html_qa', $spec['version'], get_permalink( $post_id ) );
	$response = wp_remote_get( $url, array( 'timeout' => 20, 'redirection' => 3 ) );
	$counts['urls']++;
	if ( is_wp_error( $response ) ) {
		$errors[] = "Post {$post_id}: " . $response->get_error_message();
		continue;
	}

	$status = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $status ) {
		$errors[] = "Post {$post_id}: HTTP {$status}.";
		continue;
	}
	$counts['http_200']++;

	$dom = new DOMDocument( '1.0', 'UTF-8' );
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . wp_remote_retrieve_body( $response ) );
	libxml_clear_errors();
	$xpath = new DOMXPath( $dom );

	$main_count = $xpath->query( '//main[@id="main-content"]' )->length;
	$h1_count   = $xpath->query( '//main[@id="main-content"]//h1' )->length;
	$article_count = $xpath->query( '//main[@id="main-content"]//article[@id="post-' . (int) $post_id . '"]' )->length;
	if ( 1 === $main_count ) {
		$counts['single_main']++;
	} else {
		$errors[] = "Post {$post_id}: main count {$main_count}.";
	}
	if ( 1 === $h1_count ) {
		$counts['single_h1']++;
	} else {
		$errors[] = "Post {$post_id}: H1 count {$h1_count}.";
	}
	if ( 1 === $article_count ) {
		$counts['single_article']++;
	} else {
		$errors[] = "Post {$post_id}: article count {$article_count}.";
	}
}

$pass = empty( $errors ) && 41 === $counts['urls'] && 41 === $counts['http_200'] && 41 === $counts['single_main'] && 41 === $counts['single_h1'] && 41 === $counts['single_article'];
echo wp_json_encode( array( 'pass' => $pass, 'counts' => $counts, 'errors' => $errors ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
if ( ! $pass ) {
	throw new RuntimeException( 'Frontend verification failed.' );
}
