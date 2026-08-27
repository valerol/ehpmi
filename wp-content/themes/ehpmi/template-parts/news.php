<?php
//$news = $args['materials'] ? $args['materials'] : get_posts(['category' => 33]);
$news = get_posts(['category' => 33, 'numberposts' => 100]);
?>
<?php if (!isset($args['category_name'])) : // Homepage block ?>
<section class="latest news news-grid <?= isset($args['category_name']) ? 'inner' : 'animation-element slide-left' ?>">
    <header>
        <h2>Latest from EHPMI</h2>
    </header>
    <div id="latest-carousel" class="latest-carousel container ehpmi-carousel" role="region"
         aria-label="Latest news" aria-roledescription="carousel" data-carousel-gap="30"
         data-carousel-medium-breakpoint="600" data-carousel-medium-items="3">
        <div class="ehpmi-carousel__viewport" tabindex="0">
            <div class="ehpmi-carousel__track">
            <?php foreach ($news as $news_item) : ?>
            <div class="ehpmi-carousel__item">
                <div class="news-block">
                    <?php if (get_the_post_thumbnail($news_item->ID)) : ?>
                    <div class="image">
                        <?= get_the_post_thumbnail($news_item->ID, [530,530]) ?>
                    </div>
                    <?php endif; ?>
                    <div class="news-text">
                        <h3 class="title"><a href="<?= get_post_permalink($news_item->ID,
                                true) ?>"><?= $news_item->post_title ?></a></h3>
                        <small class="date"><?= date('F j, Y', strtotime($news_item->post_date)); ?></small>
                    </div>
                </div>
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
<section class="news news-grid <?= isset($args['category_name']) ? 'inner' : 'animation-element slide-left' ?>">
    <?php if (isset($args['breadcrumbs'])) : ?><div id="breadcrumbs" class="container"><?= $args['breadcrumbs'] ?></div><?php endif; ?>
    <header>
        <h2><?= isset($args['category_name']) ? $args['category_name'] : "News" ?></h2>
    </header>
    <div class="container">
        <?php foreach ($news as $news_item) : ?>
        <div class="news-block">
            <?php if (get_the_post_thumbnail($news_item->ID)) : ?>
            <div class="image">
                <?= get_the_post_thumbnail($news_item->ID, [530,530]) ?>
            </div>
            <?php endif; ?>
            <div class="news-text">
                <h3 class="title"><a href="<?= get_post_permalink($news_item->ID,
                        true) ?>"><?= $news_item->post_title ?></a></h3>
                <p class="description"><?= $news_item->post_excerpt ? $news_item->post_excerpt :
                        get_the_content(null, true, $news_item->ID) ?></p>
                <small class="date"><?= date('F j, Y', strtotime($news_item->post_date)); ?></small>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section><?php endif ?>
