<?php
define('PRODUCT_DATA_DOWNLOAD_URL', 'onp-product-data-download');

add_action('pre_get_posts', function ($query){
	//for autocomplete used in ONP custom product data download page
	wp_enqueue_script('autocomplete-search', get_stylesheet_directory_uri() . '/js/autocomplete.js',
			['jquery', 'jquery-ui-autocomplete'], null, true);
	wp_localize_script('autocomplete-search', 'AutocompleteSearch', [
		'ajax_url' => admin_url('admin-ajax.php'),
		'ajax_nonce' => wp_create_nonce('autocompleteSearchNonce')
	]);
	//end autocomplete

    global $wp;

    if (!is_admin() && $query->is_main_query()) {
        if($wp->request == 'onp_product_data_export' && current_user_can('administrator')) {
        	display_product_data_page(true);
        	exit;
        }
    }
});

function onp_product_data_download_add_page() {
	add_submenu_page( 'woocommerce', 'ONP Product Data', 'ONP Data: Product', 'manage_options', PRODUCT_DATA_DOWNLOAD_URL, 'display_product_data_page' );
}
add_action( 'admin_menu', 'onp_product_data_download_add_page' );

function onp_product_data_download_add_body_classes($classes) {
    $classes .= ' post-type-shop_order';
    return $classes;
}
add_filter('admin_body_class', 'onp_product_data_download_add_body_classes');

function display_product_data_page($export = false) {
	set_time_limit(300);
	//ini_set('memory_limit','16M');
	$date_today = date('Y-m-d');
	global $wpdb;

	$filters = array();
	$filters['statuses'] = array('wc-completed');
	$filters['product'] = @$_GET['product'];
	$filters['year'] = @$_GET['year'];
	$product_name = '';

	if(is_numeric($filters['product'])) {
		$product = wc_get_product( $filters['product'] );
		$product_name = $product->get_name();
	}

	echo product_filters_form($filters, false, $product_name);

	ob_start();

	if(!is_numeric($filters['product'])) {
		return;
	}

	$product = wc_get_product( $filters['product'] );
	$product_name = $product->get_name();

	$total_money_from_sales = 0;
	$quantity_bought = 0;
	$order_dates = array();

	if(isset($filters['product']) && $filters['product'] != '') {
		$order_ids = get_order_ids_by_product_id( $filters);

		foreach ( $order_ids as $order_id ) {
		    // Get an instance of the WC_Order object
		    $order = wc_get_order( $order_id );
		    if ( is_a( $order, 'WC_Order' ) ) {
		    	foreach($order->get_items() as $item_id => $item) {
		    		if($item->get_product_id() == $filters['product']) {
		    			$quantity_bought = $quantity_bought + $item->get_quantity();
		    			$total_money_from_sales = $total_money_from_sales + $item->get_subtotal();

						$date_completed = $order->get_date_completed();
						if( ! empty( $date_completed) ){
							$order_dates[$date_completed->date("Y")][$date_completed->date("n")][$order_id] = array(
								'customer_name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
								'order_total' => $order->get_total(),
								'product_total' => $item->get_subtotal(),
								'quantity' => $item->get_quantity(),
							);
/*$args = array('post_type' => 'shop_order');
$post_obj = new WP_Query($args);
while($post_obj->have_posts() ) : $post_obj->the_post();
    //display comments
    $comments = get_comments(array(
        'post_id' => $post->ID,
        'number' => '2' ));
    foreach($comments as $comment) {
        print_r($comment);
    }
endwhile;*/
							//print_r($order->get_customer_note());
						}
		    			//echo $order->get_date_completed() . '<br />';
		    		}
		    	}
		        //echo $order->get_status() . '<br>'; // The order status
		    }
		}
	}

	//admin_dump($order_dates);

	$months = array(
		'January',
		'February',
		'March',
		'April',
		'May',
		'June',
		'July',
		'August',
		'September',
		'October',
		'November',
		'December',
	);
	?>
	<h1><a href="/wp-admin/post.php?post=<?php echo $filters['product']; ?>&action=edit" target="_blank"><?php echo $product_name; ?></a></h1>
	<table cellspacing="5" cellpadding="5">
	<?php
	foreach($order_dates as $year => $data) {
		?>
		<th><h2>Year: <?php echo $year; ?></h2></th>
		<tbody style="font-size: 14px;">
			<tr>
			<?php
			for($i = 1; $i < 12; $i++) {
				echo '<th style="font-size: 14pt;">' . $months[$i - 1] . '</th>';
			}
			echo '</tr>';
			echo '<tr>';
			for($i = 1; $i < 12; $i++) {
				echo '<td>' . (count($data[$i]) ? '<b>' : '<span style="color: gray;">' ) . count($data[$i]) . ' orders</td>' . (count($data[$i]) ? '</b>' : '</span>' );
			}
			echo '</tr>';

			echo '<tr style="border-top: 1px solid black;">';
			for($i = 1; $i < 12; $i++) {
				echo '<td>' . (get_number_items_sold_from_list($data[$i]) ? '<b>' : '<span style="color: gray;">' ) . get_number_items_sold_from_list($data[$i]) . ' sold</td>' . (get_number_items_sold_from_list($data[$i]) ? '</b>' : '</span>' );
			}
			echo '</tr>';

			echo '<tr>';
			for($i = 1; $i < 12; $i++) {
				echo '<td>' . (get_item_income_from_list($data[$i]) ? '<b>' : '<span style="color: gray;">' ) . '$' . number_format(get_item_income_from_list($data[$i]), 2, '.', ',') . (get_item_income_from_list($data[$i]) ? '</b>' : '</span>' ) . '</td>';
			}
			echo '</tr>';
	}

	?>
		</tbody>
	</table>

	<h2>Total items sold: <?php echo $quantity_bought; ?></h2>
	<h2>Total value: $<?php echo number_format($total_money_from_sales, 2, '.', ','); ?></h2>
	<?php

	echo ob_get_clean();
	return;
}

function get_number_items_sold_from_list($list) {
	$number_items_sold = 0;
	foreach($list as $order_id => $data) {
		$number_items_sold = $number_items_sold + $data['quantity'];
	}

	return $number_items_sold;
}

function get_item_income_from_list($list) {
	$item_income = 0;
	foreach($list as $order_id => $data) {
		$item_income = $item_income + $data['product_total'];
	}

	return $item_income;
}

function product_filters_form($filters, $show_download_button = true, $product_name = null) {
	$query = new WC_Product_Query( array(
		'limit' => 10000,
		'status' => 'publish',
	    'orderby' => 'name',
	    'order' => 'ASC',
	    'return' => 'ids',
	) );
	$products = $query->get_products();

	ob_start();
	?>
	<form action="" method="get">
		<input type="text" class="autocomplete product-search" value="<?php echo ($product_name ? $product_name : ''); ?>">
		<select name="year">
			<?php
			$years = array(2021, 2022, 2023);

			foreach($years as $cur_year) {
				echo '<option' . ($cur_year == $filters['year'] ? ' selected' : '') . '>' . $cur_year . '</option>';
			}
			?>
		</select>
		<input type="hidden" id="product-to-search-for" name="product" value="<?php echo @$filters['product']; ?>">
		<input type="hidden" name="page" value="<?php echo PRODUCT_DATA_DOWNLOAD_URL; ?>">
		<input type="submit" value="Submit">
	</form>

	<?php
	return ob_get_clean();
}

/**
 * Get All orders IDs for a given product ID.
 *
 * @param  integer  $product_id (required)
 * @param  array    $order_status (optional) Default is 'wc-completed'
 *
 * @return array
 */
function get_order_ids_by_product_id( $filters ){
    global $wpdb;

    $year = $filters['year'];
    $product_id = $filters['product'];
    $statuses = $filters['statuses'];

    $results = $wpdb->get_col("
        SELECT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
        AND posts.post_status IN ( '" . implode( "','", $statuses ) . "' )
        AND posts.post_date BETWEEN '$year-01-01' AND '$year-12-01'
        AND order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '_product_id'
        AND order_item_meta.meta_value = '$product_id'
    ");
//        LEFT JOIN {$wpdb->prefix}comments AS comments ON comments.comment_post_ID = posts.ID

    return $results;
}



/*
function get_product_data_download_column_headers() {
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
*/

//autocomplete textbox stuff
add_action('wp_ajax_nopriv_autocompleteSearch', 'onp_product_data_autocomplete_search');
add_action('wp_ajax_autocompleteSearch', 'onp_product_data_autocomplete_search');

function onp_product_data_autocomplete_search() {
	check_ajax_referer('autocompleteSearchNonce', 'security');
	$search_term = $_REQUEST['term'];
	if (!isset($_REQUEST['term'])) {
		echo json_encode([]);
	}
	$suggestions = [];
	$query = new WP_Query([
		's' => $search_term,
		'posts_per_page' => -1,
		'post_type' => 'product',
	]);
	if ($query->have_posts()) {
		while ($query->have_posts()) {
			$query->the_post();
			$suggestions[] = [
				'id' => get_the_ID(),
				'label' => get_the_title(),
				'link' => get_the_permalink()
			];
		}
		wp_reset_postdata();
	}
	echo json_encode($suggestions);
	die();
}