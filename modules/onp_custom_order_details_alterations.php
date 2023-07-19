<?php
/* File to keep all alterations to the 'Order Details' page (/wp-admin/post.php?post=122997&action=edit for example) separated from the rest of the theme's functionality */

add_action( 'woocommerce_admin_order_data_after_shipping_address', 'onp_custom_second_order_field_display', 10, 1 );


add_action( 'woocommerce_process_shop_order_meta', 'onp_save_second_order_details' );
function onp_save_second_order_details( $order_id ){
	update_post_meta( $order_id, 'second_order', sanitize_text_field( $_POST[ 'second_order' ] ) );
}

add_action( 'woocommerce_process_shop_order_meta', 'onp_save_pickup_order_details' );
function onp_save_pickup_order_details( $order_id ){
	update_post_meta( $order_id, 'pick_up', sanitize_text_field( $_POST[ 'pick_up' ] ) );
}

add_action( 'woocommerce_process_shop_order_meta', 'onp_save_pick_up_option' );
function onp_save_pick_up_option( $order_id ){
	update_post_meta( $order_id, 'pick_up', sanitize_text_field( $_POST[ 'pick_up' ] ) );
}


//show the recorded data on the admin page
function onp_custom_second_order_field_display($order) {
	$second_order = get_post_meta( $order->id, 'second_order', true );
	$second_order_title = get_option('second_order_title', 'This is my second order.  Please ship with my first order.');
	?>

	<div class="address">
		<p<?php if( empty($second_order) ) echo ' class="none_set"' ?>>
			<strong><?php echo $second_order_title; ?></strong><br>
			<?php echo ($second_order > 0 ? 'Yes' : 'No'); ?>
		</p>
	</div>
	<div class="edit_address"><?php
		woocommerce_wp_select( array( 
			'id' => 'second_order',
			'label' => $second_order_title, 
			'type' => 'select',
			'options' => array(0 => 'No', 1 => 'Yes'),
			'value' => $second_order,
		) );
	?></div>

	<br style="clear: both;" /><br />

	<?php
	//$order->status;
	$custom_order_status = new onp_order_status($order->id);

	$show_set_on_hold_button = false;
	$show_set_processing_button = true;
	$show_set_completed_button = true;

	if($order->status == 'processing') {
		$show_set_on_hold_button = true;
		$show_set_processing_button = false;
	}

	if($order->status == 'completed') {
		$show_set_completed_button = false;
	}

	$show_to_print_button = false;
	$show_reset_button = false;
	if($order->status == 'processing' && !isset($custom_order_status->order_status)) {
		$show_to_print_button = true;
	}
	if ($custom_order_status->order_status != '') {
		$show_reset_button = true;
	}
	
	?>

		<p class="order_status column-order_status" data-colname="Status">
			<span class="button make-to-print" onClick="update_onp_order_status(<?php echo $order->id; ?>, 'to-print');" style="display: <?php echo ($show_to_print_button ? 'inline-block' : 'none'); ?>;">
				To Print
			</span>

			<span class="button reset" onClick="update_onp_order_status('<?php echo $order->id; ?>', 'reset');" style="display: <?php echo ($show_reset_button ? 'inline-block' : 'none'); ?>;">
				Reset
			</span>

<?php /*
			<br />
			<h3>Change order status without sending emails to customer</h3>
			<br />

			<span class="button set-completed" onClick="update_wc_order_status_no_email(<?php echo $order->id; ?>, 'completed');" style="display: <?php echo ($show_set_completed_button ? 'inline-block' : 'none'); ?>;">
				Change order to Completed
			</span>

			<br /><br />

			<span class="button set-hold" onClick="update_wc_order_status_no_email(<?php echo $order->id; ?>, 'hold');" style="display: <?php echo ($show_set_on_hold_button ? 'inline-block' : 'none'); ?>;">
				Change to On Hold (for editing)
			</span>
			<span class="button set-processing" onClick="update_wc_order_status_no_email(<?php echo $order->id; ?>, 'processing');" style="display: <?php echo ($show_set_processing_button ? 'inline-block' : 'none'); ?>;">
				Change to Processing
			</span>
*/ ?>
		</p>

		<script type="text/javascript">
			function update_onp_order_status($id, $new_status) {
				var data = {
					'action': 'update_order_status',
					'order_id': $id,
					'order_status': $new_status
				};

				jQuery.post(ajaxurl, data, function(response) {
					//alert('Got this from the server: ' + response);
					if(response) {
						if(response == 'to-print') {
							jQuery('.make-to-print').toggle();
							jQuery('.reset').toggle();
						} else if(response == 'reset') {
							jQuery('.make-to-print').toggle();
							jQuery('.reset').toggle();
						}
						//alert(response);
					}
				});
			}

			function update_wc_order_status_no_email($id, $new_status) {
				var data = {
					'action': 'update_wc_order_status_no_email',
					'order_id': $id,
					'order_status': $new_status
				};

				jQuery.post(ajaxurl, data, function(response) {
					//alert('Got this from the server: ' + response);
					if(response) {
						window.location.reload(false);
					}
				});
			}
		</script>
	<?php
}




add_action( 'woocommerce_admin_order_totals_after_discount', 'onp_add_num_plants_to_order_admin', 10, 1);
function onp_add_num_plants_to_order_admin( $order_id ) {
	$order = wc_get_order( $order_id );
	?>
	<tr class="num-plants">
		<td class="label">Total Plants in Order:</td>
		<td width="1%">
			<script>
				<?php //need to move this <tr> up in the table as there's no way to put it at the top otherwise ?>
				jQuery('tr.num-plants').prependTo('table.wc-order-totals');
			</script>
		</td>
		<td class="total"><?php echo get_num_products_in_order($order); ?></td>
	</tr>
	<?php
}