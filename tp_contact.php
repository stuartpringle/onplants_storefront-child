<?php
	/*
	Template Name: Contact
	*/
?>

<?php get_header(); ?>

<section>
	<div class="wrapper">
    	<div class="one_half">
        	<?php the_field('left_column',14); ?>       
        </div>
        <div class="one_half">
            <?php the_field('right_column',14); ?>
        </div>
    </div>
</section>  

<?php get_footer(); ?>