<?php
define('DATA_DOWNLOAD_CUSTOMER_URL', 'onp-customer-data-download');
define('DATA_DOWNLOAD_CUSTOMER_FIRST_YEAR', 2017);

add_action('pre_get_posts', function ($query){
    global $wp;

    if (!is_admin() && $query->is_main_query()) {
        if($wp->request == 'onp_customer_data_export') {
        	display_customer_data_page(true);
        	exit;
        }
    }
});

function onp_customer_data_download_add_page() {
	add_submenu_page( 'woocommerce', 'ONP Customer Data', 'ONP Data: Customer', 'manage_options', DATA_DOWNLOAD_CUSTOMER_URL, 'display_customer_data_page' );
}
add_action( 'admin_menu', 'onp_customer_data_download_add_page' );

function onp_customer_data_download_add_body_classes($classes) {
    $classes .= ' post-type-shop_order';
    return $classes;
}
add_filter('admin_body_class', 'onp_customer_data_download_add_body_classes');

function display_customer_data_page($export = false) {
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
	$filters['just_got_here'] = 1;

	foreach(array_keys($filters) as $k) {
		if(isset($_GET[$k]) && $_GET[$k] != '') {
			$filters[$k] = $_GET[$k];
		}
	}

	/*if($filters['just_got_here']) {
		ob_start();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline" style="display: block;">Data Download</h1>

			<?php echo customer_filters_form($filters); ?>

			<br style="clear:both;" />
		</div>
		<?php
		echo ob_get_clean();
		return;
	}*/

	$column_headers = get_customer_data_download_column_headers();
	$results = get_customer_data_from_db($limit_low, $results_per_page, $filters);

	if($export) {
		//export_customer_data($results, $summary_totals, $column_headers, $filters);
		return;
	}

	echo display_customer_data_table($results, $column_headers, $filters, $results_per_page, $limit_low);
	?>
	</div>
	<?php
	
	echo ob_get_clean();
	return;
}

function customer_filters_form($filters, $show_download_button = true) {
	ob_start();
	?>
	<form method="GET" action="" style="float: left; margin-bottom: 20px;">
		<input type='hidden' name='just_got_here' value='0'>
		<input type="hidden" name="page" value="<?php echo @$_GET['page']; ?>">
		<input type="submit" value="Apply Filters" class="button reset">
	</form>
	<?php if(!$filters['just_got_here']) { ?>
	<form method="GET" action="" style="float: left; margin-bottom: 20px; margin-left: 5px;">
		<input type='hidden' name='just_got_here' value='0'>
		<input type="hidden" name="page" value="<?php echo $switch_url; ?>">
	</form>
	<?php } ?>

	<?php if($show_download_button) { ?>
	<form method="GET" action="/onp_data_export" style="float:right; margin-bottom: 20px;">
		<input type="hidden" name="page" value="<?php echo @$_GET['page']; ?>">
		<input type="hidden" name="export" value="true">
		<input type='hidden' value='0' name='just_got_here'>
		<input type="submit" value="Download" class="button reset">
	</form>
	<?php
	}
	return ob_get_clean();
}

function export_customer_data($results, $summary_totals, $column_headers, $filters) {
	$page = 'customer';
	header('Content-type: application/ms-excel');
	header('Content-Disposition: attachment; filename=onp_data_'.$page.'_'.$filters['date_start'].'_to_'.$filters['date_end'].'.csv');

	$header_line = array();
	$summary_header_line = array();
	$summary_line = array();
	$lines = array();

	$counter = 0;
	foreach($results as $cur_data) {
		$counter++;
		$data = display_customer_data_row($cur_data, $counter, $filters, $summary_totals, $column_headers, false, true);
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

function get_customer_data_from_db($start_from = 0, $results_per_page = 50, $filters = array()) {
	global $wpdb;

	$query = "
	SELECT DISTINCT meta_value FROM bzAzT_postmeta
    WHERE meta_key = '_customer_user' AND meta_value > 0
    ORDER BY meta_value ASC LIMIT " . $start_from . ", " . $results_per_page . ";
	";
	//admin_dump($query);
	//exit();
	return $wpdb->get_results($query);
}

function get_count_customer_data_from_db($filters = array()) {
	global $wpdb;

	$query = "
	SELECT COUNT(DISTINCT meta_value) AS num FROM bzAzT_postmeta
    WHERE meta_key = '_customer_user' AND meta_value > 0
    ORDER BY meta_value ASC";
	return $wpdb->get_results($query)[0]->num;
}


	$headers = array(
function get_customer_data_download_column_headers() {
		'row_number' => array(
			'name'			=> 'Number',
			'summary'		=> false,
			'money_format'	=> false,
		),
		'customer_name' => array(
			'name'			=> 'Customer Name',
			'summary'		=> false,
			'money_format'	=> false,
		),
	);

	$per_year_orders = array();
	for($i = DATA_DOWNLOAD_CUSTOMER_FIRST_YEAR; $i < date("Y") + 1; $i++) {
		$per_year_orders['per_year_orders_' . $i] = array(
			'name'			=> $i . ' Orders',
			'summary'		=> false,
			'money_format'	=> false,
		);
	}
	$headers = array_merge($headers, $per_year_orders);
	$headers['total_orders'] = array(
		'name'			=> 'Total Orders',
		'summary'		=> false,
		'money_format'	=> false,
	);
	$headers['total_revenue'] = array(
		'name'			=> 'Total Revenue',
		'summary'		=> false,
		'money_format'	=> true,
	);
	$headers['customer_source'] = array(
		'name'			=> 'Customer Source',
		'summary'		=> false,
		'money_format'	=> false,
	);

	return $headers;
}

function display_customer_data_table($results, $column_headers, $filters, $results_per_page, $limit_low) {
	//wp_enqueue_style('woocommerce_admin_styles-css', '/wp-content/plugins/woocommerce/assets/css/admin.css?ver=4.9.0');

	$count_results = get_count_customer_data_from_db($filters);

	ob_start();
	$counter = 0;
	$total_counter = $limit_low;
	foreach($results as $cur_data) {
		$counter++;
		$total_counter++;

		$total_orders = 0;
		$total_revenue = 0;
		echo display_customer_data_row($cur_data, $counter, $total_counter, $filters, $column_headers);
	}
	$order_data = ob_get_clean();

	ob_start();
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline" style="display: block;">Data Download</h1>

		<?php echo customer_filters_form($filters); ?>

		<br style="clear:both;" />
		<h2>Customer Data</h2>

	<?php echo get_customer_pagination($count_results, $results_per_page, @$_GET['pn'], $filters); ?>


		<table class="wp-list-table widefat fixed striped table-view-list posts" style="clear: both;">
			<thead>
				<tr>
					<th colspan="<?php echo count($column_headers); ?>" style="background-color: #f0f0f1; background-image: linear-gradient(to bottom, #f0f0f1, #dadada);
">
						<h4>Customer Data</h4>
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
	<?php echo get_customer_pagination($count_results, $results_per_page, @$_GET['pn'], $filters); ?>

	<?php
	return ob_get_clean();
}


function get_customer_pagination($count_results, $results_per_page, $page_number, $filters) {
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


function display_customer_data_row($cur_data, $counter, $total_counter, $filters, $column_headers, $summary = false, $export = false) {
	//echo $cur_data->meta_value;
	$user_info = get_user_meta($cur_data->meta_value);
	
	$display_vals = array();
	$display_vals['row_number'] = $total_counter;
/*    [wc-pending] => Pending payment
    [wc-processing] => Processing
    [wc-on-hold] => On hold
    [wc-completed] => Completed
    [wc-cancelled] => Cancelled
    [wc-refunded] => Refunded
    [wc-failed] => Failed*/
//	for($i = DATA_DOWNLOAD_CUSTOMER_FIRST_YEAR; $i < date("Y") + 1; $i++) {
	$args = array(
	    'customer_id' => $cur_data->meta_value,
	    'post_status' => array('wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed'),
	    'post_type' => 'shop_order',
	    //'date_created' => $i.'-01-01...'.$i.'-12-31',
	    //'return' => 'objects',
	    //'prices_include_tax' => 'yes',
	);
	$orders = wc_get_orders( $args );

	$total_revenue = 0;
	$total_orders = count($orders);

	$per_year_orders_placed = array();
	$per_year_revenue = array();
	$customer_source = array();

	foreach($orders as $cur_order) {
		$total_revenue = $total_revenue + $cur_order->data['total'];
		$order_year = $cur_order->data['date_created']->date('Y');
		
		$per_year_revenue[$order_year] = $per_year_revenue[$order_year] + $cur_order->data['total'];
		$per_year_orders_placed[$order_year]++;
		foreach($cur_order->meta_data as $cur_meta) {
			$temp_data = $cur_meta->get_data();

			if($temp_data['key'] == 'ONP_WC_Customer_Source_checkout_field') {
				if(!in_array($temp_data['value'], $customer_source)) {
					$customer_source[] = $temp_data['value'];
				}
			}
		}
		
	}

	for($i = DATA_DOWNLOAD_CUSTOMER_FIRST_YEAR; $i < date('Y') + 1; $i++) {
		$display_vals['per_year_orders_' . $i] = ($per_year_orders_placed[$i] ? '<b style="color: green;">' . $per_year_orders_placed[$i] . '</b> ($<span id="per_year_orders_' . $counter . '_' . $i . '">' . number_format($per_year_revenue[$i], 2, '.', ',') . '</span>)' : ''); // count the array of orders
	}

	$display_vals['total_orders'] = $total_orders;
	$display_vals['total_revenue'] = $total_revenue;
	$display_vals['customer_source'] = '<ul><li>' . implode('</li><li>', $customer_source) . '</li></ul>';

	$display_vals['customer_name']	= ($export ? $user_info['first_name'][0] . ' ' . $user_info['last_name'][0] : build_url($user_info['first_name'][0] . ' ' . $user_info['last_name'][0], '/wp-admin/edit.php?_customer_user=' . $cur_data->meta_value . '&post_status=all&post_type=shop_order&action=-1&m=0&paged=1&action2=-1'));
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