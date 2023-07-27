<?php

defined( 'ABSPATH' ) || die();

/*** MODULES ***/
require_once('modules/onp_custom_order_page.php');
require_once('modules/onp_custom_admin_page.php');
require_once('modules/onp_custom_mailchimp.php');
require_once('modules/onp_custom_data_download_page.php');
require_once('modules/onp_custom_product_data_download_page.php');
//require_once('modules/onp_custom_customer_data_download_page.php');
require_once('modules/onp_custom_order_details_alterations.php');

/*** CLASSES ***/
require_once('classes/pick_up_only_product.php');

function storefront_child_enqueue_styles() {
	$time = WP_DEBUG ? '?' . time() : '';
	$wp_scripts = wp_scripts();

	wp_deregister_style( 'storefront-child-style' );
	wp_register_style( 'onplants-custom-styling', get_stylesheet_directory_uri() . '/style.css' . $time );
	wp_enqueue_style( 'onplants-custom-styling');

	wp_deregister_script( 'jquery-ui' );

	wp_register_script( 'jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js', array( 'jquery' ) );

	wp_enqueue_script( 'jquery-ui' );
	wp_enqueue_script( 'jquery-ui-dialog');
	wp_enqueue_script( 'jquery-collapse', get_stylesheet_directory_uri().'/js/jquery.collapse.js' );
	wp_enqueue_style( 'wp-jquery-ui-dialog' );

	wp_enqueue_style('jquery-ui-css',
		'//ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-autocomplete']->ver . '/themes/smoothness/jquery-ui.css',
		false, null, false
	);


}
add_action( 'wp_enqueue_scripts', 'storefront_child_enqueue_styles', 35 );

function loop_columns() {
	return 4; // 4 products per row
}
add_filter( 'loop_shop_columns', 'loop_columns', 999 );

add_filter( 'storefront_credit_link', '__return_false' );


/************* Testing page **************/
add_action('pre_get_posts', function ($query){
    global $wp;
    onp_start_session();

    if(!is_admin() && $query->is_main_query()) {
    	//disabled
        if($wp->request == 'paypal_test' && 0) {
        	require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/storefront-child/paypaltest/process_paypal_orders.php');
            //echo resend_emails();
            exit;
        }
    }

    if(isset($_POST['onp-out-of-stock-submitted']) && $_POST['onp-out-of-stock-submitted'] == 1) {
    	$_SESSION['hide-out-of-stock'] = (@$_POST['hide-out-of-stock'] == 1);
    }

    if(isset($_SESSION['hide-out-of-stock']) && @$_SESSION['hide-out-of-stock'] == 1) {
	    /* Remove OUT OF STOCK items via checkbox */
	    if ( ! $query->is_main_query() || is_admin() ) {
	        return;
	    }

	    if ( $outofstock_term = get_term_by( 'name', 'outofstock', 'product_visibility' ) ) {
	        $tax_query = (array) $query->get('tax_query');
	        $tax_query[] = array(
	            'taxonomy' => 'product_visibility',
	            'field' => 'term_taxonomy_id',
	            'terms' => array( $outofstock_term->term_taxonomy_id ),
	            'operator' => 'NOT IN'
	        );

	        $query->set( 'tax_query', $tax_query );
	    }

	    //remove_action( 'pre_get_posts', 'iconic_hide_out_of_stock_products' );
    }
});

/**
 * Handle a custom '_alg_wc_custom_order_number' query var to get orders with the '_alg_wc_custom_order_number' meta.
 * @param array $query - Args for WP_Query.
 * @param array $query_vars - Query vars from WC_Order_Query.
 * @return array modified $query
 */
function handle_custom_query_var( $query, $query_vars ) {
	if ( ! empty( $query_vars['_alg_wc_custom_order_number'] ) ) {
		$query['meta_query'][] = array(
			'key' => '_alg_wc_custom_order_number',
			'value' => esc_attr( $query_vars['_alg_wc_custom_order_number'] ),
		);
	}

	return $query;
}
add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', 'handle_custom_query_var', 10, 2 );




/*********** change subject for emails containing ONLY gift cards **********/
add_filter( 'woocommerce_email_subject_customer_completed_order', 'change_completed_email_subject', 1, 2 );
add_filter( 'woocommerce_email_heading_customer_completed_order', 'change_completed_email_heading', 10, 2 );

function change_completed_email_subject( $subject, $order ) {
	global $woocommerce;

	$items = $order->get_items();
	$only_gift_card = 1;

	if( sizeof( $items ) > 0 ) {
		foreach( $items as $item_id => $item ) {
			$product = wc_get_product($item['product_id']);
			if($product->get_type() != 'pw-gift-card') {
				$only_gift_card = 0;
			}
		}
	}

	if($only_gift_card) {
		$subject = sprintf( 'Thank you for your Gift Card Order!' );
	}

	return $subject;
}

function change_completed_email_heading( $heading, $order ) {
	global $woocommerce;

	$items = $order->get_items();
	$only_gift_card = 1;

	if( sizeof( $items ) > 0 ) {
		foreach( $items as $item_id => $item ) {
			$product = wc_get_product($item['product_id']);
			if($product->get_type() != 'pw-gift-card') {
				$only_gift_card = 0;
			}
		}
	}

	if($only_gift_card) {
		$heading = sprintf( 'Gift Card Order Details' );
	}

	return $heading;
}


//Clear / empty the cart after an order has been placed
add_action( 'woocommerce_thankyou', 'order_received_empty_cart_action', 10, 1 );
function order_received_empty_cart_action( $order_id ){
	WC()->cart->empty_cart();
}

// another possible solution to this
add_action( 'wp_head', 'my_clear_cart' );
function my_clear_cart() {
	if ( is_page( array( 'thank-you', 'order-received' ) ) && isset( $_GET['order-received'] ) ) {
		WC()->cart->empty_cart();
	}
}



//clear notices on cart update
function clear_notices_on_cart_update() {
	wc_clear_notices();
};
// add the filter
add_filter( 'woocommerce_update_cart_action_cart_updated', 'clear_notices_on_cart_update', 10, 1 );







/*
SECOND ORDER BUTTON FUNCTIONS

*/
add_filter( 'woocommerce_checkout_fields' , 'custom_add_second_order_checkout_field', 10 );
add_filter( 'woocommerce_checkout_fields' , 'custom_add_pickup_checkout_field', 10 );
add_action( 'woocommerce_after_checkout_form', 'onp_custom_add_jscript_checkout');
add_action( 'woocommerce_checkout_update_order_meta', 'onp_custom_save_second_order_field');
add_action( 'woocommerce_checkout_update_order_meta', 'onp_custom_save_pickup_field');
add_action( 'woocommerce_view_order', 'onp_customer_view_order_page', 20 );

function onp_customer_view_order_page($order_id) {
	$second_order_title = get_option('second_order_title', 'This is my second order.  Please ship with my first order.');
	$second_order = get_post_meta( $order_id, 'second_order', true );
	if($second_order) {
		?>
		<div class="onp-second-order-info">
			<!--<h2>Second Order</h2>-->
			<table class="woocommerce-table shop_table second_order_status">
				<tbody>
					<tr>
						<td><?php echo $second_order_title; ?></td>
						<td><?php echo '<b class="checkmark" style="padding-right: 10px;">&#10004;</b>'; ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<script type="text/javascript">
			jQuery('document').ready(function() {
				jQuery('.onp-second-order-info').insertAfter(jQuery('.woocommerce-order-details__title'));
			});
		</script>
		<?php
	}
}

function custom_add_pickup_checkout_field($fields) {
	$pick_up_products_obj = new pick_up_only_product();

	$fields['order']['pick_up'] = array(
		'label'     => __($pick_up_products_obj->get_pick_up_only_text(), 'woocommerce'),
		'required'  => false,
		'class'     => array(''),
		'type' => 'checkbox'
	);

	return $fields;
}


//display option for second order in checkout form
function custom_add_second_order_checkout_field($fields) {
	$second_order_title = get_option('second_order_title', 'This is my second order.  Please ship with my first order.');
	$second_order_message = get_option('second_order_message', 'Thank you for your second order. The shipping cost difference to ship the two 
		orders together will be refunded to you at the time of shipping.');

	$default_email = '';
	$current_user = wp_get_current_user();
	$default_email = $current_user->user_email;

	$fields['billing']['second_order'] = array(
		'label'     => __($second_order_title, 'woocommerce'),
		'required'  => false,
		'class'     => array('form-row-wide'),
		'type' => 'checkbox'
	);

	$fields['billing']['second_order_text'] = array(
		'label'     => __($second_order_message, 'woocommerce'),
		'required'  => false,
		'class'     => array('form-row-wide'),
		'type' => 'text'
	);

	$fields['billing']['subscribe'] = array(
		'label'     => __('Subscribe to our newsletter', 'woocommerce'),
		'required'  => false,
		'class'     => array('form-row-wide'),
		'type' => 'checkbox'
	);

	$fields['billing']['subscribe_email'] = array(
		'label'     => __('Email', 'woocommerce'),
		'required'  => false,
		'class'     => array('form-row-wide'),
		'type' => 'text',
		'default'	=> $default_email
	);

	return $fields;
}


// jQuery to help the above function (checkout page only)
function onp_custom_add_jscript_checkout() {
	ob_start();
	//echo do_shortcode('[booking type=1 nummonths=2 form_type="standard"]');
	?>
	<script type="text/javascript">
		jQuery(document).ready(function() {
			hide('#second_order_text_field');
			hide('#second_order_text_field span');
			jQuery('#second_order_field').insertBefore('#payment');
			jQuery('#second_order_text_field').insertBefore('#payment');

			//jQuery('#pwgc-redeem-gift-card-form').insertAfter('.woocommerce-checkout-review-order-table');

			jQuery('#second_order').click(function() {
				jQuery('#second_order_text_field').toggle();
			});

			hide('#subscribe_email_field');
			hide('#subscribe_email_field span.optional');
			jQuery('#subscribe_field').insertBefore('#payment')
			jQuery('#subscribe_email_field').insertBefore('#payment');

			jQuery('#subscribe').click(function() {
				jQuery('#subscribe_email_field').toggle();
			});

			function hide($selector) {
				jQuery($selector).hide();
			}
		});
	</script>
	<?php
	echo ob_get_clean();
}

//save the recorded data
function onp_custom_save_second_order_field($order_id) {
	if(!empty( $_POST['subscribe'] )) {
		if($_POST['subscribe_email'] != '') {
		    $data = [
		        'email'     => $_POST['subscribe_email'],
		        'status'    => 'subscribed',
		        'firstname' => $_POST['billing_first_name'],
		        'lastname'  => $_POST['billing_last_name']
		    ];

		    // NOTE: status having 4 Option --"subscribed","unsubscribed","cleaned","pending"
		    $res = syncMailchimp($data);
		}
	}

	if ( ! empty( $_POST['second_order'] ) ) {
		onp_save_second_order_details($order_id);
	}
}

function onp_custom_save_pickup_field($order_id) {
	if ( ! empty( $_POST['pick_up'] ) ) {
		onp_save_pickup_order_details($order_id);
	}
}


add_action( 'woocommerce_checkout_order_processed', 'onp_custom_check_if_second_order',  10, 1  );
function onp_custom_check_if_second_order($order_id) {
	//wc_add_notice('test', 'error');
	if((int)$_POST['second_order'] === 1) {
		$order_url = site_url() . '/wp-admin/post.php?post='.$order_id.'&action=edit';
		$message = '<h2>'.$_POST['billing_first_name'].' '.$_POST['billing_last_name'].' has placed a second order.</h2>';
		$message .= '<h3>View it here: <a href="' . $order_url . '">'.$order_url.'</a></h3>';
		wp_mail( get_bloginfo('admin_email'), 'Second Order ('.$order_id.') placed by '.$_POST['billing_first_name'].' '.$_POST['billing_last_name'], $message );
	}
}


/*
add_filter('woocommerce_cart_item_price', 'onp_quick_fix_price_problem', 10, 3);
function onp_quick_fix_price_problem($price, $cart_item, $cart_item_key) {
	echo $price;
	return $price;
}
add_filter('woocommerce_cart_item_subtotal', 'onp_quick_fix_cart_subtotal_problem', 10, 3);
function onp_quick_fix_cart_subtotal_problem($subtotal, $cart_item, $cart_item_key) {
	echo $subtotal;
	return $subtotal;
}

add_filter('woocommerce_cart_subtotal', 'onp_quick_fix_subtotal_problem', 10, 3);
function onp_quick_fix_subtotal_problem( $cart_subtotal, $compound, $instance ){ 
	echo $cart_subtotal;
    return $cart_subtotal;
} 
add_filter('woocommerce_cart_total', 'onp_quick_fix_total_problem', 10, 3);
function onp_quick_fix_total_problem( $cart_total ){ 
	echo $cart_total;
    return $cart_total;
} 
*/



add_action('woocommerce_cart_updated', 'on_cart_update_list_num_products');
add_filter('woocommerce_check_cart_items', 'update_cart_func');
add_action('woocommerce_cart_contents', 'onp_cart_message');


function onp_cart_message() {
	$applied_coupons = WC()->cart->get_applied_coupons();

	//WC()->cart->will_pickup_order = $will_pickup_order;
	$no_pickup_min_products = 0;
	foreach($applied_coupons as $cur_coupon) {
		if(strtolower($cur_coupon) == 'smallpickup') {
			$no_pickup_min_products = 1;
		}
	}

	$cart_message = get_option('cart_message', 'Please ensure that your cart does not contain plants from a previous order before making a new order.');
	$cart_message = str_replace('--NEWLINE--', '<br />', $cart_message);
	echo '<tr class="woocommerce-cart-form__cart-item cart_item"><td colspan="6" style="border: none; padding: 20px; padding-bottom: 0px;">'.$cart_message.'</td></tr>';

	$total_quantity_in_cart = get_num_products_in_cart();

	//pickup only section
	$pick_up_only_obj = new pick_up_only_product();
	$will_pickup_order = false;

	if($pick_up_only_obj->pickup_season_is_active()) {
		if(isset($_SESSION['will_pickup_order'])) {
			$will_pickup_order = $_SESSION['will_pickup_order'];
		}

		$checkbox_disabled_text = '';

		if($total_quantity_in_cart < $pick_up_only_obj->pick_up_only_number && !$no_pickup_min_products) {
			//turn off checkbox - this is disabled currently!
			//$will_pickup_order = false;
			//$_SESSION['will_pickup_order'] = false;
			if($_SESSION['will_pickup_order'] == 'false') {
				$checkbox_disabled_text = 'disabled';
			}
		}
		//WC()->cart->will_pickup_order = $will_pickup_order;

		?>
		<input type="checkbox" id="pickup_order_cart_checkbox" name="pickup_order_cart_checkbox" <?php echo ($will_pickup_order === 'true' ? "checked=\"checked\"" : "") . ' ' . $checkbox_disabled_text; ?> onclick="onp_update_cart_after_checkbox_clicked(true)"/><label for="pickup_order_cart_checkbox"><?php echo $pick_up_only_obj->get_pick_up_only_text(); ?></label>
		<script>
			let ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

			jQuery(document).ready(function() {
				hide_shipping_address_if_local_pickup();
			});

			jQuery(document.body).on( 'updated_cart_totals', function() {
				onp_update_cart_after_checkbox_clicked();
			});

			jQuery(document.body).on( 'updated_shipping_method', function() {
				onp_update_cart_after_checkbox_clicked();
			});

			function onp_trigger_cart_update() {
				jQuery("[name='update_cart']").removeAttr('disabled');
				jQuery("[name='update_cart']").trigger("click");
			}

			function hide_shipping_address_if_local_pickup() {
				var pickup_checkbox = document.getElementById('pickup_order_cart_checkbox').checked;
				if(pickup_checkbox) {
					jQuery('.woocommerce-shipping-destination').hide();
				}
			}

			function onp_update_cart_after_checkbox_clicked(update = false) {
				var pickup_checkbox = document.getElementById('pickup_order_cart_checkbox').checked;

				hide_shipping_address_if_local_pickup();

				var data = {
					'action': 'set_session_pick_up_button',
					'checkbox_status': pickup_checkbox,
				};
				jQuery.post(ajaxurl, data, function(response) {
					if(response) {
						//alert(response);
						if(update) {
							onp_trigger_cart_update();
						}
						
						if(response == 'true') {
							//alert('yes it"s true');
						} else {

						}
					}
				});
			}
		</script>
		<?php
	}
}

function on_cart_update_list_num_products() {
	add_action('woocommerce_before_cart_totals', 'list_num_products', 10);
}

add_filter('woocommerce_shipping_packages', 'onp_update_woocommerce_shipping_options');
function onp_update_woocommerce_shipping_options($packages) {
	onp_start_session();

	//get the correct shipping package, then unset all the rest
	$desired_shipping_method = onp_set_woocommerce_shipping_var();
	//admin_dump_file($desired_shipping_method);
	foreach ( WC()->shipping->get_packages() as $key => $package ) {
		// Loop through Shipping rates
		foreach($package['rates'] as $rate_id => $rate ) {
			if($rate_id != WC()->session->get('chosen_shipping_methods')[0]) {
				unset($packages[0]['rates'][$rate_id]);
			}
		}
	}

	return $packages;
}

add_action('woocommerce_checkout_update_order_review', 'on_checkout_update_list_num_products');
function on_checkout_update_list_num_products() {
	//$test = remove_action('woocommerce_checkout_update_order_review', 'on_checkout_update_list_num_products');
	//add_action('woocommerce_review_order_after_cart_contents', 'tosser', 10, 0);
}
add_action('woocommerce_review_order_after_cart_contents', 'tosser', 10, 0);

function tosser() {
list_num_products(true, false, false); ?><script>        jQuery(document).ready(function() {
            jQuery('#pick_up_field').insertAfter('#checkout_note'); });</script><?php
}

/*
add_filter('woocommerce_package_rates', 'onp_hide_shipping_methods', 0);
function onp_hide_shipping_methods($rates) {
	$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
	$chosen_shipping = $chosen_methods[0];
	foreach($rates as $k => $v) {
		if($k !== $chosen_shipping) {
			unset($rates[$k]);
		}
	}
	return $rates;
}
*/

function list_num_products($spacer = true, $on_cart_page = true, $show_empty_cart_button = true) {
	//function fires on both cart page and checkout page
	$pick_up_products_obj = new pick_up_only_product();
	$elements_to_display = array();

	ob_start();

	//
	if($spacer) {
		if($on_cart_page) {
			echo ($spacer ? '<br />' : '');
		} else {
			$elements_to_display['spacer'] = array(
				'str' => '<div id="spacer"><br /></div>',
			);
		}
	}
	$total_quantity_in_cart = get_num_products_in_cart();
	$s = '';
	if($total_quantity_in_cart > 1 || $total_quantity_in_cart == 0) {
		$s = 's';
	}

	//$no_max_products = 0;
	//$no_min_products = 0;
	$no_pickup_min_products = 0;
	$applied_coupons = WC()->cart->get_applied_coupons();

	foreach($applied_coupons as $cur_coupon) {
		/*if(strtolower($cur_coupon) == 'over48') {
			$no_max_products = 1;
		}
		if(strtolower($cur_coupon) == 'under4' || strtolower($cur_coupon) == 'underfour') {
			$no_min_products = 1;
		}*/
		if(strtolower($cur_coupon) == 'smallpickup') {
			$no_pickup_min_products = 1;
		}
	}

	$order_multiples_number = get_option('order_multiples_number', 8);
	$order_multiples_text = get_option('order_multiples_text', '');
	$order_multiples_text = str_replace('%n', $order_multiples_number, $order_multiples_text);

	//add 'empty cart' button here
	if($show_empty_cart_button) {
		?>
		<div id="empty_cart"><a href="/cart/?alg_wc_empty_cart" style="" class="button" id="stumpy">Empty cart</a></div>

<!--		<script>
			jQuery('document').ready(function() {
				jQuery('#alg_wc_empty_cart').prependTo('div#empty_cart');
			});
		</script>-->
		<?php
		//echo do_shortcode( '[prowc_empty_cart_button]' );
	}

	if($on_cart_page) {
		if($total_quantity_in_cart) {
			echo '<p id="num_products_in_cart"><strong>You have ' . $total_quantity_in_cart . ' plant' . $s . ' in your cart.</strong></p>';
		}
	} else {
		if($total_quantity_in_cart) {
			$elements_to_display['num_products_in_cart'] = array(
				'str' => '<p id="num_products_in_cart"><strong>You have ' . $total_quantity_in_cart . ' plant' . $s . ' in your cart.</strong></p>',
			);
		}
	}


	if($total_quantity_in_cart % $order_multiples_number != 0 && $on_cart_page && $order_multiples_text != '') {
		/*$elements_to_display['order_in_multiples_of_8'] = array(
			'str' => '<p id="order_in_multiples_of_8">'.$order_multiples_text.'</p>',
		);*/
		echo '<p id="order_in_multiples_of_8">'.$order_multiples_text.'</p>';
	}
	
	if(!$on_cart_page) {
		$element_to_prepend_to = '#order_review';
		$checkout_page_note = get_option('checkout_page_note', 'Your order is estimated to ship in mid to late May.');
		
		$elements_to_display['checkout_note'] = array(
			'str' => '<p id="checkout_note"><strong>' . str_replace('--NEWLINE--', '<br />', $checkout_page_note) . '</strong></p>',
		);
		$elements_to_display['pick_up_additional_text'] = array(
			'str' => '<p id="pick_up_additional_text" style="display: none;"><b>' . $pick_up_products_obj->get_pick_up_additional_text() . '</b></p>',
		);

		echo '<script>';
		foreach(array_reverse($elements_to_display) as $element_id => $var) {
			?>
			if ( !jQuery( "#<?php echo $element_id; ?>" ).length ) {
				jQuery('<?php echo $element_to_prepend_to; ?>').prepend('<?php echo $var['str']; ?>');
			}
			<?php
		}
		if($total_quantity_in_cart < $pick_up_products_obj->pick_up_only_number && !$no_pickup_min_products) {
			echo 'document.getElementById("pick_up").disabled=true;';
		}
		echo "jQuery('#num_products_in_cart').insertBefore('#second_order_field');";
		echo '</script>';

	}

	echo ob_get_clean();
}

function update_cart_func() {
	$pick_up_only_obj = new pick_up_only_product();

	//we want to be able to disable 'checkout' button if total quantity of items in user's cart is is lower than a certain number
	$total_quantity_in_cart = get_num_products_in_cart();

	$min_products_in_cart = get_option('min_products_number', 4);
	$max_products_in_cart = get_option('max_products_number', 48);

	$no_max_products = 0;
	$no_min_products = 0;
	$no_pickup_min_products = 0;
	$applied_coupons = WC()->cart->get_applied_coupons();

	//WC()->cart->will_pickup_order = $will_pickup_order;

	foreach($applied_coupons as $cur_coupon) {
		if(strtolower($cur_coupon) == 'over48') {
			$no_max_products = 1;
		}
		if(strtolower($cur_coupon) == 'under4' || strtolower($cur_coupon) == 'underfour') {
			$no_min_products = 1;
		}
		if(strtolower($cur_coupon) == 'smallpickup') {
			$no_pickup_min_products = 1;
		}
	}

	if($_SESSION['will_pickup_order'] == 'true') {
		$no_max_products = 1;
	}

	if($total_quantity_in_cart < $min_products_in_cart && $total_quantity_in_cart != 0 && !$no_min_products) {
		remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
		$min_product_message = get_option('min_product_message', 'Each order must contain at least %s products before check-out is allowed.');
		$min_product_message = str_replace('%s', $min_products_in_cart, $min_product_message);
		wc_add_notice($min_product_message, 'error');
	} elseif($total_quantity_in_cart > $max_products_in_cart && !$no_max_products) {
		remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
		$max_product_message = get_option('max_product_message', 'Each order cannot contain more than %s products due to shipping limitations.  Please contact us for more information.');
		$max_product_message = str_replace('--NEWLINE--', '<br />', $max_product_message);
		$max_product_message = str_replace('%s', $max_products_in_cart, $max_product_message);
		$max_product_message = str_replace('%rm', $pick_up_only_obj->get_pick_up_additional_text_url_link('Read More Here'), $max_product_message);
		wc_add_notice($max_product_message, 'notice');
	}

	//pickup plants in order logic
	if($pick_up_only_obj->pickup_season_is_active()) {
		if($_SESSION['will_pickup_order'] == 'true') {
			if($total_quantity_in_cart < $pick_up_only_obj->pick_up_only_number && !$no_pickup_min_products) {
				remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
				wc_add_notice($pick_up_only_obj->get_pick_up_only_min_cart_error(), 'error');
			}
		}

		//if pickup-only product is in cart, and pickup isn't selected...  AND pickup season is set to active!
		if($pick_up_only_obj->is_pickup_only_product_in_cart() && $_SESSION['will_pickup_order'] !== 'true') {
			remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
			wc_add_notice($pick_up_only_obj->get_pick_up_only_cart_error(), 'error');
		}
	}

	$postcard_only = false;
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		//specifically if the product in the cart is ONLY a 'spring-postcard-pack'
		if($cart_item['product_id'] == 150375) {
			$postcard_only = true;
		}
	}

	$postcard_coupon_code = 'springpostcard';
	if($postcard_only) {
		if ( ! WC()->cart->has_discount( $postcard_coupon_code ) ) {
			WC()->cart->apply_coupon( $postcard_coupon_code );
		}
	} else {
		if ( WC()->cart->has_discount( $postcard_coupon_code ) ) {
			WC()->cart->remove_coupon( $postcard_coupon_code );
		}
	}

	return;
}

add_action( 'wp_ajax_nopriv_set_session_pick_up_button', 'set_session_pick_up_button' );
add_action( 'wp_ajax_set_session_pick_up_button', 'set_session_pick_up_button' );
function set_session_pick_up_button() {
	$res = onp_set_woocommerce_shipping_var();
	echo $res;

	wp_die();
}

add_action( 'wp_ajax_nopriv_get_number_of_cart_items', 'get_number_of_cart_items' );
add_action( 'wp_ajax_get_number_of_cart_items', 'get_number_of_cart_items' );
function get_number_of_cart_items() {
	$cart = WC()->cart;
	echo $cart->get_cart_contents_count();
	wp_die();
}

function onp_set_woocommerce_shipping_var() {
	//function to return the correct shipping method based on cart contents and whether pickup is selected or not.
	onp_start_session();
	if(isset($_POST['checkbox_status'])) {
		$_SESSION['will_pickup_order'] = $_POST['checkbox_status'];
	}

	//default case
	$shipping_method = 'canada_post:DOM.EP';

	if(does_order_have_free_shipping()) {
		$shipping_method = 'free_shipping:2';
	}

	//force local pickup
	if($_SESSION['will_pickup_order'] == 'true') {
		$shipping_method = 'local_pickup:4';
	}

	WC()->session->set('chosen_shipping_methods', array($shipping_method));

	return $shipping_method;
	//return WC()->session->get('chosen_shipping_methods')[0];
}

function does_order_have_free_shipping() {
	$applied_coupons = WC()->cart->get_applied_coupons();
	foreach($applied_coupons as $coupon_code) {
		$coupon = new WC_Coupon($coupon_code);

		if($coupon->get_free_shipping()) {
			return true;
		}
	}
	return false;
}


/******************************** Admin pages ***********************************/

/************ ADD COLUMN TO WOOCOMMERCE ORDERS PAGE ****************/
function onp_custom_order_page_column_header( $columns ) {
	$new_columns = array();
	foreach ( $columns as $column_name => $column_info ) {
		$new_columns[ $column_name ] = $column_info;
		if ( 'order_status' === $column_name ) {
			$new_columns['second_order'] = __('Second Order', 'second_order');
		}
	}
	return $new_columns;
}
add_filter( 'manage_edit-shop_order_columns', 'onp_custom_order_page_column_header', 20 );


function onp_custom_order_page_column_data($column) {
	global $post;

	if ('second_order' === $column) {
		$order = wc_get_order( $post->ID );
		$second_order = get_post_meta( $order->id, 'second_order', true );
		echo ($second_order ? '<b class="checkmark">&#10004;</b>' : '');
	}
}
add_action( 'manage_shop_order_posts_custom_column', 'onp_custom_order_page_column_data' );







/******* Checks for NUMBER OF PLANTS as well as PLANT NAMES IN PACK on product save ******/
add_action('woocommerce_update_product', 'check_for_plant_pack_attributes_on_product_save', 10, 1);
function check_for_plant_pack_attributes_on_product_save($product_id) {
	$product = wc_get_product($product_id);

	if(product_is_plant_pack($product_id)) {
		update_number_of_plants_in_pack($product);
	}
}

/********* Called from check_for_plant_pack_attributes_on_product_save() AND min_products_save() (which passes $new_num_plants_val) *********/
function update_number_of_plants_in_pack($product, $new_num_plants_val = null) {
	$product_id = $product->get_id();
	$new_attr = array();

	foreach($product->get_attributes() as $attr_name => $cur_attr) {
		$new_attr[$attr_name] = array(
			'name'			=> $cur_attr->get_name(),
			'value'			=> $cur_attr->get_options()[0],
			'is_visible'	=> 0,
			'is_taxonomy'	=> 0,
		);
	}
	if(!in_array('number-of-plants-in-pack', array_keys($product->get_attributes()))) {
		$new_attr['number-of-plants-in-pack'] = array(
			'name'			=> 'Number of Plants in Pack',
			'value'			=> 1,
			'is_visible'	=> 0,
			'is_taxonomy'	=> 0,
		);
	}
	if(!in_array('plant-names-in-pack', array_keys($product->get_attributes()))) {
		$new_attr['plant-names-in-pack'] = array(
			'name'			=> 'Plant Names in Pack',
			'value'			=> '',
			'is_visible'	=> 0,
			'is_taxonomy'	=> 0,
		);
	}

	if($new_num_plants_val != null) {
		$new_attr['number-of-plants-in-pack']['value'] = $new_num_plants_val;
	}

	wp_set_object_terms($product_id, $new_attr, '_product_attributes');
	update_post_meta($product_id, '_product_attributes', $new_attr);
}



function wca_add_product_attributes_automatically($new_status, $old_status, $post) {
	$product_type = WC_Product_Factory::get_product_type($post->ID);

	//if ( $new_status == "auto-draft" && isset( $post->post_type ) && $post->post_type == 'product' ){
	if ( isset( $post->post_type ) && $post->post_type == 'product' && $product_type != 'pw-gift-card') {
		$product = wc_get_product(absint( $post->ID ));
		if(product_is_plant_pack($post->ID) || product_is_tshirt($post->ID) || product_is_tote_bag($post->ID) || product_is_merchandise($post->ID)) {
			return;
		}
		if( function_exists( 'wc_get_attribute_taxonomies' ) && ( $attribute_taxonomies = wc_get_attribute_taxonomies() ) ) {

			$defaults = array();
			$manual_order = get_new_product_attribute_order();

			foreach ( $manual_order as $ordered_name ) {
				$name = wc_attribute_taxonomy_name( $ordered_name );

				// do stuff here
				$defaults[ $name ] = array (
						'name' => $name,
						'value' => '',
						'position' => 1,
						'is_visible' => 1,
						'is_variation' => 1,
						'is_taxonomy' => 1,
				);
				update_post_meta( $post->ID , '_product_attributes', $defaults );
			}
		}
	}
}
add_action('transition_post_status', 'wca_add_product_attributes_automatically', 10, 3);





/*********** HELPER FUNCTIONS **********/

function get_num_products_in_cart() {
	$total_quantity_in_cart = 0;

	//need to check to make sure no gift cards are included here
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$item_name = $cart_item['data']->get_title();
		$quantity = $cart_item['quantity'];

		//cover plant pack usecase
		$product_id = $cart_item['product_id'];
		$product = wc_get_product($product_id);
		if(product_is_plant_pack($product_id)) {
			$cur_attributes = $product->get_attributes('number-of-plants-in-pack');
			$quantity = $cur_attributes['number-of-plants-in-pack']->get_options()[0] * $cart_item['quantity'];
		}

		//cover gift cards usecase
		if($product->get_type() != 'pw-gift-card' && $item_name != 'Admin Fee' && !product_is_tshirt($product_id) && !product_is_tote_bag($product_id) && !product_is_merchandise($product_id)) {
			$total_quantity_in_cart = $total_quantity_in_cart + $quantity;
		}
	}
	return $total_quantity_in_cart;
}

function get_num_products_in_order($order) {
	$total_quantity_in_order = 0;

	//need to check to make sure no gift cards are included here
	foreach ($order->get_items() as $order_item) {
		$item_name = $order_item->get_name();
		$quantity = $order_item->get_quantity();

		//cover plant pack usecase
		$product_id = $order_item['product_id'];
		$product = wc_get_product($product_id);
		if(product_is_plant_pack($product_id)) {
			$cur_attributes = $product->get_attributes('number-of-plants-in-pack');
			$quantity = $cur_attributes['number-of-plants-in-pack']->get_options()[0] * $order_item['quantity'];
		}
		//cover gift cards usecase
		if($product->get_type() != 'pw-gift-card' && $item_name != 'Admin Fee' && !product_is_tshirt($product_id) && !product_is_tote_bag($product_id) && !product_is_merchandise($product_id)) {
			$total_quantity_in_order = $total_quantity_in_order + $quantity;
		}
	}
	return $total_quantity_in_order;
}

function get_new_product_attribute_order() {
	$items = array(
		'average-height',
		'light-requirements',
		'moisture-requirements',
		'soil-requirements',
		'poisonous-to-humans',
		'pollinators',
		'flower-colour',
		'bloom-period',
		'fall-colours',
		'hand-picked',
	);
	return $items;
}

function get_plant_pack_products() {
	$args = array(
		'category' => array( 'plant-packs' ),
		'limit' => -1,
	);
	return wc_get_products( $args );
}

function product_is_plant_pack($product_id) {
	$product = wc_get_product($product_id);
	return in_array(125, $product->get_category_ids());
}

function product_is_tshirt($product_id) {
	$product = wc_get_product($product_id);
	return in_array(612, $product->get_category_ids());
}

function product_is_tote_bag($product_id) {
	if($product_id == 140951) {
		return true;
	}
	return false;
}

function product_is_merchandise($product_id) {
	$product = wc_get_product($product_id);
	return in_array(658, $product->get_category_ids());
}

//log to text file
function admin_dump_file($text) {
	$fh = fopen('onp_log_'.date('Ymd').'.txt', 'a');
	ob_start();
	print_r($text);
	fwrite($fh, ob_get_clean() . "\n");
	fclose($fh);
}

//log to screen
function admin_dump( $arr, $admin_only = false ) {
	global $user;
	if($admin_only) {
		if(!is_admin()) {
			return;
		}
	}
	echo '<script>console.log(`';
	print_r( $arr );
	echo '`);</script>';
}

function storefront_menu_toggle_no_text( $str ) {
	return '';
}

/******** Snippets and settings ********/
/**
 * @snippet       Display "Sold Out" on Loop Pages - WooCommerce
 * @how-to        Watch tutorial @ https://businessbloomer.com/?p=19055
 * @sourcecode    https://businessbloomer.com/?p=17420
 * @author        Rodolfo Melogli
 * @testedwith    WooCommerce 3.4.3
 */

add_action( 'woocommerce_before_shop_loop_item_title', 'bbloomer_display_sold_out_loop_woocommerce' );

function bbloomer_display_sold_out_loop_woocommerce() {
	global $product;

	if ( !$product->is_in_stock() ) {
		if ( has_term( 'coming-soon', 'product_cat', $product->ID ) ) {
			echo '<span class="soldout">' . __( 'COMING SOON', 'woocommerce' ) . '</span>';
		} else {
			echo '<span class="soldout">' . __( 'OUT OF STOCK', 'woocommerce' ) . '</span>';
		}
	}
}

/*** DISABLE COMMENTS ***/

// Disable support for comments and trackbacks in post types
function df_disable_comments_post_types_support() {
	$post_types = get_post_types();
	foreach ($post_types as $post_type) {
		if(post_type_supports($post_type, 'comments')) {
			remove_post_type_support($post_type, 'comments');
			remove_post_type_support($post_type, 'trackbacks');
		}
	}
}
add_action('admin_init', 'df_disable_comments_post_types_support');
// Close comments on the front-end
function df_disable_comments_status() {
	return false;
}
add_filter('comments_open', 'df_disable_comments_status', 20, 2);
add_filter('pings_open', 'df_disable_comments_status', 20, 2);
// Hide existing comments
function df_disable_comments_hide_existing_comments($comments) {
	$comments = array();
	return $comments;
}
add_filter('comments_array', 'df_disable_comments_hide_existing_comments', 10, 2);
// Remove comments page in menu
function df_disable_comments_admin_menu() {
	remove_menu_page('edit-comments.php');
}
add_action('admin_menu', 'df_disable_comments_admin_menu');
// Redirect any user trying to access comments page
function df_disable_comments_admin_menu_redirect() {
	global $pagenow;
	if ($pagenow === 'edit-comments.php') {
		wp_redirect(admin_url()); exit;
	}
}
add_action('admin_init', 'df_disable_comments_admin_menu_redirect');
// Remove comments metabox from dashboard
function df_disable_comments_dashboard() {
	remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
}
add_action('admin_init', 'df_disable_comments_dashboard');
// Remove comments links from admin bar
function df_disable_comments_admin_bar() {
	if (is_admin_bar_showing()) {
		remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
	}
}
add_action('init', 'df_disable_comments_admin_bar');


// Add arrows to product gallery sliders
add_filter( 'woocommerce_single_product_carousel_options', 'sf_update_woo_flexslider_options' );
/**
 * Filer WooCommerce Flexslider options - Add Navigation Arrows
 */
function sf_update_woo_flexslider_options( $options ) {

	$options['directionNav'] = true;

	return $options;
}




/********** Unused but potentially useful **********/

function create_coupon($coupon_code = '', $amount = 25) {
	/**
	 * Create a coupon programatically
	 */

	//first check if a coupon code was provided and, if so, make sure it is a new coupon code.  If no code is provided, create a 16-digit randomized coupon code and make sure it is unique before use.
	if($coupon_code == '') {
		$alphabet = range('a', 'z');
		$test_coupon = new WC_Coupon($coupon_code);
		while($test_coupon->id > 0 || $coupon_code == '') {
			$coupon_code = '';
			for($i = 0; $i < 16; $i++) {
				$coupon_code = $coupon_code . $alphabet[rand(0, count($alphabet))];
			}
//			admin_dump($coupon_code);
			$test_coupon = new WC_Coupon($coupon_code);
		}
	} else {
		$test_coupon = new WC_Coupon($coupon_code);
		if($test_coupon->id > 0) {
			admin_dump('Duplicate coupon exists');
			return;
		}
	}

	$discount_type = 'fixed_cart'; // Type: fixed_cart, percent, fixed_product, percent_product

	$coupon = array(
		'post_title' => $coupon_code,
		'post_content' => 'Gift card coupon - $'.$amount,
		'post_status' => 'publish',
		'post_author' => 1,
		'post_type'		=> 'shop_coupon'
	);

	$new_coupon_id = wp_insert_post( $coupon );

	// Add meta
	update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
	update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
	update_post_meta( $new_coupon_id, 'individual_use', 'no' );
	update_post_meta( $new_coupon_id, 'product_ids', '' );
	update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
	update_post_meta( $new_coupon_id, 'usage_limit', '1' );
	update_post_meta( $new_coupon_id, 'expiry_date', '' );
	update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
	update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
}




function resend_emails() {
	$debug = 1;
	$wc_emails = WC()->mailer()->get_emails();
	if( empty( $wc_emails ) ) {
		return;
	}

    $order_ids = array(
    	'362',
	);

	for($i = 90; $i < 363; $i++) {
        $order = wc_get_orders( array( '_alg_wc_custom_order_number' => $i ) );

        $order = $order[0];
        $order_date = $order->date_created->date('Y-m-d');
        print_r($order->get_id());
        $email_id = '';

        if(strtotime($order_date) >= strtotime('2021-03-01') || $debug) {
			if ($order->has_status( 'on-hold' )) {
				$email_id = 'customer_on_hold_order';
			} elseif ($order->has_status( 'processing' )) {
				$email_id = 'customer_processing_order';
			} elseif ($order->has_status( 'completed' )) {
				$email_id = 'customer_completed_order';
			} else {
				return;
			}

        }

        if($debug) {
		    echo '<pre>';
		    print_r($order);
		    echo '</pre>';
		    return;
		    exit();
        } else {

			foreach ( $wc_emails as $wc_mail ) {
				if ( $wc_mail->id == $email_id ) {
					$wc_mail->trigger( $order->get_id() );
				}
			}
        }
	}

    return;
}

function onp_start_session() {
	if (session_status() == PHP_SESSION_DISABLED) {
		return;
	} elseif(session_status() == PHP_SESSION_NONE) {
		if ( !session_id() ) {
		    session_start( [
		        'read_and_close' => true,
		    ] );
		}
	}
}

add_action( 'widgets_init', 'hide_out_of_stock_widget' );
function hide_out_of_stock_widget() {
    // Register our own widget.
    register_widget( 'Hide_Out_of_Stock_Widget' );
}

class Hide_Out_of_Stock_Widget extends WP_Widget {
    public function __construct() {
    // id_base        ,  visible name
        parent::__construct( 'hide_out_of_stock_widget', 'ONP Hide Out of Stock' );
    }

    public function widget( $args, $instance ) {
    	onp_start_session();

        ob_start();
        echo $args['before_widget'], wpautop( $instance['text'] );
        ?>
		<form action="" method="POST" id="hide-out-of-stock-form">
			<div class="gamma widget-title" style="padding-bottom: 16px;">
				<input type="hidden" name="onp-out-of-stock-submitted" value="1">
				<input type="checkbox" id="hide-out-of-stock" name="hide-out-of-stock" value="1"
				        <?php echo (@$_SESSION['hide-out-of-stock'] ? 'checked' : ''); ?> onclick="document.getElementById('hide-out-of-stock-form').submit();">
				<label for="hide-out-of-stock" class="cat-item cat-item-125" style="font-size: 14px; color: #8ebd33;"><b>Hide out of stock products</b></label>
			</div>
		</form>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('#hide-out-of-stock-form').insertAfter('#woocommerce_product_categories-3 .widget-title');
			});
		</script>
        <?php
        echo $args['after_widget'];
        echo ob_get_clean();
    }

    public function form( $instance ) {
        $text = isset ( $instance['text'] )
            ? esc_textarea( $instance['text'] ) : '';
        printf(
            '<textarea class="widefat" rows="7" cols="20" id="%1$s" name="%2$s">%3$s</textarea>',
            $this->get_field_id( 'text' ),
            $this->get_field_name( 'text' ),
            $text
        );
    }
}