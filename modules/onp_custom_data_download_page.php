<?php
define('DATA_DOWNLOAD_COMPLETED_URL', 'onp-data-download');
define('DATA_DOWNLOAD_PROCESSING_URL', 'onp-data-download-processing');

add_action('pre_get_posts', function ($query){
    global $wp;

    if (!is_admin() && $query->is_main_query()) {
        if($wp->request == 'onp_data_export' && current_user_can('administrator')) {
        	display_data_page(true);
        	exit;
        }
    }
});

function onp_data_download_add_page() {
	add_submenu_page( 'woocommerce', 'ONP Data Completed', 'ONP Data: Completed', 'manage_options', DATA_DOWNLOAD_COMPLETED_URL, 'display_data_page' );
	add_submenu_page( 'woocommerce', 'ONP Data Processing', 'ONP Data: Processing', 'manage_options', DATA_DOWNLOAD_PROCESSING_URL, 'display_data_page' );
}
add_action( 'admin_menu', 'onp_data_download_add_page' );

function onp_data_download_add_body_classes($classes) {
    $classes .= ' post-type-shop_order';
    return $classes;
}
add_filter('admin_body_class', 'onp_data_download_add_body_classes');

function display_data_page($export = false) {
	set_time_limit(300);
	//ini_set('memory_limit','16M');
	$date_today = date('Y-m-d');
	global $wpdb;
	$results_per_page = 50;
	$limit_low = 0;
	if(isset($_GET['pn'])) {
		$limit_low = $_GET['pn'] * $results_per_page;
	}
	$limit_high = $limit_low + $results_per_page;

	//set up page default values
	$filters = array();
	$filters['show_completed'] = ($_GET['page'] == DATA_DOWNLOAD_COMPLETED_URL || $_GET['page'] == 'onp_data_export' ? 1 : 0);
	$filters['date_start'] = date('Y-m') . '-01';
	$filters['date_end'] = date('Y-m-t');
	$filters['num_plants'] = 1;
	$filters['regular_or_pickup'] = 1;
	$filters['just_got_here'] = 1;

	foreach(array_keys($filters) as $k) {
		if(isset($_GET[$k]) && $_GET[$k] != '') {
			$filters[$k] = $_GET[$k];
		}
	}

	if($filters['date_end'] > $date_today) {
		$filters['date_end'] = $date_today;
	}

	if($filters['date_start'] > $date_today || date('Y', strtotime($filters['date_start'])) < 2020 || $filters['date_end'] < $filters['date_start']) {
		?>
		<h3>Invalid date range selected.  Try again.</h3>
		<?php
		echo filters_form($filters, false);
		return;
	}

	if($filters['just_got_here']) {
		ob_start();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline" style="display: block;">Data Download</h1>

			<?php echo filters_form($filters); ?>

			<br style="clear:both;" />
		</div>
		<?php
		echo ob_get_clean();
		return;
	}

	$column_headers = get_data_download_column_headers();
	$summary_totals = array(
		'num_plants'	=> 0,
		'subtotal'		=> 0,
		'coupons'		=> 0,
		'shipping_cost'	=> 0,
		'hst'			=> 0,
		'total'			=> 0,
		'num_orders'	=> 0,
	);

	if($filters['show_completed']) {
		$results = get_data_from_db(0, 50, $filters);
		admin_dump($results);

		$list_of_order_ids = array();
		$new_results = array();
		foreach($results as $cur_res) {
			if(!in_array($cur_res->order_id, $list_of_order_ids)) {
				$list_of_order_ids[] = $cur_res->order_id;
				$new_results[] = $cur_res;
			}
		}

		$results = $new_results;

		if($export) {
			export_data($results, $summary_totals, $column_headers, $filters);
			return;
		}

		echo display_data_table($results, $summary_totals, $column_headers, $filters);

	} else {
		$processing_results = get_processing_data_from_db(0, 50, $filters);

		if($export) {
			export_data($processing_results, $summary_totals, $column_headers, $filters);
			return;
		}

		echo display_data_table($processing_results, $summary_totals, $column_headers, $filters);

	}
	?>
	</div>
	<?php
	
	echo ob_get_clean();
	return;
}

function filters_form($filters, $show_download_button = true) {
	ob_start();
	$switch_page = 'processing';
	$switch_url = DATA_DOWNLOAD_PROCESSING_URL;
	if($_GET['page'] == DATA_DOWNLOAD_PROCESSING_URL) {
		$switch_page = 'completed';
		$switch_url = DATA_DOWNLOAD_COMPLETED_URL;
	}

	$regular_or_pickup_options = array(
		'Regular Orders',
		'Pickup Orders',
		'Both',
	);
	?>
	<form method="GET" action="" style="float: left; margin-bottom: 20px;">
		<input type="date" name="date_start" id="date_start_filter" min="2020-01-01" max="<?php echo date('Y-m-d'); ?>" value="<?php echo $filters['date_start']; ?>" />
		<input type="date" name="date_end" id="date_end_filter" min="2020-01-01" max="<?php echo date('Y-m-d'); ?>" value="<?php echo $filters['date_end']; ?>" />
		<select name="regular_or_pickup" style="margin-top: -5px;">
			<?php
			$counter = 1;
			foreach($regular_or_pickup_options as $cur_option) {
				echo '<option value="' . $counter . '"' . ($filters['regular_or_pickup'] == $counter ? ' selected' : '') . '>' . $cur_option . '</option>';
				$counter++;
			}
			?>
		</select>

		<input type='hidden' name='just_got_here' value='0'>
		<input type="hidden" name="page" value="<?php echo @$_GET['page']; ?>">
		<input type="submit" value="Apply Filters" class="button reset">
	</form>
	<?php if(!$filters['just_got_here']) { ?>
	<form method="GET" action="" style="float: left; margin-bottom: 20px; margin-left: 5px;">
		<input type="hidden" name="date_start" value="<?php echo $filters['date_start']; ?>" />
		<input type="hidden" name="date_end" value="<?php echo $filters['date_end']; ?>" />
		<input type='hidden' name='just_got_here' value='0'>
		<input type='hidden' name='regular_or_pickup' value='1'>
		<input type="hidden" name="page" value="<?php echo $switch_url; ?>">
		<input type="submit" value="Switch to <?php echo ucfirst($switch_page); ?> Page" class="button reset">
	</form>
	<?php } ?>

	<?php if($show_download_button) { ?>
	<form method="GET" action="/onp_data_export" style="float:right; margin-bottom: 20px;">
		<input type="hidden" name="page" value="<?php echo @$_GET['page']; ?>">
		<input type="hidden" name="export" value="true">
		<input type='hidden' value='0' name='just_got_here'>
		<input type='hidden' name='regular_or_pickup' value='<?php echo $filters['regular_or_pickup']; ?>'>
		<input type="hidden" name="date_start" value="<?php echo $filters['date_start']; ?>">
		<input type="hidden" name="date_end" value="<?php echo $filters['date_end']; ?>">
		<input type="submit" value="Download" class="button reset">
	</form>
	<?php
	}
	return ob_get_clean();
}

function export_data($results, $summary_totals, $column_headers, $filters) {
	$page = 'completed';
	if($_GET['page'] == DATA_DOWNLOAD_PROCESSING_URL) {
		$page = 'processing';
	}
	header('Content-type: application/ms-excel');
	header('Content-Disposition: attachment; filename=onp_data_'.$page.'_'.$filters['date_start'].'_to_'.$filters['date_end'].'.csv');

	$header_line = array();
	$summary_header_line = array();
	$summary_line = array();
	$lines = array();

	$counter = 0;
	foreach($results as $cur_data) {
		$counter++;
		$data = display_data_row($cur_data, $counter, $filters, $summary_totals, $column_headers, false, true);
		$lines[] = array_values($data);
	}

	foreach($column_headers as $k => $cur_head) {
		if($cur_head['summary']) {
			$summary_header_line[] = $cur_head['name'];
			$str = $summary_totals[$k];
			if($cur_head['money_format']) {
				$str = '$' . number_format($summary_totals[$k], 2, '.', ',');
			}
			$summary_line[] = $str;
		}
		$header_line[] = $cur_head['name'];
	}

	$fp = fopen("php://output", "w");

	fputcsv($fp, $summary_header_line);
	fputcsv($fp, $summary_line);
	fputcsv($fp, $header_line);
	foreach($lines as $cur_line) {
		fputcsv($fp, $cur_line);
	}
	fclose($fp);
	return;
}

function get_data_from_db($start_from = 0, $results_per_page = 50, $filters = array()) {
	global $wpdb;

	$comment_contents = array(
		'from Processing to Completed',
		'from Pending payment to Completed',
	);
	if($filters['regular_or_pickup'] == 2) {
		$comment_contents = array(
			'from Pick-up Scheduled to Completed',
			'from Pick-up Processing to Completed',
			'Pickup completed by admin',
			'Pickup without scheduled pickup timeslot completed by admin',
		);
		//$order_ids = get_order_ids_for_pickup_orders();
	} elseif ($filters['regular_or_pickup'] == 3) {
		//in the case of wanting BOTH regular AND pickup results
		$comment_contents[] = 'from Pick-up Scheduled to Completed';
		$comment_contents[] = 'from Pick-up Processing to Completed';
		$comment_contents[] = 'Pickup completed by admin';
		$comment_contents[] = 'Pickup without scheduled pickup timeslot completed by admin';
	}

	$query = "
	SELECT 
		c.comment_post_ID AS order_id,
		p.post_date
	FROM
		bzAzT_comments c
	LEFT JOIN bzAzT_posts p ON
		c.comment_post_ID = p.ID
	WHERE
		c.comment_content REGEXP '" . implode('|', $comment_contents) . "'
	AND
		CAST(c.comment_date AS DATE) BETWEEN '" . $filters['date_start'] . "' AND '" . $filters['date_end'] . "'
	AND
	    p.post_status IN ('wc-completed')
	ORDER BY
		c.comment_date ASC
	";

	admin_dump($query);
	return $wpdb->get_results($query);
}

function get_processing_data_from_db($start_from = 0, $results_per_page = 50, $filters = array()) {
	global $wpdb;

	$order_status = array('wc-processing');
	if($filters['regular_or_pickup'] == 2) {
		$order_status = array('wc-pickup-processing');
	} elseif($filters['regular_or_pickup'] == 3) {
		$order_status[] = 'wc-pickup-processing';
	}

	$query = "SELECT
	    p.ID AS order_id,
	    p.post_date,
	    p.post_status
	FROM
	    bzAzT_posts p 
	WHERE
	    p.post_type = 'shop_order' AND
	    CAST(p.post_date AS DATE) BETWEEN '" . $filters['date_start'] . "' AND '" . $filters['date_end'] . "' AND
	    p.post_status IN ('" . implode("', '", $order_status) . "')
	GROUP BY
	    p.ID
	ORDER BY
		post_date ASC";
	return $wpdb->get_results($query);
}

function get_data_download_column_headers() {
	return array(
		'order_number' => array(
			'name'			=> 'Order Number',
			'summary'		=> false,
			'money_format'	=> false,
		),
		'customer_name' => array(
			'name'			=> 'Customer Name',
			'summary'		=> false,
			'money_format'	=> false,
		),
		'order_date' => array(
			'name'			=> 'Date',
			'summary'		=> false,
			'money_format'	=> false,
		),
		'pickup_order' => array(
			'name'			=> 'Pickup',
			'summary'		=> false,
			'money_format'	=> false,
		),
		'num_plants' => array(
			'name'			=> 'Plants in Order',
			'summary'		=> true,
			'money_format'	=> false,
		),
		'subtotal' => array(
			'name'			=> 'Subtotal',
			'summary'		=> true,
			'money_format'	=> true,
		),
		'coupons' => array(
			'name'			=> 'Coupons',
			'summary'		=> true,
			'money_format'	=> true,
		),
		'shipping_cost' => array(
			'name'			=> 'Shipping Cost',
			'summary'		=> true,
			'money_format'	=> true,
		),
		'hst' => array(
			'name'			=> 'HST',
			'summary'		=> true,
			'money_format'	=> true,
		),
		'total' => array(
			'name'			=> 'Total',
			'summary'		=> true,
			'money_format'	=> true,
		),
		'num_orders' => array(
			'name'			=> 'Orders',
			'summary'		=> true,
			'money_format'	=> false,
		),
	);
}

function build_url($text, $url, $target = '') {
	return '<a href="' . $url . '" target="' . $target . '">' . $text . '</a>';
}

function display_data_table($results, $summary_totals, $column_headers, $filters) {
	//wp_enqueue_style('woocommerce_admin_styles-css', '/wp-content/plugins/woocommerce/assets/css/admin.css?ver=4.9.0');

	ob_start();
	$counter = 0;
	foreach($results as $cur_data) {
		$counter++;
		echo display_data_row($cur_data, $counter, $filters, $summary_totals, $column_headers);
	}
	$order_data = ob_get_clean();

	ob_start();
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline" style="display: block;">Data Download</h1>

		<?php echo filters_form($filters); ?>

		<br style="clear:both;" />
		<h2><?php echo ($_GET['page'] == DATA_DOWNLOAD_COMPLETED_URL ? 'Completed' : 'Processing'); ?> Orders</h2>

		<table class="wp-list-table widefat fixed striped table-view-list posts" style="clear: both;">
			<thead>
				<tr>
					<th colspan="<?php echo count($column_headers); ?>" style="background-color: #f0f0f1; background-image: linear-gradient(to bottom, #f0f0f1, #dadada);
">
						<h4>Summary</h4>
					</th>
				</tr>
				<tr>
				<?php foreach($column_headers as $k => $cur_head) { ?>
					<th>
						<?php echo ($cur_head['summary'] ? $cur_head['name'] : ''); ?>
					</th>
				<?php } ?>
				</tr>
				<tr>
				<?php foreach($column_headers as $k => $cur_head) { ?>
					<th>
						<?php
						$str = $summary_totals[$k];
						if($cur_head['money_format']) {
							$str = '$' . number_format($summary_totals[$k], 2, '.', ',');
						}
						echo ($cur_head['summary'] ? $str : '');
						?>
					</th>
				<?php } ?>
				</tr>
			</thead>

			<thead>
				<tr>
					<th colspan="<?php echo count($column_headers); ?>" style="background-color: #f0f0f1; background-image: linear-gradient(to bottom, #f0f0f1, #dadada);
">
						<h4>Order Data</h4>
					</th>
				</tr>
				<tr>
					<?php foreach($column_headers as $k => $cur_head) { ?>
							<th scope="col" id="<?php echo $k; ?>" style="vertical-align: top;" class="column-<?php echo $k; ?>">
								<?php echo $cur_head['name']; ?>
							</th>
					<?php } ?>
				</tr>
			</thead>

			<tbody>
			<?php echo $order_data; ?>
			</tbody>

		</table>
	<?php
	return ob_get_clean();
}

function is_order_pickup($order) {
	if($order->get_status() == 'pickup-processing') {
		return true;
	}
	return false;
}

function display_data_row($cur_data, $counter, $filters, &$summary_totals, $column_headers, $summary = false, $export = false) {
	$order = wc_get_order($cur_data->order_id);
	$custom_order_number = get_post_meta($cur_data->order_id, '_alg_wc_custom_order_number', true);
	$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

	$subtotal = $order->total - $order->total_tax - $order->shipping_total;
	if($subtotal == 0 && $order->discount_total > 0) {
		$subtotal = $order->discount_total;
	}

	$display_vals = array(
		'order_number'	=> $custom_order_number,
		'customer_name'	=> ($export ? $customer_name : build_url($customer_name, '/wp-admin/post.php?post=' . $cur_data->order_id . '&action=edit')),
		'order_date'	=> date('Y-m-d', strtotime($cur_data->post_date)),
		'pickup_order'	=> (is_order_pickup($order) ? '<b class="checkmark" style="padding-right: 10px;">&#10004;</b>' : ''),
		'num_plants'	=> get_num_products_in_order($order),
		'subtotal'		=> $subtotal,
		'coupons'		=> $order->discount_total,
		'shipping_cost' => $order->shipping_total,
		'hst'			=> $order->total_tax,
		'total'			=> $order->total,
		'num_orders'	=> $counter,
	);


	/* Add this all up for the summary section later.  $summary_totals is passed by reference */
	$summary_totals['num_plants']		= $summary_totals['num_plants']		+ $display_vals['num_plants'];
	$summary_totals['total']			= $summary_totals['total']			+ $order->total;
	$summary_totals['subtotal']			= $summary_totals['subtotal']		+ $subtotal;
	$summary_totals['hst']				= $summary_totals['hst']			+ $order->total_tax;
	$summary_totals['shipping_cost']	= $summary_totals['shipping_cost']	+ $order->shipping_total;
	$summary_totals['coupons']			= $summary_totals['coupons']		+ $order->discount_total;
	$summary_totals['num_orders']		= $summary_totals['num_orders']		+ 1;

	if($export) {
		return $display_vals;
	}

	ob_start();
	echo '<tr>';
		foreach($column_headers as $k => $cur_head) {
			echo '<td>';
			$str = $display_vals[$k];
			if($cur_head['money_format']) {
				$str = '$' . number_format($display_vals[$k], 2, '.', ',');
			}
			echo ($summary ? '' : $str);
			echo '</td>';
		}
		echo '</tr>';
	return ob_get_clean();
}