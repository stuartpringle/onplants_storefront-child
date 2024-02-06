<?php
/**
 * The header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package storefront
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

	<script>document.documentElement.className = "js";</script>

<?php /*
	<!-- GOOGLE ANALYTICS -->
	<script>
		(function(i,s,o,g,r,a,m){ i['GoogleAnalyticsObject']=r; i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)}, i[r].l=1*new Date(); a=s.createElement(o),m=s.getElementsByTagName(o)[0]; a.async=1; a.src=g; m.parentNode.insertBefore(a,m) })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
ga('create', 'UA-99958740-1', 'auto'); ga('send', 'pageview');
	</script>
*/ ?>

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php

/* We get the number of items in cart for the little green circle beside 'Cart' in the header.  This gets dynamically updated on cart update in functions.php */
$cart = WC()->cart;
$num_items_in_cart = $cart->get_cart_contents_count();
?>

<header class="leaf">
	<div class="wrapper">
    	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" id="logo">
        	<img src="<?php echo get_stylesheet_directory_uri(); ?>/images/ONP-Logo-Green-Purple.png" alt="Ontario Native Plants" />
        </a>
        <div id="top_menu">
        	<div class="search"><?php get_search_form(); ?></div>
            <a href="/cart/" class="cart1">Cart <span class="header-cart-count" id="header-cart-count"><?php echo ($num_items_in_cart ? $num_items_in_cart : ''); ?></span></a>
            <!--<a href="/wishlist/" class="user noback"><i class="yith-wcwl-icon fa fa-heart" style="color: #8ebd33;"></i>My Wishlist</a>-->
            <a href="/my-account/" class="user">My Account</a>
        </div>
    </div>
</header>
<h1>HELLO</h1>
<div id="menu_bar">
	<div class="wrapper">
		<nav class=" main-navigation" role="navigation">
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'menu_class' => 'nav-menu', 'menu_id' => 'primary-menu' ) ); ?>
        </nav>
    </div>
</div>

<?php if ( !is_front_page() ) { ?>
	<div class="wrapper">
<?php } ?>

<div id="page" class="hfeed site">
	<?php
	do_action( 'storefront_before_header' ); ?>

		<?php
		/**
		 * Functions hooked in to storefront_content_top
		 *
		 * @hooked woocommerce_breadcrumb - 10
		 */
		do_action( 'storefront_content_top' );
