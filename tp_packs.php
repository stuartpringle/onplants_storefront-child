<?php
	/*
	Template Name: Plant Packs
	*/
?>

<?php get_header(); ?>
	
<h1><?php the_title(); ?></h1>


	<div class="wrapper">
		<?php while ( have_posts() ) : the_post(); ?>
		<?php the_content(); ?>
		
			<?php if( have_rows('packs') ): ?>
				<?php
				$counter = 0;
				while( have_rows('packs') ): the_row();
					$counter++;
								$product_slug = str_replace('product', '', get_sub_field('p_link'));
								$product_slug = str_replace('/', '', $product_slug);
								$product = get_page_by_path( $product_slug, OBJECT, 'product' );
								//print_r($product);
								if ( ! empty( $product ) ) {
									$product = wc_get_product( $product );
									$stock_status = $product->get_stock_status();
								}
					?>
					<div class="one_third equalH" style="position: relative;">
						<img src="<?php the_sub_field('p_image'); ?>" alt="<?php the_sub_field('p_name'); ?>" class="plantimg" />
						<?php 
						$button_text = get_sub_field('p_btn');
						if($stock_status == 'outofstock') {
							echo '<span class="soldout">OUT OF STOCK</span>';
							$button_text = 'READ MORE';
						}
						?>
						<h3 class="plant"><?php the_sub_field('p_name'); ?></h3>
						<?php the_sub_field('p_description'); ?>
						
						<?php if( have_rows('p_list') ): ?>
							<div class="plantlist">
								<ol>
									<?php while( have_rows('p_list') ): the_row(); ?>
										<li><?php the_sub_field('p_list_name'); ?></li>
									<?php endwhile; ?>
								</ol>
							</div>
						<?php endif; ?>
						<a href="<?php the_sub_field('p_link'); ?>" class="button1"><?php echo $button_text; ?></a>
					</div>
					<?php
					if($counter > 2) {
						echo '<br style="clear: both;" />';
						$counter = 0;
					}
				endwhile; ?>
			<?php endif; ?>
		
		
		<?php endwhile; wp_reset_query(); ?>
		
	</div>

<?php get_footer(); ?>