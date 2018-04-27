<?php
/**
 * SYN_Shipping_USPS class.
 *
 * @extends SYN_Shipping_Method
 */
class SYN_Shipping_USPS extends SYN_Shipping_Method {
	
	private $uris = array(
		'http://production.shippingapis.com/shippingapi.dll' => 'Production'
	);
	
	private $request = 'API=#TYPE#&XML=#XML#';
	
	private $services = array(
		//Domestic shipping based on Service tag ALL
		"RateV4-0"  => "First-Class Mail&#0174; Parcel",
		"RateV4-1"  => "Priority Mail&#0174;",
		"RateV4-2"  => "Express Mail&#0174; Hold for Pickup",
		"RateV4-3"  => "Express Mail&#0174; PO to Address",
		"RateV4-4"  => "Standard Post&#8482;",
		"RateV4-12" => "First-Class&#8482; Postcard Stamped",
		"RateV4-15" => "First-Class&#8482; Large Postcards",
		"RateV4-18" => "Priority Mail&#0174; Keys and IDs",
		"RateV4-19" => "First-Class&#8482; Keys and IDs",
		"RateV4-23" => "Express Mail&#0174; Sunday/Holiday",

		//International shipping based on Service tag ALL
		"IntlRateV2-1"  => "Express Mail International&#0174;",
		"IntlRateV2-2"  => "Priority Mail International&#0174;",
		"IntlRateV2-4"  => "Global Express Guaranteed&#0174;",
		"IntlRateV2-5"  => "Global Express Guaranteed&#0174; Document used",
		"IntlRateV2-6"  => "Global Express Guaranteed&#0174; Non-Document Rectangular",
		"IntlRateV2-7"  => "Global Express Guaranteed&#0174; Non-Document Non-Rectangular",
		"IntlRateV2-12" => "Global Express Guaranteed&#0174; Envelope",
		"IntlRateV2-13" => "First Class Package Service&#8482; International Letters",
		"IntlRateV2-14" => "First Class Package Service&#8482; International Flats",
		"IntlRateV2-15" => "First Class Package Service&#8482; International Parcel"
	);
	
	private $types = array(
		'RateV4' => array(
			'services'	=> 'Postage',
			'id'		=> 'CLASSID',
			'name'		=> 'MailService',
			'rate'		=> 'Rate'
		),
		'IntlRateV2' =>  array(
			'services'	=> 'Service',
			'id'		=> 'ID',
			'name'		=> 'SvcDescription',
			'rate'		=> 'Postage'
		)
	);
	
	private $domestic = array(
		"US", "PR", "VI"
	);
	
	private $default_user_id = "257SYNME8095";
	
	protected $boxes;
	
	private $crates;
	
	private $package;
	
	/* New var for tracking */
	protected $tracking_url = 'https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1=%s';

	public function __construct() {
		$this->id                 = 'usps';
		$this->method_title       = __( 'USPS', 'syn_usps' );
		$this->method_description = '';
		$this->init();
	}

	private function init(){
		global $woocommerce;
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
		
		$this->crates = array();
		$this->enabled				= isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : $this->enabled;
		$this->title				= $this->get_var( 'title' );
		$this->debug				= $this->get_var( 'debug' );
		$this->availability			= $this->get_var( 'availability' );
		$this->countries			= $this->get_var( 'countries', array() );
		$this->origin_postalcode	= $this->get_var( 'origin_postalcode' );
		$this->uri					= key( $this->uris );
		$this->user_id				= $this->get_var( 'user_id' );
		$this->packing_method		= $this->get_var( 'packing_method' );
		$this->fee					= $this->get_var( 'fee' );
		$this->shipping_methods		= $this->get_var( 'shipping_methods', array() );
		$this->custom_methods		= $this->get_var( 'custom_methods', array() );
		$this->boxes				= $this->get_var( 'boxes', array() );
		
		if( empty( $this->custom_methods ) && !empty( $this->services ) ){
			
			foreach( $this->services as $method_key => $method_name ){
				
				$this->custom_methods[ $method_key ] = array(
					'name'				=> woocommerce_clean( $method_name ),
					'price_ajustment'	=> '',
					'enabled'			=> ( ( isset( $this->settings['shipping_methods'] ) && array_search( $method_key, $this->settings['shipping_methods'] ) !== false ) || !isset( $this->settings['shipping_methods'] ) || empty( $this->settings['shipping_methods'] ) ? '1' : '0' )
				);
				
			}
			
		}
		
		// Used for weight based packing only
		$this->max_weight = '150';
		
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'clear_transients' ) );
		
		parent::__construct();
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		global $woocommerce;

		$this->form_fields = array(
		    'enabled' => array(
				'title'			=> __('Enable/Disable', 'syn_usps'),
				'type'			=> 'checkbox',
				'label'			=> __('Enable USPS', 'syn_usps'),
				'default'		=> 'no'
		    ),
		    'title' => array(
				'title'			=> __('Method title', 'syn_usps'),
				'type'			=> 'text',
				'description'	=> __('Enter the title of the shipping method.', 'syn_usps'),
				'default'		=> __('USPS', 'syn_usps')
		    ),
		    'debug' => array(
				'title'			=> __('Debug Mode', 'syn_usps'),
				'label'			=> __('Enable Debug Mode', 'syn_usps'),
				'type'			=> 'checkbox',
				'description'	=> __('Output the response from USPS on the cart/checkout for debugging purposes.', 'syn_usps'),
				'default'		=> 'no'
		    ),
		    'availability' => array(
				'title'			=> __( 'Method Availability', 'syn_usps' ),
				'type'			=> 'select',
				'default'		=> 'all',
				'class'			=> 'availability',
				'options'		=> array(
					'all'			=> __( 'All Countries', 'syn_usps' ),
					'specific'		=> __( 'Specific Countries', 'syn_usps' )
				)
			),
			'countries' => array(
				'title'			=> __( 'Specific Countries', 'syn_usps' ),
				'type'			=> 'multiselect',
				'class'			=> 'chosen_select',
				'css'			=> 'width: 450px;',
				'default'		=> '',
				'options'		=> $woocommerce->countries->get_allowed_countries()
			),
		    'origin_postalcode' => array(
				'title'			=> __('Origin Zip code', 'syn_usps'),
				'type'			=> 'text',
				'description'	=> __('Enter your origin zip code.', 'syn_usps'),
				'default'		=> ''
		    ),
		    'api' => array(
				'title'			=> __( 'API Settings', 'syn_usps' ),
				'type'			=> 'title',
				'description'	=> __( 'Your API access details', 'syn_usps' )
		    ),
		    'user_id' => array(
				'title'			=> __('User ID', 'syn_usps'),
				'type'			=> 'text',
				'css'			=> 'width: 250px;',
				'description'	=> __('Your USPS user ID', 'syn_usps'),
				'default'		=> $this->default_user_id
		    ),
			'packing_method' => array(
				'title'			=> __( 'Parcel Packing Method', 'syn_usps' ),
				'type'			=> 'select',
				'default'		=> 'per_item',
				'class'			=> 'packing_method',
				'options'		=> array(
					'per_item'		=> __( 'Default: Pack items individually', 'syn_usps' ),
					'weight_based'	=> __( 'Weight of all', 'syn_usps' ),
					'box_packing'	=> __( 'Box packing (Most accurate quotes)', 'syn_usps' )
				),
				'desc_tip'		=> __( 'Weight: Regular sized products (< 12 inches) are grouped and quoted for weights only. Large items are quoted individually.', 'syn_usps' )
			),
		    'fee' => array(
				'title'			=> __('Handling Fee', 'syn_usps'),
				'type'			=> 'text',
				'description'	=> __('Fee excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Leave blank for no fee.', 'syn_usps'),
				'default'		=> '0'
		    ),
		    'custom_methods' => array(
				'type'			=> 'custom_methods'
		    ),
		    'boxes'	=> array(
				'type'			=> 'box_packing'
			)
		);
	}
	
	/**
	 * validate_address function. Used USPS address verification
	 *
	 * @access public
	 * @param mixed $address
	 * @return $address
	 */
	public function validate_address( $address ){
		global $woocommerce;
		
		if( $address[ 'country' ] == 'US' && strstr( $address[ 'postalcode' ], '-' ) )
			$address[ 'postalcode' ] = current( explode( '-', $address[ 'postalcode' ] ) );
		
		return $address;
	}

	/**
	 * Send request and retrieve the result.
	 */
	public function get_shipping_request( $package ) {
		global $woocommerce;
		
		$responses = array();
		
		$service_option = '';
		
		$domestic = in_array( $package['destination']['country'], $this->domestic ) ? true : false;
		$rate_type = $domestic ? 'RateV4' : 'IntlRateV2';
		
		$this->add_notice( sprintf( __( '%s: Enter shipping request function.', 'syn_dicom' ), $this->method_title ) );
		
		$address = $this->validate_address(array('city'=>$package['destination']['city'],'province'=>$package['destination']['state'],'country'=>$package['destination']['country'],'postalcode'=>$package['destination']['postcode']));
		
		if( empty($address['country']) || empty($address['postalcode']) )
			return false;
		
		$this->set_package_requests( $package, $domestic );
		
		//Shipping details and xml filling
		
		$xml = file_get_contents(USPS_PATH.'/xml/request.xml');
		
		$xml = str_replace( '#USERID#', $this->user_id, $xml);
		$xml = str_replace( '#TYPE#', $rate_type, $xml);
		
		//Get all packages
		$xml = str_replace( '#PACKAGE#', $this->package, $xml);
		
		$xml = str_replace( '#POSTALCODE#', current( explode( '-', $this->origin_postalcode ) ), $xml);
		$xml = str_replace( '#TOPOSTALCODE#', current( explode( '-', $address['postalcode'] ) ), $xml);
		$xml = str_replace( '#TOCOUNTRY#', $this->get_country_name( $address['country'] ), $xml);
		$xml = str_replace( '#SHIPDATE#', date('d-M-Y'), $xml);
	
		$this->add_notice( sprintf( __( '%s: URI: %s, Send XML: <pre class="syn-debug">%s</pre>', 'syn_usps' ), $this->method_title, $this->uri, print_r( htmlspecialchars( $xml ), true ) ) );
			
		$this->request = str_replace( '#TYPE#', $rate_type, $this->request );
		$this->request = str_replace( '#XML#', str_replace( array( "\n", "\r", "	" ), '', $xml ), $this->request );
		
		$response = wp_remote_post( $this->uri,
    		array(
				'timeout'   => 70,
				'sslverify' => 0,
				'body'      => $this->request
		    )
		);
		
		$this->add_notice( sprintf( __( '%s: Response XML: <pre class="syn-debug">%s</pre>', 'syn_usps' ), $this->method_title, print_r( htmlspecialchars( $response['body'] ), true ) ) );
		
		//Loop throught responses
		$response = json_decode( json_encode( simplexml_load_string( $response[ 'body' ], "SimpleXMLElement", LIBXML_NOCDATA ) ), true );
		
		if( ! $response )
			$this->add_notice( sprintf( __( '%s: Failed loading XML', 'syn_usps' ), $this->method_title ) , 'error' );
		
		if( isset( $response[ 'Package' ] ) ){
			
			if( isset( $response[ 'Package' ][ '@attributes' ] ) ){
				$packages = array( $response[ 'Package' ] );
			}else{
				$packages = $response[ 'Package' ];
			}
			
			foreach( $packages as $package ){
				
				$qty = ( ( $pos = strrpos( $package[ '@attributes' ][ 'ID' ], ':' ) ) === false ) ? 1 : substr( $package[ '@attributes' ][ 'ID' ], $pos + 1 );
				
				if( isset( $package[ $this->types[ $rate_type ][ 'services' ] ] ) ){
					
					foreach( $package[ $this->types[ $rate_type ][ 'services' ] ] as $service ){
				
						$core_id = $service[ '@attributes' ][ $this->types[ $rate_type ][ 'id' ] ];
						$method_id = $rate_type . '-' . $core_id;
						$plain_title = strip_tags( htmlspecialchars_decode( str_replace( '*', '', (string) $service[ $this->types[ $rate_type ][ 'name' ] ] ) ) );
						$service_name = ! empty( $this->custom_methods[ $method_id ][ 'name' ] ) ? $this->custom_methods[ $method_id ][ 'name' ] : $plain_title;
						$rate_cost = (float) $service[ $this->types[ $rate_type ][ 'rate' ] ] * $qty;
						
						if( !isset( $this->custom_methods[ $method_id ] ) || ( isset( $this->custom_methods[ $method_id ] ) && !$this->custom_methods[ $method_id ][ 'enabled' ] ) || ( $core_id == '0' && stripos( $plain_title, 'parcel' ) === false ) )
							continue;
						
						$this->combine_estimate(array(
							'code'	=> $method_id,
							'id' 	=> $this->id . ':' . $method_id,
							'label' => $this->title . ' ' . $service_name,
							'cost' 	=> $rate_cost
						));
						
					}
					
				}
				
			}
			
			$this->reorder_crates();
			
			if( ! empty( $this->crates ) ){
			
				foreach( $this->crates as $rate ){
					$this->add_this_estimate( $rate );
				}
				
			}
			
			$this->add_notice( sprintf( __( '%s: All was good!', 'syn_usps' ), $this->method_title ) );
			
		}else if( isset( $response[ 'Number' ] ) && isset( $response[ 'Description' ] ) ){
		
			$this->check_xml_errors( $response );
			
		} else {
			
			$this->add_notice( sprintf( __( '%s: No rates returned - ensure you have defined product dimensions and weights.', 'syn_usps' ), $this->method_title ) );
			
		}
		
		return ! empty( $this->rates );
	}
	
	public function reorder_crates(){
		
		$crates = $this->crates;
		$this->crates = array();
		
		if( ! empty( $this->custom_methods ) ){
		
			foreach( $this->custom_methods as $method_key => $service ){
				
				if( isset( $crates[ $method_key ] ) )
					$this->crates[] = $crates[ $method_key ];
				
			}
			
		}
		
	}
	
	public function combine_estimate( $estimate ){
	
		if( ! isset( $this->crates[ $estimate['code'] ] ) ){
			$this->crates[ $estimate['code'] ] = $estimate;
		}else{
			$this->crates[ $estimate['code'] ]['cost'] += $estimate['cost'];
		}
		
	}
	
	public function add_this_estimate( $estimate ){
		global $woocommerce;
		
		if( !empty( $this->custom_methods[ $estimate['code'] ][ 'price_ajustment' ] ) ){
			$estimate['cost'] = $estimate['cost'] + $this->get_fee( $this->custom_methods[ $estimate['code'] ][ 'price_ajustment' ], $estimate['cost'] );
		}
		unset( $estimate[ 'code' ] );

		if( !empty( $this->fee ) ) {
			$estimate[ 'cost' ] = $estimate[ 'cost' ] + $this->get_fee($this->fee, $estimate[ 'cost' ]);
		}
		
		$this->add_rate( $estimate );
	}
	
	private function check_xml_errors( $error ){
	
		global $woocommerce;
			
		switch( $error[ 'Number' ] ){
		
			case '80040b1a':
				if( $error[ 'Description' ] == 'Authorization failure.  You are not authorized to connect to this server.' ){
					$this->add_notice( sprintf( __( '%s: Authorization failure. You must contact USPS to enable your User ID with their server.', 'syn_usps' ), $this->method_title, $error[ 'Description' ] ) , 'error' );
				}else{
					$this->add_notice( sprintf( __( '%s: %s', 'syn_usps' ), $this->method_title, $error[ 'Description' ] ) , 'error' );
				}
				break;
				
			default:
				$this->add_notice( sprintf( __( '%s: Error.<br />Error number: %s<br />Description: %s', 'syn_usps' ), $this->method_title, $error[ 'Number' ], $error[ 'Description' ] ) , 'error' );
				break;
			
		}
		
	}

	/**
	 * Shipping method available condition:
	 * 1. Set to yes
	 * 2. Origin country is CA
	 * 3. Dest country is in the list
	 * 
	 * @global type $woocommerce
	 * @return type 
	 */
	public function is_available( $package ) {
		global $woocommerce;

		if (empty($this->origin_postalcode))
			return false;

		return parent::is_available( $package );
	}
	
	private function environment_check() {
		global $woocommerce;
		?>
		<p><?php echo(sprintf(__('You must have a User ID to calculate USPS Shipping, <a href="%s" target="_blank">click here</a> to register an account with USPS.', 'syn_usps'), 'https://www.usps.com/business/web-tools-apis/welcome.htm')); ?></p>
		<?php
	}

	public function admin_options() {
	
		$this->environment_check();
		
		parent::admin_options();
	}
	
    private function set_package_requests( $package, $domestic ) {

	    // Choose selected packing
    	switch ( $this->packing_method ) {
	    	case 'per_item' :
	    	default :
	    		$this->per_item_shipping( $package, $domestic );
				break;
	    	case 'weight_based' :
	    		$this->weight_based_shipping( $package, $domestic );
				break;
	    	case 'box_packing':
	    		$this->box_shipping( $package, $domestic );
	    		break;
    	}
    	
    }
    
    /**
     * Generate shipping request for weights only
     */
    private function weight_based_shipping( $package, $domestic ) {
    	global $woocommerce;

		$total_regular_item_values = 0;
		$total_regular_item_weight = 0;

    	// Add requests for larger items
    	foreach ( $package['contents'] as $item_id => $values ) {
    	
    		if( !$values['data']->needs_shipping() ){
   				$this->add_notice( sprintf( __( 'Product #%d is virtual. Skipping.', 'syn_usps' ), $values[ 'product_id' ] ) );
    			continue;
    		}

    		if( !$values['data']->get_weight() ){
    			$this->add_notice( sprintf( __( 'Product <a href="%s" target="_blank">#%d</a> is missing weight. Aborting %s quotes.', 'syn_usps' ), get_edit_post_link( $values[ 'product_id' ] ), $values[ 'product_id' ], $this->method_title ), 'error' );
    			$total_regular_item_weight = 0;
    			$this->package = "";
	    		return;
    		}

    		$weight = ( function_exists( 'wc_get_weight' ) ? wc_get_weight( $values['data']->get_weight(), 'lbs' ) : woocommerce_get_weight( $values['data']->get_weight(), 'lbs' ) );

    		if ( $values['data']->get_length() < 12 && $values['data']->get_height() < 12 && $values['data']->get_width() < 12 ) {
    			$total_regular_item_weight += ( $weight * $values['quantity'] );
    			$total_regular_item_values += $values['data']->get_price() * $values['quantity'];
    			continue;
    		}

			$dimensions = array( ( function_exists( 'wc_get_dimension' ) ? wc_get_dimension( $values['data']->get_length(), 'in' ) : woocommerce_get_dimension( $values['data']->get_length(), 'in' ) ), ( function_exists( 'wc_get_dimension' ) ? wc_get_dimension( $values['data']->get_height(), 'in' ) : woocommerce_get_dimension( $values['data']->get_height(), 'in' ) ), ( function_exists( 'wc_get_dimension' ) ? wc_get_dimension( $values['data']->get_width(), 'in' ) : woocommerce_get_dimension( $values['data']->get_width(), 'in' ) ) );

			sort( $dimensions );

			$girth = $dimensions[0] + $dimensions[0] + $dimensions[1] + $dimensions[1];
			
			if( $domestic ){
				$piece = file_get_contents(USPS_PATH.'/xml/package.xml');
			}else{
				$piece = file_get_contents(USPS_PATH.'/xml/package_intl.xml');
			}

			$piece = str_replace('#ID#', $item_id.':'.$values['quantity'], $piece);
			$piece = str_replace('#VALUE#', $values['data']->get_price()  * $values['quantity'], $piece);
			$piece = str_replace('#SIZE#', 'LARGE', $piece);
			$piece = str_replace('#LENGTH#', $dimensions[2], $piece);
			$piece = str_replace('#WIDTH#', $dimensions[1], $piece);
			$piece = str_replace('#HEIGHT#', $dimensions[0], $piece);
			$piece = str_replace('#GIRTH#', round($girth), $piece);
			$piece = str_replace('#POUNDS#', floor( $weight ), $piece);
			$piece = str_replace('#OUNCES#', number_format( ( $weight - floor( $weight ) ) * 16, 2 ), $piece);

			$this->package .= $piece;
    	}

    	// Regular package
    	if ( $total_regular_item_weight > 0 ) {
    		$max_package_weight = ( $domestic || $package['destination']['country'] == 'MX' ) ? 70 : 44;
    		$package_weights    = array();

    		$full_packages      = floor( $total_regular_item_weight / $max_package_weight );
    		for ( $i = 0; $i < $full_packages; $i ++ )
    			$package_weights[] = $max_package_weight;

    		if ( $remainder = fmod( $total_regular_item_weight, $max_package_weight ) )
    			$package_weights[] = $remainder;

    		foreach ( $package_weights as $key => $weight ) {
    		
    			if( $domestic ){
					$piece = file_get_contents(USPS_PATH.'/xml/weight_package.xml');
				}else{
					$piece = file_get_contents(USPS_PATH.'/xml/weight_package_intl.xml');
				}
				
				$piece = str_replace('#ID#', 'regular_' . $key . ':1', $piece);
				$piece = str_replace('#SIZE#', 'REGULAR', $piece);
				$piece = str_replace('#POUNDS#', floor( $weight ), $piece);
				$piece = str_replace('#OUNCES#', number_format( ( $weight - floor( $weight ) ) * 16, 2 ), $piece);
		
				$this->package .= $piece;

			}
			
			$this->package = str_replace('#VALUE#', number_format( $total_regular_item_values / count( $package_weights ), 2 ), $this->package);

    	}

    }
    
    /**
     * per_item_shipping function.
     *
     * @access private
     * @param mixed $package
     * @return void
     */
    private function per_item_shipping( $package, $domestic ) {
    
	    global $woocommerce;
	    
	    $size = 'REGULAR';

    	// Get weight of order
    	foreach ( $package['contents'] as $item_id => $values ) {

    		if( !$values['data']->needs_shipping() ){
   				$this->add_notice( sprintf( __( 'Product #%d is virtual. Skipping.', 'syn_usps' ), $values[ 'product_id' ] ) );
    			continue;
    		}

    		if( !$values['data']->get_weight() || !$values['data']->get_length() || !$values['data']->get_height() || !$values['data']->get_width() ){
    			$this->add_notice( sprintf( __( 'Product <a href="%s" target="_blank">#%d</a> is missing dimensions and / or weight. Aborting %s quotes.', 'syn_usps' ), get_edit_post_link( $values[ 'product_id' ] ), $values[ 'product_id' ], $this->method_title ), 'error' );
    			$this->package = "";
	    		return;
    		}

			$dimensions = array( ( function_exists( 'wc_get_dimension' ) ? wc_get_dimension( $values['data']->get_length(), 'in' ) : woocommerce_get_dimension( $values['data']->get_length(), 'in' ) ), ( function_exists( 'wc_get_dimension' ) ? wc_get_dimension( $values['data']->get_height(), 'in' ) : woocommerce_get_dimension( $values['data']->get_height(), 'in' ) ), ( function_exists( 'wc_get_dimension' ) ? wc_get_dimension( $values['data']->get_width(), 'in' ) : woocommerce_get_dimension( $values['data']->get_width(), 'in' ) ) );
				
			sort( $dimensions );
			
			$girth = $dimensions[0] + $dimensions[0] + $dimensions[1] + $dimensions[1];
			
			if( max( $dimensions ) > 12 )
				$size   = 'LARGE';
			
			if( $domestic ){
				$piece = file_get_contents(USPS_PATH.'/xml/package.xml');
			}else{
				$piece = file_get_contents(USPS_PATH.'/xml/package_intl.xml');
			}
			
			$pounds = ( function_exists( 'wc_get_weight' ) ? wc_get_weight( $values['data']->get_weight(), 'lbs' ) : woocommerce_get_weight( $values['data']->get_weight(), 'lbs' ) );
			$ounces = round( ( $pounds - floor( $pounds ) ) * 16, 2 );
			
			if( $ounces <= 0 && $pounds <= 0 )
				$ounces = 1;
			
			$piece = str_replace('#ID#', $item_id.':'.$values['quantity'], $piece);
			$piece = str_replace('#VALUE#', $values['data']->get_price()  * $values['quantity'], $piece);
			$piece = str_replace('#SIZE#', $size, $piece);
			$piece = str_replace('#LENGTH#', $dimensions[2], $piece);
			$piece = str_replace('#WIDTH#', $dimensions[1], $piece);
			$piece = str_replace('#HEIGHT#', $dimensions[0], $piece);
			$piece = str_replace('#GIRTH#', round($girth), $piece);
			$piece = str_replace('#POUNDS#', floor($pounds), $piece);
			$piece = str_replace('#OUNCES#', $ounces, $piece);
			
			$this->package .= $piece;
			
    	}
    	
    }
    
    /**
     * box_shipping function.
     *
     * @access private
     * @param mixed $package
     * @return void
     */
    private function box_shipping( $package, $domestic ) {
    
	    global $woocommerce;
	    
	    $box_packages = $this->box_shipping_packages( $package );
		
		if( $box_packages === false )
			return;
	    
	    $size = 'REGULAR';

    	// Get weight of order
    	foreach ( $box_packages as $key => $box_package ) {

			$dimensions = array( ( function_exists( 'wc_get_dimension' ) ? wc_get_dimension( $box_package->length, 'in' ) : woocommerce_get_dimension( $box_package->length, 'in' ) ), ( function_exists( 'wc_get_dimension' ) ? wc_get_dimension( $box_package->height, 'in' ) : woocommerce_get_dimension( $box_package->height, 'in' ) ), ( function_exists( 'wc_get_dimension' ) ? wc_get_dimension( $box_package->width, 'in' ) : woocommerce_get_dimension( $box_package->width, 'in' ) ) );
				
			sort( $dimensions );
			
			$girth = $dimensions[0] + $dimensions[0] + $dimensions[1] + $dimensions[1];
			
			if( max( $dimensions ) > 12 )
				$size   = 'LARGE';
			
			if( $domestic ){
				$piece = file_get_contents(USPS_PATH.'/xml/package.xml');
			}else{
				$piece = file_get_contents(USPS_PATH.'/xml/package_intl.xml');
			}
			
			$pounds = ( function_exists( 'wc_get_weight' ) ? wc_get_weight( $box_package->weight, 'lbs' ) : woocommerce_get_weight( $box_package->weight, 'lbs' ) );
			$ounces = round( ( $pounds - floor( $pounds ) ) * 16, 2 );
			
			if( $ounces <= 0 && $pounds <= 0 )
				$ounces = 1;
			
			$piece = str_replace('#ID#', $key . ':1', $piece);
			$piece = str_replace('#VALUE#', round( $box_package->value, 2), $piece);
			$piece = str_replace('#SIZE#', $size, $piece);
			$piece = str_replace('#LENGTH#', $dimensions[2], $piece);
			$piece = str_replace('#WIDTH#', $dimensions[1], $piece);
			$piece = str_replace('#HEIGHT#', $dimensions[0], $piece);
			$piece = str_replace('#GIRTH#', round($girth), $piece);
			$piece = str_replace('#POUNDS#', floor($pounds), $piece);
			$piece = str_replace('#OUNCES#', $ounces, $piece);
			
			$this->package .= $piece;
			
    	}
    	
    }
	
	private function get_country_name( $code ) {
		$countries = apply_filters( 'usps_countries', array(
			'AF' => __( 'Afghanistan', 'syn_usps' ),
			'AX' => __( '&#197;land Islands', 'syn_usps' ),
			'AL' => __( 'Albania', 'syn_usps' ),
			'DZ' => __( 'Algeria', 'syn_usps' ),
			'AD' => __( 'Andorra', 'syn_usps' ),
			'AO' => __( 'Angola', 'syn_usps' ),
			'AI' => __( 'Anguilla', 'syn_usps' ),
			'AQ' => __( 'Antarctica', 'syn_usps' ),
			'AG' => __( 'Antigua and Barbuda', 'syn_usps' ),
			'AR' => __( 'Argentina', 'syn_usps' ),
			'AM' => __( 'Armenia', 'syn_usps' ),
			'AW' => __( 'Aruba', 'syn_usps' ),
			'AU' => __( 'Australia', 'syn_usps' ),
			'AT' => __( 'Austria', 'syn_usps' ),
			'AZ' => __( 'Azerbaijan', 'syn_usps' ),
			'BS' => __( 'Bahamas', 'syn_usps' ),
			'BH' => __( 'Bahrain', 'syn_usps' ),
			'BD' => __( 'Bangladesh', 'syn_usps' ),
			'BB' => __( 'Barbados', 'syn_usps' ),
			'BY' => __( 'Belarus', 'syn_usps' ),
			'BE' => __( 'Belgium', 'syn_usps' ),
			'PW' => __( 'Belau', 'syn_usps' ),
			'BZ' => __( 'Belize', 'syn_usps' ),
			'BJ' => __( 'Benin', 'syn_usps' ),
			'BM' => __( 'Bermuda', 'syn_usps' ),
			'BT' => __( 'Bhutan', 'syn_usps' ),
			'BO' => __( 'Bolivia', 'syn_usps' ),
			'BQ' => __( 'Bonaire, Saint Eustatius and Saba', 'syn_usps' ),
			'BA' => __( 'Bosnia and Herzegovina', 'syn_usps' ),
			'BW' => __( 'Botswana', 'syn_usps' ),
			'BV' => __( 'Bouvet Island', 'syn_usps' ),
			'BR' => __( 'Brazil', 'syn_usps' ),
			'IO' => __( 'British Indian Ocean Territory', 'syn_usps' ),
			'VG' => __( 'British Virgin Islands', 'syn_usps' ),
			'BN' => __( 'Brunei', 'syn_usps' ),
			'BG' => __( 'Bulgaria', 'syn_usps' ),
			'BF' => __( 'Burkina Faso', 'syn_usps' ),
			'BI' => __( 'Burundi', 'syn_usps' ),
			'KH' => __( 'Cambodia', 'syn_usps' ),
			'CM' => __( 'Cameroon', 'syn_usps' ),
			'CA' => __( 'Canada', 'syn_usps' ),
			'CV' => __( 'Cape Verde', 'syn_usps' ),
			'KY' => __( 'Cayman Islands', 'syn_usps' ),
			'CF' => __( 'Central African Republic', 'syn_usps' ),
			'TD' => __( 'Chad', 'syn_usps' ),
			'CL' => __( 'Chile', 'syn_usps' ),
			'CN' => __( 'China', 'syn_usps' ),
			'CX' => __( 'Christmas Island', 'syn_usps' ),
			'CC' => __( 'Cocos (Keeling) Islands', 'syn_usps' ),
			'CO' => __( 'Colombia', 'syn_usps' ),
			'KM' => __( 'Comoros', 'syn_usps' ),
			'CG' => __( 'Congo (Brazzaville)', 'syn_usps' ),
			'CD' => __( 'Congo (Kinshasa)', 'syn_usps' ),
			'CK' => __( 'Cook Islands', 'syn_usps' ),
			'CR' => __( 'Costa Rica', 'syn_usps' ),
			'HR' => __( 'Croatia', 'syn_usps' ),
			'CU' => __( 'Cuba', 'syn_usps' ),
			'CW' => __( 'Cura&Ccedil;ao', 'syn_usps' ),
			'CY' => __( 'Cyprus', 'syn_usps' ),
			'CZ' => __( 'Czech Republic', 'syn_usps' ),
			'DK' => __( 'Denmark', 'syn_usps' ),
			'DJ' => __( 'Djibouti', 'syn_usps' ),
			'DM' => __( 'Dominica', 'syn_usps' ),
			'DO' => __( 'Dominican Republic', 'syn_usps' ),
			'EC' => __( 'Ecuador', 'syn_usps' ),
			'EG' => __( 'Egypt', 'syn_usps' ),
			'SV' => __( 'El Salvador', 'syn_usps' ),
			'GQ' => __( 'Equatorial Guinea', 'syn_usps' ),
			'ER' => __( 'Eritrea', 'syn_usps' ),
			'EE' => __( 'Estonia', 'syn_usps' ),
			'ET' => __( 'Ethiopia', 'syn_usps' ),
			'FK' => __( 'Falkland Islands', 'syn_usps' ),
			'FO' => __( 'Faroe Islands', 'syn_usps' ),
			'FJ' => __( 'Fiji', 'syn_usps' ),
			'FI' => __( 'Finland', 'syn_usps' ),
			'FR' => __( 'France', 'syn_usps' ),
			'GF' => __( 'French Guiana', 'syn_usps' ),
			'PF' => __( 'French Polynesia', 'syn_usps' ),
			'TF' => __( 'French Southern Territories', 'syn_usps' ),
			'GA' => __( 'Gabon', 'syn_usps' ),
			'GM' => __( 'Gambia', 'syn_usps' ),
			'GE' => __( 'Georgia', 'syn_usps' ),
			'DE' => __( 'Germany', 'syn_usps' ),
			'GH' => __( 'Ghana', 'syn_usps' ),
			'GI' => __( 'Gibraltar', 'syn_usps' ),
			'GR' => __( 'Greece', 'syn_usps' ),
			'GL' => __( 'Greenland', 'syn_usps' ),
			'GD' => __( 'Grenada', 'syn_usps' ),
			'GP' => __( 'Guadeloupe', 'syn_usps' ),
			'GT' => __( 'Guatemala', 'syn_usps' ),
			'GG' => __( 'Guernsey', 'syn_usps' ),
			'GN' => __( 'Guinea', 'syn_usps' ),
			'GW' => __( 'Guinea-Bissau', 'syn_usps' ),
			'GY' => __( 'Guyana', 'syn_usps' ),
			'HT' => __( 'Haiti', 'syn_usps' ),
			'HM' => __( 'Heard Island and McDonald Islands', 'syn_usps' ),
			'HN' => __( 'Honduras', 'syn_usps' ),
			'HK' => __( 'Hong Kong', 'syn_usps' ),
			'HU' => __( 'Hungary', 'syn_usps' ),
			'IS' => __( 'Iceland', 'syn_usps' ),
			'IN' => __( 'India', 'syn_usps' ),
			'ID' => __( 'Indonesia', 'syn_usps' ),
			'IR' => __( 'Iran', 'syn_usps' ),
			'IQ' => __( 'Iraq', 'syn_usps' ),
			'IE' => __( 'Ireland', 'syn_usps' ),
			'IM' => __( 'Isle of Man', 'syn_usps' ),
			'IL' => __( 'Israel', 'syn_usps' ),
			'IT' => __( 'Italy', 'syn_usps' ),
			'CI' => __( 'Ivory Coast', 'syn_usps' ),
			'JM' => __( 'Jamaica', 'syn_usps' ),
			'JP' => __( 'Japan', 'syn_usps' ),
			'JE' => __( 'Jersey', 'syn_usps' ),
			'JO' => __( 'Jordan', 'syn_usps' ),
			'KZ' => __( 'Kazakhstan', 'syn_usps' ),
			'KE' => __( 'Kenya', 'syn_usps' ),
			'KI' => __( 'Kiribati', 'syn_usps' ),
			'KW' => __( 'Kuwait', 'syn_usps' ),
			'KG' => __( 'Kyrgyzstan', 'syn_usps' ),
			'LA' => __( 'Laos', 'syn_usps' ),
			'LV' => __( 'Latvia', 'syn_usps' ),
			'LB' => __( 'Lebanon', 'syn_usps' ),
			'LS' => __( 'Lesotho', 'syn_usps' ),
			'LR' => __( 'Liberia', 'syn_usps' ),
			'LY' => __( 'Libya', 'syn_usps' ),
			'LI' => __( 'Liechtenstein', 'syn_usps' ),
			'LT' => __( 'Lithuania', 'syn_usps' ),
			'LU' => __( 'Luxembourg', 'syn_usps' ),
			'MO' => __( 'Macao S.A.R., China', 'syn_usps' ),
			'MK' => __( 'Macedonia', 'syn_usps' ),
			'MG' => __( 'Madagascar', 'syn_usps' ),
			'MW' => __( 'Malawi', 'syn_usps' ),
			'MY' => __( 'Malaysia', 'syn_usps' ),
			'MV' => __( 'Maldives', 'syn_usps' ),
			'ML' => __( 'Mali', 'syn_usps' ),
			'MT' => __( 'Malta', 'syn_usps' ),
			'MH' => __( 'Marshall Islands', 'syn_usps' ),
			'MQ' => __( 'Martinique', 'syn_usps' ),
			'MR' => __( 'Mauritania', 'syn_usps' ),
			'MU' => __( 'Mauritius', 'syn_usps' ),
			'YT' => __( 'Mayotte', 'syn_usps' ),
			'MX' => __( 'Mexico', 'syn_usps' ),
			'FM' => __( 'Micronesia', 'syn_usps' ),
			'MD' => __( 'Moldova', 'syn_usps' ),
			'MC' => __( 'Monaco', 'syn_usps' ),
			'MN' => __( 'Mongolia', 'syn_usps' ),
			'ME' => __( 'Montenegro', 'syn_usps' ),
			'MS' => __( 'Montserrat', 'syn_usps' ),
			'MA' => __( 'Morocco', 'syn_usps' ),
			'MZ' => __( 'Mozambique', 'syn_usps' ),
			'MM' => __( 'Myanmar', 'syn_usps' ),
			'NA' => __( 'Namibia', 'syn_usps' ),
			'NR' => __( 'Nauru', 'syn_usps' ),
			'NP' => __( 'Nepal', 'syn_usps' ),
			'NL' => __( 'Netherlands', 'syn_usps' ),
			'AN' => __( 'Netherlands Antilles', 'syn_usps' ),
			'NC' => __( 'New Caledonia', 'syn_usps' ),
			'NZ' => __( 'New Zealand', 'syn_usps' ),
			'NI' => __( 'Nicaragua', 'syn_usps' ),
			'NE' => __( 'Niger', 'syn_usps' ),
			'NG' => __( 'Nigeria', 'syn_usps' ),
			'NU' => __( 'Niue', 'syn_usps' ),
			'NF' => __( 'Norfolk Island', 'syn_usps' ),
			'KP' => __( 'North Korea', 'syn_usps' ),
			'NO' => __( 'Norway', 'syn_usps' ),
			'OM' => __( 'Oman', 'syn_usps' ),
			'PK' => __( 'Pakistan', 'syn_usps' ),
			'PS' => __( 'Palestinian Territory', 'syn_usps' ),
			'PA' => __( 'Panama', 'syn_usps' ),
			'PG' => __( 'Papua New Guinea', 'syn_usps' ),
			'PY' => __( 'Paraguay', 'syn_usps' ),
			'PE' => __( 'Peru', 'syn_usps' ),
			'PH' => __( 'Philippines', 'syn_usps' ),
			'PN' => __( 'Pitcairn', 'syn_usps' ),
			'PL' => __( 'Poland', 'syn_usps' ),
			'PT' => __( 'Portugal', 'syn_usps' ),
			'QA' => __( 'Qatar', 'syn_usps' ),
			'RE' => __( 'Reunion', 'syn_usps' ),
			'RO' => __( 'Romania', 'syn_usps' ),
			'RU' => __( 'Russia', 'syn_usps' ),
			'RW' => __( 'Rwanda', 'syn_usps' ),
			'BL' => __( 'Saint Barth&eacute;lemy', 'syn_usps' ),
			'SH' => __( 'Saint Helena', 'syn_usps' ),
			'KN' => __( 'Saint Kitts and Nevis', 'syn_usps' ),
			'LC' => __( 'Saint Lucia', 'syn_usps' ),
			'MF' => __( 'Saint Martin (French part)', 'syn_usps' ),
			'SX' => __( 'Saint Martin (Dutch part)', 'syn_usps' ),
			'PM' => __( 'Saint Pierre and Miquelon', 'syn_usps' ),
			'VC' => __( 'Saint Vincent and the Grenadines', 'syn_usps' ),
			'SM' => __( 'San Marino', 'syn_usps' ),
			'ST' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'syn_usps' ),
			'SA' => __( 'Saudi Arabia', 'syn_usps' ),
			'SN' => __( 'Senegal', 'syn_usps' ),
			'RS' => __( 'Serbia', 'syn_usps' ),
			'SC' => __( 'Seychelles', 'syn_usps' ),
			'SL' => __( 'Sierra Leone', 'syn_usps' ),
			'SG' => __( 'Singapore', 'syn_usps' ),
			'SK' => __( 'Slovakia', 'syn_usps' ),
			'SI' => __( 'Slovenia', 'syn_usps' ),
			'SB' => __( 'Solomon Islands', 'syn_usps' ),
			'SO' => __( 'Somalia', 'syn_usps' ),
			'ZA' => __( 'South Africa', 'syn_usps' ),
			'GS' => __( 'South Georgia/Sandwich Islands', 'syn_usps' ),
			'KR' => __( 'South Korea', 'syn_usps' ),
			'SS' => __( 'South Sudan', 'syn_usps' ),
			'ES' => __( 'Spain', 'syn_usps' ),
			'LK' => __( 'Sri Lanka', 'syn_usps' ),
			'SD' => __( 'Sudan', 'syn_usps' ),
			'SR' => __( 'Suriname', 'syn_usps' ),
			'SJ' => __( 'Svalbard and Jan Mayen', 'syn_usps' ),
			'SZ' => __( 'Swaziland', 'syn_usps' ),
			'SE' => __( 'Sweden', 'syn_usps' ),
			'CH' => __( 'Switzerland', 'syn_usps' ),
			'SY' => __( 'Syria', 'syn_usps' ),
			'TW' => __( 'Taiwan', 'syn_usps' ),
			'TJ' => __( 'Tajikistan', 'syn_usps' ),
			'TZ' => __( 'Tanzania', 'syn_usps' ),
			'TH' => __( 'Thailand', 'syn_usps' ),
			'TL' => __( 'Timor-Leste', 'syn_usps' ),
			'TG' => __( 'Togo', 'syn_usps' ),
			'TK' => __( 'Tokelau', 'syn_usps' ),
			'TO' => __( 'Tonga', 'syn_usps' ),
			'TT' => __( 'Trinidad and Tobago', 'syn_usps' ),
			'TN' => __( 'Tunisia', 'syn_usps' ),
			'TR' => __( 'Turkey', 'syn_usps' ),
			'TM' => __( 'Turkmenistan', 'syn_usps' ),
			'TC' => __( 'Turks and Caicos Islands', 'syn_usps' ),
			'TV' => __( 'Tuvalu', 'syn_usps' ),
			'UG' => __( 'Uganda', 'syn_usps' ),
			'UA' => __( 'Ukraine', 'syn_usps' ),
			'AE' => __( 'United Arab Emirates', 'syn_usps' ),
			'GB' => __( 'United Kingdom', 'syn_usps' ),
			'US' => __( 'United States', 'syn_usps' ),
			'UY' => __( 'Uruguay', 'syn_usps' ),
			'UZ' => __( 'Uzbekistan', 'syn_usps' ),
			'VU' => __( 'Vanuatu', 'syn_usps' ),
			'VA' => __( 'Vatican', 'syn_usps' ),
			'VE' => __( 'Venezuela', 'syn_usps' ),
			'VN' => __( 'Vietnam', 'syn_usps' ),
			'WF' => __( 'Wallis and Futuna', 'syn_usps' ),
			'EH' => __( 'Western Sahara', 'syn_usps' ),
			'WS' => __( 'Western Samoa', 'syn_usps' ),
			'YE' => __( 'Yemen', 'syn_usps' ),
			'ZM' => __( 'Zambia', 'syn_usps' ),
			'ZW' => __( 'Zimbabwe', 'woocommerce' )
		));

	    if ( isset( $countries[ $code ] ) ) {
		    return strtoupper( $countries[ $code ] );
	    } else {
		    return false;
	    }
    }
    
}
	
?>