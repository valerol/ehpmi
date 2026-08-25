<?php

require get_template_directory() . '/classes/class-menu-dropdown.php';

function ehpmi_enqueue_styles() {
    wp_enqueue_style('theme', get_template_directory_uri() . '/style.css', array(), filemtime(get_template_directory() . '/style.css'));
    wp_enqueue_style('ehpmi', get_template_directory_uri() . '/css/style.css', array(), filemtime(get_template_directory() . '/css/style.css'));
}
add_action('wp_enqueue_scripts', 'ehpmi_enqueue_styles');

function ehpmi_theme_setup() {
    add_theme_support('custom-logo');
    add_theme_support('widgets');
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    
    register_nav_menus([
        'top-menu' => __( 'Top Menu', 'ehpmi' ), // key = theme_location, value = label in admin
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

// Menus
add_theme_support( 'menus' );

add_action( 'init', 'ehpmi_menus' );

function ehpmi_menus() {
    register_nav_menus(
        array(
            'footer-menu-1' => __( 'Footer Menu 1' ),
            'footer-menu-2' => __( 'Footer Menu 2' )
        )
    );
}

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