<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woo_Extra_Cost
 * @subpackage Woo_Extra_Cost/public
 * @author     Multidots <wordpress@multidots.com>
 */
class Woo_Extra_Cost_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

	/**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
	private $version;

	/**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
	public function __construct($plugin_name, $version) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->ace_charge_type = get_option('aec_charge_type');
		$this->ser_aec_charge_type = maybe_unserialize($this->ace_charge_type);
		$this->aec_charge_type_country = $this->ser_aec_charge_type['country'];
		$this->aec_charge_type_user_role= $this->ser_aec_charge_type['user_role'];
		$this->aec_charge_type_shipping= $this->ser_aec_charge_type['shipping'];
		$this->aec_charge_type_cart_total= $this->ser_aec_charge_type['cart_total'];
		$this->aec_charge_type_cart_quantity= $this->ser_aec_charge_type['cart_quantity'];
		$this->aec_charge_type_cart_weight= $this->ser_aec_charge_type['cart_weight'];
		$this->aec_charge_type_product= $this->ser_aec_charge_type['product'];
		$this->aec_charge_variabl_product= $this->ser_aec_charge_type['variabl_product'];
		$this->aec_charge_cat_product= $this->ser_aec_charge_type['cat_product'];
		$this->aec_charge_tag_product = $this->ser_aec_charge_type['tag_product'];
		$this->aec_charge_coupon_product = $this->ser_aec_charge_type['coupon_product'];
		$this->aec_charge_based_paymentgateway = $this->ser_aec_charge_type['based_paymentgateway'];
	}

	/**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
	public function enqueue_styles() {

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/woo-extra-cost-public.css', array(), $this->version, 'all');
	}

	/**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
	public function enqueue_scripts() {

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/woo-extra-cost-public.js', array('jquery'), $this->version, false);
	}

	/**
     * WooCommerce Extra Feature
     * --------------------------
     *
     * Add custom fee to cart automatically
     *
     */
	function woo_add_cart_fee() {

		global $woocommerce, $post;
		if (is_admin() && !defined('DOING_AJAX'))
		return;

		foreach ($woocommerce->cart->cart_contents as $key => $values) {
			$is_enable_extra_cost = get_post_meta($values['product_id'], '_extra_cost_enable', true);
			$is_extra_cost_enable_qty = get_post_meta($values['product_id'], '_extra_cost_enable_qty', true);
			$is_extra_cost_disable_qty = get_post_meta($values['product_id'], '_extra_cost_amount_quatity', true);

			if ((isset($is_enable_extra_cost) && !empty($is_enable_extra_cost)) && (isset($is_extra_cost_enable_qty) && !empty($is_extra_cost_enable_qty))) {
				$extra_cost_price = get_post_meta($values['product_id'], '_extra_cost_amount', true);
				$extra_cost = $extra_cost_price * $values['quantity'];
			}
			if ((isset($is_enable_extra_cost) && !empty($is_enable_extra_cost)) && (isset($is_extra_cost_enable_qty) && empty($is_extra_cost_enable_qty) )) {
				$extra_cost_price = get_post_meta($values['product_id'], '_extra_cost_amount', true);
				$extra_cost = $extra_cost_price;
			}

			if ((isset($is_extra_cost_disable_qty) && !empty($is_extra_cost_disable_qty)) && empty($is_extra_cost_enable_qty) && $values['quantity'] > 1) {
				$extra_cost_price = get_post_meta($values['product_id'], '_extra_cost_amount', true);
				$extra_cost_quantity_prise = get_post_meta($values['product_id'], '_extra_cost_amount_quatity', true);
				$quantity = $values['quantity'] - 1;
				$extra_cost_data = $extra_cost_quantity_prise * $quantity;
				$extra_cost = $extra_cost_price + $extra_cost_data;
			}

			if (isset($is_enable_extra_cost) && $is_enable_extra_cost != '' && !empty($is_enable_extra_cost)) {
				$extra_cost_lable = get_post_meta($values['product_id'], '_extra_cost_label', true);
				$woocommerce->cart->add_fee(apply_filters('extra_feature_extra_cost_name', $extra_cost_lable . ': ' . get_the_title($values['product_id'])), $extra_cost, false, 'standard');
			}
		}
	}

	/**
     * WooCommerce Extra Feature
     * Extra Cost Based on Country
     * --------------------------
     *
     * Add custom fee to cart automatically
     *
     */
	function woo_add_cart_fee_based_on_country_free () {
		global $woocommerce, $post, $wpdb;
		if (is_admin() && !defined('DOING_AJAX'))
		return;
		$country_extra_cost = get_option('wc_extra_cost_country');
		//$country_aec_charge_type = get_option('aec_charge_type');

		if (isset($country_extra_cost) && !empty($country_extra_cost) && $country_extra_cost == 'yes') {
			$cart_sub_total = $woocommerce->cart->subtotal;
			$max_total_option_val = get_option('wc_extra_cost_max_total');
			$extra_cost_cart_max_total = get_option('wc_ec_max_cart_total');

			$get_wc_extra_cost_based_on_country = get_option('wc_extra_cost_based_on_country');
			$get_wc_extra_cost_based_on_country = maybe_unserialize($get_wc_extra_cost_based_on_country);
			$selected_country = $woocommerce->customer->get_shipping_country();
			foreach ($get_wc_extra_cost_based_on_country as $key) {
				if (strtoupper($key['extra_cost_based_country_code']) == $selected_country) {
					$extraCostLabel = $key['extra_cost_based_country_cost_title'];
					//get Extra cost

					if (isset($this->ser_aec_charge_type) && !empty($this->ser_aec_charge_type)) {
						if ($this->aec_charge_type_country =='per') {
							$surcharge = ($cart_sub_total * $key['extra_cost_based_country_amount']) / 100;
						}else if ($this->aec_charge_type_country  =='fix') {
							$surcharge = $key['extra_cost_based_country_amount'];
						}
					}else {
						$surcharge = $key['extra_cost_based_country_amount'];
					}


					//Add Extra fee in cart page.

					if (isset($max_total_option_val) && !empty($max_total_option_val) && $max_total_option_val == 'yes') {

						if (!($cart_sub_total >= $extra_cost_cart_max_total)) {
							$woocommerce->cart->add_fee($extraCostLabel, $surcharge, false, '');
						}
					} else if (isset($max_total_option_val) && !empty($max_total_option_val) && $max_total_option_val == 'no') {
						$woocommerce->cart->add_fee($extraCostLabel , $surcharge, false, '');
					}
				}
			}
		} else {

		}
	}

	


	/**
     * Extra Cost Based On Product
     * ---------------------------
     * 
     * Create function woo_add_cart_fee_based_on_product
     */
	function woo_add_cart_fee_based_on_product_free() {
		global $woocommerce, $post, $wpdb;
		if (is_admin() && !defined('DOING_AJAX'))
		return;
		/**
         * get wc_settings_extra_cost_product option 
         */
		$cart_product_extra_cost = get_option('wc_extra_cost_cart_product');
		$cart_sub_total = $woocommerce->cart->subtotal;
		if (isset($cart_product_extra_cost) && !empty($cart_product_extra_cost) && $cart_product_extra_cost == 'yes') {

			$product_array = array();


			$wc_extra_cost_based_on_product = get_option('wc_extra_cost_based_on_product');
			$wc_extra_cost_based_on_product = maybe_unserialize($wc_extra_cost_based_on_product);

			$max_total_option_val = get_option('wc_extra_cost_max_total');
			$extra_cost_cart_max_total = get_option('wc_ec_max_cart_total');

			foreach ($wc_extra_cost_based_on_product as $key =>$val) {
				$product_array[$key] = $val['extra_cost_based_product_name'];
			}

			$cart_product_array = $woocommerce->cart->get_cart();
			$product_title = '';
			foreach ($cart_product_array as $item => $values) {
				$cart_product_id = $values['data']->post;
				$product_title = $cart_product_id->post_title;
				if (in_array($cart_product_id->ID, $product_array)) {
					$key = array_search($cart_product_id->ID, $product_array);
					$find_value = $wc_extra_cost_based_on_product[$key];
					$extraCostLabel = $find_value['extra_cost_based_product_cost_title'];
					//get Extra cost
					//$surcharge = $find_value['extra_cost_based_product_amount'];

					if (isset($this->ser_aec_charge_type) && !empty($this->ser_aec_charge_type)) {
						if ($this->aec_charge_type_product =='per') {
							$surcharge = ($cart_sub_total * $find_value['extra_cost_based_product_amount']) / 100 ;
						}else if ($this->aec_charge_type_product =='fix') {
							$surcharge = $find_value['extra_cost_based_product_amount'];
						}
					}else {
						$surcharge = $find_value['extra_cost_based_product_amount'];
					}


					//Add Extra fee in cart page.
					if (isset($max_total_option_val) && !empty($max_total_option_val) && $max_total_option_val == 'yes') {
						if (!($cart_sub_total >= $extra_cost_cart_max_total)) {
							$woocommerce->cart->add_fee($extraCostLabel, $surcharge, false, '');
						}
					} else if (isset($max_total_option_val) && !empty($max_total_option_val) && $max_total_option_val == 'no') {
						$woocommerce->cart->add_fee($extraCostLabel , $surcharge, false, '');
					}
				}
			}
		}
	}

	/**
     * Extra Cost Based On Product Category
     * -------------------------------------
     * 
     * Create Function woo_add_cart_fee_based_on_product_category
     */
	function woo_add_cart_fee_based_on_product_category_free() {
		global $woocommerce, $post, $wpdb;
		if (is_admin() && !defined('DOING_AJAX'))
		return;
		/**
         * get wc_settings_extra_cost_product_cat
         */
		$cart_product_extra_cost_cat = get_option('wc_extra_cost_cart_category_product');

		if (isset($cart_product_extra_cost_cat) && !empty($cart_product_extra_cost_cat) && $cart_product_extra_cost_cat == 'yes') {

			$product_cat_array = array();

			$wc_extra_cost_based_on_category = get_option('wc_extra_cost_based_on_category');
			$wc_extra_cost_based_on_category = maybe_unserialize($wc_extra_cost_based_on_category);

			foreach ($wc_extra_cost_based_on_category as $key) {
				$product_cat_array[] = $key['extra_cost_based_product_category_name'];
			}

			$cart_product_array = $woocommerce->cart->get_cart();
			$product_title = '';
			$cart_sub_total = $woocommerce->cart->subtotal;
			$max_total_option_val = get_option('wc_extra_cost_max_total');
			$extra_cost_cart_max_total = get_option('wc_ec_max_cart_total');


			foreach ($woocommerce->cart->get_cart() as $cart_item_key => $values) {
				$_product = $values['data'];
				$terms = get_the_terms($_product->id, 'product_cat');

				// second level loop search, in case some items have several categories

				$_categoryid = array();
				if (isset($terms) && !empty($terms)) {
					foreach ($terms as $term) {
						$_categoryid[] = $term->term_id;
					}
				}
				if (isset($_categoryid) && !empty($_categoryid)) {
					foreach ($_categoryid as $keycatid) {
						if (in_array($keycatid, $product_cat_array)) {
							$key = array_search($keycatid, $product_cat_array);
							$find_value = $wc_extra_cost_based_on_category[$key];
							$extraCostLabel = $find_value['extra_cost_based_product_category_title'];
							//get Extra cost
							//$surcharge = $find_value['extra_cost_based_product_category_amount'];


							if (isset($this->ser_aec_charge_type) && !empty($this->ser_aec_charge_type)) {
								if ($this->aec_charge_cat_product =='per') {
									$surcharge = ($cart_sub_total * $find_value['extra_cost_based_product_category_amount']) / 100 ;
								}else if ($this->aec_charge_cat_product =='fix') {
									$surcharge = $find_value['extra_cost_based_product_category_amount'];
								}
							}else {
								$surcharge = $find_value['extra_cost_based_product_amount'];
							}

							//Add Extra fee in cart page.
							if (isset($max_total_option_val) && !empty($max_total_option_val) && $max_total_option_val == 'yes') {
								if (!($cart_sub_total >= $extra_cost_cart_max_total)) {
									$woocommerce->cart->add_fee($extraCostLabel, $surcharge, false, '');
								}
							} else if (isset($max_total_option_val) && !empty($max_total_option_val) && $max_total_option_val == 'no') {
								$woocommerce->cart->add_fee($extraCostLabel, $surcharge, false, '');
							}
						}
					}//
				}
			}
		}
	}
	

	/**
     * BN code added
     */
	function paypal_bn_code_filter_wc_extra_cost_free ($paypal_args) {
		$paypal_args['bn'] = 'Multidots_SP';
		return $paypal_args;
	}

}
