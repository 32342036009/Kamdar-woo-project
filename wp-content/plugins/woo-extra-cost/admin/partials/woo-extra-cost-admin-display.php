<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://www.multidots.com/
 * @since      1.0.0
 *
 * @package    Woo_Extra_Cost
 * @subpackage Woo_Extra_Cost/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Settings_Extra_Cost {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Class hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {
		// Add WC settings tab
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'settings_tab_extra_cost' ), 60 );

		// Settings page contents
		add_action( 'woocommerce_settings_tabs_extra_cost_settings', array( $this, 'settings_page_extra_cost' ) );

		// Save settings page
		add_action( 'woocommerce_update_options_extra_cost_settings', array( $this, 'update_options_extra_cost' ) );
	}

	/**
	 * Settings tab.
	 *
	 * Add a WooCommerce settings tab for the Receiptful settings page.
	 *
	 * @since 1.0.0
	 *
	 * @param 	array	$tabs 	Array of default tabs used in WC.
	 * @return 	array 			All WC settings tabs including newly added.
	 */
	public function settings_tab_extra_cost( $tabs ) {

		$tabs['extra_cost_settings'] = __( 'Woo Extra Cost', 'woocommerce-extra-cost' );

		return $tabs;

	}

	/**
	 * Settings page content.
	 *
	 * @since 1.0.0
	 */
	public function settings_page_extra_cost() { 
		global $wpdb;
		if ( ! defined( 'ABSPATH' ) ) {
			exit;
		}
		?>
		<?php 
		
		global $wpdb, $post;
		global $woocommerce;
		/**
         * accordion title tooltips messages
         */
		$tip_title = "This name will be visible to the customer at the time of checkout.";
		$tip_charge = "Cost Amount";
		$enable_cost_for_vaiable_product 		= 'Enable for variable product';
		$extra_cost_based_on_country 	 		= 'Extra cost based on different country';
		$extra_cost_based_on_cart_total  		= 'Extra cost range based on cart total.';
		$extra_cost_based_on_quantity 	 		= 'Extra cost based on total cart products quantity.';
		$extra_cost_based_on_weight 	 		= 'Extra Cost on Total Cart Weight';
		$extra_cost_based_on_shipping_class 	= 'Extra cost based on product shipping classes.';
		$extra_cost_based_on_product 			= 'Extra cost based on particular product.';
		$extra_cost_based_on_product_cat 		= 'Extra cost based on particular product category.';
		$extra_cost_based_on_product_tag 		= 'Extra cost based on particular product tag.';
		$extra_cost_based_on_product_sku 		= 'Extra cost based on particular product SKU.';
		$extra_cost_based_on_product_coupon 	= 'Extra cost based on particular product coupon.';
		$extra_cost_based_on_product_user 		= 'Extra cost based on particular user.';
		$extra_cost_based_on_product_user_role  = 'Extra cost based on user role.';
		$extra_cost_based_on_max_total 			= 'Remove all extra cost based on max cart total amount.';
		
		$aec_charge_type_admin 			= get_option('aec_charge_type');
		$aec_charge_type_admin_ser 		= maybe_unserialize($aec_charge_type_admin);

		/**
         * add extra cost accordion menu
         * 
         */
		
		$extra_cost_method_settings = get_option('wc_settings_extra_cost_method_settings');
		$extra_cost_master_sertting = get_option('extra_cost_master_setting');
		
		if ($extra_cost_method_settings == '') {
            ?>
            <div class="main wc_extra_cost_main">
                <fieldset>
                    <legend><?php _e('Extra Cost Settings','woocommerce-extra-cost'); ?></legend>
                    <div>
                        <p><?php _e('The "Extra Cost for Woocommerce" allow store owners to add extra fixed charges/cost to the customers order based on different conditions. You can configure product specific, category specific, country specific or order amount specific extra charges and it will be applicable to entire order.  The charges will be added to the cart total of the cart.'); ?></p>
                        <p><?php _e('You can charge Extra cost in two types', 'woocommerce-extra-cost'); ?></p>
                        
                        <h3><?php _e('Fixed cost', 'woocommerce-extra-cost'); ?></h3>
                        <h4><?php _e('For example:', 'woocommerce-extra-cost'); ?></h4>
                        <p><?php _e('1. You can charge extra cost based on "Country" like USA= $10. Ex. If user purchase products from the USA then extra $10 cost will be added to the cart total.', 'woocommerce-extra-cost'); ?></p>
                        <p><?php _e('2. You can charge extra cost based on "Cart total Quantity" like cart total Quantity = 5. Ex. If Cart total Quantity ="5" and above then extra $3 cost will be added to the cart total.', 'woocommerce-extra-cost'); ?></p>
                        <h3><?php _e('Percentage (Calculate on sub total)', 'woocommerce-extra-cost'); ?></h3>
                        <h4><?php _e('For example:', 'woocommerce-extra-cost'); ?></h4>
                        <p><?php _e('1. You can charge extra cost based on "Country" like USA= 10% of sub total. Ex. If user purchase products $500 from the USA then extra $50 (It will calculate on cart sub total $500 *10% = $50) cost will be added to the cart total.', 'woocommerce-extra-cost'); ?></p>
                        <p><?php _e('2. You can charge extra cost based on "Cart total Weight" like Cart total Weight = 500 lbs and charge 20% extra (20% on sub total) and . Ex. If Cart total Weight = "500 lbs" and above then extra $100 (It will calculate on cart sub total $500 *20% = $100) cost will be added to the cart total.', 'woocommerce-extra-cost'); ?></p>
                    </div>
                    <?php $country_option_val = get_option('wc_extra_cost_country'); ?>

                    <fieldset class="fs_global">
                        <legend><?php _e('Global Settings','woocommerce-extra-cost')?></legend>
                        <div class="div_main_global globalcon">
                       		<div class="div_master_setting">
                       			<p>
                       				<p><strong>1.Display extra fee itemised</strong>&nbsp;&nbsp;Add Extra fee individual as itemised</p>
                       			<p><strong>2.Display fee as single unit</strong>&nbsp;&nbsp;Add Extra fee as a single unit with sum</p>
                       			<label><strong>Select Master Settings</strong></label>
                       			<select name="ddl_master_setting" disabled class="ddl_master_setting" id="ddl_master_setting">
                       			<option value="itemised"<?php if ($extra_cost_master_sertting == 'itemised') echo ' selected="selected"'; ?>>Display extra fee itemised</option>
                       			<option value="single_unit"<?php if ($extra_cost_master_sertting == 'single_unit') echo ' selected="selected"'; ?>>Display fee as single unit</option>
                       			                       			                       			
                       			</select>
                       			<div class="sinlge_unit_label">
                      			</div>
                       			</p>
                       		</div><!--div_master_setting-->
                            <div id="accordian_global">

                                <h3><?php $country_option_val = get_option('wc_extra_cost_country'); ?><?php
                                echo __('Based on Country', 'woocommerce-extra-cost');
                                echo '<img class="help_tip extra-cost-tooltip"  data-tip="' . esc_attr__($extra_cost_based_on_country, 'woocommerce-extra-cost') . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
                                ?><span class="enble-disable-label"><?php
                                if ($country_option_val == 'yes') {
                                	echo '<p class="green">' . __('( Enable )', 'woocommerce-extra-cost') . '</p>';
                                } else {
                                	echo '<p class="red">' . __('( Disable )', 'woocommerce-extra-cost') . '</p>';
                                }
                                    ?></span></h3>
                                <div class="div_based_country">
                                    <?php $wc_extra_cost_based_on_country = get_option('wc_extra_cost_based_on_country'); ?>

                                    <p class="wc_short_desc_own"><?php echo _e('Using this feature you can charge Extra Cost based on country.', 'woocommerce-extra-cost'); ?></p>                                    
                                    <p class="wc_short_desc_own"><?php echo _e('For example, you can charge extra cost based on "Country" like USA= $10. Ex. If user purchase products from the USA then extra $10 cost will be added to the cart total.', 'woocommerce-extra-cost'); ?></p>                                    
                                    
                                    <div class="enable-disable"><input type="checkbox" <?php 
		                                    if ($country_option_val == 'yes') {
		                                    	echo'checked';
		                                    }
                                        ?> class="wc_enable_option wc_enable_option_based_country" value="yes" name="ec-country-enable" > <?php echo __('Enable / Disable', 'woocommerce-extra-cost'); ?></div>
                                   <p>
                                    	<span class="cls_strong"><?php _e('Select Charge Type','woocommerce-extra-cost'); ?></span>
	                                    <select name="aec_charge_type[country]">
		                                    <option <?php if ($aec_charge_type_admin_ser['country'] == 'fix' ) echo 'selected' ; ?> value="fix">Fixed</option>
		                                    <option <?php if ($aec_charge_type_admin_ser['country'] == 'per' ) echo 'selected' ; ?> value="per">Percentage (%)</option>
	                                    </select>
                                    </p>
                                    

                                    <table class="wc_extra_cost_tbl_country wc_input_table widefat">
                                        <thead>
                                            <tr>
                                                <th width="20%"><?php _e('Country Code','woocommerce-extra-cost'); ?><img class="help_tip" data-tip="<?php echo 'Enter country code (UK,IN,US).'; ?>" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="40%"><?php _e('Cost Title','woocommerce-extra-cost'); ?><img data-tip="Cost Title will be visible to the customer at the time of checkout"  class="help_tip" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="48%"><?php _e('Amount to Charge','woocommerce-extra-cost'); ?><img class="help_tip" data-tip="<?php echo $tip_charge; ?>" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $wc_extra_cost_based_on_country = maybe_unserialize($wc_extra_cost_based_on_country);
                                            if (isset($wc_extra_cost_based_on_country) && !empty($wc_extra_cost_based_on_country)) {

                                            	foreach ($wc_extra_cost_based_on_country as $key) {
                                                    ?>
                                                    <tr class="">
                                                        <td class="name" width="8%">
                                                            <input id="wc_ec_country_chk_validate"   type="text" class="" value="<?php echo $key['extra_cost_based_country_code']; ?>" name="extra_cost_based_country_code[]">
                                                        </td>

                                                        <td class="name" width="40%">
                                                            <input type="text"    class="" value="<?php echo $key['extra_cost_based_country_cost_title']; ?>" name="extra_cost_based_country_cost_title[]">
                                                        </td>

                                                        <td class="rate" width="48%">
                                                            <input type="number" step="any"  class="check_valid_charge" value="<?php echo $key['extra_cost_based_country_amount']; ?>"  name="extra_cost_based_country_amount[]">
                                                        </td>
                                                    </tr>
                                                    <?php
                                            	}
                                            } else {
                                                ?>
                                                <tr class="">

                                                    <td class="name" width="8%">
                                                        <input id="wc_ec_country_chk_validate"   type="text" class="" value="" name="extra_cost_based_country_code[]">
                                                    </td>

                                                    <td class="name" width="40%">
                                                        <input type="text" step="any"   class="" value="" name="extra_cost_based_country_cost_title[]">
                                                    </td>

                                                    <td class="rate" width="48%">
                                                        <input type="number" step="any" data-validation="number"  class="check_valid_charge" value=""  name="extra_cost_based_country_amount[]">
                                                    </td>
                                                </tr>
                                            <?php } ?>								
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="10">
                                                    <a href="#" class="button plus insert"><?php _e('Insert row','woocommerce-extra-cost')?></a>
                                                    <a href="#" class="button minus remove_tax_rates"><?php _e('Remove selected row(s)','woocommerce-extra-cost')?></a>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>

                                </div> <!--div_based_country-->
                                
                                
                                
                                <h3><?php
                                echo __('Based on User Role', 'woocommerce-extra-cost');
                                echo '<img class="help_tip" data-tip="' . esc_attr__($extra_cost_based_on_product_user_role, 'woocommerce-extra-cost') . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
                                ?><span class="enble-disable-label"><?php
                                
                                	echo '<p class="red paid_version_feature">' . __('( Enable only for paid version )', 'woocommerce-extra-cost') . '</p>';
                              
                                    ?></span></h3>
                                    
                                <div class="div_based_user_role">

                                    <p class="wc_short_desc_own"><?php echo _e('Using this feature you can charge Extra Cost based on User Role.(like Author,Subscriber)', 'woocommerce-extra-cost'); ?></p>                                    
                                    <p class="wc_short_desc_own"><?php echo _e('For example, if we select user role "author" = $30 extra cost. When "author" add product in to the cart then extra $30 cost will be added to the total cart.', 'woocommerce-extra-cost'); ?></p>                                    

                                    <div class="enable-disable"><input type="checkbox" /><?php echo __('Enable / Disable', 'woocommerce-extra-cost'); ?></div>
                                          <p>
                                    	<span class="cls_strong"><?php _e('Select Charge Type','woocommerce-extra-cost'); ?></span>
	                                    <select name="aec_charge_type[user_role]">
		                                   <option value="fix">Fixed</option>
		                                    <option selected="selected">Percentage (%)</option>
	                                    </select>
                                    </p>

                                   

                                    <table class="wc_extra_cost_based_user_role wc_input_table widefat">
                                        <thead>
                                            <tr>
                                                <th width="20%"><?php _e('Cost Title','woocommerce-extra-cost'); ?><img class="help_tip" data-tip="<?php echo $tip_title; ?>" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="40%"><?php _e('Select User Role','woocommerce-extra-cost'); ?><img data-tip="Select User Role"  class="help_tip" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="48%"><?php _e('Amount to Charge','woocommerce-extra-cost'); ?><img class="help_tip" data-tip="<?php echo $tip_charge; ?>" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                      
                                                <tr class="">
                                                    <td class="name" width="8%">
                                                        <input id="wc_ec_country_chk_validate"   type="text" class="" value="" name="">
                                                    </td>

                                                    <td class="name" width="40%">
                                                        <select class="wc_product_chk_value chk_valid ddl_rolebase"   name="">
                                                            <option value="">--Select--</option>
                                                            
                                                        </select>

                                                    </td>
                                                    <td class="rate" width="48%">
                                                        <input type="number"  step="any"  class=""  name="extra_cost_based_user_role_amount[]">
                                                    </td>
                                                </tr>
                                               
                                       							
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="10">
                                                    <a  class="button "><?php _e('Insert row','woocommerce-extra-cost'); ?></a>
                                                    <a  class="button "><?php _e('Remove selected row(s)','woocommerce-extra-cost'); ?></a>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>


                                </div><!--div_based_user_role-->
                               

                                <h3><?php
                                echo __('Based on Shipping Class', 'woocommerce-extra-cost');
                                echo '<img class="help_tip" data-tip="' . esc_attr__($extra_cost_based_on_shipping_class, 'woocommerce-extra-cost') . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
                                ?><span class="enble-disable-label"><?php 
                                $shipping_class_option_val = '';
                              
                              echo '<p class="red paid_version_feature">' . __('( Enable only for paid version )', 'woocommerce-extra-cost') . '</p>';
                                    ?></span></h3>
                                <div class="div_based_shipping_method">
                                    <p class="wc_short_desc_own"><?php echo _e('Using this feature you can charge Extra Cost based on Shipping Classes. You can view all product shipping class here.', 'woocommerce-extra-cost'); ?></p>                                    
                                    <p class="wc_short_desc_own"><?php echo _e('For example, Lets say shipping class "Large" = $20 extra cost. When the customer adds "Large"  shipping class product into the cart then extra $20 cost will be add into the cart.', 'woocommerce-extra-cost'); ?></p>                                    
                                    <div class="accordion-tab-link"><?php echo __('These costs can optionally be added based on the ', 'woocommerce-extra-cost'); ?><a href="<?php echo admin_url("edit-tags.php?taxonomy=product_shipping_class&post_type=product"); ?>" class="shipping_tab"><?php echo __('product shipping class', 'woocommerce-extra-cost'); ?></a></div>
                                    <div class="enable-disable"><input type="checkbox" <?php
                                    if ($shipping_class_option_val == 'yes') {
                                    	echo'checked';
                                    }
                                        ?> value="yes" class="wc_enable_option wc_enable_option_based_shipping_class" name="ec-shipping-class-enable" ><?php echo __('Enable / Disable', 'woocommerce-extra-cost'); ?></div>
                                     <p>
                                    	<span class="cls_strong"><?php _e('Select Charge Type','woocommerce-extra-cost'); ?></span>
	                                    <select name="aec_charge_type[shipping]">
		                                     <option <?php if ($aec_charge_type_admin_ser['shipping'] == 'fix' ) echo 'selected' ; ?> value="fix"><?php _e('Fixed','woocommerce-extra-cost'); ?></option>
		                                    <option <?php if ($aec_charge_type_admin_ser['shipping'] == 'per' ) echo 'selected' ; ?> value="per"><?php _e('Percentage (%)','woocommerce-extra-cost'); ?></option>
	                                    </select>
                                    </p>

                                    <table cellpadding="10" cellspacing="10">
                                        <?php
                                        $shipping_classes = get_terms('product_shipping_class', array('hide_empty' => '0'));
                                        if (!empty($shipping_classes) && $shipping_classes != '') {
                                        	$counter = 1;
                                        	foreach ($shipping_classes as $shipping_values) {
                                        		$shippping_class = $shipping_values->name;
                                        		$shippin_option_values = get_option('wc_ec_shipping_class_' . $shippping_class);
                                                ?>
                                                <tr>
                                                    <td><?php echo __('Shipping Class', 'woocommerce-extra-cost'); ?> "<b><?php echo $shippping_class; ?></b>"<input type="hidden" value="<?php echo $shippping_class; ?>" name="<?php echo 'ec_shipping_name[' . $counter . ']'; ?>"> </td>
                                                    <td><input class="check_valid_charge"  class="check_valid_charge" step="any"  value="<?php echo $shippin_option_values; ?>" name="<?php echo 'ec_shipping_charge[' . $counter . ']'; ?>"></td>
                                                </tr>
                                                <?php
                                                $counter = $counter + 1;
                                        	}
                                        }
                                        ?>
                                    </table>

                                </div><!--div_based_shipping_method-->
                                
                                <h3><?php
                                echo __('Remove all Extra Cost Based on Max Total', 'woocommerce-extra-cost');
                                echo '<img class="help_tip" data-tip="' . esc_attr__('Remove all Extra Cost Based on Max Total', 'woocommerce-extra-cost') . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
                                ?> <span class="enble-disable-label"><?php
                                	echo '<p class="red paid_version_feature">' . __('( Enable only for paid version )', 'woocommerce-extra-cost') . '</p>';
                                    ?></span><div class="red"></div></h3>
                                <div class="div_based_remove_all">
                                    <p class="wc_short_desc_own"><?php echo _e('Using this feature you can remove all extra cost based on Maximum Cart Total Amount.', 'woocommerce-extra-cost'); ?></p>                                    
                                    <p class="wc_short_desc_own"><?php echo _e('For example, if we specify maximum cart total amount = $500 and above then remove all extra cost. When customer cart total amount $500 and above then remove all extra cost from the current cart.', 'woocommerce-extra-cost'); ?></p>                                    


                                    <div class="enable-disable"><input type="checkbox" <?php
                                        ?> class="wc_enable_option wc_enable_option_based_max_total" value="yes" name="ec-cart-max-total-enable" ><?php echo __('Enable / Disable', 'woocommerce-extra-cost'); ?></div>
                                    <div class="wc_ec_cart_max_total">
                                        <table cellpadding="10" cellspacing="10">
                                            <tr>
                                                <td><b><?php echo __('Enter Maximum Cart Total Amount', 'woocommerce-extra-cost'); ?> : </b></td>
                                                <td><input type="number"  class="check_valid_charge"  name="wc_ec_max_total_charge"><?php echo '<img class="help_tip" data-tip="' . esc_attr__('Provide amount for max cart total to remove all extra cost.', 'woocommerce-extra-cost') . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />'; ?></td>
                                            </tr>
                                        </table>
                                    </div>		
                                </div><!--div_based_remove_all-->

                                <h3><?php
                                echo __('Based on Payment Gateway', 'woocommerce-extra-cost');
                                echo '<img class="help_tip" data-tip="' . esc_attr__('Extra cost based on payment getway', 'woocommerce-extra-cost') . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
                                ?><span class="enble-disable-label"><?php
                                echo '<p class="red paid_version_feature">' . __('( Enable only for paid version )', 'woocommerce-extra-cost') . '</p>';
                                    ?></span></h3>

                                <div class="div_based_paymentgateway">
                                
                                    <p class="wc_short_desc_own"><?php echo _e('Using this feature you can charge Extra Cost based on Product. you can see all product here.', 'woocommerce-extra-cost'); ?></p> 
                                    
                                     <div class="enable-disable"><input type="checkbox" <?php
                                        ?> value="yes" class="wc_enable_option wc_enable_option_based_paymentgateway" name="ec-paymentgateway-enable" ><?php echo __('Enable / Disable', 'woocommerce-extra-cost'); ?></div> 
                                         <p>
                                    	<span class="cls_strong"><?php _e('Select Charge Type','woocommerce-extra-cost'); ?></span>
	                                    <select name="aec_charge_type[based_paymentgateway]">
		                                   <option  value="fix">Fixed</option>
		                                    <option value="per">Percentage (%)</option>
	                                    </select>
                                    </p>  
                                    
                                        
                                       <table class="wc_extra_cost_based_paymentgateway wc_input_table widefat">
                                        <thead>
                                            <tr>
                                                <th width="20%"><?php _e('Cost Title','woocommerce-extra-cost'); ?><img class="help_tip" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="40%"><?php _e('Select Payment Gateway','woocommerce-extra-cost'); ?><img data-tip="Select Product"  class="help_tip" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="48%"><?php _e('Amount to Charge','woocommerce-extra-cost'); ?><img class="help_tip"  src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                           
                                            <tr class="">

                                                <td class="name" width="8%">
                                                    <input id=""   type="text" class="" value="" name="extra_cost_based_paymentgateway_title[]">
                                                </td>

                                                <td class="name" width="40%">
                                                    <select class="wc_product_chk_value ddl_paymentgateway"  name="extra_cost_based_paymentgateway_name[]">
                                                        <option value="">--Select--</option>
                                                    </select>
                                                </td>
                                                <td class="rate" width="48%">
                                                    <input type="number" step="any" step="any"  class="" value=""  name="extra_cost_based_paymentgateway_amount[]">
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="10">
                                                    <a  class="button"><?php _e('Insert row','woocommerce-extra-cost'); ?></a>
                                                    <a  class="button "><?php _e('Remove selected row(s)','woocommerce-extra-cost'); ?></a>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table> 
                                        
                                        
                                    </div><!--div_based_paymentgateway-->
                                
                                
                                
                                </div><!--accordian_global-->
                           <!-- <input name="wc_extra_cost_save_global" class="button-primary btn_wc_extra_cost_save_global" type="submit" value="Save changes">-->
                            
                        </div> <!-- div_main_global-->

                    </fieldset>
                    </legend>
                    <fieldset class="fs_cart">
                        <legend><?php _e('Cart Based Settings','woocommerce-extra-cost'); ?></legend>
                        <div class="div_cart_settings globalcon">

                            <div id="accordion_cart">
                                <h3><?php
                                echo __('Based on Cart Total', 'woocommerce-extra-cost');
                                echo '<img class="help_tip" data-tip="' . esc_attr__("Cart Based Settings", 'woocommerce-extra-cost') . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
                                ?><span class="enble-disable-label"><?php
                             	  echo '<p class="red paid_version_feature">' . __('( Enable only for paid version )', 'woocommerce-extra-cost') . '</p>';
                                    ?></span></h3>
                                <div class="div_based_cart_total">
                                    
                                    <p class="wc_short_desc_own"><?php echo _e('Using this feature you can charge Extra Cost based on Maximum and Minimum Cart Total value.', 'woocommerce-extra-cost'); ?></p>                                    
                                    <p class="wc_short_desc_own"><?php echo _e('For example, if we specify cart total value greater than or equal to (>=) $500 then charge Extra cost $20 and if cart total less than or equal to (>=) $100 then charge Extra cost $30. When the customer cart total amount is $500 and above then Extra $20 cost will be added to the cart total. OR if cart total amount is $100 and less then Extra $30 cost will be added to the cart total.', 'woocommerce-extra-cost'); ?></p>                                    
                                    <div class="enable-disable"><input type="checkbox" <?php
                                        ?>  value="yes" class="wc_enable_option wc_enable_option_based_cart_total" name="ec-cart-total-enable" ><?php echo __('Enable / Disable', 'woocommerce-extra-cost'); ?></div>
                                        
                                    <p>
                                    	<span class="cls_strong"><?php _e('Select Charge Type','woocommerce-extra-cost'); ?></span>
	                                    <select name="aec_charge_type[cart_total]">
		                                    <option  value="fix"><?php _e('Fixed','woocommerce-extra-cost'); ?></option>
		                                    <option  value="per"><?php _e('Percentage (%)','woocommerce-extra-cost'); ?></option>
	                                    </select>
                                    </p>

                                    <h4><?php echo _e('If the cart total is greater than or equal to (>=) ', 'woocommerce-extra-cost'); ?></h4> 
                                    <table class="wc_extra_cost_cart_total wc_input_table widefat">
                                        <thead>
                                            <tr>
                                                <th width="20%"><?php _e('Cost Title','woocommerce-extra-cost'); ?><img class="help_tip"  src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="40%"><?php _e('Cart Total','woocommerce-extra-cost'); ?><img data-tip="Enter Cart Total"  class="help_tip" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="48%"><?php _e('Amount to Charge','woocommerce-extra-cost'); ?><img class="help_tip"  src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <tr class="">
                                                <td class="name" width="8%">
                                                    <input id="" type="text" class=""   name="extra_cost_based_cart_total_cost_title_greater">
                                                </td>

                                                <td class="name" width="40%">
                                                    <input type="number" step="any"   class=""  name="extra_cost_based_cart_total_cart_total_greater">
                                                </td>

                                                <td class="rate" width="48%">
                                                    <input type="number"  step="any"  class="check_valid_charge"   name="extra_cost_based_cart_total_amount_greater">
                                                </td>
                                            </tr>

                                        </tbody>
                                        <tfoot>

                                        </tfoot>
                                    </table>
                                    <h4><?php echo _e('If the cart total is less than or equal to (<=)  ', 'woocommerce-extra-cost'); ?></h4> 
                                    <table class="wc_extra_cost_cart_total wc_input_table widefat">
                                        <thead>
                                            <tr>
                                                <th width="20%"><?php _e('Cost Title','woocommerce-extra-cost'); ?><img class="help_tip"  src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="40%"><?php _e('Cart Total','woocommerce-extra-cost'); ?><img data-tip="Enter Cart Total"  class="help_tip" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="48%"><?php _e('Amount to Charge','woocommerce-extra-cost'); ?><img class="help_tip"  src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <tr class="">
                                                <td class="name" width="8%">
                                                    <input id="" type="text" class=""   name="extra_cost_based_cart_total_cost_title_less">
                                                </td>

                                                <td class="name" width="40%">
                                                    <input type="number" step="any"  class=""  name="extra_cost_based_cart_total_cart_total_less">
                                                </td>

                                                <td class="rate" width="48%">
                                                    <input type="number" step="any"  class="check_valid_charge"   name="extra_cost_based_cart_total_amount_less">
                                                </td>
                                            </tr>

                                        </tbody>
                                        <tfoot>

                                        </tfoot>
                                    </table>

                                </div> <!--div_based_cart_total-->


                                <h3><?php
                                echo __('Based on Total Cart Quantity', 'woocommerce-extra-cost');
                                echo '<img class="help_tip" data-tip="' . esc_attr__('Extra cost based on quantity', 'woocommerce-extra-cost') . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
                                ?><span class="enble-disable-label"><?php
                                echo '<p class="red paid_version_feature">' . __('( Enable only for paid version )', 'woocommerce-extra-cost') . '</p>';
                                    ?></span></h3>
                                <div class="div_based_cart_quantity">
                                    <p class="wc_short_desc_own"><?php echo _e('Using this feature you can charge Extra Cost based on maximum and minimum cart total quantity.', 'woocommerce-extra-cost'); ?></p>                                    
                                    <p class="wc_short_desc_own"><?php echo _e('For example, if we specify Cart Total Quantity greater than or equal to (>=) 20 then charge Extra cost $20 and if cart total Quantity less than or equal to (>=) 10 then charge Extra cost $10. When the customer cart total Quantity is 22 and above then Extra $20 cost will be added to the cart total. OR if cart total Quantity is 8 and less then Extra $10 cost will be added to the cart total.', 'woocommerce-extra-cost'); ?></p>                                    

                                    <div class="enable-disable"><input type="checkbox" <?php
                                        ?> value="yes" class="wc_enable_option wc_enable_option_based_cart_quantity" name="ec-cart-quantity-enable" ><?php echo __('Enable / Disable', 'woocommerce-extra-cost'); ?></div>
                                          <p>
                                    	<span class="cls_strong"><?php _e('Select Charge Type','woocommerce-extra-cost'); ?></span>
	                                    <select name="aec_charge_type[cart_quantity]">
		                                    <option  value="fix"><?php _e('Fixed','woocommerce-extra-cost'); ?></option>
		                                    <option  value="per"><?php _e('Percentage (%)','woocommerce-extra-cost'); ?></option>
	                                    </select>
                                    </p>


                                    <h4><?php echo _e('If the cart quantity is greater than or equal to (>=) ', 'woocommerce-extra-cost'); ?></h4> 

                                    <table class="wc_extra_cost_based_qty wc_input_table widefat">
                                        <thead>
                                            <tr>
                                                <th width="20%"><?php _e('Cost Title','woocommerce-extra-cost'); ?><img class="help_tip"  src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="40%"><?php _e('Cart Quantity','woocommerce-extra-cost'); ?><img data-tip="Enter total cart quantity"  class="help_tip" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="48%"><?php _e('Amount to Charge','woocommerce-extra-cost'); ?><img class="help_tip"  src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <tr class="">
                                                <td class="name" width="8%">
                                                    <input id="" type="text" class=""   name="extra_cost_based_cart_quantity_cost_title_greater">
                                                </td>

                                                <td class="name" width="40%">
                                                    <input type="number" class=""   step="any"  name="extra_cost_based_cart_quantity_qty_greater">
                                                </td>

                                                <td class="rate" width="48%">
                                                    <input type="number" step="any"  class=""   name="extra_cost_based_cart_quantity_amount_greater">
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>

                                        </tfoot>
                                    </table>

                                    <h4><?php echo _e('If the cart quantity is less than or equal to (<=)  ', 'woocommerce-extra-cost'); ?></h4> 

                                    <table class="wc_extra_cost_based_qty wc_input_table widefat">
                                        <thead>
                                            <tr>
                                                <th width="20%"><?php _e('Cost Title','woocommerce-extra-cost'); ?><img class="help_tip"  src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="40%"><?php _e('Cart Quantity','woocommerce-extra-cost'); ?><img data-tip="Enter total cart quantity"  class="help_tip" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="48%"><?php _e('Amount to Charge','woocommerce-extra-cost'); ?><img class="help_tip"  src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <tr class="">
                                                <td class="name" width="8%">
                                                    <input id="" type="text"  class=""  name="extra_cost_based_cart_quantity_cost_title_less">
                                                </td>

                                                <td class="name" width="40%">
                                                    <input type="number" class=""  step="any"  name="extra_cost_based_cart_quantity_qty_less">
                                                </td>

                                                <td class="rate" width="48%">
                                                    <input type="number" step="any"  class=""   name="extra_cost_based_cart_quantity_amount_less">
                                                </td>
                                            </tr>

                                        </tbody>
                                        <tfoot>

                                        </tfoot>
                                    </table>


                                </div><!--div_based_cart_quantity-->
                                <h3><?php
                                echo __('Based on Total Cart Weight', 'woocommerce-extra-cost');
                                echo '<img class="help_tip" data-tip="' . esc_attr__("Based on Total Cart Weight", 'woocommerce-extra-cost') . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
                                ?><span class="enble-disable-label"><?php
                                echo '<p class="red paid_version_feature">' . __('( Enable only for paid version )', 'woocommerce-extra-cost') . '</p>';
                                    ?></span></h3>
                                <div class="div_based_cart_weight">
                                    <p class="wc_short_desc_own"><?php echo _e('Using this feature you can charge Extra Cost based on maximum and minimum cart total weight.', 'woocommerce-extra-cost'); ?></p>                                    
                                    <p class="wc_short_desc_own"><?php echo _e('For example, if we specify Cart Total Weight greater than or equal to (>=) 500 lbs then charge Extra cost $50 and if cart total weight less than or equal to (>=) 300 lbs then charge Extra cost $20. When the customer cart total weight is 550 lbs and above then Extra $50 cost will be added to the cart total. OR if cart total weight is 250 lbs and less then Extra $20 cost will be added to the cart total.', 'woocommerce-extra-cost'); ?></p>                                    


                                    <div class="enable-disable"><input type="checkbox" <?php
                                        ?> class="wc_enable_option wc_enable_option_based_cart_weight" value="yes" name="ec-cart-weight-enable" ><?php echo __('Enable / Disable', 'woocommerce-extra-cost'); ?></div>
                                            <p>
                                    	<span class="cls_strong"><?php _e('Select Charge Type','woocommerce-extra-cost'); ?></span>
	                                    <select name="aec_charge_type[cart_weight]">
		                                     <option  value="fix"><?php _e('Fixed','woocommerce-extra-cost'); ?></option>
		                                    <option  value="per"><?php _e('Percentage (%)','woocommerce-extra-cost'); ?></option>
	                                    </select>
                                    </p>                            
                                        
                                        

                                    <tr class="">
                                    <h4><?php echo _e('If the cart weight is greater than or equal to (>=) ', 'woocommerce-extra-cost'); ?></h4> 

                                    <table class="wc_extra_cost_based_weight wc_input_table widefat">
                                        <thead>
                                            <tr>
                                                <th width="20%"><?php _e('Cost Title','woocommerce-extra-cost'); ?><img class="help_tip" data-tip="<?php //echo $tip_title; ?>" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="40%"><?php _e('Total Cart Weight','woocommerce-extra-cost'); ?><img data-tip="Enter cart weight."  class="help_tip" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="48%"><?php _e('Amount to Charge','woocommerce-extra-cost'); ?><img class="help_tip" data-tip="<?php //echo $tip_charge; ?>" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                        <td class="name" width="8%">
                                            <input id="" type="text"  class=""  name="extra_cost_based_cart_weight_title_greater">
                                        </td>

                                        <td class="name" width="40%">
                                            <input type="number" step="any"  class=""  name="extra_cost_based_cart_weight_total_weight_greater">
                                        </td>

                                        <td class="rate" width="48%">
                                            <input type="number" step="any" class=""   name="extra_cost_based_cart_weight_amount_greater">
                                        </td>
                                        </tr>

                                        </tbody>
                                        <tfoot>

                                        </tfoot>
                                    </table>

                                    <h4><?php echo _e('If the cart weight is less than or equal to (<=) ', 'woocommerce-extra-cost'); ?></h4> 

                                    <table class="wc_extra_cost_based_weight wc_input_table widefat">
                                        <thead>
                                            <tr>
                                                <th width="20%"><?php _e('Cost Title','woocommerce-extra-cost'); ?><img class="help_tip" data-tip="<?php //echo $tip_title; ?>" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="40%"><?php _e('Total Cart Weight','woocommerce-extra-cost'); ?><img data-tip="Enter cart weight."  class="help_tip" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="48%"><?php _e('Amount to Charge','woocommerce-extra-cost'); ?><img class="help_tip" data-tip="<?php //echo $tip_charge; ?>" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <tr class="">
                                                <td class="name" width="8%">
                                                    <input id="" type="text"  class=""  name="extra_cost_based_cart_weight_title_less">
                                                </td>

                                                <td class="name" width="40%">
                                                    <input type="number" step="any"  class=""  name="extra_cost_based_cart_weight_total_weight_less">
                                                </td>

                                                <td class="rate" width="48%">
                                                    <input type="number" step="any"  class=""   name="extra_cost_based_cart_weight_amount_less">
                                                </td>
                                            </tr>

                                        </tbody>
                                        <tfoot>

                                        </tfoot>
                                    </table>
                                </div><!--div_based_cart_weight-->   

                            </div><!--accordion_cart-->
                            <!--<input name="wc_extra_cost_save_cart_setting" class="button-primary btn_wc_extra_cost_save_cart_setting" type="submit" value="Save changes">-->
                        </div><!--div_cart_settings-->

                    </fieldset>
                    </legend>
                    <fieldset class="fs_product">
                        <legend><?php _e('Product Based Settings','woocommerce-extra-cost'); ?></legend>		
                        <div class="div_product_setting globalcon">

                            <div id="accordion_product">
                                <?php $product_option_val = get_option('wc_extra_cost_cart_product'); ?>
                                <h3><?php
                                echo __('Based on Product', 'woocommerce-extra-cost');
                                echo '<img class="help_tip" data-tip="' . esc_attr__($extra_cost_based_on_product, 'woocommerce-extra-cost') . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
                                ?><span class="enble-disable-label"><?php
                                if ($product_option_val == 'yes') {
                                	echo '<p class="green">' . __('( Enable )', 'woocommerce-extra-cost') . '</p>';
                                } else {
                                	echo '<p class="red">' . __('( Disable )', 'woocommerce-extra-cost') . '</p>';
                                }
                                    ?></span></h3>

                                <div class="div_based_product">
                                    <p class="wc_short_desc_own"><?php echo _e('Using this feature you can charge Extra Cost based on Product. you can see all product here.', 'woocommerce-extra-cost'); ?></p>                                    
                                    <p class="wc_short_desc_own"><?php echo _e('For example, if we select product "beach T-shirt" = $20 extra cost. When the customer adds "beach T-shirt" product into the cart then extra $20 cost will be added into the cart total.', 'woocommerce-extra-cost'); ?></p>                                    

                                    <div class="enable-disable"><input type="checkbox" <?php
                                    if ($product_option_val == 'yes') {
                                    	echo'checked';
                                    }
                                        ?> value="yes" class="wc_enable_option wc_enable_option_based_product" name="ec-product-enable" ><?php echo __('Enable / Disable', 'woocommerce-extra-cost'); ?></div>
                                             <p>
                                    	<span class="cls_strong"><?php _e('Select Charge Type','woocommerce-extra-cost'); ?></span>
	                                    <select name="aec_charge_type[product]">
		                                    <option <?php if ($aec_charge_type_admin_ser['product'] == 'fix' ) echo 'selected' ; ?> value="fix"><?php _e('Fixed','woocommerce-extra-cost'); ?></option>
		                                    <option <?php if ($aec_charge_type_admin_ser['product'] == 'per' ) echo 'selected' ; ?> value="per"><?php _e('Percentage (%)','woocommerce-extra-cost'); ?></option>
	                                    </select>
                                    </p>   

                                    <?php $wc_extra_cost_based_on_product = get_option('wc_extra_cost_based_on_product'); ?>

                                    <table class="wc_extra_cost_based_product wc_input_table widefat">
                                        <thead>
                                            <tr>
                                                <th width="20%"><?php _e('Cost Title','woocommerce-extra-cost'); ?><img class="help_tip" data-tip="<?php echo $tip_title; ?>" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="40%"><?php _e('Select Product','woocommerce-extra-cost'); ?><img data-tip="Select Product"  class="help_tip" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="48%"><?php _e('Amount to Charge','woocommerce-extra-cost'); ?><img class="help_tip" data-tip="<?php echo $tip_charge; ?>" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $wc_extra_cost_based_on_product = maybe_unserialize($wc_extra_cost_based_on_product);
                                            $args = array('post_type' => 'product', 'posts_per_page' => -1);
                                            $products_array = get_posts($args);
                                            echo '<div class="cls_get_product" style="display:none;"><option value="">--Select--</option>';
                                            foreach ($products_array as $products_value) {
                                                ?>
                                            <option value="<?php echo $products_value->ID; ?>"><?php echo $products_value->post_title; ?></option>
                                            <?php
                                            }
                                            echo '</div>';
                                            if (isset($wc_extra_cost_based_on_product) && !empty($wc_extra_cost_based_on_product)) {

                                            	foreach ($wc_extra_cost_based_on_product as $key) {
                                                ?>
                                                <tr class="">
                                                    <td class="name" width="8%">
                                                        <input id=""   type="text" class="" value="<?php echo $key['extra_cost_based_product_cost_title']; ?>" name="extra_cost_based_product_cost_title[]">
                                                    </td>

                                                    <td class="name" width="40%">
                                                        <select class="wc_product_chk_value product_ddl" name="extra_cost_based_product_name[]">
                                                            <option value="">--Select--</option>
                                                            <?php
                                                            $products_array =  $wpdb->get_results( "SELECT ID,post_title FROM {$wpdb->prefix}posts WHERE  post_status = 'publish' and post_type='product'" );
															
                                                            foreach ($products_array as $products_value) {
                                                            	if ($products_value->ID == $key['extra_cost_based_product_name']) {
                                                            		echo'<option value="' . $products_value->ID . '" selected>' . $products_value->post_title . '</option>';
                                                            	} else {
                                                            		echo'<option value="' . $products_value->ID . '">' . $products_value->post_title . '</option>';
                                                            	}
                                                            }
                                                            ?>
                                                        </select>

                                                    </td>

                                                    <td class="rate" width="48%">
                                                        <input type="number"  step="any"  class="" value="<?php echo $key['extra_cost_based_product_amount']; ?>"  name="extra_cost_based_product_amount[]">
                                                    </td>
                                                </tr>
                                                <?php
                                            	}
                                            } else {
                                            ?>
                                            <tr class="">

                                                <td class="name" width="8%">
                                                    <input id=""   type="text" class="" value="" name="extra_cost_based_product_cost_title[]">
                                                </td>

                                                <td class="name" width="40%">
                                                    <select class="wc_product_chk_value product_ddl"  name="extra_cost_based_product_name[]">
                                                        <option value="">--Select--</option>
                                                        <?php
                                                        $args = array('post_type' => 'product', 'posts_per_page' => -1);
                                                        $products_array = get_posts($args);
                                                        foreach ($products_array as $key => $products_value) {
                                                        	if ($products_value->ID == $key['extra_cost_based_product_name']) {
                                                        		echo'<option value="' . $products_value->ID . '" selected>' . $products_value->post_title . '</option>';
                                                        	} else {
                                                        		echo'<option value="' . $products_value->ID . '">' . $products_value->post_title . '</option>';
                                                        	}
                                                        }
                                                        ?>
                                                    </select>
                                                </td>

                                                <td class="rate" width="48%">
                                                    <input type="number"  step="any"  class="" value=""  name="extra_cost_based_product_amount[]">
                                                </td>
                                            </tr>
                                        <?php } ?>								
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="10">
                                                    <a href="#" class="button plus insert"><?php _e('Insert row','woocommerce-extra-cost'); ?></a>
                                                    <a href="#" class="button minus remove_tax_rates"><?php _e('Remove selected row(s)','woocommerce-extra-cost'); ?></a>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>

                                </div><!--div_based_product-->

                                <?php $varible_product_option_val = get_option('wc_extra_cost_variable_product'); ?>
                                <h3><?php
                                echo __('Enable for Variable Product', 'woocommerce-extra-cost');
                                echo '<img class="help_tip extra-cost-tooltip"  data-tip="' . esc_attr__($enable_cost_for_vaiable_product, 'woocommerce-extra-cost') . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
                                ?><span class="enble-disable-label"><?php
                               		echo '<p class="red paid_version_feature">' . __('( Enable only for paid version )', 'woocommerce-extra-cost') . '</p>';
                                    ?></span></h3>

                                <div class="div_based_variabl_product">
                                    <p class="wc_option_desc"><?php _e('If variable Product is "Enable" then you can add "Extra Cost Message" and "Cost". When customer will add this product in to the cart at that time extra cost charge is automatically added in to cart total.'); ?></p>								
                                    <p class="wc_option_desc"><?php _e('For example, You have varitions for product with Red and Blue colour.  if we specify Red = $10 and Blue= $20 extra charge. When customer will add "red color product" in to the cart and  "extra $10 cost" will be added in the cart total.'); ?></p>								
                                    <div class="enable-disable"><input type="checkbox" <?php
                                    if ($varible_product_option_val == 'yes') {
                                    	echo'checked';
                                    }
                                        ?> class="wc_enable_option wc_enable_option_variabl_product" value="yes" name="ec-variable-product-enable" > <?php echo __('Enable / Disable', 'woocommerce-extra-cost'); ?></div>
                                         <p>
                                    	<span class="cls_strong"><?php _e('Select Charge Type','woocommerce-extra-cost'); ?></span>
	                                    <select name="aec_charge_type[variabl_product]">
		                                      <option <?php if ($aec_charge_type_admin_ser['variabl_product'] == 'fix' ) echo 'selected' ; ?> value="fix">Fixed</option>
		                                    <option <?php if ($aec_charge_type_admin_ser['variabl_product'] == 'per' ) echo 'selected' ; ?> value="per">Percentage (%)</option>
	                                    </select>
                                    </p>   

                                </div><!--div_based_variabl_product-->


                                <?php $product_cat_option_val = get_option('wc_extra_cost_cart_category_product'); ?>
                                <h3><?php
                                echo __('Based on Product Category', 'woocommerce-extra-cost');
                                echo '<img class="help_tip" data-tip="' . esc_attr__($extra_cost_based_on_product_cat, 'woocommerce-extra-cost') . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
                                ?><span class="enble-disable-label"><?php
                                if ($product_cat_option_val == 'yes') {
                                	echo '<p class="green">' . __('( Enable )', 'woocommerce-extra-cost') . '</p>';
                                } else {
                                	echo '<p class="red">' . __('( Disable )', 'woocommerce-extra-cost') . '</p>';
                                }
                                    ?></span></h3>

                                <div class="div_based_category">
                                    <p class="wc_short_desc_own"><?php echo _e('Using this feature you can charge Extra Cost based on Product Category. you can view all category here.', 'woocommerce-extra-cost'); ?></p>                                    
                                    <p class="wc_short_desc_own"><?php echo _e('For example, if we select "Book" category = $30 extra cost. When the customer adds "xyz book" from book category into the cart then extra $30 cost will be added into the total cart', 'woocommerce-extra-cost'); ?></p>                                    

                                    <div class="enable-disable"><input type="checkbox" <?php
                                    if ( $product_cat_option_val == 'yes' ) {
                                    	echo'checked';
                                    }
                                        ?> value="yes" class="wc_enable_option wc_enable_option_based_cat_product" name="ec-product-category-enable" ><?php echo __('Enable / Disable', 'woocommerce-extra-cost'); ?></div>
                                        <p>
                                    	<span class="cls_strong"><?php _e('Select Charge Type','woocommerce-extra-cost'); ?></span>
	                                    <select name="aec_charge_type[cat_product]">
		                                   <option <?php if ($aec_charge_type_admin_ser['cat_product'] == 'fix' ) echo 'selected' ; ?> value="fix">Fixed</option>
		                                    <option <?php if ($aec_charge_type_admin_ser['cat_product'] == 'per' ) echo 'selected' ; ?> value="per">Percentage (%)</option>
	                                    </select>
                                    </p>   

                                    <?php $wc_extra_cost_based_on_category = get_option('wc_extra_cost_based_on_category'); ?>

                                    <table class="wc_extra_cost_based_product_cat wc_input_table widefat">
                                        <thead>
                                            <tr>
                                                <th width="20%"><?php _e('Cost Title','woocommerce-extra-cost'); ?><img class="help_tip" data-tip="<?php echo $tip_title; ?>" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="40%"><?php _e('Select Product Category','woocommerce-extra-cost'); ?><img data-tip="Select Product"  class="help_tip" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="48%"><?php _e('Amount to Charge','woocommerce-extra-cost'); ?><img class="help_tip" data-tip="<?php echo $tip_charge; ?>" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $wc_extra_cost_based_on_category = maybe_unserialize($wc_extra_cost_based_on_category);

                                            echo '<div class="cls_get_product_cat" style="display:none;"><option value="">--Select--</option>';
                                            $product_category = get_terms('product_cat', array('hide_empty' => '0'));
                                            if (!empty($product_category) && $product_category != '') {
                                            	foreach ($product_category as $product_category_value) {
                                                    ?>
                                                <option value="<?php echo $product_category_value->term_id; ?>"><?php echo $product_category_value->name; ?></option>
                                                <?php
                                            	}
                                            }
                                            echo '</div>';
                                            if (isset($wc_extra_cost_based_on_category) && !empty($wc_extra_cost_based_on_category)) {

                                            	foreach ($wc_extra_cost_based_on_category as $key) {
                                                ?>
                                                <tr class="">
                                                    <td class="name" width="8%">
                                                        <input id=""   type="text" class="" value="<?php echo $key['extra_cost_based_product_category_title']; ?>" name="extra_cost_based_product_category_title[]">
                                                    </td>

                                                    <td class="name" width="40%">
                                                        <select class="wc_product_chk_value ddl_product_cat"   name="extra_cost_based_product_category_name[]">
                                                            <option value="">--Select--</option>
                                                            <?php
                                                            $product_category = get_terms('product_cat', array('hide_empty' => '0'));
                                                            $products_array = get_posts($args);
                                                            foreach ($product_category as $product_category_value) {
                                                            	if ($product_category_value->term_id == $key['extra_cost_based_product_category_name']) {
                                                            		echo'<option value="' . $product_category_value->term_id . '" selected>' . $product_category_value->name . '</option>';
                                                            	} else {
                                                            		echo'<option value="' . $product_category_value->term_id . '">' . $product_category_value->name . '</option>';
                                                            	}
                                                            }
                                                            ?>
                                                        </select>

                                                    </td>

                                                    <td class="rate" width="48%">
                                                        <input type="number" step="any"  class="" value="<?php echo $key['extra_cost_based_product_category_amount']; ?>"  name="extra_cost_based_product_category_amount[]">
                                                    </td>
                                                </tr>
                                                <?php
                                            	}
                                            } else {
                                            ?>
                                            <tr class="">

                                                <td class="name" width="8%">
                                                    <input id="" type="text"   class="" value="" name="extra_cost_based_product_category_title[]">
                                                </td>

                                                <td class="name" width="40%">
                                                    <select class="wc_product_chk_value ddl_product_cat"  name="extra_cost_based_product_category_name[]">
                                                        <option value="">--Select--</option>
                                                        <?php
                                                        $product_category = get_terms('product_cat', array('hide_empty' => '0'));
                                                        $products_array = get_posts($args);

                                                        foreach ($product_category as $product_category_value) {
                                                        	if ($product_category_value->term_id == isset($key['extra_cost_based_product_category_name']) ? $key['extra_cost_based_product_category_name'] :'') {
                                                        		echo'<option value="' . $product_category_value->term_id . '" selected>' . $product_category_value->name . '</option>';
                                                        	} else {
                                                        		echo'<option value="' . $product_category_value->term_id . '">' . $product_category_value->name . '</option>';
                                                        	}
                                                        }
                                                        ?>
                                                    </select>
                                                </td>

                                                <td class="rate" width="48%">
                                                    <input type="number" step="any"  class="" value=""  name="extra_cost_based_product_category_amount[]">
                                                </td>
                                            </tr>
                                        <?php } ?>								
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="10">
                                                    <a href="#" class="button plus insert"><?php _e('Insert row','woocommerce-extra-cost'); ?></a>
                                                    <a href="#" class="button minus remove_tax_rates"><?php _e('Remove selected row(s)','woocommerce-extra-cost'); ?></a>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div><!--div_based_category-->

                                <h3><?php
                                echo __('Based on Product Tag', 'woocommerce-extra-cost');
                                echo '<img class="help_tip" data-tip="' . esc_attr__($extra_cost_based_on_product_tag, 'woocommerce-extra-cost') . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
                                ?><span class="enble-disable-label"><?php
                                echo '<p class="red paid_version_feature">' . __('( Enable only for paid version )', 'woocommerce-extra-cost') . '</p>';
                                    ?></span></h3>
                                <div class="div_based_tag">
                                    <p class="wc_short_desc_own"><?php echo _e('Using this feature you can charge Extra Cost based on Product Tag. you can view all product tag here.', 'woocommerce-extra-cost'); ?></p>                                    
                                    <p class="wc_short_desc_own"><?php echo _e('For example, if we specify Product Tag "SPECIAL011" = $15 extra cost. When the customer adds product with product tag "SPECIAL011"  into the cart then extra $15 cost will be added to the total cart.', 'woocommerce-extra-cost'); ?></p>                                    
                                    <div class="enable-disable"><input type="checkbox" <?php
                                        ?> value="yes" class="wc_enable_option wc_enable_option_based_tag_product" name="ec-product-tag-enable" ><?php echo __('Enable / Disable', 'woocommerce-extra-cost'); ?></div>
                                         <p>
                                    	<span class="cls_strong"><?php _e('Select Charge Type','woocommerce-extra-cost'); ?></span>
	                                    <select name="aec_charge_type[tag_product]">
		                                     <option  value="fix">Fixed</option>
		                                    <option  value="per">Percentage (%)</option>
	                                    </select>
                                    </p>
									

                                    <table class="wc_extra_cost_based_product_tag wc_input_table widefat">
                                        <thead>
                                            <tr>
                                                <th width="20%"><?php _e('Cost Title','woocommerce-extra-cost'); ?><img class="help_tip" data-tip="<?php //echo $tip_title; ?>" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="40%"><?php _e('Select Product tag','woocommerce-extra-cost'); ?><img data-tip="Select Product"  class="help_tip" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="48%"><?php _e('Amount to Charge','woocommerce-extra-cost'); ?><img class="help_tip" data-tip="<?php //echo $tip_charge; ?>" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php

                                            echo '<div class="cls_get_product_tag" style="display:none;"><option value="">--Select--</option>';
                                            echo '</div>';
                                            ?>
                                            <tr class="">

                                                <td class="name" width="8%">
                                                    <input id=""   type="text" class="" value="" name="extra_cost_based_product_tag_title[]">
                                                </td>

                                                <td class="name" width="40%">
                                                    <select class="wc_product_chk_value ddl_product_tag"  name="extra_cost_based_product_tag_name[]">
                                                        <option value="">--Select--</option>
                                                        <?php
                                                        $product_tag = get_terms('product_tag', array('hide_empty' => '0'));
                                                        $products_array = get_posts($args);
                                                        foreach ($product_tag as  $product_tag_value) {

                                                        	echo'<option value="' . $product_tag_value->term_id . '">' . $product_tag_value->name . '</option>';


                                                        }
                                                        ?>
                                                    </select>
                                                </td>

                                                <td class="rate" width="48%">
                                                    <input type="number" step="any"   class="" value=""  name="extra_cost_based_product_tag_amount[]">
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="10">
                                                    <a class="button "><?php _e('Insert row','woocommerce-extra-cost'); ?></a>
                                                    <a class="button "><?php _e('Remove selected row(s)','woocommerce-extra-cost'); ?></a>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div><!--div_based_tag-->
                                
                                <h3><?php
                                echo __('Based on Product Coupon', 'woocommerce-extra-cost');
                                echo '<img class="help_tip" data-tip="' . esc_attr__("Based on Product Coupon", 'woocommerce-extra-cost') . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
                                ?><span class="enble-disable-label"><?php
                               echo '<p class="red paid_version_feature">' . __('( Enable only for paid version )', 'woocommerce-extra-cost') . '</p>';
                                    ?></span></h3>

                                <div class="div_based_product_coupon">
                                    <p class="wc_short_desc_own"><?php echo _e('Using this feature you can charge Extra Cost based on Product Coupon. you can view all coupons here.', 'woocommerce-extra-cost'); ?></p>                                    
                                    <p class="wc_short_desc_own"><?php echo _e('For example, if we specify Coupon "Code011" = $15 extra cost. When the customer apply coupon "Code011" into the cart then extra $15 cost will be added to the total cart.', 'woocommerce-extra-cost'); ?></p>                                    

                                    <div class="enable-disable"><input type="checkbox" <?php
                                        ?> value="yes" class="wc_enable_option wc_enable_option_based_coupon_product" name="ec-product-coupon-enable" ><?php echo __('Enable / Disable', 'woocommerce-extra-cost'); ?></div>
                                        <p>
                                    	<span class="cls_strong"><?php _e('Select Charge Type','woocommerce-extra-cost'); ?></span>
	                                    <select name="aec_charge_type[coupon_product]">
		                                     <option  value="fix">Fixed</option>
		                                    <option  value="per">Percentage (%)</option>
	                                    </select>
                                    </p>
                                        


                                    <table class="wc_extra_cost_based_coupon wc_input_table widefat">
                                        <thead>
                                            <tr>
                                                <th width="20%"><?php _e('Cost Title','woocommerce-extra-cost'); ?><img class="help_tip"  src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="40%"><?php _e('Select Product Coupon','woocommerce-extra-cost'); ?><img data-tip="Select Product"  class="help_tip" src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                                <th width="48%"><?php _e('Amount to Charge','woocommerce-extra-cost'); ?><img class="help_tip"  src="<?php echo site_url(); ?>/wp-content/plugins/woocommerce/assets/images/help.png" height="16" width="16"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                          
                                            echo '<div class="cls_get_product_coupon" style="display:none;"><option value="">--Select--</option>';
                                            
                                            echo '</div>';
                                            ?>
                                            <tr class="">

                                                <td class="name" width="8%">
                                                    <input id="wc_ec_country_chk_validate"   type="text" class="" value="" name="extra_cost_based_product_coupon_cost_title[]">
                                                </td>

                                                <td class="name" width="40%">
                                                    <select class="wc_product_chk_value ddl_coupon"  name="extra_cost_based_product_coupon_name[]">
                                                        <option value="">--Select--</option>
                                                    </select>
                                                </td>

                                                <td class="rate" width="48%">
                                                    <input type="number" step="any"  class="" value=""  name="extra_cost_based_product_coupon_amount[]">
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="10">
                                                    <a  class="button "><?php _e('Insert row','woocommerce-extra-cost'); ?></a>
                                                    <a  class="button "><?php _e('Remove selected row(s)','woocommerce-extra-cost'); ?></a>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>

                                </div><!--div_based_product_coupon-->
                            </div><!--div_product_setting-->
                            <!--<input name="wc_extra_cost_save_product_setting" class="button-primary wc_extra_cost_save_product_setting" type="submit" value="Save changes">-->

                        </div><!--end .accordion_product-->
                    </fieldset>
                    </legend>
               
                 
                 </fieldset>
                <div id="wc_ec_charge_err_msg" style="display:none;"><?php echo __('Please enter valid charge', 'woo-extra-cost'); ?></div>
                <div id="wc_ec_values_err_msg" style="display:none;"><?php echo __('Please enter valid value ', 'woo-extra-cost'); ?></div>
                <div id="wc_ec_delete_confirm_err_msg" style="display:none;"><?php echo __('Are you sure to delete this record?', 'woo-extra-cost'); ?></div>
                <div id="wc_ec_not_select_row_err_msg" style="display:none;"><?php echo __('Please select row to delete', 'woo-extra-cost'); ?></div>
            </div> <!--wc_extra_cost_main-->
            <?php
		
		}
	}

	/**
	 * Save settings.
	 *
	 * Save settings based on WooCommerce save_fields() method.
	 *
	 * @since 1.0.0
	 */
	public function update_options_extra_cost() {
		global $woocommerce,$post,$wpdb;
		
			$this->save_extra_cost();
	}
		
	/**
	 * Save Extra cost rates.
	 *
	 * @since 1.0.0
	 */
	public function save_extra_cost() {
		global $wpdb;
		
		//start save_Extra_cost_page_accordion function
		global $wpdb;
		/**
         * enable \ disable Extra cost methods
         */
		$ec_product_sku_enable = isset($_POST['ec-product-sku-enable']) ? $_POST['ec-product-sku-enable'] : 'no';
		if (isset($_POST['save'])) {
			
			$available_gateways = array();
			if (isset($_POST['aec_charge_type']) && !empty($_POST['aec_charge_type'])) {
				$ace_charge_type = array();
				$ace_charge_type = maybe_serialize($_POST['aec_charge_type']);
				update_option('aec_charge_type',$ace_charge_type);
			}
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
			$ec_paymentgateway_enable = isset($_POST['ec-paymentgateway-enable']) ? $_POST['ec-paymentgateway-enable'] : 'no';

			if (!empty($ec_paymentgateway_enable) && $ec_paymentgateway_enable != '') {
				update_option('wc_extra_cost_paymentgateway', $ec_paymentgateway_enable);
			}
			
			
			/**
			 * Update master settings
			 */
			if (isset($_POST['ddl_master_setting']) && !empty($_POST['ddl_master_setting'])) {
				update_option('extra_cost_master_setting',$_POST['ddl_master_setting']);
				
			}
			if (isset($_POST['single_unit_label']) && !empty($_POST['single_unit_label'])) {
				update_option('extra_cost_master_single_unit_label_setting',$_POST['single_unit_label']);
				
			}
			
			
			/**
             * Below code is used to save data for based on cart total
             */
			$temp_based_cart_total = array();
			if (!empty($_POST['extra_cost_based_cart_total_cost_title_less']) && !empty($_POST['extra_cost_based_cart_total_cart_total_less']) && !empty($_POST['extra_cost_based_cart_total_amount_less'])) {

				$sanitize_val1 = sanitize_text_field($_POST['extra_cost_based_cart_total_cost_title_less']);
				$sanitize_val2 = sanitize_text_field($_POST['extra_cost_based_cart_total_cart_total_less']);
				$sanitize_val3 = sanitize_text_field($_POST['extra_cost_based_cart_total_amount_less']);
				if (!empty($sanitize_val1) && !empty($sanitize_val1) && !empty($sanitize_val1)) {
					$temp_based_cart_total['total_less_than']['extra_cost_based_cart_total_cost_title'] = sanitize_text_field($_POST['extra_cost_based_cart_total_cost_title_less']);
					$temp_based_cart_total['total_less_than']['extra_cost_based_cart_total_cart_total'] = sanitize_text_field($_POST['extra_cost_based_cart_total_cart_total_less']);
					$temp_based_cart_total['total_less_than']['extra_cost_based_cart_total_amount'] = sanitize_text_field($_POST['extra_cost_based_cart_total_amount_less']);
				}
			}
			if (!empty($_POST['extra_cost_based_cart_total_cost_title_greater']) && !empty($_POST['extra_cost_based_cart_total_cart_total_greater']) && !empty($_POST['extra_cost_based_cart_total_amount_greater'])) {

				$sanitize_val1 = sanitize_text_field($_POST['extra_cost_based_cart_total_cost_title_greater']);
				$sanitize_val2 = sanitize_text_field($_POST['extra_cost_based_cart_total_cart_total_greater']);
				$sanitize_val3 = sanitize_text_field($_POST['extra_cost_based_cart_total_amount_greater']);
				if (!empty($sanitize_val1) && !empty($sanitize_val1) && !empty($sanitize_val1)) {
					$temp_based_cart_total['total_greater_than']['extra_cost_based_cart_total_cost_title'] = sanitize_text_field($_POST['extra_cost_based_cart_total_cost_title_greater']);
					$temp_based_cart_total['total_greater_than']['extra_cost_based_cart_total_cart_total'] = sanitize_text_field($_POST['extra_cost_based_cart_total_cart_total_greater']);
					$temp_based_cart_total['total_greater_than']['extra_cost_based_cart_total_amount'] = sanitize_text_field($_POST['extra_cost_based_cart_total_amount_greater']);
				}
			}

			$temp_based_cart_total = maybe_serialize($temp_based_cart_total);
			update_option('wc_extra_cost_based_on_cart_total', $temp_based_cart_total);

			/* --------------------------------------------------------------------------------------------------------------------------------------------- */


			$ec_user_role_enable = isset($_POST['ec-user-role-enable']) ? $_POST['ec-user-role-enable'] : 'no';
			$ec_country_enable = isset($_POST['ec-country-enable']) ? $_POST['ec-country-enable'] : 'no';
			$ec_shipping_class_enable = isset($_POST['ec-shipping-class-enable']) ? $_POST['ec-shipping-class-enable'] : 'no';
			$ec_cart_max_total_enable = isset($_POST['ec-cart-max-total-enable']) ? $_POST['ec-cart-max-total-enable'] : 'no';

			if (!empty($ec_user_role_enable) && $ec_user_role_enable != '') {
				update_option('wc_extra_cost_role_user', $ec_user_role_enable);
			}

			if (!empty($ec_country_enable) && $ec_country_enable != '') {
				update_option('wc_extra_cost_country', $ec_country_enable);
			}

			if (!empty($ec_shipping_class_enable) && $ec_shipping_class_enable != '') {
				update_option('wc_extra_cost_cart_shipping_class', $ec_shipping_class_enable);
			}

			if (!empty($ec_cart_max_total_enable) && $ec_cart_max_total_enable != '') {
				update_option('wc_extra_cost_max_total', $ec_cart_max_total_enable);
			}

			/**
             * Below code is used to save data for based country
             */
			$temp_based_country = array();
			$length_based_country = isset($_POST['extra_cost_based_country_code']) ? $_POST['extra_cost_based_country_code'] : '';
			$length_based_country = count($length_based_country);
			
			for ($i = 0; $i < $length_based_country; $i++) {
				if (!empty($_POST['extra_cost_based_country_code'][$i]) && !empty($_POST['extra_cost_based_country_cost_title'][$i]) && !empty($_POST['extra_cost_based_country_amount'][$i])) {

					$sanitize_val1 = sanitize_text_field($_POST['extra_cost_based_country_code'][$i]);
					$sanitize_val2 = sanitize_text_field($_POST['extra_cost_based_country_cost_title'][$i]);
					$sanitize_val3 = sanitize_text_field($_POST['extra_cost_based_country_amount'][$i]);
					if (!empty($sanitize_val1) && !empty($sanitize_val1) && !empty($sanitize_val1)) {

						$temp_based_country[$i]['extra_cost_based_country_code'] = sanitize_text_field($_POST['extra_cost_based_country_code'][$i]);
						$temp_based_country[$i]['extra_cost_based_country_cost_title'] = sanitize_text_field($_POST['extra_cost_based_country_cost_title'][$i]);
						$temp_based_country[$i]['extra_cost_based_country_amount'] = sanitize_text_field($_POST['extra_cost_based_country_amount'][$i]);
					}
				}
			}
			$temp_based_country = maybe_serialize($temp_based_country);
			update_option('wc_extra_cost_based_on_country', $temp_based_country);
			

			/**
             * Extra Cost based on shipping class
             */

			if (isset($_POST['ec_shipping_name']) && !empty($_POST['ec_shipping_name'])) {
				$shipping_class_title_array = array();
				$shipping_class_charge_array = array();
				$shipping_class_combine_array = array();
				$shipping_class_title_array = isset($_POST['ec_shipping_name']) ? ($_POST['ec_shipping_name']) : array();
				$shipping_class_charge_array = isset($_POST['ec_shipping_charge']) ? ($_POST['ec_shipping_charge']) : array();
	
				$shipping_class_combine_array = array_combine($shipping_class_title_array, $shipping_class_charge_array);
	
				foreach ($shipping_class_combine_array as $shipping_class_combine_key => $shipping_class_combine_value) {
					$shipping_class_key = 'wc_ec_shipping_class_' . $shipping_class_combine_key;
					if (empty($shipping_class_combine_value) && $shipping_class_combine_value == '') {
						update_option($shipping_class_key, $shipping_class_combine_value);
					} else {
						update_option($shipping_class_key, number_format($shipping_class_combine_value, 2, '.', ''));
					}
				}
	
	
				$wc_ec_max_total_charge = isset($_POST['wc_ec_max_total_charge']) ? sanitize_text_field($_POST['wc_ec_max_total_charge']) : 0;
				$wc_ec_max_total_key = 'wc_ec_max_cart_total';
				if (!empty($wc_ec_max_total_charge) && $wc_ec_max_total_charge != '') {
					update_option($wc_ec_max_total_key, number_format($wc_ec_max_total_charge, 2, '.', ''));
				}
			}
		//}

		/* --------------------------------------------------------------------------------------------------------------------------------------------- */
		

			$ec_variable_product_enable = isset($_POST['ec-variable-product-enable']) ? $_POST['ec-variable-product-enable'] : 'no';
			if (!empty($ec_variable_product_enable) && $ec_variable_product_enable != '') {
				update_option('wc_extra_cost_variable_product', $ec_variable_product_enable);
			}
			$ec_product_enable = isset($_POST['ec-product-enable']) ? $_POST['ec-product-enable'] : 'no';
			if (!empty($ec_product_enable) && $ec_product_enable != '') {
				update_option('wc_extra_cost_cart_product', $ec_product_enable);
			}

			$ec_product_category_enable = isset($_POST['ec-product-category-enable']) ? $_POST['ec-product-category-enable'] : 'no';
			if (!empty($ec_product_category_enable) && $ec_product_category_enable != '') {
				update_option('wc_extra_cost_cart_category_product', $ec_product_category_enable);
			}

			$ec_product_tag_enable = isset($_POST['ec-product-tag-enable']) ? $_POST['ec-product-tag-enable'] : 'no';
			if (!empty($ec_product_tag_enable) && $ec_product_tag_enable != '') {
				update_option('wc_extra_cost_cart_tag_product', $ec_product_tag_enable);
			}


			$ec_product_coupon_enable = isset($_POST['ec-product-coupon-enable']) ? $_POST['ec-product-coupon-enable'] : 'no';
			if (!empty($ec_product_coupon_enable) && $ec_product_coupon_enable != '') {
				update_option('wc_extra_cost_cart_coupon_product', $ec_product_coupon_enable);
			}
			
			/**
             * Below code is used to save data for based product
             */
			$temp_based_product = array();
			$length_based_product = isset($_POST['extra_cost_based_product_cost_title']) ? $_POST['extra_cost_based_product_cost_title'] : '';
			$length_based_product = count($length_based_product);
			for ($i = 0; $i < $length_based_product; $i++) {
				if (!empty($_POST['extra_cost_based_product_cost_title'][$i]) && !empty($_POST['extra_cost_based_product_name'][$i]) && !empty($_POST['extra_cost_based_product_amount'][$i])) {
					$sanitize_val1 = sanitize_text_field($_POST['extra_cost_based_product_cost_title'][$i]);
					$sanitize_val2 = sanitize_text_field($_POST['extra_cost_based_product_name'][$i]);
					$sanitize_val3 = sanitize_text_field($_POST['extra_cost_based_product_amount'][$i]);
					if (!empty($sanitize_val1) && !empty($sanitize_val1) && !empty($sanitize_val1)) {

						$temp_based_product[$i]['extra_cost_based_product_cost_title'] = sanitize_text_field($_POST['extra_cost_based_product_cost_title'][$i]);
						$temp_based_product[$i]['extra_cost_based_product_name'] = sanitize_text_field($_POST['extra_cost_based_product_name'][$i]);
						$temp_based_product[$i]['extra_cost_based_product_amount'] = sanitize_text_field($_POST['extra_cost_based_product_amount'][$i]);
					}
				}
			}
			$temp_based_product = maybe_serialize($temp_based_product);
			update_option('wc_extra_cost_based_on_product', $temp_based_product);

			/* --------------------------------------------------------------------------------------------------------------------------------------------- */

			/**
             * Below code is used to save data for based product category
             */
			$temp_based_product_cat = array();
			$length_based_product_cat = isset($_POST['extra_cost_based_product_category_title']) ? $_POST['extra_cost_based_product_category_title'] : '';
			$length_based_product_cat = count($length_based_product_cat);
			
			for ($i = 0; $i < $length_based_product_cat; $i++) {
				if (!empty($_POST['extra_cost_based_product_category_title'][$i]) && !empty($_POST['extra_cost_based_product_category_name'][$i]) && !empty($_POST['extra_cost_based_product_category_amount'][$i])) {

					$sanitize_val1 = sanitize_text_field($_POST['extra_cost_based_product_category_title'][$i]);
					$sanitize_val2 = sanitize_text_field($_POST['extra_cost_based_product_category_name'][$i]);
					$sanitize_val3 = sanitize_text_field($_POST['extra_cost_based_product_category_amount'][$i]);
					if (!empty($sanitize_val1) && !empty($sanitize_val1) && !empty($sanitize_val1)) {

						$temp_based_product_cat[$i]['extra_cost_based_product_category_title'] = sanitize_text_field($_POST['extra_cost_based_product_category_title'][$i]);
						$temp_based_product_cat[$i]['extra_cost_based_product_category_name'] = sanitize_text_field($_POST['extra_cost_based_product_category_name'][$i]);
						$temp_based_product_cat[$i]['extra_cost_based_product_category_amount'] = sanitize_text_field($_POST['extra_cost_based_product_category_amount'][$i]);
					}
				}
			}
			$temp_based_product_cat = maybe_serialize($temp_based_product_cat);
			update_option('wc_extra_cost_based_on_category', $temp_based_product_cat);

	
		}

		
	}
	
	/**
	 * get Extra cost array
	 *
	 * @since 1.0.0
	 */
	public function get_posted_extra_cost( $key ) {
		$extra_cost_rate     = array();
		$extra_cost_rate_keys = array(
			'extra_cost_country_code',
			'extra_cost_name',
			'extra_cost_value'
		);

		foreach ( $extra_cost_rate_keys as $extra_cost_rate_key ) {
			if ( isset( $_POST[ $extra_cost_rate_key ] ) && isset( $_POST[ $extra_cost_rate_key ][ $key ] ) ) {
				$extra_cost_rate[ $extra_cost_rate_key ] = wc_clean( $_POST[ $extra_cost_rate_key ][ $key ] );
			}
		}
		return $extra_cost_rate;
	}
	
	/**
	 * prepare Extra cost rate
	 *
	 * @since 1.0.0
	 */
	private static function prepare_extra_cost( $extra_cost_rate ) {
		foreach ( $extra_cost_rate as $key => $value ) {
			if ( method_exists( __CLASS__, 'format_' . $key ) ) {
				$extra_cost_rate[ $key ] = call_user_func( array( __CLASS__, 'format_' . $key ), $value );
			}
		}
		return $extra_cost_rate;
	}
	
	/**
	 * insert Extra cost rate
	 *
	 * @since 1.0.0
	 */
	public static function _insert_extra_cost( $extra_cost_rate ) {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'woocommerce_extra_cost', self::prepare_extra_cost( $extra_cost_rate ) );
		return $wpdb->insert_id;
	}
	
	/**
	 * delete Extra cost
	 *
	 * @since 1.0.0
	 */
	public static function _delete_extra_cost( $extra_cost_id ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_extra_cost WHERE extra_cost_rate_id = %d;", $extra_cost_id ) );
	}
	
	/**
	 * update Extra cost rate
	 *
	 * @since 1.0.0
	 */
	public static function _update_extra_cost( $extra_cost_id, $extra_cost_rate ) {
		global $wpdb;

		$extra_cost_id = absint( $extra_cost_id );

		$wpdb->update(
			$wpdb->prefix . "woocommerce_extra_cost",
			self::prepare_extra_cost( $extra_cost_rate ),
			array(
				'extra_cost_rate_id' => $extra_cost_id
			)
		);
	}
}