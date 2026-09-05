<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <?php wp_head(); ?>
    <script type="application/ld+json">
        <?php
        echo wp_json_encode(
            array(
                '@context' => 'https://schema.org',
                '@type'    => 'Organization',
                'name'     => html_entity_decode(
                    get_option( 'blogname' ),
                    ENT_QUOTES | ENT_HTML5,
                    get_bloginfo( 'charset' )
                ),
                'url'      => home_url( '/' ),
                'logo'     => get_theme_file_uri( '/images/logo.svg' ),
            ),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        ?>
    </script>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text skip-link" href="#main-content"><?php esc_html_e( 'Skip to content', 'ehpmi' ); ?></a>

<!--Header Nav-->
<header class="header">
    <div class="container">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="custom-logo-link" rel="home"<?php echo is_front_page() ? ' aria-current="page"' : ''; ?>><img width="500" height="179" src="<?php echo esc_url( get_theme_file_uri( '/images/logo.svg' ) ); ?>" class="custom-logo" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" decoding="async"></a>

        <nav class="primary-navigation" aria-label="<?php esc_attr_e( 'Primary navigation', 'ehpmi' ); ?>">
            <?php
            wp_nav_menu([
                'theme_location' => 'top-menu',
                'menu_class'     => 'nav',
                'container'      => 'ul',
                'walker'         => new Top_Nav(),
                'depth'          => 2,
                'fallback_cb'    => false,
            ]);
            ?>
        </nav>

        <nav class="navbar" aria-label="<?php esc_attr_e( 'Mobile navigation', 'ehpmi' ); ?>">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent1"
                    aria-controls="navbarSupportedContent1" aria-expanded="false" aria-label="Toggle navigation"></button>
                <?php
                wp_nav_menu([
                    'theme_location' => 'top-menu',
                    'menu_class' => 'collapse navbar-collapse navbar-nav',
                    'menu_id' => 'navbarSupportedContent1',
                    'container'  => 'ul',
                    'container_class'  => 'navbar-nav',
                    'walker' => New Top_Nav(),
                    'depth' => 0,
                    'no_dropdown' => [1480],
                    'fallback_cb' => false,
                ]);
                ?>        
        </nav>
        <a class="btn btn-primary" href="<?php echo esc_url( home_url( '/#contact-us' ) ); ?>">Contact us</a>
    </div>
</header>
<?php if (function_exists('bcn_display') && !is_front_page()) : ?>
    <nav id="breadcrumbs" class="container" aria-label="<?php esc_attr_e( 'Breadcrumb', 'ehpmi' ); ?>"><?php bcn_display(); ?></nav><?php endif ?>
