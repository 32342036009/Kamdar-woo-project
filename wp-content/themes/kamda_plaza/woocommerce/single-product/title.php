<?php
/**
 * Single Product title
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/title.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.woocommerce.com/document/template-structure/
 * @author     WooThemes
 * @package    WooCommerce/Templates
 * @version    1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $post, $product;
the_title( '<h3 itemprop="name" class="product_title entry-title">', '</h3>' ); 

	if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ) ) ) : ?>

		<span class="sku_wrapper"><?php _e( 'SKU:', 'woocommerce' ); ?> <span class="sku" itemprop="sku"><?php echo ( $sku = $product->get_sku() ) ? $sku : __( 'N/A', 'woocommerce' ); ?></span></span>

	<?php endif; ?>

<div class="r_n_c_u_l">
   <ul>
     <li> <a href="#tab-reviews" class="trig_review"> <i class="fa fa-comment" aria-hidden="true"></i>  Review this</a></li>
     <li>  <a href="#" onclick="window.open('/contact-us/', 'myWindow', 'width=800,height=600');"> <i class="fa fa-pencil-square-o" aria-hidden="true"></i> Ask question </a> </li>
   </ul>
  
</div>

