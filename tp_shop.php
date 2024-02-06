<?php
	/*
	Template Name: Shop
	*/
?>

<?php get_header(); ?>

	<?php if( get_field('add_notification_message', 745) ): ?>
		<div class="message bkgd_green"><?php the_field('nm_content', 745); ?></div>
	<?php // do something ?>
	
	<?php endif; ?>

	<?php
	// Some code to duplicate the sidebar so we can use CSS to turn the first one off, then enable it and turn the second off if on mobile device (to change order of elements)
	ob_start();
	get_sidebar('catalog');
	$sidebar = ob_get_clean();
	?>


<div class="one_fourth catalog_first">
	<?php echo $sidebar; ?>
</div>

<div class="three_fourth" id="store_products">
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<?php while ( have_posts() ) : the_post();

				do_action( 'storefront_page_before' );

				//get_template_part( 'content', 'page' );

				/**
				 * Functions hooked in to storefront_page_after action
				 *
				 * @hooked storefront_display_comments - 10
				 */
				do_action( 'storefront_page_after' );

			endwhile; // End of the loop. ?>

		</main><!-- #main -->
	</div><!-- #primary -->
</div>

<div class="one_fourth catalog_second">
	<?php echo $sidebar ?>
</div>

<?php get_footer(); ?>