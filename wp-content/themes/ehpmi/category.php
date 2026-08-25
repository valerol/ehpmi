<?php get_header(); ?>

<?php

if ( have_posts() ) :

    // Get current category object
    $current_cat = get_queried_object();
    $cat_slug = $current_cat->slug;

    switch ( $cat_slug ) {
        case 'news':
            // Include a custom template for 'news' category
            get_template_part( 'template-parts/news', get_post_type(), ['category_name' => single_cat_title('', false)] );
            break;

        case 'projects':
        case 'current':
        case 'potential':
        case 'past':
            // Include a custom template for 'projects' category
            get_template_part( 'template-parts/projects', get_post_type(), ['category_name' => single_cat_title('', false), 'projects' => $wp_query->posts ] );
            break;

        default:
	    // The Loop
	   
            while ( have_posts() ) : the_post();

	    echo '<article class="main-article"><div class="container"><header><h1>' . get_the_title() . '</header></h1>' . get_the_content() . '</div></article>';
            endwhile;

            $materials = get_posts([
                'post_type'      => 'material',
                'posts_per_page' => -1, // or a specific number
                'orderby'        => 'date',
                'order'          => 'DESC',
                'tax_query'      => [
                    [
                        'taxonomy' => 'category',   // default WordPress category
                        'field'    => 'slug',       // you can also use 'term_id'
                        'terms'    => $cat_slug,
                    ],
                ],
            ]);
            
            // Include a custom template for 'library' subcategories
            get_template_part( 'template-parts/library', get_post_type(), ['materials' => $materials] );
            break;
    }
endif;

get_template_part( 'template-parts/footer-menus-widgets' );

get_footer();
