<?php
	/*
	Template Name: FAQ
	*/
?>

<?php get_header(); ?>

<h1><?php the_title(); ?></h1>

	<div class="wrapper">
		
		<div class="full">
			<?php the_field('content_faq_top'); ?>
		</div>
		
		<div id="custom-show-hide">
        	<?php if( have_rows('faq') ): ?> 
				<?php while( have_rows('faq') ): the_row(); ?>
            		<div class="coltitle"><?php the_sub_field('question'); ?></div>
					<div><?php the_sub_field('answer'); ?></div>
                <?php endwhile; ?>
            <?php endif; ?> 
		</div>
		
		<div class="full">
			<?php the_field('content_faq'); ?>
		</div>
    </div>

<?php get_footer(); ?>