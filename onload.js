$(document).ready(function(){
    // Desktop screen effects
    if (screen.width > 1024){
        // Menu dropdown toggle
        $('.dropdown-toggle').dropdown();
        // SlideDown animation to dropdown when expanding
        $('.dropdown').on('show.bs.dropdown', function() {
            $(this).find('.dropdown-menu').first().stop(true, true).slideDown();
        });
        // SlideDown animation to dropdown when collapsing
        $('.dropdown').on('hide.bs.dropdown', function() {
            $(this).find('.dropdown-menu').first().stop(true, true).slideUp();
        });

        // hero carousel
        $('.carousel').carousel();
        // hero scrolling effects
        $(window).on('load resize scroll', function() {
            $('.hero').each(function() {
                var windowTop = $(window).scrollTop();
                var elementTop = $(this).offset().top;
                var margin = windowTop - elementTop;
                $(this).find('img').css({ 'margin-top': margin/5 });
                $(this).find('.text').css({ 'margin-top': 30 - margin/10 });
                $(this).find('.text').css({ 'margin-bottom': 30 + margin/10 });
            });
        });

        /* effects on page scrolling */
        var $animation_elements = $('.animation-element');
        var $window = $(window);

        function check_if_in_view() {
            var window_height = $window.height();
            var window_top_position = $window.scrollTop();
            var window_bottom_position = (window_top_position + window_height);

            $.each($animation_elements, function() {
                var $element = $(this);
                var element_height = $element.outerHeight();
                var element_top_position = $element.offset().top;
                var element_bottom_position = (element_top_position + element_height);

                //check to see if this current container is within viewport
                if ((element_bottom_position >= window_top_position) &&
                    (element_top_position <= window_bottom_position)) {
                    $element.addClass('in-view');
                } else {
                    $element.removeClass('in-view');
                }
            });
        }

        $window.on('scroll resize', check_if_in_view);
        $window.trigger('scroll');
    }

    // toggle search form
    $("#header-search .search-icon").click(function(){
        $("#header-search input").slideToggle();
    });

    // close map point details by click on other element
    var points = [...document.querySelectorAll('.point')];
    document.addEventListener('click', function(e) {
        if (!points.some(f => f.contains(e.target))) {
            points.forEach(f => f.removeAttribute('open'));
        } else {
            points.forEach(f => !f.contains(e.target) ? f.removeAttribute('open') : '');
        }
    })

    // testimonials carousel init
    $("#testimonials-carousel .owl-carousel").owlCarousel({
        loop:true,
        margin:10,
        nav:true,
        responsive:{
            0: {
                items:1
            },
            721:{
                items:2
            },
            1025:{
                items:3
            }
        }
    });

    // partners carousel init
    $("#partner-carousel .owl-carousel").owlCarousel({
        loop:true,
        margin:10,
        nav:true,
        responsive:{
            0:{
                items:1
            },
            600:{
                items:3
            },
            1000:{
                items:5
            }
        }
    });
});
