<?php
/**
 * Shared specification for the dev-only HTML structure migration.
 */

if ( ! function_exists( 'ehpmi_html_refactor_spec' ) ) {
	function ehpmi_html_refactor_spec() {
		return array(
			'version'     => '2026-09-05-v1',
			'database'    => 'nykvymmy_ehpmidev',
			'home'        => 'https://dev.ehpmi.org',
			'content_ids' => array( 448, 455, 457, 461, 558, 717, 761, 803, 811, 821, 832, 857, 892, 1039, 1061, 1073, 1113, 1420, 1524, 1545, 1676, 1690, 1692, 1694, 1731, 1733, 1770, 1858, 1873, 1888, 1899, 1953, 1988, 2004, 2028, 2058, 2075, 2310, 2329, 2333, 2355 ),
			'project_ids' => array( 448, 455, 457, 461, 558, 717, 761, 803, 811, 821, 832, 857, 892, 1039, 1061, 1073, 1113, 1420, 1545, 1690, 1692, 1694, 1770, 1873, 1888, 1899, 1953, 1988 ),
			'fields'      => array(
				'project_intro'       => 'field_ehpmi_project_intro',
				'project_dates'       => 'field_ehpmi_project_dates',
				'people_at_risk'      => 'field_ehpmi_people_at_risk',
				'pollution_source'    => 'field_ehpmi_pollution_source',
				'project_implementers'=> 'field_ehpmi_project_implementers',
				'project_budget'      => 'field_ehpmi_project_budget',
				'project_funding'     => 'field_ehpmi_project_funding',
			),
			'featured_alts' => array(
				779 => 'Mercury-contaminated irrigation channel remediation site in Naiman, Kyrgyzstan.',
				462 => 'Lead contamination risk mitigation site in Akhtala, Armenia.',
				449 => 'Hazardous waste assessment site in Lori, Armenia.',
				619 => 'Lead health risk assessment site in Alaverdi, Armenia.',
			),
		);
	}
}

if ( ! function_exists( 'ehpmi_html_refactor_assert_dev' ) ) {
	function ehpmi_html_refactor_assert_dev() {
		$spec = ehpmi_html_refactor_spec();
		$home = untrailingslashit( (string) get_option( 'home' ) );
		$db   = defined( 'DB_NAME' ) ? DB_NAME : '';

		if ( $spec['home'] !== $home || $spec['database'] !== $db ) {
			throw new RuntimeException( sprintf( 'Boundary guard failed: home=%s db=%s', $home, $db ) );
		}
	}
}

if ( ! function_exists( 'ehpmi_html_refactor_plain_text' ) ) {
	function ehpmi_html_refactor_plain_text( $html ) {
		$html = preg_replace( '/<br\s*\/?\s*>/i', "\n", (string) $html );
		$html = preg_replace( '/<\/p\s*>/i', "\n", $html );
		$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = str_replace( "\xc2\xa0", ' ', $text );
		$text = preg_replace( '/[\t ]+/u', ' ', $text );
		$text = preg_replace( '/\s*\n\s*/u', "\n", $text );
		return trim( $text );
	}
}

if ( ! function_exists( 'ehpmi_html_refactor_parse_excerpt' ) ) {
	function ehpmi_html_refactor_parse_excerpt( $html ) {
		$plain = ehpmi_html_refactor_plain_text( $html );
		$labels = array(
			'The total cost of program implementation' => 'project_budget',
			'Number of residents / children at risk'   => 'people_at_risk',
			'Number of residents/children at risk'     => 'people_at_risk',
			'Number of residents at risk'              => 'people_at_risk',
			'Number of people at risk'                 => 'people_at_risk',
			'Source of pollution/contaminants'         => 'pollution_source',
			'Source of contamination/contaminants'     => 'pollution_source',
			'Source of pollution/pollutants'           => 'pollution_source',
			'Source of pollution'                      => 'pollution_source',
			'Project implementers'                     => 'project_implementers',
			'Project implementer'                      => 'project_implementers',
			'Implementers'                             => 'project_implementers',
			'Implementer'                              => 'project_implementers',
			'Projected costs'                          => 'project_budget',
			'Estimated cost'                           => 'project_budget',
			'Total cost'                               => 'project_budget',
			'Project duration'                         => 'project_dates',
			'Project dates'                            => 'project_dates',
			'Project term'                             => 'project_dates',
			'Funder'                                   => 'project_funding',
		);

		$alternatives = array_map(
			static function ( $label ) {
				$quoted = preg_quote( $label, '/' );
				return str_replace( array( '\\ ', '\\/' ), array( '\\s+', '\\s*\\/\\s*' ), $quoted );
			},
			array_keys( $labels )
		);
		$pattern = '/(?<![\p{L}\p{N}])(?<label>' . implode( '|', $alternatives ) . ')\s*:?\s*/iu';
		preg_match_all( $pattern, $plain, $matches, PREG_OFFSET_CAPTURE );

		$result = array(
			'project_intro'        => '',
			'project_dates'        => '',
			'people_at_risk'       => '',
			'pollution_source'     => '',
			'project_implementers' => '',
			'project_budget'       => '',
			'project_funding'      => '',
		);

		if ( empty( $matches[0] ) ) {
			$result['project_intro'] = wp_kses_post( trim( (string) $html ) );
			return $result;
		}

		$first_offset = $matches[0][0][1];
		$intro        = trim( substr( $plain, 0, $first_offset ), " \t\n\r\0\x0B:-" );
		if ( '' !== $intro ) {
			$result['project_intro'] = esc_html( $intro );
		}

		$count = count( $matches[0] );
		for ( $index = 0; $index < $count; $index++ ) {
			$label       = preg_replace( '/\s+/u', ' ', trim( $matches['label'][ $index ][0] ) );
			$matched_key = null;
			foreach ( $labels as $candidate => $key ) {
				if ( 0 === strcasecmp( preg_replace( '/\s+/u', ' ', $candidate ), $label ) ) {
					$matched_key = $key;
					break;
				}
			}
			if ( null === $matched_key ) {
				continue;
			}

			$value_start = $matches[0][ $index ][1] + strlen( $matches[0][ $index ][0] );
			$value_end   = ( $index + 1 < $count ) ? $matches[0][ $index + 1 ][1] : strlen( $plain );
			$value       = trim( substr( $plain, $value_start, $value_end - $value_start ), " \t\n\r\0\x0B:-" );
			$value       = preg_replace( '/\s*\n\s*/u', ' ', $value );
			if ( '' !== $value ) {
				$result[ $matched_key ] = $value;
			}
		}

		return $result;
	}
}

if ( ! function_exists( 'ehpmi_html_refactor_clean_content' ) ) {
	function ehpmi_html_refactor_clean_content( $post_id, $content, &$counters ) {
		$before  = $content;
		$content = preg_replace( '/\s*<!--\s+wp:post-excerpt(?:\s+\{"moreText":""\})?\s+\/-->\s*/', "\n\n", $content, -1, $removed );
		$counters['excerpt_blocks'] += $removed;

		if ( in_array( (int) $post_id, array( 803, 1988 ), true ) ) {
			$content = preg_replace( '/\s*<!-- wp:heading -->\s*<h2 class="wp-block-heading"><\/h2>\s*<!-- \/wp:heading -->\s*/', "\n\n", $content, -1, $empty_headings );
			$counters['empty_headings'] += $empty_headings;
		}

		if ( 1953 === (int) $post_id ) {
			$content = str_replace( '<!-- wp:heading {"level":4} -->', '<!-- wp:heading {"level":3} -->', $content, $heading_blocks );
			$content = str_replace( array( '<h4 class="wp-block-heading">', '</h4>' ), array( '<h3 class="wp-block-heading">', '</h3>' ), $content, $heading_tags );
			$counters['heading_blocks'] += $heading_blocks;
			$counters['heading_tags']   += $heading_tags / 2;
		}

		if ( 1988 === (int) $post_id ) {
			$old = '<figure class="wp-block-table"><table class="has-fixed-layout"><tbody><tr><td>Location</td><td>Description</td><td>DDT concentration</td></tr>';
			$new = '<figure class="wp-block-table"><table class="has-fixed-layout ehpmi-data-table"><caption>Highest DDT concentrations identified during site assessments</caption><thead><tr><th scope="col">Location</th><th scope="col">Description</th><th scope="col">DDT concentration</th></tr></thead><tbody>';
			$content = str_replace( $old, $new, $content, $table_1988 );
			$counters['tables'] += $table_1988;
		}

		if ( 2355 === (int) $post_id ) {
			$content = preg_replace( '/<table\s+style="[^"]*">/', '<table class="ehpmi-data-table"><caption>Workshop presentations by EHPMI members and partners</caption>', $content, 1, $table_2355 );
			$content = preg_replace( '/(<(?:th|td)\b[^>]*)\s+style="[^"]*"([^>]*>)/i', '$1$2', $content );
			$counters['tables'] += $table_2355;
		}

		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$processor = new WP_HTML_Tag_Processor( $content );
			while ( $processor->next_tag( 'iframe' ) ) {
				$src = (string) $processor->get_attribute( 'src' );
				if ( false === stripos( $src, 'google.com/maps' ) && false === stripos( $src, 'maps.google.' ) ) {
					continue;
				}
				if ( ! $processor->get_attribute( 'title' ) ) {
					$processor->set_attribute( 'title', 'Project location map: ' . get_the_title( $post_id ) );
				}
				$processor->set_attribute( 'loading', 'lazy' );
				$processor->set_attribute( 'referrerpolicy', 'no-referrer-when-downgrade' );
				$counters['map_iframes']++;
			}
			$content = $processor->get_updated_html();
		}

		return array( $content, $before !== $content );
	}
}
