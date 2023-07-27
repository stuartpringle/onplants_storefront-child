<?php

define('DEBUG', FALSE);

add_action('pre_get_posts', function ($query){
    global $wp;

    if (!is_admin() && $query->is_main_query()) {
        if ($wp->request == 'show_packing_slip'){
        	display_packing_slip($_GET['id']);
            exit;
        } elseif($wp->request == 'add_onp_db') {
        	echo onp_install();
        	echo 'Success!';
        }
    }
});


function onp_order_processing_add_page() {
	add_submenu_page( 'woocommerce', 'ONP Order Processing', 'ONP Order Processing', 'manage_options', 'onp-order-processing', 'display_orders' );
}
add_action( 'admin_menu', 'onp_order_processing_add_page' );

function add_body_classes($classes) {
    $classes .= ' post-type-shop_order';
    return $classes;
}
add_filter('admin_body_class', 'add_body_classes');



function display_orders() {
	global $wpdb;
	$results_per_page = 50;
	$limit_low = 0;
	if(isset($_GET['pn'])) {
		$limit_low = $_GET['pn'] * $results_per_page;
	}
	$limit_high = $limit_low + $results_per_page;
	wp_enqueue_style('woocommerce_admin_styles-css', '/wp-content/plugins/woocommerce/assets/css/admin.css?ver=4.9.0');
	wp_enqueue_style('woocommerce_admin_styles-css', '/wp-content/plugins/woocommerce/assets/css/admin.css?ver=4.9.0');

	$filters = get_current_filters();
	$url = add_query_arg(array('pn' => $_GET['pn']));


	//for pagination
	$count_query = "SELECT
	    COUNT(*)
	FROM
	    bzAzT_posts p
	WHERE
	    p.post_type = 'shop_order' AND
	    p.post_date BETWEEN '".$filters['display_year']."-01-01' AND '".$filters['display_year']."-12-31' AND
	    p.post_status NOT IN ('wc-cancelled', 'wc-refunded', 'trash', 'wc-completed', 'wc-pending', 'wc-failed', 'wp-pending', '".($filters['show_pickup'] ? 'wc-processing' : 'wc-pickup-processing') ."')
	ORDER BY
		post_date DESC";


	$count_results = $wpdb->get_results($count_query, 'ARRAY_N');
	$results = get_orders_from_db($limit_low, $results_per_page, $filters);
	ob_start();
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">Order Processing</h1>
		<?php

		global $wpdb;
		$temp = new onp_order_status();
		$table_name = $temp->table_name;
		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
		if ( ! $wpdb->get_var( $query ) == $table_name ) { ?>
			<a href="/add_onp_db" class="button">Create database table for ONP Order Processing page</a>
		<?php } ?>

		<hr class="wp-header-end">

		<form method="GET" action="">
			<table style="padding-bottom: 7px;">
				<tr>
					<th style="padding: 0px 20px;">Number of Plants</th>
					<th style="padding: 0px 20px;">Second Order</th>
					<th style="padding: 0px 20px;">Tree and Shrub Only</th>
					<th style="padding: 0px 20px;">Order ID Search</th>
					<th style="padding: 0px 20px;">Year</th>
					<th style="padding: 0px 20px;">Order Type</th>
				</tr>
				<tr>
					<?php foreach($filters as $k => $v) {
						if($k == 'order_id') { ?>
						<td style="padding: 0px 20px;">
							<input type="text" id="<?php echo $k; ?>" name="<?php echo $k; ?>" <?php if($v) { echo 'value="'.$v.'"'; } ?>>
						</td>
						<?php } elseif($k == 'display_year') { ?>
						<td style="padding: 0px 20px;">
							<select name="display_year">
								<?php foreach(range(2021, date('Y')) as $cur_year) { ?>
									<option value="<?php echo $cur_year; ?>" <?php echo ($cur_year == $filters['display_year'] ? 'selected="selected"' : ''); ?>><?php echo $cur_year; ?></option>
								<?php } ?>
							</select>
						</td>
						<?php } elseif($k == 'show_pickup') { ?>
						<td style="padding: 0px 20px;">
							<select name="show_pickup">
								<option value="0" <?php echo ($filters['show_pickup'] == 0 ? 'selected="selected"' : ''); ?>>Normal Orders</option>
								<option value="1" <?php echo ($filters['show_pickup'] ? 'selected="selected"' : ''); ?>>Pick-up Orders</option>
							</select>
						</td>
						<?php } else { ?>
						<td style="padding: 0px 20px;">
							<input type="checkbox" id="<?php echo $k; ?>" name="<?php echo $k; ?>" value="1" <?php if($v) { echo 'checked'; } ?>>
						</td>
						<?php }
					}
					?>
					<td style="padding: 0px 20px;">
						<input type="submit" value="Apply Filters" class="button reset">
					</td>
				</tr>
			</table>
			<input type="hidden" name="pn" value="<?php echo (isset($_GET['pn']) ? $_GET['pn'] : 0); ?>">
			<input type="hidden" name="page" value="<?php echo @$_GET['page']; ?>">
		</form>

		<?php echo get_pagination($count_results[0][0], $results_per_page, $_GET['pn'], $filters); ?>
		<table class="wp-list-table widefat fixed striped table-view-list posts">
			<thead>
				<tr>
<?php /*
					<td id="cb" class="manage-column column-cb check-column">
						<label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox">
					</td>
*/ ?>
					<th style="vertical-align: top;">
						Reset to Processing
					</th>
					<th scope="col" id="order_number" style="vertical-align: top;" class="manage-column column-order_number column-primary">
						<!-- <a href="http://onplants.iris-development.com/wp-admin/edit.php?post_type=shop_order&amp;orderby=ID&amp;order=asc"> sortable desc-->
						<span>Order</span><span class="sorting-indicator"></span>
					</th>
					<?php if($filters['num_plants'] > 0) { ?>
					<th scope="col" id="number_plants" style="vertical-align: top;" class="manage-column column-number_plants">
						Plants in Order
					</th>
					<?php } ?>
					<th scope="col" id="print_label" style="vertical-align: top;" class="manage-column column-print_label">
						Print Label
					</th>
					<th scope="col" id="order_date" style="vertical-align: top;" class="manage-column column-order_date">
						<span>Date</span><span class="sorting-indicator"></span>
					</th>
					<th scope="col" id="order_status" style="vertical-align: top;" class="manage-column column-order_status">
						Status
					</th>
					<?php if($filters['second_order'] > 0) { ?>
					<th scope="col" id="second_order" style="vertical-align: top;" class="manage-column column-second_order">
						Second Order
					</th>
					<?php }	if($filters['tree_shrub'] > 0) { ?>
					<th scope="col" id="tree_and_shrub_only" style="vertical-align: top;" class="manage-column column-tree_and_shrub_only">
						Tree and Shrub Only
					</th>
					<?php } ?>
					<th scope="col" id="order_total" style="vertical-align: top;" class="manage-column column-order_total">
						<span>Total</span><span class="sorting-indicator"></span>
					</th>
				</tr>
			</thead>

			<?php 
			$counter = 0;
			foreach($results as $cur_order) {
				$counter++;
				echo display_order_row($cur_order, $counter, $filters);
			} ?>
		</table>
	</div>
	<?php echo get_pagination($count_results[0][0], $results_per_page, $_GET['pn'], $filters); ?>

	<script type="text/javascript" >
		function update_onp_order_status($id, $new_status) {
			var data = {
				'action': 'update_order_status',
				'order_id': $id,
				'order_status': $new_status
			};
			jQuery.post(ajaxurl, data, function(response) {
				//alert('Got this from the server: ' + response);
				if(response) {
					if(response == 'completed') {
						jQuery('tr#post-' + $id).hide();
					} else if(response == 'reset') {
						remove_status_classes($id)
						jQuery('tr#post-' + $id + ' td.order_status span').html('Processing');
						jQuery('tr#post-' + $id + ' td.order_status span').css('textTransform', 'capitalize');
						jQuery('tr#post-' + $id + ' td.order_status mark').addClass('status-processing');
						jQuery('tr#post-' + $id + ' .pdf-packing-slip').hide();
						jQuery('tr#post-' + $id + ' .set-order-completed').hide();
						jQuery('tr#post-' + $id + ' .make-to-print').show();
					} else {
						jQuery('tr#post-' + $id + ' td.order_status span').html(response);
						jQuery('tr#post-' + $id + ' td.order_status span').css('textTransform', 'capitalize');
						remove_status_classes($id);
						var $class = 'processing';
						var $show = '.make-to-print';
						if(response == 'to-print') {
							$class = 'on-hold';
							$show = '.pdf-packing-slip';
						} else if(response == 'printed') {
							$class = 'cancelled';
							$show = '.set-order-completed'
						}
						jQuery('tr#post-' + $id + ' td.order_status mark').addClass('status-' + $class);
						jQuery('tr#post-' + $id + ' .make-to-print').hide();
						jQuery('tr#post-' + $id + ' .pdf-packing-slip').hide();
						jQuery('tr#post-' + $id + ' .set-order-completed').hide();
						jQuery('tr#post-' + $id + ' ' + $show).toggle();
					}
				}
			});
		}
		function remove_status_classes($id) {
			jQuery('tr#post-' + $id + ' td.order_status mark').removeClass('status-on-hold');
			jQuery('tr#post-' + $id + ' td.order_status mark').removeClass('status-completed');
			jQuery('tr#post-' + $id + ' td.order_status mark').removeClass('status-failed');
			jQuery('tr#post-' + $id + ' td.order_status mark').removeClass('status-processing');
		}

		function get_new_row($id, $old_id) {
			var data = {
				'action': 'get_new_row_for_order_page',
				<?php foreach($filters as $k => $v) {
					if($k != 'order_id') {
						echo "'" . $k . "': '" . $v . "',";
					}
				} ?>
				'order_id': $id
			};
			if(!jQuery('tr#post-' + $id).html()) {
				add_new_row(data, $old_id);
			} else {
				jQuery('tr#post-' + $id).insertAfter('tr#post-' + $old_id);
				jQuery('tr#post-' + $id).css('background-color', '#cccccc');
			}
			jQuery('#second-order-load-' + $old_id).hide();
		}

		function add_new_row(data, $old_id) {
			jQuery.post(ajaxurl, data, function(response) {
				<?php if(DEBUG) { ?> console.log(response); <?php } ?>
				jQuery(response).insertAfter('tr#post-' + $old_id);
			});
		}
	</script>
	<?php

	echo ob_get_clean();
	return;
}

function get_current_filters() {
	return $filters = array(
		'num_plants' => (isset($_GET['num_plants']) ? $_GET['num_plants'] : 0),
		'second_order' => (isset($_GET['second_order']) ? $_GET['second_order'] : 0),
		'tree_shrub' => (isset($_GET['tree_shrub']) ? $_GET['tree_shrub'] : 0),
		'order_id' => (isset($_GET['order_id']) ? $_GET['order_id'] : 0),
		'display_year' => (isset($_GET['display_year']) ? $_GET['display_year'] : date('Y')),
		'show_pickup' => (isset($_GET['show_pickup']) ? $_GET['show_pickup'] : 0),
	);
}

function get_orders_from_db($start_from = 0, $results_per_page = 50, $filters = array()) {
	global $wpdb;

	$wc_order_id = ((int)$filters['wc_order_id'] > 0 ? $filters['wc_order_id'] : null);
	if($filters['order_id'] > 0) {
		$start_from = 0;
		$wc_order_id = get_wc_order_id_by_new_order_id($filters['order_id'], $filters);
	}

	$query = "SELECT
	    p.ID AS order_id,
	    p.post_date,
	    p.post_status,
	    max( CASE WHEN pm.meta_key = '_billing_first_name' AND p.ID = pm.post_id THEN pm.meta_value END ) AS _billing_first_name,
	    max( CASE WHEN pm.meta_key = '_billing_last_name' AND p.ID = pm.post_id THEN pm.meta_value END ) AS _billing_last_name,
	    max( CASE WHEN pm.meta_key = '_customer_user' AND p.ID = pm.post_id THEN pm.meta_value END ) AS _customer_user,
	    max( CASE WHEN pm.meta_key = '_alg_wc_custom_order_number' AND p.ID = pm.post_id THEN pm.meta_value END ) AS wc_custom_order_number,
	    max( CASE WHEN pm.meta_key = 'second_order' AND p.ID = pm.post_id THEN pm.meta_value END ) AS second_order,
	    max( CASE WHEN pm.meta_key = '_order_total' AND p.ID = pm.post_id THEN pm.meta_value END ) AS order_total,
	    ( SELECT group_concat( order_item_id separator '|' ) FROM bzAzT_woocommerce_order_items WHERE order_id = p.ID ) AS order_items
	FROM
	    bzAzT_posts p 
	    JOIN bzAzT_postmeta pm ON p.ID = pm.post_id
	    JOIN bzAzT_woocommerce_order_items oi ON p.ID = oi.order_id
	WHERE
	    p.post_type = 'shop_order' AND
	    p.post_date BETWEEN '".$filters['display_year']."-01-01' AND '".$filters['display_year']."-12-31' AND
	    p.post_status NOT IN ('wc-cancelled', 'wc-refunded', 'trash', 'wc-completed', 'wc-failed', 'wp-pending', '" .($filters['show_pickup'] ? 'wc-processing' : 'wc-pickup-processing') . "')"
	    . ($wc_order_id != null ? " AND p.ID = '" . $wc_order_id . "'" : '') .
	    "
	GROUP BY
	    p.ID
	ORDER BY
		post_date DESC LIMIT " . $start_from . ", " . $results_per_page;

	return $wpdb->get_results($query);
}

function get_wc_order_id_by_new_order_id(?int $order_id, $filters = array()) {
	if((int)$order_id < 1) {
		return null;
	}

	global $wpdb;
	$query = "SELECT
	    p.ID AS order_id,
	    ( CASE WHEN pm.meta_key = '_alg_wc_custom_order_number' AND p.ID = pm.post_id THEN pm.meta_value END ) AS wc_custom_order_number
	FROM
	    bzAzT_posts p 
	    JOIN bzAzT_postmeta pm ON p.ID = pm.post_id
	WHERE
	    post_type = 'shop_order' AND
	    post_date BETWEEN '".$filters['display_year']."-01-01' AND '".$filters['display_year']."-12-31' AND
	    post_status NOT IN ('wc-cancelled', 'wc-refunded', 'trash', 'wc-completed', 'wc-failed', 'wp-pending')"
	    . ($order_id != null ? " AND ( CASE WHEN pm.meta_key = '_alg_wc_custom_order_number' AND p.ID = pm.post_id THEN pm.meta_value END ) = '" . $order_id . "'" : '') .
	    "
	ORDER BY
		post_date DESC";

	$results = $wpdb->get_results($query);
	return ((int)$results[0]->order_id > 0 ? $results[0]->order_id : null);
}

function display_order_row($cur_order, $counter, $filters) {
	ob_start();
	$custom_order_status = new onp_order_status($cur_order->order_id);
	if(isset($custom_order_status->order_status)) {
		$order_status = $custom_order_status->order_status;
		$style_status = 'on-hold';
		if($order_status == 'printed') {
			$style_status = 'wc-cancelled';
		}
	} else {
		$order_status = $cur_order->post_status;
		if($order_status == 'wc-processing') {
			$order_status = 'processing';
		} elseif($order_status == 'wc-completed') {
			$order_status = 'completed';
		}
		$style_status = $order_status;
	}
	?>
	<tr id="post-<?php echo $cur_order->order_id; ?>" class="iedit author-other level-0 post-<?php echo $cur_order->order_id; ?> type-shop_order status-wc-processing post-password-required hentry">
	<?php /*
		<th scope="row" class="check-column">
			<label class="screen-reader-text" for="cb-select-112611">Select Order – March 11, 2021 @ 11:03 PM			</label>
			<input id="cb-select-112611" type="checkbox" name="post[]" value="112611">
		</th>
	*/ ?>
		<td>
			<?php if(DEBUG) { echo $counter . ' '; } ?>
			<span class="button reset" onClick="update_onp_order_status(<?php echo $cur_order->order_id; ?>, 'reset');">
				Reset
			</span>

		</td>
		<td class="order_number column-order_number has-row-actions column-primary" data-colname="Order">
			<a href="/wp-admin/post.php?post=<?php echo $cur_order->order_id; ?>&action=edit">
			<?php
			echo $cur_order->wc_custom_order_number . ' ' 
			. $cur_order->_billing_first_name . ' ' 
			. $cur_order->_billing_last_name;
			?>
			</a>
		</td>
		<?php if($filters['num_plants'] > 0) { ?>
		<td class="number_plants column-number_plants column-primary" data-colname="number_plants">
			<?php
			$order = wc_get_order( $cur_order->order_id );
			echo get_num_products_in_order($order);
			?>
		</td>
		<?php } ?>
		<td>
			<?php
			$to_print_display = 'none';
			$pdf_packing_display = 'none';
			$complete_order_display = 'none';
			if($order_status == 'processing') {
				$pdf_packing_display = 'inline-block';
			} elseif($order_status == 'to-print') {
				$to_print_display = 'inline-block';
			} elseif($order_status == 'printed') {
				$complete_order_display = 'inline-block';
			}
			?>
			<a href="/show_packing_slip?id=<?php echo $cur_order->order_id; ?>" class="button pdf-packing-slip" style="display: <?php echo $to_print_display; ?>;" target="blank" onClick="update_onp_order_status(<?php echo $cur_order->order_id; ?>, 'printed');">
				Packing Slip
			</a>
			
			<span class="button make-to-print" onClick="update_onp_order_status(<?php echo $cur_order->order_id; ?>, 'to-print');" style="display: <?php echo $pdf_packing_display; ?>;">
				To Print
			</span>
			
			<span class="button set-order-completed" onClick="update_onp_order_status(<?php echo $cur_order->order_id; ?>, 'completed');" style="display: <?php echo $complete_order_display; ?>;">
				Order Completed
			</span>
<!--
			<span class="button set-order-completed" onClick="update_onp_order_status(<?php echo $cur_order->order_id; ?>, 'completed-no-email');" style="display: none<?php // TURNED OFF AND NOT WORKING echo $complete_order_display; ?>;">
				Order Completed (NO EMAIL)
			</span>
-->
		</td>
		<td class="order_date column-order_date" data-colname="Date">
			<time datetime="<?php echo $cur_order->post_date; ?>" title="<?php echo $cur_order->post_date; ?>"><?php echo get_time_ago(strtotime($cur_order->post_date));
	/* DATE AND TIME
			$test = explode(' ', get_time_ago(strtotime($cur_order->post_date)));
			if($test[0] < 0) {
				$test[0] = 'A few';
			}
			echo implode(' ', $test);
			*/
			?>
			</time>
		</td>
		<td class="order_status column-order_status" data-colname="Status">
			<mark class="order-status status-<?php echo $style_status; ?> tips"><span><?php echo ucfirst($order_status); ?></span></mark>
		</td>
		<?php if($filters['second_order'] > 0) { ?>
		<td class="second_order column-second_order" data-colname="Second Order">
			<p>
			<?php
			if($cur_order->second_order) {
				echo '<b class="checkmark" style="padding-right: 10px;">&#10004;</b>';
				$second_order = get_second_order_by_customer_id($cur_order->_customer_user, $cur_order->order_id, $filters);
				if(isset($second_order[0]->order_id)) {
//					echo ' <a href="/wp-admin/post.php?post='.$second_order[0]->order_id.'&action=edit">'.$second_order[0]->order_id.'</a>';
					?>
					<span id="second-order-load-<?php echo $cur_order->order_id; ?>" class="button" onclick="get_new_row(<?php echo $second_order[0]->order_id . ', ' . $cur_order->order_id; ?>)">Load Order</span>
					<?php
				} else {
					echo ' <a class="button" href="http://onplants.iris-development.com/wp-admin/edit.php?s='.$cur_order->_billing_first_name . '+' 
			. $cur_order->_billing_last_name.'&post_status=all&post_type=shop_order" target="_BLANK">Search</a>';
				}
			}
			?>
			</p>
		</td>
		<?php }
		if($filters['tree_shrub'] > 0) { ?>
		<td>
			<?php echo (tree_shrub_only($cur_order->order_items) ? '<b class="checkmark">&#10004;</b>' : ''); ?>
		</td>
		<?php } ?>
		<td class="order_total column-order_total" data-colname="Total">
			<span class="tips"><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">$</span><?php echo $cur_order->order_total; ?></span></span>
		</td>
	</tr>
	<?php
	return ob_get_clean();
}

function get_url_with_filters($filters = array(), $page_number = 0) {
	$url_arr = array();
	foreach($filters as $k => $v) {
		if($v > 0) {
			$url_arr[$k] = $v;
		}
	}
	$url_arr['pn'] = $page_number;
	return $url = add_query_arg($url_arr);
}

function get_pagination($count_results, $results_per_page, $page_number, $filters) {
	ob_start();
	?>
	<div class="">
		Page:
		<?php
		for($i = 0; $i < ($count_results / $results_per_page); $i++) {
			$url = get_url_with_filters($filters, $i);
			echo ($page_number == $i ? '<b style="font-size: 16px;">' : '') . '<a href="' . $url . '">'. ($i + 1) . '</a>' . ($page_number == $i ? '</b>' : '') . ' ';
		}
		?>
	</div>
	<?php
	return ob_get_clean();
}

add_action( 'wp_ajax_get_new_row_for_order_page', 'get_new_row_for_order_page' );
function get_new_row_for_order_page() {
	if(!isset($_POST['order_id'])) {
		echo 'Invalid order ID';
		wp_die();
	}
	$filters = array(
		'num_plants' => (isset($_POST['num_plants']) ? $_POST['num_plants'] : 0),
		'second_order' => (isset($_POST['second_order']) ? $_POST['second_order'] : 0),
		'tree_shrub' => (isset($_POST['tree_shrub']) ? $_POST['tree_shrub'] : 0),
		'wc_order_id' => $_POST['order_id'],
	);

	$results = get_orders_from_db(0, 1, $filters);
	foreach($results as $cur_order) {
		$temp = display_order_row($cur_order, 1, $filters);
		$text = explode('<tr', $temp);
		$text[0] = $text[0] . '<tr style="background-color: #cccccc;"';
		$return_text = implode('', $text);
		echo $return_text;
	}
	wp_die();
}

add_action( 'wp_ajax_update_order_status', 'update_order_status' );
function update_order_status() {
	if(!isset($_POST['order_id'])) {
		echo 'Invalid order ID';
		wp_die();
	}

	$order_status = new onp_order_status($_POST['order_id']);

	if($_POST['order_status'] == 'reset') {
		$order_status->remove_order_status();
		echo $_POST['order_status'];
    	wp_die();
	}

	if($_POST['order_status'] == 'completed') {
		$order_status->remove_order_status();
		$order = wc_get_order($_POST['order_id']);
    	$order->update_status('completed');
    	echo $order->get_status();
    	wp_die();
	}

/*
	if($_POST['order_status'] == 'completed-no-email') {
		$order_status->remove_order_status();
		$order = wc_get_order($_POST['order_id']);
    	$order->update_status('completed', 'Order status set to complete via ONP Order Processing page - no email sent.', true);
    	echo $order->get_status();
    	wp_die();
	}*/

	$order_status->set_order_status($_POST['order_status']);
	echo $order_status->get_order_status();
	wp_die();
}

add_action( 'wp_ajax_update_wc_order_status_no_email', 'update_wc_order_status_no_email' );
function update_wc_order_status_no_email() {
	if(!isset($_POST['order_id'])) {
		echo 0;
		wp_die();
	}
	$order_id = (int)$_POST['order_id'];

	$status = '';
	if($_POST['order_status'] == 'hold') {
		$status = 'wc-on-hold';
	} elseif($_POST['order_status'] == 'processing') {
		$status = 'wc-processing';
	} elseif($_POST['order_status'] == 'completed') {
		$status = 'wc-completed';
	} else {
		echo 0;
		wp_die();
	}

	global $wpdb;

	$table = 'bzAzT_posts';
	$data = [ 'post_status' => $status ];
	$where = [ 'id' => $order_id ];

	$res = $wpdb->update( $table, $data, $where );
	echo $res;
	wp_die();
}


function display_packing_slip($order_id) {
	header('Content-type: application/pdf');
	$packing_slip = wcpdf_get_packing_slip( $order_id, true );
	echo $packing_slip->get_pdf();
}



function tree_shrub_only($order_items) {
	global $wpdb;

	$order_items = explode('|', $order_items);
	$tree_shrub_only = true;
	$sql_order_items = "'" . implode("', '", $order_items) . "'";

	$query = "SELECT t.term_taxonomy_id as cat,
	i.order_item_id as order_item 
	FROM 
		bzAzT_term_relationships t
		INNER JOIN bzAzT_woocommerce_order_itemmeta i 	ON t.object_id 			= i.meta_value
		INNER JOIN bzAzT_term_taxonomy tax 				ON t.term_taxonomy_id 	= tax.term_taxonomy_id
	WHERE
		i.meta_key = '_product_id' AND
		tax.taxonomy = 'product_cat' AND
    	i.order_item_id IN (" . $sql_order_items . ")";

	$tree_shrub_cat_ids = array(
		121,
		123,
	);

	$results = $wpdb->get_results($query);

	//group items by product
	$order_items = array();
	foreach($results as $cur_order_item) {
		$order_items[$cur_order_item->order_item][] = $cur_order_item->cat;
	}
//admin_dump($order_items);
	foreach($order_items as $item_id => $product_details) {
		$flag = false;
		foreach($tree_shrub_cat_ids as $cur_id) {
			if(in_array($cur_id, $product_details)) {
				$flag = true;
			}
		}
		if($flag == false) {
			$tree_shrub_only = false;
		}
	}
	return $tree_shrub_only;
}


function get_time_ago($time) {
	$periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
	$lengths = array("60","60","24","7","4.35","12","10");

	$now = current_time('timestamp');

	$difference     = $now - $time;
	$tense         = "ago";

	for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
		$difference /= $lengths[$j];
	}

	$difference = round($difference);

	if($difference != 1) {
	$periods[$j].= "s";
	}

	return "$difference $periods[$j] ago";
}


function get_second_order_by_customer_id($customer_id, $first_order_id, $filters = array()) {
	global $wpdb;
	$query = "SELECT
	    p.ID AS order_id,
	    p.post_date,
	    p.post_status,
	    ( CASE WHEN pm.meta_key = '_customer_user' AND p.ID = pm.post_id THEN pm.meta_value END ) AS _customer_user
	FROM
	    bzAzT_posts p 
	    JOIN bzAzT_postmeta pm ON p.ID = pm.post_id
	WHERE
	    post_type = 'shop_order' AND
	    post_date BETWEEN '".$filters['display_year']."-01-01' AND '".$filters['display_year']."-12-31' AND
	    p.ID < ".$first_order_id." AND
	    post_status NOT IN ('wc-cancelled', 'wc-refunded', 'trash', 'wc-completed') AND
	    CASE WHEN
	    	pm.meta_key 	= '_customer_user' AND
	    	p.ID 			= pm.post_id AND
	    	pm.meta_value 	= ".$customer_id." THEN true
	    END
	GROUP BY
	    p.ID
	ORDER BY
		post_date DESC LIMIT 1";

	return $wpdb->get_results($query);
}



//onp_install();
function onp_install() {
	global $wpdb;
	global $onp_db_version;
	$onp_db_version = '1.0';

	$temp = new onp_order_status();
	$table_name = $temp->table_name;
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		order_status text NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'onp_db_version', $onp_db_version );
}


class onp_order_status {
	public $table_name;
	public $order_id;
	public $order_status;

	function __construct($order_id = 0) {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'onp_order_status';
		$this->order_id = $order_id;
		if($order_id == 0) {
			return;
		}
		$this->order_status = $this->get_order_status_by_id($this->order_id);
		return $this->order_status;
	}

	public function get_order_status() {
		global $wpdb;
		$query = 'SELECT order_status FROM ' . $this->table_name . ' WHERE id="' . $this->order_id . '" LIMIT 1';
		$this->order_status = $wpdb->get_results($query)[0]->order_status;
		return $this->order_status;
	}

	public function get_order_status_by_id($id) {
		global $wpdb;
		$query = 'SELECT order_status FROM ' . $this->table_name . ' WHERE id="' . $id . '" LIMIT 1';
		return @$wpdb->get_results($query)[0]->order_status;
	}

	public function remove_order_status() {
		global $wpdb;
		$wpdb->delete($this->table_name, array('id' => $this->order_id));
	}

	public function set_order_status($status) {
		global $wpdb;
		if(!isset($this->order_id)) {
			return false;
		}
		if($this->get_order_status_by_id($this->order_id) != '') {
			$wpdb->update($this->table_name, array('order_status' => $status), array('id' => $this->order_id));
		} else {
			$data = array('id' => $this->order_id, 'order_status' => $status);
			$wpdb->insert($this->table_name, $data);
		}
	}
}