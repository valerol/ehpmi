<?php
$base = 'https://dev.ehpmi.org';
$inventory_path = $argv[1] ?? '';
$inventory = json_decode( file_get_contents( $inventory_path ), true, 512, JSON_THROW_ON_ERROR );
$failures = array();
$check = static function ( $condition, $label ) use ( &$failures ) {
    if ( ! $condition ) {
        $failures[] = $label;
    }
};
$request = static function ( $url, $head = false, $follow = true ) {
    $headers = array();
    $handle = curl_init( $url );
    curl_setopt_array(
        $handle,
        array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => $follow,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_NOBODY => $head,
            CURLOPT_USERAGENT => 'EHPMI-Stage4-QA/1.0',
            CURLOPT_HEADERFUNCTION => static function ( $curl, $line ) use ( &$headers ) {
                $length = strlen( $line );
                $parts = explode( ':', $line, 2 );
                if ( 2 === count( $parts ) ) {
                    $headers[ strtolower( trim( $parts[0] ) ) ] = trim( $parts[1] );
                }
                return $length;
            },
        )
    );
    $body = curl_exec( $handle );
    $error = curl_error( $handle );
    $code = (int) curl_getinfo( $handle, CURLINFO_RESPONSE_CODE );
    $effective = (string) curl_getinfo( $handle, CURLINFO_EFFECTIVE_URL );
    curl_close( $handle );
    return array( 'code' => $code, 'body' => false === $body ? '' : $body, 'error' => $error, 'headers' => $headers, 'effective' => $effective );
};
$parse = static function ( $html ) {
    $dom = new DOMDocument( '1.0', 'UTF-8' );
    libxml_use_internal_errors( true );
    $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
    libxml_clear_errors();
    return new DOMXPath( $dom );
};
$card_ids = static function ( DOMXPath $xpath, $prefix ) {
    $ids = array();
    foreach ( $xpath->query( '//article[starts-with(@id,"' . $prefix . '")]' ) as $node ) {
        $ids[] = (int) substr( $node->getAttribute( 'id' ), strlen( $prefix ) );
    }
    return $ids;
};

$routes = array(
    '/library/' => array( array( 1852, 1739, 532, 529, 526, 523, 520, 516, 513, 510, 507, 504 ), true ),
    '/library/page/2/' => array( array( 501, 498, 494, 491, 488, 485, 482 ), true ),
    '/library/health-and-pollution/' => array( array( 523, 520, 516, 513, 510, 507, 504, 501, 498, 494, 491, 488 ), true ),
    '/library/health-and-pollution/page/2/' => array( array( 485, 482 ), true ),
    '/library/action-plans/' => array( array( 1852, 529, 526 ), false ),
    '/library/lead-health-risk-assessment/' => array( array( 1739, 532 ), false ),
    '/library/publications-and-co-authored-publications/' => array( array(), false ),
    '/library/videos/' => array( array(), false ),
);
$route_results = array();
$root_links = array();
$root_thumbnails = array();
foreach ( $routes as $path => $definition ) {
    $url = $base . $path;
    $response = $request( $url );
    $check( '' === $response['error'], $path . ' request error' );
    $check( 200 === $response['code'], $path . ' HTTP 200' );
    $xpath = $parse( $response['body'] );
    $ids = $card_ids( $xpath, 'material-card-' );
    $check( $definition[0] === $ids, $path . ' exact ordered IDs' );
    $check( 1 === $xpath->query( '//link[@rel="canonical" and @href="' . $url . '"]' )->length, $path . ' self canonical' );
    $check( 1 === $xpath->query( '//nav[@id="breadcrumbs" and @aria-label="Breadcrumb"]' )->length, $path . ' breadcrumb' );
    $check( 1 === $xpath->query( '//main[@id="main-content"]//h1' )->length, $path . ' one H1' );
    $pagination_count = $xpath->query( '//nav[contains(concat(" ",normalize-space(@class)," ")," material-pagination ")]' )->length;
    $check( ( $definition[1] ? 1 : 0 ) === $pagination_count, $path . ' pagination state' );

    foreach ( $xpath->query( '//article[starts-with(@id,"material-card-")]' ) as $card ) {
        $id = (int) substr( $card->getAttribute( 'id' ), strlen( 'material-card-' ) );
        $links = $xpath->query( './/a', $card );
        $primary = $xpath->query( './/a[contains(concat(" ",normalize-space(@class)," ")," material__primary ")]', $card );
        $headings = $xpath->query( './/h2[contains(concat(" ",normalize-space(@class)," ")," file ")]', $card );
        $meta = $xpath->query( './/*[contains(concat(" ",normalize-space(@class)," ")," material__meta ")]', $card );
        $images = $xpath->query( './/img', $card );
        $check( 1 === $links->length && 1 === $primary->length, $path . ' one primary link ' . $id );
        $check( 1 === $headings->length && '' !== trim( $headings->item( 0 )->textContent ), $path . ' semantic heading ' . $id );
        $check( 1 === $meta->length && 1 === preg_match( '/^(PDF|JPEG) . [0-9.]+ (KB|MB|GB)$/u', trim( $meta->item( 0 )->textContent ) ), $path . ' derived metadata ' . $id );
        $check( 1 === $images->length && '' === $images->item( 0 )->getAttribute( 'alt' ), $path . ' decorative thumbnail ' . $id );
        if ( 1 === $primary->length ) {
            $check( '' !== trim( $primary->item( 0 )->textContent ), $path . ' accessible link name ' . $id );
        }
        if ( '/library/' === $path || '/library/page/2/' === $path ) {
            $root_links[ $id ] = $primary->item( 0 )->getAttribute( 'href' );
            $root_thumbnails[ $id ] = $images->item( 0 )->getAttribute( 'src' );
        }
    }
    $route_results[ $path ] = array( 'http' => $response['code'], 'ids' => $ids, 'bytes' => strlen( $response['body'] ) );
}
$check( 19 === count( $root_links ), '19 unique root file links' );
$check( 19 === count( $root_thumbnails ), '19 unique root thumbnails' );

$publications = $request( $base . '/library/publications-and-co-authored-publications/' );
$publications_xpath = $parse( $publications['body'] );
$check( 2 <= $publications_xpath->query( '//main[@id="main-content"]//a[@href]' )->length, 'Publications editorial links retained' );
$videos = $request( $base . '/library/videos/' );
$videos_xpath = $parse( $videos['body'] );
$check( 2 <= $videos_xpath->query( '//main[@id="main-content"]//iframe' )->length, 'Videos embeds retained' );

$file_http = array();
foreach ( $root_links as $id => $url ) {
    $response = $request( $url, true );
    $check( 200 === $response['code'], 'file HTTP 200 ' . $id );
    $file_http[ $id ] = array( 'http' => $response['code'], 'url' => $url );
}
$thumbnail_http = array();
foreach ( $root_thumbnails as $id => $url ) {
    $response = $request( $url, true );
    $check( 200 === $response['code'], 'thumbnail HTTP 200 ' . $id );
    $thumbnail_http[ $id ] = array( 'http' => $response['code'], 'url' => $url );
}

$legacy_http = array();
foreach ( $inventory['materials'] as $material ) {
    $url = $base . $material['legacy_single_path'];
    $response = $request( $url, true, false );
    $expected_location = $material['resolved_file_url'];
    $location = $response['headers']['location'] ?? '';
    $check( 301 === $response['code'] && $expected_location === $location, 'legacy redirect ' . $material['id'] );
    $legacy_http[ $material['id'] ] = array( 'http' => $response['code'], 'location' => $location );
}

$private = array(
    '/material/',
    '/material-type/health-and-pollution/',
    '/wp-json/wp/v2/material',
    '/wp-sitemap-posts-material-1.xml',
    '/wp-sitemap-taxonomies-material_type-1.xml',
);
$private_results = array();
foreach ( $private as $path ) {
    $response = $request( $base . $path );
    $check( 404 === $response['code'], $path . ' private HTTP 404' );
    $private_results[ $path ] = $response['code'];
}
$sitemap = $request( $base . '/wp-sitemap.xml' );
$check( 200 === $sitemap['code'], 'sitemap index HTTP 200' );
$check( false === strpos( $sitemap['body'], 'wp-sitemap-posts-material-' ), 'no Material sitemap provider' );
$check( false === strpos( $sitemap['body'], 'wp-sitemap-taxonomies-material_type-' ), 'no material_type sitemap provider' );
$search = $request( $base . '/?s=ESCM%20activities%202023-2024' );
$search_xpath = $parse( $search['body'] );
$check( 200 === $search['code'] && 0 === $search_xpath->query( '//article[starts-with(@id,"material-card-")]' )->length, 'Material excluded from search' );

$regressions = array(
    '/' => array( 'project-card-' => 3 ),
    '/blog/news/' => array( 'news-card-' => 12 ),
    '/blog/news/page/2/' => array( 'news-card-' => 1 ),
    '/projects/' => array( 'project-card-' => 12 ),
    '/projects/page/2/' => array( 'project-card-' => 12 ),
    '/projects/page/3/' => array( 'project-card-' => 4 ),
    '/projects/current/' => array( 'project-card-' => 6 ),
    '/projects/past/' => array( 'project-card-' => 12 ),
    '/projects/past/page/2/' => array( 'project-card-' => 1 ),
    '/projects/potential/' => array( 'project-card-' => 9 ),
);
$regression_results = array();
foreach ( $regressions as $path => $expectation ) {
    $response = $request( $base . $path );
    $xpath = $parse( $response['body'] );
    $check( 200 === $response['code'], $path . ' regression HTTP 200' );
    foreach ( $expectation as $prefix => $count ) {
        $actual = count( $card_ids( $xpath, $prefix ) );
        $check( $count === $actual, $path . ' regression ' . $prefix . ' count' );
        $regression_results[ $path ] = array( 'http' => $response['code'], 'cards' => $actual );
    }
}
$home = $request( $base . '/' );
$home_xpath = $parse( $home['body'] );
$check( 0 === $home_xpath->query( '//nav[contains(concat(" ",normalize-space(@class)," ")," projects-pagination ")]' )->length, 'homepage Projects unpaginated' );

$report = array(
    'result' => $failures ? 'FAIL' : 'PASS',
    'failures' => $failures,
    'library_routes' => $route_results,
    'file_http' => $file_http,
    'thumbnail_http' => $thumbnail_http,
    'legacy_http' => $legacy_http,
    'private_surfaces' => $private_results,
    'regressions' => $regression_results,
);
echo json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ), "\n";
exit( $failures ? 1 : 0 );
