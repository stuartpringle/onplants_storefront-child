<?php
/**
 * Template used to display post content.
 *
 * @package storefront
 */

?>

<article id="post-<?php the_ID(); ?>" class="line" <?php post_class(); ?>>

	<?php
	/**
	 * Functions hooked in to storefront_loop_post action.
	 *
	 * @hooked storefront_post_header          - 10
	 * @hooked storefront_post_meta            - 20
	 * @hooked storefront_post_content         - 30
	 * @hooked storefront_init_structured_data - 40
	 */
	do_action( 'storefront_loop_post' );
	?>

</article><!-- #post-## -->
