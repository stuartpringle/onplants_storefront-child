<?php
    /*
    Template Name: Home
    */
?>
<?php //activate_plugin('akismet/akismet.php'); ?>
<?php get_header(); ?>

<div class="wrapper">
<div id="slider">

<?php /*IMPORTANT!  SLIDESHOW IS HERE!  DISABLED! */ ?>
    <?php

    $video_start_date = '2022-02-15';
    $video_start_hour = '08';

    $video_end_date = '2022-04-22';
    $video_end_hour = '22';

    $show_video = 0;
    if((strtotime(date("Y-m-d")) == strtotime($video_start_date) && current_time('H') >= $video_start_hour)
         || strtotime(date("Y-m-d")) > strtotime($video_start_date)) {
        if((strtotime(date("Y-m-d")) == strtotime($video_end_date) && current_time('H') < $video_end_hour)
             || strtotime(date("Y-m-d")) < strtotime($video_end_date)) {
             $show_video = 1;
        }
    }
    
    $homepage_display = 'show_store_closed_image';
    $show_video = 0; // this is force overridden from above
    $video_url = '803499409?h=a46bc4f23e';
    $show_testimonials = 0;
    $show_new_products_on_homepage = 0;

    if($homepage_display == 'show_video' && $show_video) {
        ob_start();
        //echo do_shortcode('[evp_embed_video url="/wp-content/uploads/2022/02/ONP_LONG_VERSIONP_FEB-6.mp4" autoplay="true"]');
        ?>
<div style="padding:56.25% 0 0 0;position:relative;"><iframe src="https://player.vimeo.com/video/<?php echo $video_url; ?>&amp;badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="position:absolute;top:0;left:0;width:100%;height:100%;" title="FINAL ONP Planting Video_2022"></iframe></div><script src="https://player.vimeo.com/api/player.js"></script>
        <?php
        echo ob_get_clean();
    } else {
        if($homepage_display == 'show_gift_card_homepage') {
            ob_start();
            ?>
            <a href="/product-category/summer-sale/"><img src="<?php echo get_stylesheet_directory_uri(); ?>/images/SALE BANNER_2022.png" title="Summer Sale" alt="Summer Sale" /></a>

            <div id="new_slider_box">
                <?php /*THIS DISABLED FOR NOW
                <span class="tagline"><?php echo get_bloginfo('description'); ?></span>
                <br />
                <br /> */ ?>
                <a href="/shop/" class="button1">Shop Plants</a>
            </div>
            <?php
            echo ob_get_clean();
        } elseif($homepage_display == 'show_store_closed_image') {
            ob_start();
            ?>
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/website-banner-sept-2023.png" title="See you in spring!" alt="See you in spring!" />
            <?php
            echo ob_get_clean();
        } else {
            ?>
            <div id="slider_box">
                <span class="tagline"><?php echo get_bloginfo('description'); ?></span>
                <br />
                <br />
                <a href="/shop/" class="button1">Shop Plants</a>
            </div>
            <?php
            echo do_shortcode("[metaslider id=897]");
        }
    }
    ?>

</div>

<div id="cta">
        <?php if( have_rows('5_buttons', 11) ): ?>
            <?php while( have_rows('5_buttons', 11) ): the_row(); ?>
                <a href="<?php the_sub_field('cta_link', 11); ?>" class="one_fifth" style="background-image:url(<?php the_sub_field('cta_bkgd_image', 11); ?>);">
                    <div class="icon"><img src="<?php the_sub_field('cta_icon', 11); ?>" alt="<?php the_sub_field('cta_name', 11); ?>" /></div>
                    <?php the_sub_field('cta_name', 11); ?>
                </a>
            <?php endwhile; ?>
        <?php endif; ?>
</div>
</div>

<section class="bkgd_ligray intro">
    <div class="wrapper">
        <div class="one_third_i">
            <a href="/wp-content/uploads/2023/03/ONP-Plant-Catalog_February-2023.pdf" target="_blank"><img id="butterfly" src="<?php the_field('intro_image', 11); ?>" alt="Online Retail Native Plants" /></a>
        </div>
        <div class="two_third_i">
            <h4 class="subtitle"><?php the_field('intro_h2', 11); ?></h4>
            <h1 class="title"><?php the_field('intro_h1', 11); ?></h1>
            <?php the_field('intro_content', 11); ?>
            <a href="<?php the_field('intro_btn_link', 11); ?>" class="button1"><?php the_field('intro_btn_title', 11); ?></a>
        </div>
    </div>
</section>

<?php if($show_new_products_on_homepage) { ?>
<section>
    <div class="wrapper">
        <div class="one_half">
            <!--<a href="#" class="more">See More</a>-->
            <h2><?php the_field('bs_title', 11); ?></h2>
            <?php if( have_rows('bs_plants', 11) ): ?>
                <?php while( have_rows('bs_plants', 11) ): the_row(); ?>
                    <div class="one_half_best">
                    <a href="<?php the_sub_field('bs_link', 11); ?>">
                        <div class="best" style="background-image:url(<?php the_sub_field('bs_image', 11); ?>);" /></div>
                        <?php the_sub_field('bs_name', 11); ?>
                    </a>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
        <div class="one_half">
            <h2><?php the_field('fp_title', 11); ?></h2>
            <div class="featured bkgd_green">
                <div class="featured_img" style="background-image:url(<?php the_field('fp_image', 11); ?>);" /></div>
                <h4><?php the_field('fp_name', 11); ?></h4>
                <p><?php the_field('fp_description', 11); ?></p>
                <a href="<?php the_field('fp_link', 11); ?>" class="button1"><?php the_field('fp_button', 11); ?></a>
            </div>
        </div>
    </div>
</section>
<?php } ?>

<?php if($show_testimonials) { ?>
<section class="bkgd_ligray intro">
    <div class="wrapper">
        <h4 class="center npm">Testimonials</h4>
        <?php the_field('intro_testimonials'); ?>
    </div>
</div>
<?php } ?>

<?php get_footer(); ?>