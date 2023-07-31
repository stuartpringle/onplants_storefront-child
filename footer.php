<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package storefront
 */
?>



    <?php do_action( 'storefront_before_footer' ); ?>
    <?php do_action( 'storefront_after_footer' ); ?>

</div><!-- #page -->

<?php if ( !is_front_page() ) { ?>
    </div>
<?php } ?>

<footer class="bkgd_medgray">
    <div class="wrapper center">
        <p>Specializing in the online ordering and shipping of native plants</p>
        <a href="/shop/" class="button1">Order Plants Online</a>
        <div class="footer_menu">
            <div class="social">
                <a href="<?php echo 'https://www.facebook.com/onplants/'; //the_field('facebook_link', 11); ?>" target="_blank"><img src="<?php echo get_stylesheet_directory_uri(); ?>/images/facebook.svg" alt="Facebook"/></a>
                <a href="<?php echo 'https://www.instagram.com/onplants.ca/'; //the_field('instagram_link', 11); ?>" target="_blank"><img src="<?php echo get_stylesheet_directory_uri(); ?>/images/instagram.svg" alt="Instagram"/></a>
            </div>
            <a href="<?php the_field('f_1', 11); ?>">Privacy Policy</a> &nbsp; | &nbsp;
            <a href="<?php the_field('f_2', 11); ?>">Terms and Conditions</a><!-- &nbsp; | &nbsp;
            <a href="<?php the_field('f_3', 11); ?>">Site Map</a>-->
        </div>
    </div>
    <div class="bkgd_darkgray">
        <div class="wrapper center copyright">
            &copy; Copyright <?php echo date('Y'); ?> Ontario Native Plants. Grown and shipped from Southern Ontario. Website Design by <a href="http://www.christiehoeksema.com" target="_blank">Christie Hoeksema</a>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>

<script>
    new jQueryCollapse(jQuery("#custom-show-hide"), {
        open: function() {
        this.slideDown(350);
        },
        close: function() {
        this.slideUp(350);
        }
    });


    jQuery(document).ready(function() {
        if(jQuery('.woocommerce-MyAccount-navigation-link--edit-address a').html() == '') {
            jQuery('.woocommerce-MyAccount-navigation-link--edit-address a').html('Addresses');
        }
        jQuery('.woocommerce-MyAccount-navigation-link--edit-address a').html('Addresses');

        const ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
        jQuery(document.body).on( 'updated_cart_totals', function() {
            update_cart_item_count_with_ajax_call();
        });

        jQuery(document.body).on( 'added_to_cart', function() {
            update_cart_item_count_with_ajax_call();
        });

        jQuery('#order_comments').on('input', function() {
            if(jQuery('#additional-info-message').length < 1) {
                jQuery('.woocommerce-additional-fields__field-wrapper').prepend('<p id="additional-info-message"><b><?php echo get_option('order_notes_message'); ?></b></p>');
            }
        });

        jQuery('.woocommerce-product-gallery__trigger').on('click', function() {
            //hide the arrows!
            jQuery('.flex-direction-nav').css('display', 'none');
        });
        jQuery('.pswp__container').on('click', function() {
            //show the arrows!
            jQuery('.flex-direction-nav').css('display', 'block');
        });

        jQuery(document).keyup(function(e) {
            if (e.key === "Escape") { // escape key maps to keycode `27`
                if(jQuery('.flex-direction-nav').css('display') == 'none') {
                    jQuery('.flex-direction-nav').css('display', 'block');
                }
            }
        });
    });

    function update_cart_item_count_with_ajax_call() {
        var data = {
            'action': 'get_number_of_cart_items',
        };
        jQuery.post(ajaxurl, data, function(response) {
            if(jQuery.isNumeric(response)) {
                document.getElementById('header-cart-count').innerHTML = response;
            }
        });
        
    }
</script>



<!-- Blur Out of Stock Items (Inactive)
<?php
$term = get_term_by( 'slug', get_query_var('term'), get_query_var('taxonomy') );
$store_page_ids = array(
    '33',
    '246',
    '2801'
);

if(in_array(get_the_ID(), $store_page_ids) || @$term->name != '') {
$effect = 'blur(1px) brightness(1) contrast(0.8) grayscale(0.3) hue-rotate(0deg) invert(0) opacity(0.5) saturate(100%) sepia(0)';
$filters = array(
    '-webkit-filter',
    '-moz-filter',
    '-o-filter',
    '-ms-filter',
    'filter'
);
?>

<script>
    jQuery(document).ready(function() {

        function set_filters() {
            <?php foreach($filters as $cur_filter) { ?>
            jQuery('.soldout').siblings('img').css('<?php echo $cur_filter; ?>', '<?php echo $effect; ?>');
            jQuery('.soldout').siblings('picture').find('img').css('<?php echo $cur_filter; ?>', '<?php echo $effect; ?>');
            <?php } ?>
        }

        set_filters();

        jQuery('.soldout').siblings('picture').hover(function() {
            <?php foreach($filters as $cur_filter) { ?>
            jQuery(this).find('img').css('<?php echo $cur_filter; ?>', 'blur(0.2px)');
            <?php } ?>
            jQuery(this).find('img').css('-webkit-transform', 'translateZ(0)');
            jQuery(this).find('img').css('transition', '0.3s');
        }, function() {
            set_filters();
        });
        jQuery('.soldout').siblings('img').hover(function() {
            <?php foreach($filters as $cur_filter) { ?>
            jQuery(this).css('<?php echo $cur_filter; ?>', 'blur(0.2px)');
            <?php } ?>
            jQuery(this).css('-webkit-transform', 'translateZ(0)');
            jQuery(this).css('transition', '0.3s');
        }, function() {
            set_filters();
        });
    });
</script>
<?php
} ?>
-->

<?php
//echo 'THE ID IS: ' . get_the_ID();
// jQuery that runs ONLY on the CHECKOUT page
if(in_array(get_the_ID(), array(37))) {
    ob_start();
    ?>
    <script type="text/javascript">
        function checkout_page_pickup_only_clicked(called_on_page_load = false) {
            let checkbox = document.getElementById('pick_up').checked;
            const ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

            if(!called_on_page_load) {
                let data = {
                    'action': 'set_session_pick_up_button',
                    'checkbox_status': checkbox,
                };
                jQuery.when(jQuery.post(ajaxurl, data, function(response) {
                    if(response) {
                        //alert(response);
                        if(response == 'completed') {
                            //alert('hi');
                        }
                    }
                })).done(function() {
                    jQuery(document.body).trigger('update_checkout');
                });
            }
            jQuery('#pick_up_additional_text').toggle();
            

        }

        jQuery(document).ready(function() {
            jQuery('#pick_up').click(function() {
                checkout_page_pickup_only_clicked();
            });
            <?php
            if(@$_SESSION['will_pickup_order'] === 'true') {
                ?>
                document.getElementById('pick_up').checked = true;
                checkout_page_pickup_only_clicked(true);
                //jQuery('#pick_up').trigger("click");
                <?php
            }
            ?>
        });
    </script>
    <?php
    echo ob_get_clean();
}
?>
</body>
</html>
