<?php
/**
 * Customer completed order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-completed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 3.7.0
 */

// custom code for gift card switch
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
	//$email_heading = 'Gift Card Order Details';
}

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );

/* translators: %s: Customer first name */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<?php /* translators: %s: Site title */ ?>
<?php /* <p><?php esc_html_e( 'We have finished processing your order.', 'woocommerce' ); ?></p> */ ?>
<?php

/*  We want to put the shipping details from Canada Post above the main content of the email, however Canada Post simply appends this to the woocommerce_email_order_meta hook
*	Therefore we have to split that hook (not the best solution) and put one part at the top and one part near the bottom
*/
ob_start();
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
$test = ob_get_clean();

$test = explode('<h3', $test);

$normal_meta = $test[0];
$shipping = '<h3' . $test[1];
echo $shipping;

if(!$only_gift_card) {
	//normal behaviour...  For orders that contain ONLY gift cards, we won't show this

	/**
	 * Show user-defined additional content - this is set in each email's settings.
	 */
	if ( $additional_content ) {
		echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	}

}

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
echo $normal_meta;

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
