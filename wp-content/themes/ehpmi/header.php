<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" integrity="sha512-tS3S5qG0BlhnQROyJXvNjeEM4UpMXHrQfTGmbQ1gKmelCxlSEBUaxhRBj/EFTzpbP4RVSrpEikbmdJobCvhE3g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css" integrity="sha512-sMXtMNL1zRzolHYKEujM2AqCLUR9F2C4/05cdbxjjLSRvMQIciEPCQZo++nk7go3BtSuK9kfa/s+a4f4i5pLkw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.compat.min.css" integrity="sha512-b42SanD3pNHoihKwgABd18JUZ2g9j423/frxIP5/gtYgfBz/0nDHGdY/3hi+3JwhSckM3JLklQ/T6tJmV7mZEw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://kit.fontawesome.com/51d28c3d4c.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js" integrity="sha512-bPs7Ae6pVvhOSiIcyUClR7/q2OAsRiovw4vAkX+zJbw3ShAeeqezq50RIIcIURq7Oa20rW2n2q+fyXBNcU9lrw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="/wp-content/themes/ehpmi/onload.js"></script>
    <?php wp_head(); ?>
    <script type="application/ld+json">
        {"@context" : "http://schema.org",
        "@type" : "Organization",  
        "name" : "Environmental Health & Pollution Management Institute (EHPMI)",
        "url" : "https://dev.ehpmi.org/", 
        "logo": "https://dev.ehpmi.org/wp-content/uploads/2023/04/DSC_0161-1-scaled-e1681361370623.jpg" }
    </script>
</head>
<body>

<!--Header Nav-->
<header class="header">
    <div class="container">
        <?php // the_custom_logo(); ?>
        <a href="https://dev.ehpmi.org/" class="custom-logo-link" rel="home" aria-current="page"><img width="500" height="179" src="https://dev.ehpmi.org/wp-content/themes/ehpmi/images/logo.svg" class="custom-logo" alt="EHPMI" decoding="async"></a>

        <?php
        
        wp_nav_menu([
            'theme_location' => 'top-menu',
            'menu_class'     => 'nav',
            'container'      => 'ul',
            'walker'         => new Top_Nav(),
            'depth'          => 2, // adjust depth for submenus
        ]);
        ?>

        <nav class="navbar">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent1"
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
                    'no_dropdown' => [1480]
                ]);
                ?>        
        </nav>
        <a class="btn btn-primary" href="/#contact-us">Contact us</a>
    </div>
</header>
<?php if (function_exists('bcn_display') && !is_front_page()) : ?>
    <div id="breadcrumbs" class="container"><?php bcn_display(); ?></div><?php endif ?>
