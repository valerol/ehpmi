<?php
//$news = $args['materials'] ? $args['materials'] : get_posts(['category' => 33]);
$news = get_posts(['category' => 33, 'numberposts' => 100]);
?>
<?php if (!isset($args['category_name'])) : // Homepage block ?>
<section class="latest news news-grid <?= isset($args['category_name']) ? 'inner' : 'animation-element slide-left' ?>">
    <header>
        <h2>Latest from EHPMI</h2>
    </header>
    <div id="latest-carousel" class="latest-carousel container">
        <div class="owl-carousel">
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
                    <small class="date"><?= date('F j, Y', strtotime($news_item->post_date)); ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
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