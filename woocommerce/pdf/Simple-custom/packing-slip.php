<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php do_action( 'wpo_wcpdf_before_document', $this->type, $this->order ); ?>

<style>
	table.order-details {
		margin-bottom: 0px;
		padding-bottom: 20px;
	}
	table.order-details th {
		background-color: #fff;
		border-bottom-color: #505050;
		border-top: none;
		color: #000;
		font-size: 14px;
	}
	.head.container .shop-details {
		float: right;
		margin-right: -40px !important;
	}
	h2.total-plants {
		float: right;

	}
</style>

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
		<td class="shop-details">
			<div class="shop-name"><h3><?php $this->shop_name(); ?></h3></div>
			<div class="shop-address"><?php $this->shop_address(); ?></div>
		</td>
	</tr>
</table>

<h2 style="padding-bottom: 15px;">
Order Summary
<?php //if( $this->has_header_logo() ) echo $this->get_title(); ?>
</h2>

<?php do_action( 'wpo_wcpdf_after_document_label', $this->type, $this->order ); ?>
<?php

$items = $this->get_order_items();

function get_primary_product_category_id($product_category = array()) {
	$product_categories_to_hide = array(603, 604, 518, 159, 120, 538, 157, 160, 158, 171);

	foreach($product_category as $cur_category_id) {
		if(!in_array($cur_category_id, $product_categories_to_hide)) {
			return $cur_category_id;
		}
	}
	return '';
}

function update_line_count($line_count, $tag) {
	$line_count++;
	return $line_count;
	if($line_count > 15) {

		if($tag == 'ul') {
			?>
					</ul>
				</td>
			</tr>
			<tr>
				<td class="product" style="padding-left: 10px;">
					<ul class="item-name" style="padding-left: 30px; padding-top: 8px;">
			<?php
		} elseif($tag == 'tr') {
			?>
				</tbody>
			</table>
			<table class="order-details">
				<thead style="display: none;">
					<tr>
						<th class="product"><?php _e('Product', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
						<th class="quantity"><?php _e('Quantity', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
		}

		$line_count = 0;
	}
	return $line_count;
}

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

$new_order = array(
	'Plant Packs',
	'Wildflowers',
	'Grasses',
	'Ferns',
	'Trees',
	'Shrubs',
);

$sorted_hierarchy = array();

/* Sorting categories by list above; all the rest put below those */
foreach($new_order as $cur_ordering) {
	if(isset($product_hierarchy[$cur_ordering])) {
		$sorted_hierarchy[$cur_ordering] = $product_hierarchy[$cur_ordering];
	}
}

foreach($product_hierarchy as $k => $v) {
	if(!in_array($k, $new_order)) {
		$sorted_hierarchy[$k] = $product_hierarchy[$k];
	}
}

/* Sorting items within categories alphabetically.  Yucky. */
foreach($sorted_hierarchy as $cur_cat => $v) {
	$new_product_order = array();
	$old_product_order = array();
	foreach($v as $cur_id => $cur_item) {
		$old_product_order[$cur_item['name']] = $cur_id;
	}

	/* Get  correctly sorted items, then loop through them and add them to new_product_order array, and use this to replace the current $sorted_hierarchy array items */
	ksort($old_product_order);
	foreach($old_product_order as $key => $val) {
		$new_product_order[] = $sorted_hierarchy[$cur_cat][$val];
	}
	$sorted_hierarchy[$cur_cat] = $new_product_order;

}

// Set the sorted array as the array we'll use below.
$product_hierarchy = $sorted_hierarchy;

?>
<table class="order-data-addresses">
	<tr>
		<td class="address shipping-address">
			<?php if ( isset($this->settings['display_billing_address']) && $this->ships_to_different_address()) { ?>
			<h3><?php _e( 'Shipping Address:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
			<?php } ?>
			<?php do_action( 'wpo_wcpdf_before_shipping_address', $this->type, $this->order ); ?>
			<?php $this->shipping_address(); ?>
			<?php do_action( 'wpo_wcpdf_after_shipping_address', $this->type, $this->order ); ?>
			<?php if ( isset($this->settings['display_email']) ) { ?>
			<br />
			<br />
			<div class="billing-email"><?php $this->billing_email(); ?></div>
			<?php } ?>
			<?php if ( isset($this->settings['display_phone']) ) { ?>
			<div class="billing-phone"><?php $this->billing_phone(); ?></div>
			<?php } ?>
		</td>
		<td class="address billing-address">
			<?php if ( isset($this->settings['display_billing_address']) && $this->ships_to_different_address()) { ?>
			<h3><?php _e( 'Billing Address:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
			<?php do_action( 'wpo_wcpdf_before_billing_address', $this->type, $this->order ); ?>
			<?php $this->billing_address(); ?>
			<?php do_action( 'wpo_wcpdf_after_billing_address', $this->type, $this->order ); ?>
			<?php } ?>
		</td>
		<td class="order-data">
			<table>
				<?php do_action( 'wpo_wcpdf_before_order_data', $this->type, $this->order ); ?>
				<tr class="order-number">
					<th><?php _e( 'Order Number:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php $this->order_number(); ?></td>
				</tr>
				<tr class="order-date">
					<th><?php _e( 'Order Date:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<td><?php $this->order_date(); ?></td>
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
		</tr>
	</thead>
	<tbody>

	<?php
$line_count = 0;
$total_num_products_in_order = 0;

if( sizeof( $items ) > 0 ) : foreach( $product_hierarchy as $category_name => $temp_item ) :
?>

		<tr class="<?php echo apply_filters( 'wpo_wcpdf_item_row_class', $item_id, $this->type, $this->order, $item_id ); ?>">
			<td class="category" style="padding-left: 10px; background-color: #ededed;" colspan="2">
				<h3 class="category-title"><?php echo $category_name; $line_count++; ?></h3>
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

		//if the current product is in the category 'Plant Packs', we want to find all the plants that are included in that pack so we can display them on the packing slip
		$plant_names_in_pack = array();
		if(in_array(125, $product_category)) {
			$cur_attributes = $product->get_attributes();
			$number_in_pack = (is_object($cur_attributes['number-of-plants-in-pack']) ? $cur_attributes['number-of-plants-in-pack']->get_options()[0] : 1);
			$display_latin_name = 0;
			$display_plants_in_pack = 1;

			$plant_names_in_pack = (is_object($cur_attributes['plant-names-in-pack']) ? $cur_attributes['plant-names-in-pack']->get_options()[0] : 'None');

			$plant_names_in_pack = explode("\n", $plant_names_in_pack);
		}

		//To get the 'total plants in order' data, we need to count all plants - but obviously gift cards and admin fees are not plants!  And plant packs have multiple plants in them!
		if($product->get_type() != 'pw-gift-card' && strtolower($item['name']) != 'admin fee' && !$product_is_tshirt && !$product_is_tote_bag) {
			$total_num_products_in_order = $total_num_products_in_order + ($product_count * $number_in_pack);
		} else {
			$display_latin_name = 0;
		}

		//Sort everything by 'primary term' and put plants in the proper categories - also make sure 'primary term' is not summer sale
		$primary_term_product_id = get_primary_product_category_id($product_category);
		$term = get_term_by('id', $primary_term_product_id, 'product_cat');
		$primary_term = $term->name;

		//Get the latin name for each plant
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
				<?php do_action( 'wpo_wcpdf_after_item_meta', $this->type, $item, $this->order  ); 

			/*********** PLANT NAMES UNDER PLANT PACKS  **********/
			if($display_plants_in_pack) {
				ob_start();
				if(count(@$plant_names_in_pack)) {
				?>
					<ul class="item-name" style="padding-left: 30px; padding-top: 8px;">
					<?php
					asort($plant_names_in_pack);
					foreach($plant_names_in_pack as $cur_plant_name) { ?>
						<li style="margin-top: -3px;"><i><?php echo $cur_plant_name; ?></i></li>
					<?php
						$line_count = update_line_count($line_count, 'ul');
					} ?>
					</ul>
				<?php
				}
				echo ob_get_clean();
			} ?>
			</td>
			<td class="quantity"><?php echo $item['quantity']; 
			?></td>
		</tr>
		<?php
			$line_count = update_line_count($line_count, 'tr');
			?>
	<?php } endforeach; endif; ?>
	</tbody>
</table>

<h2 class="total-plants">
	Total Plants in Order: <?php echo $total_num_products_in_order; ?>
</h2>

<?php do_action( 'wpo_wcpdf_after_order_details', $this->type, $this->order ); ?>

<?php do_action( 'wpo_wcpdf_before_customer_notes', $this->type, $this->order ); ?>
<div class="customer-notes">
	<?php if ( $this->get_shipping_notes() ) : ?>
		<h3><?php _e( 'Customer Notes', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
		<?php $this->shipping_notes(); ?>
	<?php endif; ?>
</div>
<?php do_action( 'wpo_wcpdf_after_customer_notes', $this->type, $this->order ); ?>


<?php if ( $order->get_meta('_wcpdf_invoice_notes') && $this->get_shipping_notes() || 1 ) : ?>
<!-- spacer -->
<br />
<?php endif; ?>


<?php do_action( 'wpo_wcpdf_before_order_notes', $this->type, $this->order ); ?>
<div class="order-notes">
	<?php if ( $order->get_meta('_wcpdf_invoice_notes') ) : ?>
		<h3><?php _e( 'Order Notes', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
		<?php echo wp_kses_post( wpautop( wptexturize( $order->get_meta('_wcpdf_invoice_notes') ) ) ); ?>
	<?php endif; ?>
</div>
<?php do_action( 'wpo_wcpdf_after_order_notes', $this->type, $this->order ); ?>


<?php if ( $this->get_footer() ): ?>
<div id="footer">
	<?php $this->footer(); ?>
</div><!-- #letter-footer -->
<?php endif; ?>

<?php do_action( 'wpo_wcpdf_after_document', $this->type, $this->order ); ?>