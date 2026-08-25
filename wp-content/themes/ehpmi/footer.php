<!--Footer-->
<footer class="footer">
    <section class="container footer-nav">
        <?php dynamic_sidebar( 'footer-text' ); ?>
        <?php dynamic_sidebar( 'footer-menu-1' ); ?>
        <?php dynamic_sidebar( 'footer-menu-2' ); ?>
        <?php dynamic_sidebar( 'footer-contacts' ); ?>
    </section>

    <div class="footer-bottom">
        <div class="container">
            <p class="copyright">© 2022-<?php echo esc_html( wp_date( 'Y' ) ); ?> Copyright: <?php
                dynamic_sidebar( 'footer-copyright' ); ?></p>
            <?php dynamic_sidebar( 'footer-social' ); ?>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
