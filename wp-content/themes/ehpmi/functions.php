<?php

require_once get_template_directory() . '/classes/class-menu-dropdown.php';

/**
 * Return a cache-safe version for a theme asset.
 */
function ehpmi_asset_version( $relative_path ) {
    $absolute_path = get_theme_file_path( $relative_path );

    return file_exists( $absolute_path )
        ? (string) filemtime( $absolute_path )
        : wp_get_theme()->get( 'Version' );
}

/**
 * Register the accepted frontend stack through the WordPress lifecycle.
 *
 * Library versions intentionally match the visual baseline. Version alignment
 * and removal of jQuery are separate refactor stages.
 */
function ehpmi_enqueue_assets() {
    wp_enqueue_style(
        'ehpmi-fonts',
        'https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap',
        array(),
        null
    );
    wp_enqueue_style( 'ehpmi-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3' );
    wp_enqueue_style( 'ehpmi-owl-carousel', 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css', array(), '2.3.4' );
    wp_enqueue_style( 'ehpmi-owl-theme', 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css', array( 'ehpmi-owl-carousel' ), '2.3.4' );
    wp_enqueue_style( 'ehpmi-animate', 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.compat.min.css', array(), '4.1.1' );
    wp_enqueue_style( 'ehpmi-theme', get_stylesheet_uri(), array( 'ehpmi-bootstrap' ), ehpmi_asset_version( '/style.css' ) );
    wp_enqueue_style( 'ehpmi-site', get_theme_file_uri( '/css/style.css' ), array( 'ehpmi-theme', 'ehpmi-owl-theme', 'ehpmi-animate' ), ehpmi_asset_version( '/css/style.css' ) );

    wp_enqueue_script( 'ehpmi-font-awesome', 'https://kit.fontawesome.com/51d28c3d4c.js', array(), null, false );
    wp_enqueue_script( 'ehpmi-popper', 'https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js', array(), '1.12.9', true );
    wp_enqueue_script( 'ehpmi-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js', array( 'jquery', 'ehpmi-popper' ), '4.0.0', true );
    wp_enqueue_script( 'ehpmi-owl-carousel', 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js', array( 'jquery' ), '2.3.4', true );
    wp_enqueue_script(
        'ehpmi-site',
        get_theme_file_uri( '/onload.js' ),
        array( 'jquery', 'ehpmi-bootstrap', 'ehpmi-owl-carousel' ),
        ehpmi_asset_version( '/onload.js' ),
        true
    );
}
add_action( 'wp_enqueue_scripts', 'ehpmi_enqueue_assets' );

/**
 * Retain the integrity attributes from the accepted baseline stylesheet tags.
 */
function ehpmi_style_loader_tag( $html, $handle ) {
    $attributes = array(
        'ehpmi-bootstrap' => array(
            'integrity'   => 'sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3',
            'crossorigin' => 'anonymous',
        ),
        'ehpmi-owl-carousel' => array(
            'integrity'      => 'sha512-tS3S5qG0BlhnQROyJXvNjeEM4UpMXHrQfTGmbQ1gKmelCxlSEBUaxhRBj/EFTzpbP4RVSrpEikbmdJobCvhE3g==',
            'crossorigin'    => 'anonymous',
            'referrerpolicy' => 'no-referrer',
        ),
        'ehpmi-owl-theme' => array(
            'integrity'      => 'sha512-sMXtMNL1zRzolHYKEujM2AqCLUR9F2C4/05cdbxjjLSRvMQIciEPCQZo++nk7go3BtSuK9kfa/s+a4f4i5pLkw==',
            'crossorigin'    => 'anonymous',
            'referrerpolicy' => 'no-referrer',
        ),
        'ehpmi-animate' => array(
            'integrity'      => 'sha512-b42SanD3pNHoihKwgABd18JUZ2g9j423/frxIP5/gtYgfBz/0nDHGdY/3hi+3JwhSckM3JLklQ/T6tJmV7mZEw==',
            'crossorigin'    => 'anonymous',
            'referrerpolicy' => 'no-referrer',
        ),
    );

    if ( ! isset( $attributes[ $handle ] ) ) {
        return $html;
    }

    $processor = new WP_HTML_Tag_Processor( $html );
    if ( ! $processor->next_tag( 'link' ) ) {
        return $html;
    }

    foreach ( $attributes[ $handle ] as $attribute => $value ) {
        $processor->set_attribute( $attribute, $value );
    }

    return $processor->get_updated_html();
}
add_filter( 'style_loader_tag', 'ehpmi_style_loader_tag', 10, 2 );

/**
 * Retain the integrity attributes from the accepted baseline script tags.
 */
function ehpmi_script_loader_tag( $tag, $handle ) {
    $attributes = array(
        'ehpmi-font-awesome' => array(
            'crossorigin' => 'anonymous',
        ),
        'ehpmi-popper' => array(
            'integrity'   => 'sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q',
            'crossorigin' => 'anonymous',
        ),
        'ehpmi-bootstrap' => array(
            'integrity'   => 'sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl',
            'crossorigin' => 'anonymous',
        ),
        'ehpmi-owl-carousel' => array(
            'integrity'      => 'sha512-bPs7Ae6pVvhOSiIcyUClR7/q2OAsRiovw4vAkX+zJbw3ShAeeqezq50RIIcIURq7Oa20rW2n2q+fyXBNcU9lrw==',
            'crossorigin'    => 'anonymous',
            'referrerpolicy' => 'no-referrer',
        ),
    );

    if ( ! isset( $attributes[ $handle ] ) ) {
        return $tag;
    }

    $processor = new WP_HTML_Tag_Processor( $tag );
    if ( ! $processor->next_tag( 'script' ) ) {
        return $tag;
    }

    foreach ( $attributes[ $handle ] as $attribute => $value ) {
        $processor->set_attribute( $attribute, $value );
    }

    return $processor->get_updated_html();
}
add_filter( 'script_loader_tag', 'ehpmi_script_loader_tag', 10, 2 );

/**
 * Preserve the font connection hints that used to be hardcoded in header.php.
 */
function ehpmi_resource_hints( $urls, $relation_type ) {
    if ( 'preconnect' !== $relation_type ) {
        return $urls;
    }

    $urls[] = 'https://fonts.googleapis.com';
    $urls[] = array(
        'href'        => 'https://fonts.gstatic.com',
        'crossorigin' => 'anonymous',
    );

    return $urls;
}
add_filter( 'wp_resource_hints', 'ehpmi_resource_hints', 10, 2 );

function ehpmi_theme_setup() {
    add_theme_support('custom-logo');
    add_theme_support('widgets');
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support(
        'html5',
        array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script' )
    );
    add_theme_support( 'responsive-embeds' );
    
    register_nav_menus([
        'top-menu' => __( 'Top Menu', 'ehpmi' ), // key = theme_location, value = label in admin
        'footer-menu-1' => __( 'Footer Menu 1', 'ehpmi' ),
        'footer-menu-2' => __( 'Footer Menu 2', 'ehpmi' ),
    ]);
}
add_action('after_setup_theme','ehpmi_theme_setup');

// Our custom post type function
function ehpmi_posttype() {

    register_post_type( 'testimonial',
        array(
            'labels' => [
                'name' => __( 'Testimonials' ),
                'singular_name' => __( 'Testimonial' )
            ],
            'supports' => [ 'title', 'thumbnail', 'editor' ],
            'public' => true,
        )
    );

    register_post_type( 'partner',
        array(
            'labels' => [
                'name' => __( 'Members' ),
                'singular_name' => __( 'Member' )
            ],
            'supports' => [ 'title', 'thumbnail', 'editor', 'custom-fields' ],
            'public' => true,
        )
    );
    
    register_post_type( 'partner2',
        array(
            'labels' => [
                'name' => __( 'Partners' ),
                'singular_name' => __( 'Partner' )
            ],
            'supports' => [ 'title', 'thumbnail', 'editor', 'custom-fields' ],
            'public' => true,
        )
    );
    
    register_post_type( 'material',
        array(
            'labels' => [
                'name' => __( 'Materials' ),
                'singular_name' => __( 'Material' )
            ],
            'supports' => [ 'title', 'thumbnail', 'editor', 'custom-fields' ],
            'public' => true,
            'taxonomies' => [ 'category' ],
        )
    );
    
    register_post_type('staff_member', [
        'labels'       => [
            'name'          => 'Staff',
            'singular_name' => 'Staff member',
        ],
        'public'       => true,
        'has_archive'  => false,
        'rewrite'      => [
            'slug'       => 'about/staff', // NO %placeholder%
            'with_front' => false,
        ],
        'supports'     => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
        'show_in_rest' => true,
    ]);
}
// Hooking up our function to theme setup
add_action( 'init', 'ehpmi_posttype' );

// Custom widgets
function register_custom_widget_area() {
    register_sidebar(
        array(
            'id' => 'slider-text',
            'name' => esc_html__( 'Slider text', 'ehpmi' ),
            'before_widget' => '<div class="text">',
            'after_widget' => '</div>',
        )
    );
    register_sidebar(
        array(
            'id' => 'map-text',
            'name' => esc_html__( 'Map text', 'ehpmi' ),
            'before_widget' => '<div class="text">',
            'after_widget' => '</div>',
        )
    );
    register_sidebar(
        array(
            'id' => 'newsletter',
            'name' => esc_html__( 'Newsletter', 'ehpmi' ),
            'before_widget' => '',
            'after_widget' => '',
            'before_title' => '<h3>',
            'after_title' => '</h3>'
        )
    );
    register_sidebar(
        array(
            'id' => 'contact-form-1',
            'name' => esc_html__( 'Contact form 1', 'ehpmi' ),
            'before_widget' => '',
            'after_widget' => '',
        )
    );
    register_sidebar(
        array(
            'id' => 'contact-form-2',
            'name' => esc_html__( 'Contact form 2', 'ehpmi' ),
            'before_widget' => '',
            'after_widget' => '',
        )
    );
    register_sidebar(
        array(
            'id' => 'footer-text',
            'name' => esc_html__( 'Footer text', 'ehpmi' ),
            'before_widget' => '<div class="footer-nav-block">',
            'after_widget' => '</div>',
            'before_title' => '<h3>',
            'after_title' => '</h3>'
        )
    );
    register_sidebar(
        array(
            'id' => 'footer-menu-1',
            'name' => esc_html__( 'Footer menu 1', 'ehpmi' ),
            'before_widget' => '<div class="footer-nav-block">',
            'after_widget' => '</div>',
            'before_title' => '<h3>',
            'after_title' => '</h3>'
        )
    );
    register_sidebar(
        array(
            'id' => 'footer-menu-2',
            'name' => esc_html__( 'Footer menu 2', 'ehpmi' ),
            'before_widget' => '<div class="footer-nav-block">',
            'after_widget' => '</div>',
            'before_title' => '<h3>',
            'after_title' => '</h3>'
        )
    );
    register_sidebar(
        [
            'id' => 'footer-contacts',
            'name' => esc_html__( 'Footer contacts', 'ehpmi' ),
            'before_widget' => '<div class="footer-nav-block">',
            'after_widget' => '</div>'
        ]
    );
    register_sidebar(
        [
            'id' => 'footer-social',
            'name' => esc_html__( 'Footer social', 'ehpmi' ),
            'before_widget' => '',
            'after_widget' => '',
        ]
    );
    register_sidebar(
        [
            'id' => 'footer-copyright',
            'name' => esc_html__( 'Footer copyright', 'ehpmi' ),
            'before_widget' => '',
            'after_widget' => '',
        ]
    );
}
add_action( 'widgets_init', 'register_custom_widget_area' );

remove_filter('get_the_excerpt', 'wp_trim_excerpt');
/*
wp_cache_flush();

add_action('init', function() {
    flush_rewrite_rules();
});*/
