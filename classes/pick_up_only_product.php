<?php

function onp_pickup_register_pickup_order_status() {
    register_post_status( 'wc-pickup-processing', array(
        'label'                     => 'Pick-up Processing',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Pick-up Processing (%s)', 'Pick-up Processing (%s)' )
    ) );
    register_post_status( 'wc-pickup-scheduled', array(
        'label'                     => 'Pick-up Completed',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Pick-up Scheduled (%s)', 'Pick-up Scheduled (%s)' )
    ) );
}
add_action( 'init', 'onp_pickup_register_pickup_order_status' );


//add button to my account > orders page to 'schedule pickup' for all pickup processing orders
add_filter( 'woocommerce_my_account_my_orders_actions', 'onp_wc_my_account_add_schedule_order_button', 10, 2 );
function onp_wc_my_account_add_schedule_order_button( $actions, $order ) {
    if ( $order->has_status( 'pickup-processing' ) ) {
        $action_slug = 'onp-schedule-pickup';

        $actions[$action_slug] = array(
            'url'  => home_url('/my-account/schedule-pickup?selected_order=' . $order->ID),
        'name' => 'Schedule Pickup',
        );
    }
    return $actions;
}

function add_pickup_to_order_statuses( $order_statuses ) {
     $new_order_statuses = array();
     foreach ( $order_statuses as $key => $status ) {
         $new_order_statuses[ $key ] = $status;
 
        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-pickup-processing'] = 'Pick-up Processing';
            $new_order_statuses['wc-pickup-scheduled'] = 'Pick-up Scheduled';
        }
    }
 
    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'add_pickup_to_order_statuses' );

// Adding custom status 'pickup' to admin order list bulk dropdown
add_filter( 'bulk_actions-edit-shop_order', 'onp_wc_custom_dropdown_bulk_actions_shop_order', 10, 1 );
function onp_wc_custom_dropdown_bulk_actions_shop_order( $actions ) {
	$actions['mark_pickup_processing'] = __( 'Change status to pick-up processing', 'woocommerce' );
	$actions['mark_pickup_scheduled'] = __( 'Change status to pick-up scheduled', 'woocommerce' );
	//usort($actions);
	return $actions;
}

add_filter('handle_bulk_actions-edit-shop_order', 'handle_onp_wc_custom_dropdown_bulk_actions_shop_order', 10, 3);
function handle_onp_wc_custom_dropdown_bulk_actions_shop_order($redirect_to, $action, $post_ids) {
	if($action !== 'mark_pickup_processing' && $action !== 'mark_pickup_scheduled') {
		return $redirect_to;
	}

	$new_order_status = 'pickup-processing';
	if($action == 'mark_pickup_scheduled') {
		$new_order_status = 'pickup-scheduled';
	}
	
	$note_text = 'Status updated to ' . $new_order_status . wc_get_order_status_name($new_order_status);
	$processed_ids = array();

	foreach ( $post_ids as $post_id ) {
		$order = wc_get_order( $post_id );
		$order->update_status($new_order_status, $note_text, true);
		$processed_ids[] = $post_id;
	}

	return $redirect_to = add_query_arg( array(
		'onp_to_pickup_status' => count( $processed_ids )//'1',
		//'processed_count' => count( $processed_ids ),
		//'processed_ids' => implode( ',', $processed_ids ),
	), $redirect_to );

}

// The results notice from bulk action on orders
add_action( 'admin_notices', 'downloads_bulk_action_admin_notice' );
function downloads_bulk_action_admin_notice() {
	if ( empty( $_REQUEST['onp_to_pickup_status'] ) ) return; // Exit

	$count = intval( $_REQUEST['onp_to_pickup_status'] );

	printf( '<div id="message" class="updated fade"><p>' .
		_n( '%s order status changed.',
		'%s order statuses changed.',
		$count,
		'onp_to_pickup_status'
	) . '</p></div>', $count );
}

function add_pickup_woocommerce_emails( $email_classes ) {
    // include our custom email class
    require_once( 'class-wc-email-customer-pickup-processing.php' );
    require_once( 'class-wc-email-customer-pickup-completed.php' );

    // add the email class to the list of email classes that WooCommerce loads
    $email_classes['WC_Email_Customer_Pickup_Processing'] = new WC_Email_Customer_Pickup_Processing();
    $email_classes['WC_Email_Customer_Pickup_Completed'] = new WC_Email_Customer_Pickup_Completed();

    return $email_classes;

}
add_filter( 'woocommerce_email_classes', 'add_pickup_woocommerce_emails' );


//remove default processing emails
function unhook_those_pesky_emails($email_class){
	remove_action( 'woocommerce_order_status_completed_notification', array( $email_class->emails['WC_Email_Customer_Completed_Order'], 'trigger' ) ); // cancels automatic email of order complete status update.
	//remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) ); // cancels automatic email of new order placed (when defined to processing status)
	remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger' ) ); // cancels automatic email of status update to processing.
}
add_action ('woocommerce_email', 'unhook_those_pesky_emails');

//for ALL order status changes - we're just interested in going from pickup-processing to pickup-scheduled or
//from pickup-scheduled to pickup-completed
add_filter( 'woocommerce_before_order_object_save', 'onp_wc_check_pickup_status_change', 10, 2 );
function onp_wc_check_pickup_status_change( $order, $data_store ) {
	$changes = $order->get_changes();
	$pickup_base_class = new onp_wc_pickup_base_class();

	$changes = $order->get_changes();
	if( ! empty($changes) && isset($changes['status']) ) {
		$old_status    = str_replace( 'wc-', '', get_post_status($order->get_id()) );
		$new_status    = $changes['status'];
		$user          = wp_get_current_user();

		if($old_status == 'pickup-scheduled') {
			if($new_status == 'completed') {
				$pickup_base_class->log_message('Status: ' . $order->get_status() . ' - new data: ' . serialize($changes), 'error');

				$scheduled_timeslot_obj = new onp_wc_pickup_scheduled_timeslot();
				if($scheduled_timeslot_obj->load_by_order_id($order->get_id())) {
					$scheduled_timeslot_obj->status = 'complete';
					$scheduled_timeslot_obj->save();
				}
			} else {
				$pickup_base_class->log_message('Status: ' . $order->get_status() . ' - new data: ' . serialize($changes), 'error');

				$scheduled_timeslot_obj = new onp_wc_pickup_scheduled_timeslot();
				if($scheduled_timeslot_obj->load_by_order_id($order->get_id())) {
					$scheduled_timeslot_obj->status = $new_status;
					$scheduled_timeslot_obj->save();
				}
			}
		}

		// Avoid status change from "processing" to "on-hold"
		/*
		if ( 'processing' === $old_status && 'on-hold' === $new_status && ! empty($matched_roles) ) {
			throw new Exception( sprintf( __("You are not allowed to change order from %s to %s.", "woocommerce" ), $old_status, $new_status ) );
			return false;
		}
		*/
    }
    return $order;
}

add_action( 'woocommerce_order_status_changed', 'onp_pickup_thankyou_change_order_status', 5 );
function onp_pickup_thankyou_change_order_status( $order_id, $checkout = null ){
	if( ! $order_id ) return;
	$order = wc_get_order($order_id);
	$pickup_field = get_post_meta( $order->id, 'pick_up', true );

	$scheduled_timeslot_obj = new onp_wc_pickup_scheduled_timeslot();


	if($pickup_field) {
		// Status without the "wc-" prefix
		if($order->status === 'processing') {
			$order->update_status( 'pickup-processing' );
		}
	}

	if($order->status === 'pickup-processing') {
        $email = WC()->mailer()->get_emails()['WC_Email_Customer_Pickup_Processing'];
        $email->trigger( $order_id );
	} elseif($order->status === 'processing') {
        $email = WC()->mailer()->get_emails()['WC_Email_Customer_Processing_Order'];
        $email->trigger( $order_id );
	} elseif($order->status === 'completed') {
		if($scheduled_timeslot_obj->order_was_scheduled($order_id)) {
			$email = WC()->mailer()->get_emails()['WC_Email_Customer_Pickup_Completed'];
			$email->trigger( $order_id );
		} else {
			$email = WC()->mailer()->get_emails()['WC_Email_Customer_Completed_Order'];
			$email->trigger( $order_id );
		}
	}
	return;
}

add_action('woocommerce_before_thankyou','onp_before_thankyou');
function onp_before_thankyou(){
    onp_start_session();
    unset($_SESSION['will_pickup_order']);
}


add_action('onp-wc-pickup-calendar-page-before-calendar', [new pick_up_only_product(), 'display_before_calendar_text']);
add_action('onp-wc-pickup-calendar-page-after-calendar', [new pick_up_only_product(), 'display_after_calendar_text']);

class pick_up_only_product {
	public function __construct() {
		foreach($this->get_option_names() as $cur_option_name => $default_value) {
			$this->$cur_option_name = get_option($cur_option_name, $default_value);
		}
	}

	function get_option_names() {
		return array(
			'pick_up_only_products'			=> '',
			'pick_up_only_text'				=> 'I am ordering %n+ plants and would like to pick up my order!',
			'pick_up_only_number'			=> 24,
			'pick_up_only_min_cart_error'	=> 'Ferns are only available for pickup orders of %n plants or more.',
			'pick_up_only_cart_error'		=> 'Ferns are only available for pickup orders of %n plants or more.',
			'pick_up_additional_text'		=> 'Read more about our pickup process.',
			'pick_up_additional_text_url'	=> 'pick-up',
			'pick_up_before_calendar_text'	=> 'There are currently no dates available for pick up. On Monday, May 23rd we will open up dates for the week of June 6th. Every Monday, a new week of future dates will be made available.',
			'pick_up_after_calendar_text'	=> '',
		);
	}

	public function get_list_as_array() {
		$arr = explode('--NEWLINE--', $this->pick_up_only_products);
		$res_arr = array();
		foreach($arr as $cur_line) {
			$product_slug = trim($cur_line);
			//admin_dump('"' . $product_slug . '"');
			if($product_slug != '') {
				$res_arr[] = $product_slug;
			}
		}
		return $res_arr;
	}

	public function pickup_season_is_active() {
		$pickup_admin_obj = new onp_wc_pickup_admin();
		admin_dump('got here and the answer is: ' . ($pickup_admin_obj->season_active() ? 'TRUE' : 'FALSE'));
		return $pickup_admin_obj->season_active();
	}

	public function display_before_calendar_text() {
		echo '<p>' . $this->get_before_calendar_text() . '</p>';
	}

	public function display_after_calendar_text() {
		echo '<p>' . $this->get_after_calendar_text() . '</p>';
	}

	public function get_before_calendar_text() {
		return $this->pick_up_before_calendar_text;
	}

	public function get_after_calendar_text() {
		return $this->pick_up_after_calendar_text;
	}

	public function get_pick_up_only_text() {
		return str_replace('%n', $this->pick_up_only_number, $this->pick_up_only_text);
	}

	public function get_pick_up_only_min_cart_error() {
		return str_replace('%n', $this->pick_up_only_number, $this->pick_up_only_min_cart_error);
	}

	public function get_pick_up_only_cart_error() {
		return str_replace('%n', $this->pick_up_only_number, $this->pick_up_only_cart_error);
	}

	public function get_pick_up_additional_text() {
		$ret = $this->get_pick_up_additional_text_url_link();
		return $ret;
	}

	public function get_pick_up_additional_text_url_link($text = null) {
		if($text === null) {
			$text = str_replace('%n', $this->pick_up_only_number, $this->pick_up_additional_text);
		}
		return '<a href="/' . $this->pick_up_additional_text_url . '" target="_blank">' . $text . '</a>';
	}

	public function save($pick_up_only_products) {
		$pick_up_only_products = trim($pick_up_only_products);
		while(stristr($pick_up_only_products, "\n\n")) {
			$pick_up_only_products = str_replace("\n\n", "\n", $pick_up_only_products);
		}
		$pick_up_only_products = str_replace("\n", '--NEWLINE--', $pick_up_only_products);
		$pick_up_only_products = sanitize_text_field( $pick_up_only_products );

		update_option('pick_up_only_products', $pick_up_only_products);
	}

	public function is_pickup_only_product_in_cart() {
		$ret = false;
		$pick_up_only_products = $this->get_list_as_array();
		admin_dump($pick_up_only_products);
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			//admin_dump($cart_item['data']->get_slug());
			if(in_array($cart_item['data']->get_slug(), $pick_up_only_products)) {
				$ret = true;
				admin_dump('PICKUP ONLY PRODUCT DETECTED');
			}
			//wc_add_notice('<pre>' . print_r($cart_item['data']->get_slug()) . '</pre>', 'error');
		}
		return $ret;
	}
}