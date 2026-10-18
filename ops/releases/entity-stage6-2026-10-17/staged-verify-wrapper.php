<?php
$staging_root = '/home2/nykvymmy/backups/entity-stage6-2026-10-17/staging-mobile-gate';

add_filter(
    'theme_file_path',
    static function ( $path, $file ) use ( $staging_root ) {
        if ( in_array( $file, array( 'template-parts/slider.php', 'onload.js' ), true ) ) {
            return $staging_root . '/' . $file;
        }

        return $path;
    },
    10,
    2
);

require $staging_root . '/verify.php';
