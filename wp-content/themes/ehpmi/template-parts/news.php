<?php
$is_inner = ! empty( $args['inner'] );
$heading  = isset( $args['heading'] ) ? $args['heading'] : __( 'Latest from EHPMI', 'ehpmi' );
$show_heading = ! isset( $args['show_heading'] ) || (bool) $args['show_heading'];
$news     = get_posts(
    array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => isset( $args['numberposts'] ) ? (int) $args['numberposts'] : 100,
        'orderby'        => 'date',
        'order'          => 'DESC',
    )
);
?>
<?php if ( ! $is_inner ) : // Homepage block. ?>
<section class="latest news news-grid animation-element slide-left">
    <header>
        <h2><?php echo esc_html( $heading ); ?></h2>
    </header>
    <div id="latest-carousel" class="latest-carousel container ehpmi-carousel" role="region"
         aria-label="Latest news" aria-roledescription="carousel" data-carousel-gap="30"
         data-carousel-medium-breakpoint="600" data-carousel-medium-items="3">
        <div class="ehpmi-carousel__viewport" tabindex="0">
            <div class="ehpmi-carousel__track">
            <?php foreach ($news as $news_item) : ?>
            <div class="ehpmi-carousel__item">
                <article <?php post_class( 'news-block', $news_item->ID ); ?> id="news-card-<?php echo esc_attr( $news_item->ID ); ?>">
                    <?php if (get_the_post_thumbnail($news_item->ID)) : ?>
                    <div class="image">
                        <?= get_the_post_thumbnail($news_item->ID, 'thumbnail') ?>
                    </div>
                    <?php endif; ?>
                    <div class="news-text">
                        <h3 class="title"><a href="<?php echo esc_url( get_permalink( $news_item ) ); ?>"><?php echo esc_html( $news_item->post_title ); ?></a></h3>
                        <time class="date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $news_item ) ); ?>"><?php echo esc_html( get_the_date( 'F j, Y', $news_item ) ); ?></time>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <div class="ehpmi-carousel__nav" aria-label="Latest news controls">
            <button class="ehpmi-carousel__control ehpmi-carousel__control--prev" type="button" aria-label="Previous news item"></button>
            <button class="ehpmi-carousel__control ehpmi-carousel__control--next" type="button" aria-label="Next news item"></button>
        </div>
        <p class="ehpmi-carousel__status screen-reader-text" aria-live="polite"></p>
    </div>
</section><?php else: ?>
<section class="news news-grid inner"<?php echo $show_heading ? '' : ' aria-label="' . esc_attr( $heading ) . '"'; ?>>
    <?php if ( $show_heading ) : ?>
    <header>
        <h1><?php echo esc_html( $heading ); ?></h1>
    </header>
    <?php endif; ?>
    <div class="container">
        <?php foreach ($news as $news_item) : ?>
        <article <?php post_class( 'news-block', $news_item->ID ); ?> id="news-card-<?php echo esc_attr( $news_item->ID ); ?>">
            <?php if (get_the_post_thumbnail($news_item->ID)) : ?>
            <div class="image">
                <?= get_the_post_thumbnail($news_item->ID, 'thumbnail') ?>
            </div>
            <?php endif; ?>
            <div class="news-text">
                <h2 class="title"><a href="<?php echo esc_url( get_permalink( $news_item ) ); ?>"><?php echo esc_html( $news_item->post_title ); ?></a></h2>
                <div class="description"><?php echo $news_item->post_excerpt
                    ? wp_kses_post( wpautop( $news_item->post_excerpt ) )
                    : '<p>' . esc_html( wp_trim_words( wp_strip_all_tags( strip_shortcodes( $news_item->post_content ) ), 55 ) ) . '</p>'; ?></div>
                <time class="date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $news_item ) ); ?>"><?php echo esc_html( get_the_date( 'F j, Y', $news_item ) ); ?></time>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section><?php endif; ?>
