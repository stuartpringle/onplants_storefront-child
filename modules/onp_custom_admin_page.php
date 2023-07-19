<?php



/************** ONP Custom page *************/

add_action('admin_menu', 'min_products_register_page');
add_action('admin_post', 'min_products_save');

function min_products_register_page() {
	add_submenu_page( 'woocommerce', 'ONP Custom Settings', 'ONP Custom Settings', 'manage_options', 'onp-settings', 'onp_settings_callback' );
}

function onp_settings_callback() {
	$plant_pack_products = get_plant_pack_products();

	$min_products_number = get_option('min_products_number', 4);
	$min_product_message = get_option('min_product_message', 'Each order must contain at least %s products before check-out is allowed.');
	$max_products_number = get_option('max_products_number', 48);
	$max_product_message = get_option('max_product_message', 'Each order cannot contain more than %s products due to shipping limitations.  Please contact us for more information.');
	$second_order_title = get_option('second_order_title', 'This is my second order.  Please ship with my first order.');
	$second_order_message = get_option('second_order_message', 'Thank you for your second order. You will be charged shipping for each order, but not to worry! We will send you a refund for any unused shipping fees when your combined order ships.');

	$order_notes_message = get_option('order_notes_message', 'Please note: we will add their note to the box, but we cannot guarantee that canada post will follow that.');
	$checkout_page_note = get_option('checkout_page_note', 'Your order is estimated to ship in mid to late May.');
	$cart_message = get_option('cart_message', 'Please ensure that your cart does not contain plants from a previous order before making a new order.');

	$order_multiples_text = get_option('order_multiples_text', 'To optimize the shipping cost, consider ordering in multiples of %n (of any species).');
	$order_multiples_number = get_option('order_multiples_number', 8);

	$pick_up_only_text = get_option('pick_up_only_text', 'I am ordering %n+ plants and would like to pick up my order!');
	$pick_up_only_number = get_option('pick_up_only_number', 24);
	$pick_up_products_obj = new pick_up_only_product();

	$args = array(
	    //'category' => array( 'hoodies' ),
	    'orderby'	=> 'name',
	    'downloadable' => false,
	    'limit'		=> -1,
	);
	$products = wc_get_products( $args );

	ob_start();
//admin_dump($products);
	?>
	<div class="wrap">
		<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
		<div class="ever-settings d-flex">
			<div class="ever-settings-content">
				<div id="min_prod_general_settings" class="group" style="">
					<form method="post" action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>">
						<h2>Min/Max Products Per Order</h2>
						<table class="form-table" role="presentation">
							<tr class="min_product_quantity">
								<th scope="row">
								<label for="min_num_products_for_cart">Minimum Order Quantity</label>
								</th>
								<td>
									<input type="number" name="min_num_products_for_cart" class="regular-number" value="<?php echo $min_products_number; ?>" />
									<p class="description">Specify a minimum number of items that must be in a user's cart before they may check out</p>
								</td>
							</tr>
							<tr class="min_product_message">
								<th scope="row">
								<label for="min_product_message">Minimum Order Message</label>
								</th>
								<td>
									<textarea name="min_product_message" class="regular-text"><?php echo str_replace('--NEWLINE--', "\n", $min_product_message); ?></textarea>
									<p class="description">Add a message to cart page to let people know they must have more than a certain number of items in order to check out.  You can use %s to display the number you've chosen in Minimum Order Quantity above.</p>
								</td>
							</tr>
							<tr class="max_product_quantity">
								<th scope="row">
								<label for="max_num_products_for_cart">Maximum Order Quantity</label>
								</th>
								<td>
									<input type="number" name="max_num_products_for_cart" class="regular-number" value="<?php echo $max_products_number; ?>" />
									<p class="description">Specify a maximum number of items that can be in a user's cart before they may check out</p>
								</td>
							</tr>
							<tr class="max_product_message">
								<th scope="row">
								<label for="max_product_message">Maximum Order Message</label>
								</th>
								<td>
									<textarea name="max_product_message" class="regular-text"><?php echo str_replace('--NEWLINE--', "\n", $max_product_message); ?></textarea>
									<p class="description">Add a message to cart page to let people know they must have fewer than a certain number of items in order to check out.  You can use %s to display the number you've chosen in Maximum Order Quantity above.  You can use %rm to display a link to "Pick-Up Only Additional Text URL" below.</p>
								</td>
							</tr>
						</table>

						<br />

						<h2>Plant Packs</h2>
						<p>Here you can specify the number of plants in each plant pack.  Default is 12.</p>
						<table class="form-table" role="presentation">
							<?php
							$counter = 0;
							foreach ($plant_pack_products as $cur_product) {
								$cur_attributes = $cur_product->get_attributes();

								if(!in_array('number-of-plants-in-pack', array_keys($cur_attributes))) {
									//update_number_of_plants_in_pack($cur_product);
									//$cur_attributes = $cur_product->get_attributes();
								} else {
									$number_in_pack = ($cur_attributes['number-of-plants-in-pack']->get_options()[0]);
								}

								$counter++;
								if($counter > 2) {
									echo '							<tr class="min_product_quantity">';
								}
								?>

								<th scope="row">
								<label for="plants-in-<?php echo $cur_product->get_slug(); ?>">
									<a href="/product/<?php echo $cur_product->get_slug(); ?>">
										<?php echo $cur_product->get_name(); ?>
									</a>
								</label>
								</th>
								<td>
									<input type="number" name="plants-in-<?php echo $cur_product->get_slug(); ?>" class="regular-number" value="<?php echo $number_in_pack; ?>" />
								</td>
							<?php
								if($counter > 1) {
									echo '							</tr>';
									$counter = 0;
								}
							}
							?>
						</table>

						<br />

						<table style="width: 100%;">
<?php /*
							<tr colspan="2">
								<td style="vertical-align: top;">
									<h2>Pickup-only Products</h2>
									<p>This is a list of product IDs that the store will not allow to be purchased unless the 'pick up' shipping option is selected</p>
									<table class="form-table" role="presentation">
										<tr class="pickup-only-products">
											<td>
												<textarea name="pickup_only_products" class="regular-text" cols="130" rows="5"><?php echo str_replace('--NEWLINE--', "\n", $pickup_only_products); ?></textarea>
											</td>
										</tr>
									</table>									
								</td>
							</tr>
*/ ?>
							<tr>
								<td style="width: 45%; border-right: 1px solid #cccccc; vertical-align: top;">
									<h2>Second Order Settings</h2>
									<p>Here you can specify the message to be displayed upon a customer checking the 'This is my second order' check box at checkout.</p>
									<table class="form-table" role="presentation">
										<tr class="second_order_title">
											<th scope="row">
											<label for="second_order_title">Second Order Checkbox Label</label>
											</th>
											<td>
												<input type="text" name="second_order_title" class="text" value="<?php echo $second_order_title; ?>" />
												<p class="description">Specify the title for the checkbox for use when customers make a second order</p>
											</td>
										</tr>
										<tr class="max_product_message">
											<th scope="row">
											<label for="max_product_message">Second Order Message</label>
											</th>
											<td>
												<textarea name="second_order_message" class="regular-text" cols="130" rows="5"><?php echo str_replace('--NEWLINE--', "\n", $second_order_message); ?></textarea>
												<p class="description">Text to be displayed when second order checkbox is selected.</p>
											</td>
										</tr>
									</table>
								</td>
								<td style="width: 45%; vertical-align: top; padding-left: 2%;">
									<h2>Additional Info Checkout Field Text</h2>
									<p>Specify the message to be displayed upon a customer filling out the 'Order Notes' additional info field on the checkout page.</p>
									<table class="form-table" role="presentation">
										<tr class="order-notes-message">
											<th scope="row">
											<label for="order_notes_message">Additional Info Checkout Field Text</label>
											</th>
											<td>
												<textarea name="order_notes_message" class="regular-text" cols="130" rows="5"><?php echo str_replace('--NEWLINE--', "\n", $order_notes_message); ?></textarea>
												<p class="description">Text to be displayed when order notes are filled out.</p>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td style="width: 45%; border-right: 1px solid #cccccc; vertical-align: top;">
									<h2>Order Notes Message</h2>
									<p>Under "YOUR ORDER" on the checkout page and under the text "You have X # plants in your cart"</p>
									<table class="form-table" role="presentation">
										<tr class="order-notes-message">
											<th scope="row">
											<label for="checkout_page_note">Order Notes Message</label>
											</th>
											<td>
												<textarea name="checkout_page_note" class="regular-text" cols="130" rows="5"><?php echo str_replace('--NEWLINE--', "\n", $checkout_page_note); ?></textarea>
												<p class="description">Estimated shipping date.</p>
											</td>
										</tr>
									</table>									
								</td>
								<td style="width: 45%; vertical-align: top; padding-left: 2%;">
									<h2>Cart Message</h2>
									<p>Appears on the cart page under products in cart and above coupon code area</p>
									<table class="form-table" role="presentation">
										<tr class="order-notes-message">
											<th scope="row">
											<label for="cart_message">Cart Message</label>
											</th>
											<td>
												<textarea name="cart_message" class="regular-text" cols="130" rows="5"><?php echo str_replace('--NEWLINE--', "\n", $cart_message); ?></textarea>
												<p class="description">Text to remind users to make sure their carts are empty before making additional orders.</p>
											</td>
										</tr>
									</table>									
								</td>
							</tr>
							<tr>
								<td style="width: 45%; border-right: 1px solid #cccccc; vertical-align: top;">
									<h2>Order Multiples Text</h2>
									<p>On cart page under 'EMPTY CART' button and 'You have X plants in your cart.' text.</p>
									<table class="form-table" role="presentation">
										<tr class="order-multiples-text">
											<th scope="row">
											<label for="order_multiples_text">Order Multiples Text</label>
											</th>
											<td>
												<textarea name="order_multiples_text" class="regular-text" cols="130" rows="5"><?php echo str_replace('--NEWLINE--', "\n", $order_multiples_text); ?></textarea>
												<p class="description">Use %n to reference the number set below.</p>
											</td>
										</tr>
										<tr class="order-multiples-number">
											<th scope="row">
											<label for="order_multiples_number">Order Multiples Number</label>
											</th>
											<td>
												<input type="text" name="order_multiples_number" class="text" value="<?php echo $order_multiples_number; ?>" />
												<p class="description">Number of plants best suited to optimize order size.</p>
											</td>
										</tr>
									</table>
								</td>
								<td style="width: 45%; vertical-align: top; padding-left: 2%;">
									<h2>Pick-up Only Products</h2>
									<p>List of products that cannot be purchased with a shipping option other than "Pick-up"</p>
									<table class="form-table" role="presentation">
										<tr class="order-notes-message">
											<th scope="row">
											<label for="cart_message">Product List</label>
											</th>
											<td>
												<div id="pick_up_products_display">
												<?php
												$pick_up_only_product_list = $pick_up_products_obj->get_list_as_array();

												foreach($pick_up_only_product_list as $cur_prod_slug) {
													$cur_prod_slug = trim($cur_prod_slug);
													foreach($products as $cur_prod) {
														if($cur_prod->get_slug() == $cur_prod_slug && $cur_prod_slug != '') {
															echo '<div class="" style="display: inline-block; border: 1px solid blue; border-radius: 5px; max-width: 300px; background-color: white; padding: 5px; margin: 5px;" id="' . $cur_prod_slug . '" onclick=""><a href="/product/' . $cur_prod_slug . '" target="_blank" style="text-decoration: none;">';
															echo $cur_prod->get_name();
															echo '</a><span class="x-button" style="padding-left: 10px; color: red; cursor: pointer;" onclick="remove_product_from_pick_up_only_area(\'' . $cur_prod_slug . '\')">x</span></div>';
														}
													}
												}
												?>
												</div>
												<textarea name="pick_up_only_products" id="pick_up_only_products" style="display: none;"><?php echo str_replace('--NEWLINE--', "\n", $pick_up_products_obj->pick_up_only_products); ?></textarea>
												<p class="description">Select the product you wish to add in the box below, then click "Set Product Pick-up Only".</p>
											</td>
										</tr>
										<tr>
											<td>
												<select id="product_list_select">
													<?php
													foreach($products as $cur_prod) {
														if(!in_array($cur_prod->get_slug(), $pick_up_only_product_list)) {
															echo '<option value="' . $cur_prod->get_slug() . '">' . $cur_prod->get_name() . '</option>';
														}
													}
													?>
												</select>
											</td>
											<td>
												<input style="" type="button" value="Set Product Pick-up Only" onclick="add_product_to_pick_up_only_textarea()">
											</td>
										</tr>
									</table>
									<table>
										<tr class="pick_up_only">
											<th scope="row" style="vertical-align: top;">
												<label for="pick_up_only_number">Pick-Up Only Minimum Plants</label>
											</th>
											<td>
												<input type="number" name="pick_up_only_number" class="regular-number" value="<?php echo $pick_up_products_obj->pick_up_only_number; ?>" />
												<p class="description">Specify a minimum number of items that must be in a user's cart before they may select 'Pick-up Only' at check out</p>
											</td>
										</tr>
										<tr class="pick_up_only_text">
											<th scope="row" style="vertical-align: top;">
												<label for="pick_up_only_text">Pick-Up Only Checkout Option</label>
											</th>
											<td>
												<textarea name="pick_up_only_text" class="regular-text"><?php echo str_replace('--NEWLINE--', "\n", $pick_up_only_text); ?></textarea>
												<p class="description">Option text for pick-up only checkbox on checkout page.  You can use %s to display the number you've chosen in Pick-Up Only Minimum Plants above.</p>
											</td>
										</tr>
										<tr class="pick_up_only_text">
											<th scope="row" style="vertical-align: top;">
												<label for="pick_up_only_min_cart_error">Pick-Up Only Min Cart Message</label>
											</th>
											<td>
												<textarea name="pick_up_only_min_cart_error" class="regular-text"><?php echo str_replace('--NEWLINE--', "\n", $pick_up_products_obj->pick_up_only_min_cart_error); ?></textarea>
												<p class="description">Add a message to cart page to let people know they must have more than a certain number of items in order to select 'Pick-Up' at check out.  You can use %s to display the number you've chosen in Pick-Up Only Minimum Plants above.</p>
											</td>
										</tr>
										<tr class="pick_up_only_text">
											<th scope="row" style="vertical-align: top;">
												<label for="pick_up_only_cart_error">Pick-Up Only Cart Message</label>
											</th>
											<td>
												<textarea name="pick_up_only_cart_error" class="regular-text"><?php echo str_replace('--NEWLINE--', "\n", $pick_up_products_obj->pick_up_only_cart_error); ?></textarea>
												<p class="description">Add a message to cart page when people select pick-up only products but do not meet the minimum number of plants required.  You can use %s to display the number you've chosen in Pick-Up Only Minimum Plants above.</p>
											</td>
										</tr>
										<tr class="pick_up_additional_text">
											<th scope="row" style="vertical-align: top;">
												<label for="pick_up_additional_text">Pick-Up Only Additional Text</label>
											</th>
											<td>
												<textarea name="pick_up_additional_text" class="regular-text"><?php echo str_replace('--NEWLINE--', "\n", $pick_up_products_obj->pick_up_additional_text); ?></textarea>
												<p class="description">Text to be displayed as a link on checkout page when user selects pick-up as shipping option.  Uses the URL below.</p>
											</td>
										</tr>
										<tr class="pick_up_only_text">
											<th scope="row" style="vertical-align: top;">
												<label for="pick_up_only_text">Pick-Up Only Additional Text URL</label>
											</th>
											<td>
												<input type="text" name="pick_up_additional_text_url" class="regular-text" value="<?php echo str_replace('--NEWLINE--', "\n", $pick_up_products_obj->pick_up_additional_text_url); ?>">
												<p class="description">URL the text in Pick-Up Only Additional Text will link to.</p>
											</td>
										</tr>
										<tr class="pick_up_before_calendar_text">
											<th scope="row" style="vertical-align: top;">
												<label for="pick_up_before_calendar_text">Before Calendar Text</label>
											</th>
											<td>
												<input type="text" name="pick_up_before_calendar_text" class="regular-text" value="<?php echo str_replace('--NEWLINE--', "\n", $pick_up_products_obj->pick_up_before_calendar_text); ?>">
												<p class="description">Text that appears above the pick-up calendar.</p>
											</td>
										</tr>
										<tr class="pick_up_after_calendar_text">
											<th scope="row" style="vertical-align: top;">
												<label for="pick_up_after_calendar_text">After Calendar Text</label>
											</th>
											<td>
												<input type="text" name="pick_up_after_calendar_text" class="regular-text" value="<?php echo str_replace('--NEWLINE--', "\n", $pick_up_products_obj->pick_up_after_calendar_text); ?>">
												<p class="description">Text that appears below the pick-up calendar.</p>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td style="width: 45%; border-right: 1px solid #cccccc; vertical-align: top;">
								</td>
							</tr>
						</table>


						<?php
							wp_nonce_field( 'min-order-settings-save', 'order-save-please' );
							submit_button();
						?>
					</form>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		var product_list_select = document.getElementById('product_list_select');
		var pick_up_only_products = document.getElementById('pick_up_only_products');
		var pick_up_products_display = document.getElementById('pick_up_products_display');

		function add_product_to_pick_up_only_textarea() {
			var start_html = '<div class="" style="display: inline-block; border: 1px solid blue; border-radius: 5px; max-width: 300px; background-color: white; padding: 5px; margin: 5px;" id="' + product_list_select.value + '" onclick=""><a href="/product/' + product_list_select.value + '" target="_blank" style="text-decoration: none;">';
			var end_html = '</a><span class="x-button" style="padding-left: 10px; color: red; cursor: pointer;" onclick="remove_product_from_pick_up_only_area(\'' + product_list_select.value + '\')">x</span></div>';

			if(!document.getElementById(product_list_select.value)) {
				pick_up_only_products.innerHTML = pick_up_only_products.innerHTML + "\n" + product_list_select.value + "\n";
				pick_up_products_display.innerHTML = pick_up_products_display.innerHTML + start_html + product_list_select.options[product_list_select.selectedIndex].text + end_html;
			}
		}

		function remove_product_from_pick_up_only_area(product_slug) {
			pick_up_only_products.innerHTML = pick_up_only_products.innerHTML.replace(product_slug, '');
			document.getElementById(product_slug).outerHTML = "";
		}
	</script>
	<?php
	echo ob_get_clean();
}

function min_products_save() {
	if(!min_products_check_nonce() && current_user_can('manage_options')) {
		admin_dump('Not allowed to do that');
	} else {
		$min_num_products_for_cart = sanitize_text_field( $_POST['min_num_products_for_cart'] );
		update_option('min_products_number', $min_num_products_for_cart);

		$min_product_message = str_replace("\n", '--NEWLINE--', $_POST['min_product_message']);
		$min_product_message = sanitize_text_field( $min_product_message );
		update_option('min_product_message', $min_product_message);

		$max_num_products_for_cart = sanitize_text_field( $_POST['max_num_products_for_cart'] );
		update_option('max_products_number', $max_num_products_for_cart);

		$max_product_message = str_replace("\n", '--NEWLINE--', stripslashes($_POST['max_product_message']));
		$max_product_message = sanitize_text_field( $max_product_message );
		update_option('max_product_message', $max_product_message);

		$second_order_title = sanitize_text_field( $_POST['second_order_title'] );
		update_option('second_order_title', $second_order_title);

		$second_order_message = str_replace("\n", '--NEWLINE--', $_POST['second_order_message']);
		$second_order_message = sanitize_text_field( $second_order_message );
		update_option('second_order_message', $second_order_message);

		$order_notes_message = str_replace("\n", '--NEWLINE--', $_POST['order_notes_message']);
		$order_notes_message = sanitize_text_field( $order_notes_message );
		update_option('order_notes_message', $order_notes_message);

		$checkout_page_note = str_replace("\n", '--NEWLINE--', $_POST['checkout_page_note']);
		$checkout_page_note = sanitize_text_field( $checkout_page_note );
		update_option('checkout_page_note', $checkout_page_note);

		$cart_message = str_replace("\n", '--NEWLINE--', $_POST['cart_message']);
		$cart_message = sanitize_text_field( $cart_message );
		update_option('cart_message', $cart_message);

		$order_multiples_text = str_replace("\n", '--NEWLINE--', $_POST['order_multiples_text']);
		$order_multiples_text = sanitize_text_field( $order_multiples_text );
		update_option('order_multiples_text', $order_multiples_text);

		$order_multiples_number = $_POST['order_multiples_number'];
		update_option('order_multiples_number', $order_multiples_number);

		$pick_up_only_obj = new pick_up_only_product();
		$pick_up_only_obj->save($_POST['pick_up_only_products']);

		$pick_up_only_number = sanitize_text_field( $_POST['pick_up_only_number'] );
		update_option('pick_up_only_number', $pick_up_only_number);

		$pick_up_only_text = str_replace("\n", '--NEWLINE--', $_POST['pick_up_only_text']);
		$pick_up_only_text = sanitize_text_field( $pick_up_only_text );
		update_option('pick_up_only_text', $pick_up_only_text);

		$pick_up_additional_text = str_replace("\n", '--NEWLINE--', $_POST['pick_up_additional_text']);
		$pick_up_additional_text = sanitize_text_field( $pick_up_additional_text );
		update_option('pick_up_additional_text', $pick_up_additional_text);

		$pick_up_additional_text_url = str_replace("\n", '--NEWLINE--', $_POST['pick_up_additional_text_url']);
		$pick_up_additional_text_url = sanitize_text_field( $pick_up_additional_text_url );
		update_option('pick_up_additional_text_url', $pick_up_additional_text_url);

		$pick_up_only_min_cart_error = str_replace("\n", '--NEWLINE--', $_POST['pick_up_only_min_cart_error']);
		$pick_up_only_min_cart_error = sanitize_text_field( $pick_up_only_min_cart_error );
		update_option('pick_up_only_min_cart_error', $pick_up_only_min_cart_error);

		$pick_up_only_cart_error = str_replace("\n", '--NEWLINE--', $_POST['pick_up_only_cart_error']);
		$pick_up_only_cart_error = sanitize_text_field( $pick_up_only_cart_error );
		update_option('pick_up_only_cart_error', $pick_up_only_cart_error);

		$pick_up_before_calendar_text = str_replace("\n", '--NEWLINE--', $_POST['pick_up_before_calendar_text']);
		$pick_up_before_calendar_text = sanitize_text_field( $pick_up_before_calendar_text );
		update_option('pick_up_before_calendar_text', $pick_up_before_calendar_text);

		$pick_up_after_calendar_text = str_replace("\n", '--NEWLINE--', $_POST['pick_up_after_calendar_text']);
		$pick_up_after_calendar_text = sanitize_text_field( $pick_up_after_calendar_text );
		update_option('pick_up_after_calendar_text', $pick_up_after_calendar_text);

		$plant_pack_products = get_plant_pack_products();

		foreach($plant_pack_products as $cur_product) {
			update_number_of_plants_in_pack($cur_product, sanitize_text_field($_POST['plants-in-'.$cur_product->get_slug()]));
		}
	}
	min_products_redirect();
}

function min_products_redirect() {
	// To make the Coding Standards happy, we have to initialize this.
	if (!isset($_POST['_wp_http_referer'])) { // Input var okay.
		$_POST['_wp_http_referer'] = wp_login_url();
	}

	// Sanitize the value of the $_POST collection for the Coding Standards.
	$url = sanitize_text_field(wp_unslash($_POST['_wp_http_referer']));

	// Finally, redirect back to the admin page.
	wp_safe_redirect(urldecode($url));
	exit;
}

function min_products_check_nonce() {
	// If the field isn't even in the $_POST, then it's invalid.
	if ( ! isset( $_POST['order-save-please'] ) ) { // Input var okay.
		return false;
	}

	$field  = wp_unslash( $_POST['order-save-please'] );
	$action = 'min-order-settings-save';

	return wp_verify_nonce( $field, $action );
}

