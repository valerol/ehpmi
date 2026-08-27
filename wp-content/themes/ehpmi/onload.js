(function () {
    'use strict';

    var desktopMedia = window.matchMedia('(min-width: 1025px)');

    function initializeBootstrapComponents() {
        document.querySelectorAll('.dropdown-toggle').forEach(function (toggle) {
            bootstrap.Dropdown.getOrCreateInstance(toggle);
        });

        document.querySelectorAll('.carousel').forEach(function (carousel) {
            bootstrap.Carousel.getOrCreateInstance(carousel);
        });
    }

    function initializeParallax() {
        var frameRequested = false;
        var parallaxElements = document.querySelectorAll(
            '.hero img, .hero .text, .map:not(.inner) .image, .map:not(.inner) .text'
        );

        function clearParallax() {
            parallaxElements.forEach(function (element) {
                element.style.removeProperty('margin-top');
                element.style.removeProperty('margin-bottom');
            });
        }

        function updateParallax() {
            frameRequested = false;

            if (!desktopMedia.matches) {
                clearParallax();
                return;
            }

            document.querySelectorAll('.hero').forEach(function (hero) {
                var margin = window.scrollY - (hero.getBoundingClientRect().top + window.scrollY);
                var image = hero.querySelector('img');
                var text = hero.querySelector('.text');

                if (image) {
                    image.style.marginTop = (margin / 5) + 'px';
                }
                if (text) {
                    text.style.marginTop = (10 - margin / 10) + 'px';
                    text.style.marginBottom = (10 + margin / 10) + 'px';
                }
            });

            document.querySelectorAll('.map:not(.inner)').forEach(function (map) {
                var margin = window.scrollY - (map.getBoundingClientRect().top + window.scrollY);
                var image = map.querySelector('.image');
                var text = map.querySelector('.text');

                if (image) {
                    image.style.marginTop = (250 + margin / 5) + 'px';
                }
                if (text) {
                    text.style.marginTop = (margin / 10) + 'px';
                    text.style.marginBottom = (10 + margin / 10) + 'px';
                }
            });
        }

        function requestUpdate() {
            if (!frameRequested) {
                frameRequested = true;
                window.requestAnimationFrame(updateParallax);
            }
        }

        window.addEventListener('load', requestUpdate, { once: true });
        window.addEventListener('resize', requestUpdate, { passive: true });
        window.addEventListener('scroll', requestUpdate, { passive: true });
        desktopMedia.addEventListener('change', requestUpdate);
        requestUpdate();
    }

    function initializeScrollAnimations() {
        var elements = document.querySelectorAll('.animation-element');

        if (!('IntersectionObserver' in window)) {
            elements.forEach(function (element) {
                element.classList.add('in-view');
            });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                entry.target.classList.toggle('in-view', entry.isIntersecting);
            });
        });

        elements.forEach(function (element) {
            observer.observe(element);
        });
    }

    function initializeSearchToggle() {
        document.querySelectorAll('.header .search-icon').forEach(function (button) {
            var form = button.closest('.header').querySelector('.searchform');

            if (!form) {
                return;
            }

            button.setAttribute('role', 'button');
            button.setAttribute('tabindex', '0');
            button.setAttribute('aria-expanded', 'false');

            function toggleForm() {
                var isOpening = window.getComputedStyle(form).display === 'none';

                if (isOpening) {
                    form.style.display = 'block';
                    form.animate(
                        [
                            { height: '0', opacity: 0, overflow: 'hidden' },
                            { height: form.scrollHeight + 'px', opacity: 1, overflow: 'hidden' }
                        ],
                        { duration: 200, easing: 'ease-out' }
                    ).finished.then(function () {
                        form.style.removeProperty('height');
                        form.style.removeProperty('overflow');
                    });
                } else {
                    form.animate(
                        [
                            { height: form.scrollHeight + 'px', opacity: 1, overflow: 'hidden' },
                            { height: '0', opacity: 0, overflow: 'hidden' }
                        ],
                        { duration: 200, easing: 'ease-in' }
                    ).finished.then(function () {
                        form.style.display = 'none';
                        form.style.removeProperty('height');
                        form.style.removeProperty('overflow');
                    });
                }

                button.setAttribute('aria-expanded', String(isOpening));
            }

            button.addEventListener('click', toggleForm);
            button.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    toggleForm();
                }
            });
        });
    }

    function initializeMapPoints() {
        var points = Array.from(document.querySelectorAll('.point'));

        document.addEventListener('click', function (event) {
            points.forEach(function (point) {
                if (!point.contains(event.target)) {
                    point.removeAttribute('open');
                }
            });
        });
    }

    function initializeCarousel(carousel) {
        var viewport = carousel.querySelector('.ehpmi-carousel__viewport');
        var items = Array.from(carousel.querySelectorAll('.ehpmi-carousel__item'));
        var previous = carousel.querySelector('.ehpmi-carousel__control--prev');
        var next = carousel.querySelector('.ehpmi-carousel__control--next');
        var navigation = carousel.querySelector('.ehpmi-carousel__nav');
        var status = carousel.querySelector('.ehpmi-carousel__status');
        var gap = Number(carousel.dataset.carouselGap || 0);
        var loops = carousel.dataset.carouselLoop === 'true';
        var itemsPerView = 1;
        var currentIndex = 0;
        var scrollFrameRequested = false;

        if (!viewport || !items.length || !previous || !next || !navigation) {
            return;
        }

        function configuredItemsPerView() {
            var count = Number(carousel.dataset.carouselSmallItems || 1);
            var mediumBreakpoint = Number(carousel.dataset.carouselMediumBreakpoint || 0);
            var largeBreakpoint = Number(carousel.dataset.carouselLargeBreakpoint || 0);

            if (mediumBreakpoint && window.innerWidth >= mediumBreakpoint) {
                count = Number(carousel.dataset.carouselMediumItems || count);
            }
            if (largeBreakpoint && window.innerWidth >= largeBreakpoint) {
                count = Number(carousel.dataset.carouselLargeItems || count);
            }

            return Math.max(1, Math.min(count, items.length));
        }

        function maximumIndex() {
            return Math.max(0, items.length - itemsPerView);
        }

        function itemStep() {
            return items[0].getBoundingClientRect().width + gap;
        }

        function updateState() {
            var lastVisible = Math.min(items.length, currentIndex + itemsPerView);
            var maximum = maximumIndex();

            items.forEach(function (item, index) {
                var isVisible = index >= currentIndex && index < lastVisible;

                item.setAttribute('aria-label', (index + 1) + ' of ' + items.length);
                item.setAttribute('aria-hidden', String(!isVisible));
                item.inert = !isVisible;
            });

            previous.disabled = !loops && currentIndex <= 0;
            next.disabled = !loops && currentIndex >= maximum;
            navigation.hidden = maximum === 0;

            if (status) {
                status.textContent = 'Items ' + (currentIndex + 1) + '–' + lastVisible + ' of ' + items.length;
            }
        }

        function goTo(index, behavior) {
            var maximum = maximumIndex();

            if (loops && index < 0) {
                index = maximum;
            } else if (loops && index > maximum) {
                index = 0;
            }

            currentIndex = Math.max(0, Math.min(index, maximum));
            viewport.scrollTo({ left: currentIndex * itemStep(), behavior: behavior || 'smooth' });
            updateState();
        }

        function applyResponsiveLayout() {
            itemsPerView = configuredItemsPerView();
            carousel.style.setProperty('--carousel-items', itemsPerView);
            carousel.style.setProperty('--carousel-gap', gap + 'px');
            currentIndex = Math.min(currentIndex, maximumIndex());

            window.requestAnimationFrame(function () {
                var itemWidth = (viewport.clientWidth - gap * (itemsPerView - 1)) / itemsPerView;

                carousel.style.setProperty('--carousel-item-width', Math.max(0, itemWidth) + 'px');
                goTo(currentIndex, 'auto');
                carousel.classList.add('is-ready');
            });
        }

        previous.addEventListener('click', function () {
            goTo(currentIndex - 1);
        });

        next.addEventListener('click', function () {
            goTo(currentIndex + 1);
        });

        viewport.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                goTo(currentIndex + (event.key === 'ArrowLeft' ? -1 : 1));
            }
        });

        viewport.addEventListener('scroll', function () {
            if (scrollFrameRequested) {
                return;
            }

            scrollFrameRequested = true;
            window.requestAnimationFrame(function () {
                var step = itemStep();

                scrollFrameRequested = false;
                if (step > 0) {
                    currentIndex = Math.max(0, Math.min(Math.round(viewport.scrollLeft / step), maximumIndex()));
                    updateState();
                }
            });
        }, { passive: true });

        window.addEventListener('resize', applyResponsiveLayout, { passive: true });
        applyResponsiveLayout();
    }

    function initialize() {
        initializeBootstrapComponents();
        initializeParallax();
        initializeScrollAnimations();
        initializeSearchToggle();
        initializeMapPoints();
        document.querySelectorAll('.ehpmi-carousel').forEach(initializeCarousel);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
