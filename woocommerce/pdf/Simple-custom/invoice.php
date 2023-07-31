<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php do_action( 'wpo_wcpdf_before_document', $this->type, $this->order ); ?>

<table class="head container">
	<tr>
		<td class="header">
		<?php
		if( $this->has_header_logo() ) {
			$this->header_logo();
		} else {
			echo $this->get_title();
		}
		?>
		</td>
		<td class="shop-info">
			<div class="shop-name"><h3><?php $this->shop_name(); ?></h3></div>
			<div class="shop-address"><?php $this->shop_address(); ?></div>
		</td>
	</tr>
</table>


<h1 class="document-type-label">
<?php if( $this->has_header_logo() ) echo $this->get_title(); ?>
</h1>

<?php do_action( 'wpo_wcpdf_after_document_label', $this->type, $this->order );

function get_primary_product_category_id($product_category = array()) {
	$product_categories_to_hide = array(603, 604, 518, 159, 120, 538, 157, 160, 158, 171);

	foreach($product_category as $cur_category_id) {
		if(!in_array($cur_category_id, $product_categories_to_hide)) {
			return $cur_category_id;
		}
	}
	return '';
}

$items = $this->get_order_items();

//make sure we don't display summer sale or new as the product categories here

$product_hierarchy = array();
if(sizeof($items) > 0) {
	foreach($items as $item_id => $item) {
		$product = wc_get_product($item['product_id']);
		$product_category = $product->get_category_ids();
		$primary_term_product_id = get_primary_product_category_id($product_category);
		$term = get_term_by('id', $primary_term_product_id, 'product_cat');
		$primary_term = $term->name;

		$product_hierarchy[$primary_term][$item_id] = $item;
	}
}


?>
<table class="order-data-addresses">
	<tr>
		<td class="address billing-address">
			<?php do_action( 'wpo_wcpdf_before_billing_address', $this->type, $this->order ); ?>
			<?php $this->billing_address(); ?>
			<?php do_action( 'wpo_wcpdf_after_billing_address', $this->type, $this->order ); ?>
			<?php if ( isset($this->settings['display_email']) ) { ?>
			<div class="billing-email"><?php $this->billing_email(); ?></div>
			<?php } ?>
			<?php if ( isset($this->settings['display_phone']) ) { ?>
			<div class="billing-phone"><?php $this->billing_phone(); ?></div>
			<?php } ?>
		</td>
		<td class="address shipping-address">
			<?php if ( isset($this->settings['display_shipping_address']) && $this->ships_to_different_address()) { ?>
			<h3><?php _e( 'Ship To:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
			<?php do_action( 'wpo_wcpdf_before_shipping_address', $this->type, $this->order ); ?>
			<?php $this->shipping_address(); ?>
			<?php do_action( 'wpo_wcpdf_after_shipping_address', $this->type, $this->order ); ?>
			<?php } ?>
		</td>
		<td class="order-data">
			<table>
				<?php do_action( 'wpo_wcpdf_before_order_data', $this->type, $this->order ); ?>
				<?php if ( isset($this->settings['display_number']) ) { ?>
				<tr class="invoice-number">
					<th><?php _e( 'Invoice Number:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php $this->invoice_number(); ?></td>
				</tr>
				<?php } ?>
				<?php if ( isset($this->settings['display_date']) ) { ?>
				<tr class="invoice-date">
					<th><?php _e( 'Invoice Date:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php $this->invoice_date(); ?></td>
				</tr>
				<?php } ?>
				<tr class="order-number">
					<th><?php _e( 'Order Number:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php $this->order_number(); ?></td>
				</tr>
				<tr class="order-date">
					<th><?php _e( 'Order Date:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php $this->order_date(); ?></td>
				</tr>
				<tr class="payment-method">
					<th><?php _e( 'Payment Method:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php $this->payment_method(); ?></td>
				</tr>
				<?php do_action( 'wpo_wcpdf_after_order_data', $this->type, $this->order ); ?>
			</table>
		</td>
	</tr>
</table>

<?php do_action( 'wpo_wcpdf_before_order_details', $this->type, $this->order ); ?>

<table class="order-details">
	<thead>
		<tr>
			<th class="product"><?php _e('Product', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
			<th class="quantity"><?php _e('Quantity', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
			<th class="price"><?php _e('Price', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php

$total_num_products_in_order = 0;

if( sizeof( $items ) > 0 ) : foreach( $product_hierarchy as $category_name => $temp_item ) :
?>

		<tr class="<?php echo apply_filters( 'wpo_wcpdf_item_row_class', $item_id, $this->type, $this->order, $item_id ); ?>">
			<td class="category" style="padding-left: 10px; background-color: #ededed;" colspan="3">
				<h3><?php echo $category_name; ?></h3>
			</td>
		</tr>

<?php
	foreach($temp_item as $item_id => $item) {
		$product = wc_get_product($item['product_id']);
		$product_count = $item['quantity'];

		$display_latin_name = 1;
		$number_in_pack = 1;
		$display_plants_in_pack = 1;
		$product_category = $product->get_category_ids();

		$product_is_tshirt = false;
		if(in_array(612, $product->get_category_ids())) {
			$product_is_tshirt = true;
		}

		$product_is_tote_bag = false;
		if($product->get_id() == 140951) {
			$product_is_tote_bag = true;
		}

		$plant_names_in_pack = array();
		if(in_array(125, $product_category)) {
			$cur_attributes = $product->get_attributes();
			$number_in_pack = (is_object($cur_attributes['number-of-plants-in-pack']) ? $cur_attributes['number-of-plants-in-pack']->get_options()[0] : 1);
			$display_latin_name = 0;
			$display_plants_in_pack = 1;

			$temp_plant_names_in_pack = (is_object($cur_attributes['plant-names-in-pack']) ? $cur_attributes['plant-names-in-pack']->get_options()[0] : 'None');

			$plant_names_in_pack = explode("\n", $temp_plant_names_in_pack);
		}

		if($product->get_type() != 'pw-gift-card' && strtolower($item['name']) != 'admin fee' && !$product_is_tshirt && !$product_is_tote_bag) {
			$total_num_products_in_order = $total_num_products_in_order + ($product_count * $number_in_pack);
		} else {
			$display_latin_name = 0;
		}


		$temp_latin_name = explode("\n", $product->get_short_description());
		$product_latin_name = trim($temp_latin_name[0]);
?>
		<tr class="<?php echo apply_filters( 'wpo_wcpdf_item_row_class', $item_id, $this->type, $this->order, $item_id ); ?>">
			<td class="product" style="padding-left: 10px;">
				<?php $description_label = __( 'Description', 'woocommerce-pdf-invoices-packing-slips' ); // registering alternate label translation ?>
				<span class="item-name">
					<?php echo $item['name'];
					echo ($product_latin_name != '' && $display_latin_name ? ' ('.$product_latin_name.')' : '');
					?>
				</span>
				<?php do_action( 'wpo_wcpdf_before_item_meta', $this->type, $item, $this->order  ); ?>
				<span class="item-meta"><?php echo $item['meta']; ?></span>
<?php /*
				<dl class="meta">
					<?php $description_label = __( 'SKU', 'woocommerce-pdf-invoices-packing-slips' ); // registering alternate label translation ?>
					<?php if( !empty( $item['sku'] ) ) : ?><dt class="sku"><?php _e( 'SKU:', 'woocommerce-pdf-invoices-packing-slips' ); ?></dt><dd class="sku"><?php echo $item['sku']; ?></dd><?php endif; ?>
					<?php if( !empty( $item['weight'] ) ) : ?><dt class="weight"><?php _e( 'Weight:', 'woocommerce-pdf-invoices-packing-slips' ); ?></dt><dd class="weight"><?php echo $item['weight']; ?><?php echo get_option('woocommerce_weight_unit'); ?></dd><?php endif; ?>
				</dl>
*/ ?>
				<?php do_action( 'wpo_wcpdf_after_item_meta', $this->type, $item, $this->order  ); ?>

			<?php
			/*********** PLANT NAMES UNDER PLANT PACKS  **********/
			if($display_plants_in_pack) {
				ob_start();
				if(count($plant_names_in_pack)) {
				?>
					<ul class="item-name" style="padding-left: 30px; padding-top: 8px;">
					<?php foreach($plant_names_in_pack as $cur_plant_name) { ?>
						<li style="margin-top: -3px;"><i><?php echo $cur_plant_name; ?></i></li>
					<?php } ?>
					</ul>
				<?php
				}
				echo ob_get_clean();
			} ?>
			</td>
			<td class="quantity"><?php echo $item['quantity']; ?></td>
			<td class="price"><?php echo $item['order_price']; ?></td>
		</tr>
	<?php } endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr class="no-borders">
			<td class="no-borders">
				<div class="customer-notes">
					<?php do_action( 'wpo_wcpdf_before_customer_notes', $this->type, $this->order ); ?>
					<?php if ( $this->get_shipping_notes() ) : ?>
						<h3><?php _e( 'Customer Notes', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
						<?php $this->shipping_notes(); ?>
					<?php endif; ?>
					<?php do_action( 'wpo_wcpdf_after_customer_notes', $this->type, $this->order ); ?>
				</div>

				<?php if ( $order->get_meta('_wcpdf_invoice_notes') && $this->get_shipping_notes() || 1) : ?>
				<!-- spacer -->
				<br />
				<?php endif; ?>
				
				<div class="order-notes">
					<?php do_action( 'wpo_wcpdf_before_order_notes', $this->type, $this->order ); ?>
					<?php if ( $order->get_meta('_wcpdf_invoice_notes') ) : ?>
						<h3><?php _e( 'Order Notes', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
						<?php echo wp_kses_post( wpautop( wptexturize( $order->get_meta('_wcpdf_invoice_notes') ) ) ); ?>
					<?php endif; ?>
					<?php do_action( 'wpo_wcpdf_after_order_notes', $this->type, $this->order ); ?>
				</div>
			</td>
			<td class="no-borders" colspan="2">
				<table class="totals">
					<tfoot>
						<tr class="Subtotal">
							<td class="no-borders"></td>
							<th class="description">Total Plants in Order</th>
							<td class="price"><span class="totals-price"><?php echo $total_num_products_in_order; ?></span></td>
						</tr>
						<?php foreach( $this->get_woocommerce_totals() as $key => $total ) : ?>
						<tr class="<?php echo $key; ?>">
							<td class="no-borders"></td>
							<th class="description"><?php echo $total['label']; ?></th>
							<td class="price"><span class="totals-price"><?php echo $total['value']; ?></span></td>
						</tr>

						<?php endforeach; ?>
					</tfoot>
				</table>
			</td>
		</tr>
	</tfoot>
</table>

<?php do_action( 'wpo_wcpdf_after_order_details', $this->type, $this->order ); ?>

<?php if ( $this->get_footer() ): ?>
<div id="footer">
	<?php $this->footer(); ?>
</div><!-- #letter-footer -->
<?php endif; ?>
<?php do_action( 'wpo_wcpdf_after_document', $this->type, $this->order ); ?>
