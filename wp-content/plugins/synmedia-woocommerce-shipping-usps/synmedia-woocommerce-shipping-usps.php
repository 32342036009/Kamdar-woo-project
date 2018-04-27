<?php
/*
	Plugin Name: WooMedia WooCommerce USPS
	Plugin URI: http://www.woomedia.info/en/plugins/usps-shipping-method-for-woocommerce
	Description: Automatic Shipping Calculation using the USPS Shipping API for WooCommerce
	Version: 2.2.6
	Author: WooMedia Inc.
	Author URI: http://www.woomedia.info
	Requires at least: 4.0
	Tested up to: 4.7.5
	
	Copyright: © 2012-2017 WooMedia Inc.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


require_once("syn-includes/syn-functions.php");
require_once("syn-shipping/syn-functions.php");

function syn_usps_update_init(){
	$syn_update = new SYN_Auto_Update( get_plugin_data(__FILE__), plugin_basename( __FILE__ ), '4507629', 'kiI3NCs2M8mDBSzQIDoHBYjp0' );
}
add_action('admin_init', 'syn_usps_update_init', 11);

define('USPS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ));

function syn_usps_activate(){
	syn_clear_transients( 'usps' );
}
register_activation_hook( __FILE__, 'syn_usps_activate' );

/**
 * Localisation
 */
load_plugin_textdomain( 'syn_usps', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages/' );

/**
 * Check if WooCommerce is active
 */
if ( is_woo_enabled() ) {
	/**
	 * woocommerce_init_shipping_table_rate function.
	 *
	 * @access public
	 * @return void
	 */
	function syn_usps_init() {
		if ( ! class_exists( 'SYN_Shipping_USPS' ) )
			include_once( 'classes/class-syn-shipping-usps.php' );
		
		$met = new SYN_Shipping_USPS();
		if( $met->debug && $met->is_enabled() ){
			wp_register_style( 'syn-debug', plugins_url( 'assets/css/debug.css', __FILE__ ) );
			wp_enqueue_style( 'syn-debug' );
		}
	}

	add_action( 'woocommerce_shipping_init', 'syn_usps_init' );
	add_action( 'init', 'syn_usps_init', 1 );

	/**
	 * syn_usps_add_method function.
	 *
	 * @access public
	 * @param mixed $methods
	 * @return void
	 */
	function syn_usps_add_method( $methods ) {
		$methods[] = 'SYN_Shipping_USPS';
		return $methods;
	}

	add_filter( 'woocommerce_shipping_methods', 'syn_usps_add_method' );

	/**
	 * Display a notice ...
	 * @return void
	 */
	function syn_usps_notices() {
	
		global $woocommerce;
	
		if ( ! class_exists( 'SYN_Shipping_USPS' ) )
			include_once( 'classes/class-syn-shipping-usps.php' );
			
		$missings = array();
	
		$usps = new SYN_Shipping_USPS();
		
		if( empty($usps->origin_postalcode) ){
			
			$missings[] = "Origin zip code";
			
		}
		
		if( empty($usps->user_id) ){
			
			$missings[] = "User ID";
			
		}
		
		if( empty( $missings ) )
			return false;
		
		$url = self_admin_url( 'admin.php?page=' . ( version_compare($woocommerce->version, '2.1.0') >= 0 ? 'wc-settings' : 'woocommerce_settings' ) . '&tab=shipping&section=syn_shipping_usps' );

		$message = sprintf( __( 'USPS error, some fields are missing: %s' , 'syn_usps' ), implode( ", ", $missings ) );

		echo '<div class="error fade"><p><a href="' . $url . '">' . $message . '</a></p></div>' . "\n";
	
	}

	add_action( 'admin_notices', 'syn_usps_notices' );
	
	/**
	 * Show action links on the plugin screen
	 */
	function syn_usps_action_links( $links ) {
		return array_merge( array(
			'<a href="' . admin_url( 'admin.php?page=' . ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ? 'wc-settings' : 'woocommerce_settings' ) . '&tab=shipping&section=syn_shipping_usps' ) . '">' . __( 'Settings', 'syn_usps' ) . '</a>'
		), $links );
	}
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'syn_usps_action_links' );

}

?>