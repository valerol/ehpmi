<?php
$materials = array_key_exists( 'materials', $args )
    ? $args['materials']
    : get_posts(
        array(
            'post_type'      => 'material',
            'post_status'    => 'publish',
            'posts_per_page' => 50,
        )
    );
if ($materials) {
?>
<section class="materials"><?php if ( isset( $args['heading'] ) ) : ?>
    <header>
        <h1><?php echo esc_html( $args['heading'] ); ?></h1>
    </header><?php endif ?>
    <div class="container"><?php foreach ($materials as $material) : ?>
        <article class="material"><?php if (get_the_post_thumbnail($material)) : ?>
            <?= get_the_post_thumbnail($material, [200, 150]) ?><?php endif; ?>
            <?php 
            $file_id  = get_post_meta( $material->ID, 'file', true );
            $file_url = $file_id ? wp_get_attachment_url( $file_id ) : '';
            ?>
            <div class="text">
                <?php if ( $file_url ) : ?>
                    <a class="file" href="<?php echo esc_url( $file_url ); ?>"><?php echo esc_html( get_the_title( $material ) ); ?></a>
                <?php else : ?>
                    <span class="file"><?php echo esc_html( get_the_title( $material ) ); ?></span>
                <?php endif; ?>
            </div>
        </article><?php endforeach; ?>
    </div>
</section>
<?php } ?>
