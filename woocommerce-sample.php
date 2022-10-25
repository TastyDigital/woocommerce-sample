<?php
/**
 * Plugin Name: WooCommerce Sample NWFC
 * Plugin URI: https://github.com/TastyDigital/woocommerce-sample
 * Description: Include Get Sample Button in products of your online store. This is a fork of an abandoned plugin
 * Author: Michele Menciassi / Tasty Digital
 * Author URI: https://tasty.digital
 * Version: 0.9.2
 * License: GPLv2 or later
 */
 
// Exit if accessed directly
if (!defined('ABSPATH'))
  exit;

//Checks if the WooCommerce plugins is installed and active.
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	if (!class_exists('WooCommerce_Sample')) {
		class WooCommerce_Sample {
		/**
		 * Gets things started by adding an action to initialize this plugin once
		 * WooCommerce is known to be active and initialized
		 */
		public function __construct() {
			add_action('woocommerce_init', array(&$this, 'init'));
		}

		/**
		 * to add the necessary actions for the plugin
		 */
		public function init() {
	        // backend stuff
	        add_action('woocommerce_product_write_panel_tabs', array($this, 'product_write_panel_tab'));
	        add_action('woocommerce_product_data_panels', array($this, 'product_write_panel'));
	        add_action('woocommerce_process_product_meta', array($this, 'product_save_data'), 10, 2);
	        // frontend stuff
            // Do we have this connected to Add to cart form? Makes it dependant on product being In Stock...
	        add_action('woocommerce_single_product_summary', array($this, 'product_sample_button'), 13);
            // add_action('woocommerce_after_add_to_cart_form', array($this, 'product_sample_button'));

            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 100);
			//add_action('woocommerce_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );

            add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart_samples' ), 10, 1 );
            add_action( 'woocommerce_after_checkout_validation', array( $this, 'check_customer_sample_order' ), 20, 2 );

            add_action('woocommerce_thankyou', array($this, 'after_checkout'), 10, 1);

			// Prevent add to cart
			add_filter('woocommerce_add_to_cart_validation', array( $this, 'add_to_cart_validation' ), 40, 4 );
			add_filter('woocommerce_add_cart_item_data', array( $this, 'add_sample_to_cart_item_data' ), 10, 3 );
			add_filter('woocommerce_add_cart_item', array( $this, 'add_sample_to_cart_item' ), 10, 2 );
			add_filter('woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );
			add_filter('woocommerce_get_cart_item_from_session', array( $this, 'filter_session'), 10, 3);
			add_filter('woocommerce_cart_item_name', array( $this, 'cart_title'), 10, 3);
			add_filter('woocommerce_cart_widget_product_title', array( $this, 'cart_widget_product_title'), 10, 2);
			add_filter('woocommerce_cart_item_quantity', array( $this, 'cart_item_quantity'), 10, 2);

            add_filter( 'woocommerce_get_price_html',  array( $this, 'wc_override_free_sample_price_display'), 10, 2);

            add_filter( 'woocommerce_order_item_name', array( $this, 'sample_order_item_name'), 10, 2 );

	
			add_filter('woocommerce_shipping_free_shipping_is_available', array( $this, 'enable_free_shipping'), 40, 1);
			
			if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>' ) ){
				add_filter('woocommerce_package_rates', array( $this, 'free_shipping_filter'), 10, 1);
			}else{
				add_filter('woocommerce_available_shipping_methods', array( $this, 'free_shipping_filter'), 10, 1);
			}
			//add_action('wc_add_order_item_meta', array($this, 'add_order_item_meta'), 10, 2);
            add_action( 'woocommerce_checkout_create_order_line_item',  array($this, 'create_order_line_meta'), 10, 4 );
			
			// filter for Minimum/Maximum plugin override overriding
			if (in_array('woocommerce-min-max-quantities/min-max-quantities.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				add_filter('wc_min_max_quantity_minimum_allowed_quantity', array($this, 'minimum_quantity'), 10, 4 );
				add_filter('wc_min_max_quantity_maximum_allowed_quantity', array($this, 'maximum_quantity'), 10, 4 );
				add_filter('wc_min_max_quantity_group_of_quantity', array($this, 'group_of_quantity'), 10, 4 );			
			}

			// filter for Measurement Price Calculator plugin override overriding
			if (in_array('woocommerce-measurement-price-calculator/woocommerce-measurement-price-calculator.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				add_filter('wc_measurement_price_calculator_add_to_cart_validation', array($this, 'measurement_price_calculator_add_to_cart_validation'), 10, 4 );
			}

			// filter for WooCommerce Chained Products plugin override overriding
			if (in_array('woocommerce-chained-products/woocommerce-chained-products.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				add_action( 'wc_after_chained_add_to_cart', array( $this, 'remove_chained_products' ), 20, 6 ); 
			}

			add_filter( 'woocommerce_post_class', array( $this, 'filter_woocommerce_sample_post_class'), 10, 2 );

		}

        function wc_override_free_sample_price_display( $price, $product ){
		    if( $product->get_meta('sample_enable') == 1 && $product->get_price() == 0 ){
		        return __('Free sample', 'woosample_textdomain');
            }else{
		        return $price;
            }
        }


		function remove_chained_products ($chained_parent_id, $quantity, $chained_variation_id, $chained_variation_data, $chained_cart_item_data, $cart_item_key){

			$cart = WC()->cart->get_cart();
			$main_is_sample = $cart[$cart_item_key]['sample'];
			if ($main_is_sample) {
				$main_product_id = $cart[$cart_item_key]['product_id'];
				if ( !get_post_meta($main_product_id, 'sample_chained_enabled', true) ) {
					foreach ($cart as $cart_key => $cart_item) {
						if ($cart_item['product_id'] == $chained_parent_id) {
							WC()->cart->remove_cart_item($cart_key);
							break;
						}
					}
				}
			}
		}

		function measurement_price_calculator_add_to_cart_validation ($valid, $product_id, $quantity, $measurements){

			$validation = $valid;
			if (get_post_meta($product_id, 'sample_enable', true)==1 && $_REQUEST['sample']){
				WC()->session->set( 'wc_notices', null );
				$validation = true;
			}
			return $validation;
		}

//		function add_order_item_meta ($item_id, $values){
//			if ($values['sample']){
//                wc_add_order_item_meta( $item_id, 'product type', 'sample');
//			}
//		}
		function create_order_line_meta( $item, $cart_item_key, $values, $order ) {

                if ( !empty( $values['sample'] ) ) {
                    $item->add_meta_data( 'Sample', $values['sample'], true ); // $values['sample'] only has value of 1 [true] atm
                    $item->set_name($item->get_name().' [' . __('Sample','woosample') . '] ');
                    // make sure quantity is one as plugin field bypassing add-to-cart validation
                    if($item->get_quantity() > 1){
                        $item->set_quantity( 1 );
                    }

                }
		}

		// filter for Minimum/Maximum plugin overriding
		function minimum_quantity($minimum_quantity, $checking_id, $cart_item_key, $values){
			if ($values['sample'])
				$minimum_quantity = 1;
			return $minimum_quantity;
		}
      
		function maximum_quantity($maximum_quantity, $checking_id, $cart_item_key, $values){
			if ($values['sample'])
				$maximum_quantity = 1;
			return $maximum_quantity;
		}

		function group_of_quantity($group_of_quantity, $checking_id, $cart_item_key, $values){
			if ($values['sample'])
				$group_of_quantity = 1;
			return $group_of_quantity;
		}
		// end filter for Mimimum/Maximum plugin overriding

		function enable_free_shipping($is_available){

			$cart_items = WC()->cart->get_cart();
			$only_free_samples      = false;

			// only enable free shipping if only free samples in basket
			foreach ( $cart_items as $cart_item ) {
				//echo '<pre>'. print_r($cart_item,true).'</pre>';
				if ( !empty($cart_item['sample']) ){
					$only_free_samples = true;
				}else {
					$only_free_samples = false;
					break;
				}
			}

			return $is_available && $only_free_samples;


      }

      function free_shipping_filter( $rates ) {
	      // limits to only free shipping option if it is available
		    $free = array();
		    foreach ( $rates as $rate_id => $rate ) {
		        if ( 'free_shipping' === $rate->method_id ) {
		            $free[ $rate_id ] = $rate;
		            break;
		        }
		    }
		    return ! empty( $free ) ? $free : $rates;
		}

      function cart_item_quantity ($product_quantity, $cart_item_key){

      	      if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
      	      	      $cart_items = WC()->cart->get_cart();
      	      	      $cart_item =$cart_items[$cart_item_key];
      	      	      if (!empty($cart_item['sample'])){
      	      	          $product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
      	      	      }
      	      }			
      	      return $product_quantity; 
      }
      
      function cart_title($title, $values, $cart_item_key){
      	      if (!empty($values['sample'])){
      	      	      $title .= ' [' . __('Sample','woosample') . '] ';
      	      }
      	      return $title;
      }
	  
      function cart_widget_product_title($title, $cart_item){
			if (is_array($cart_item) && !empty($cart_item['sample'])){
				$title .= ' [' . __('Sample','woosample') . '] ';
			}
			return $title;
	  }
	  function filter_session($cart_content, $value, $key){
		    if (!empty($value['sample'])){
		        $cart_content['sample'] = true;
                $cart_content['unique_key'] = $value['unique_key'];
                //$cart_content['data']->set_price('0');
                $product_id = $cart_content['product_id'];
                /*
                    2019-12-30 TT
                    Changing 'default' to 'free' for 'sample_shipping_mode' and 'sample_price_mode'
                */
                $sample_price_mode = get_post_meta($product_id, 'sample_price_mode', true) ? get_post_meta($product_id, 'sample_price_mode', true) : 'free';
                $sample_price = get_post_meta($product_id, 'sample_price', true) ? get_post_meta($product_id, 'sample_price', true) : 0;
                if ($sample_price_mode === 'custom'){
                    $cart_content['data']->set_price( $sample_price );
                }else if ($sample_price_mode === 'free'){
                    $cart_content['data']->set_price('0');
                }else{
                    //default
                }
		    }
		    return $cart_content;
		}

		/*
		    filter woocommerce_get_item_data:  Display on the pages: Cart, Checkout, Order Received
		 */
            function get_item_data($item_data, $cart_item){
                if ( isset($cart_item['sample']) ) {
                    $item_data[] = array(
                        'key'     => 'Sample',
                        'value'   => '0',
                        'display' => 'Free', // in case you would like to display "value" in another way (for users)
                    );
                }
//                echo 'cart_item';
//                var_dump($cart_item);
//                echo 'item_data';
//                var_dump($item_data);

                return $item_data;
            }
            function sample_order_item_name($product_name, $item){
                if( isset( $item['sample'] ) ) {
                    $product_name .= sprintf(
                        '<ul><li>%s: %s</li></ul>',
                        __( 'Sample', 'woosample' ),
                        'Free'
                    );
                }
                return $product_name;
            }

            function add_sample_to_cart_item_data ($cart_item_data, $product_id, $variation_id){
                if (get_post_meta($product_id, 'sample_enable',true)==1 && !empty($_REQUEST['sample'])){
                    $cart_item_data['sample'] = true;
                    $cart_item_data['unique_key'] = md5($product_id . 'sample');
                }
                return $cart_item_data;
            }

            function add_sample_to_cart_item ($cart_item, $cart_item_key){
                if (!empty($cart_item['sample']) && $cart_item['sample'] === true){
                    $cart_item['data']->set_price('0');
                }
                return $cart_item;
            }

            /*
                We limit sample ordering per account so users do not abuse the three sample limit
            */
            function canUserOrderSamples($user)
            {
                $lastSampleOrdered = get_user_meta($user->ID, 'last_sample_ordered_date', true);

                if (!$lastSampleOrdered) {
                    // No meta information about this user is saved yet
                    // So they can order samples
                    return true;
                }
                $today = new DateTime();
                $lastSampleOrdered = new DateTime($lastSampleOrdered);
                $difference = date_diff($today, $lastSampleOrdered);

                $numMonths = ($difference->y * 12) + $difference->m;
                if ($numMonths >= 3) {
                    // to order more samples, last order has to be three months ago or greater!
                    return true;
                } else {
                    return false;
                }
            }
            function debit_samples($items)
            {
                $user = wp_get_current_user();
                // Only set last ordered once.

                $isSampleOrder = false;
                foreach ($items as $key => $item) {
                    $sampleID = wc_get_order_item_meta($key, 'Sample');
                    if ($sampleID) {
                        $isSampleOrder = true;
                    }
                }
                if ($isSampleOrder) {
                    $lastSampleOrdered = get_user_meta($user->ID, 'last_sample_ordered_date', true);
                    $today = new DateTime();
                    $noSamplesOrderedBefore = $lastSampleOrdered == "";

                    $lastSampleOrdered = new DateTime($lastSampleOrdered);
                    $difference = date_diff($today, $lastSampleOrdered);

                    if ($difference->y >= 1) {
                        update_user_meta($user->ID, 'last_sample_ordered_date', $today->format('Y-m-d'));
                    }
                    if ($noSamplesOrderedBefore) {
                        update_user_meta($user->ID, 'last_sample_ordered_date', $today->format('Y-m-d'));
                    }
                }
            }
            function after_checkout($id)
            {
                //$order = new WC_Order($id);
	            $order = wc_get_order( $id );
                // Sample process
                if ($order->get_status() == 'processing') {
                    if ($order->update_status('completed')) {
                        $this->debit_samples($order->get_items());
                    }
                }
            }
            function check_customer_sample_order( $fields, $errors ) {

                // Get current customer/user
                if(is_user_logged_in()){
                    $customer = wp_get_current_user();
                }else{
                    $customer = get_user_by('email',$fields['billing_email']);
                }
                // If user is not empty, get passed orders
                if (!empty($customer)) {
                    if ( !$this->canUserOrderSamples($customer) ){
                        $errors->add( 'validation', 'Sorry, you have already ordered some of our samples recently, which are of limited availability. Please <a href="/contact-us/?sku=Free+Samples">contact us</a> for more information.' );
                    }
                }

            }
            function check_cart_samples($return){
                // TODO Set total samples $limit as a plugin option
                $total = 0;
                $limit = 3;

                foreach ( WC()->cart->cart_contents as $cart_item ) {
                    if(!empty($cart_item['sample']) && $cart_item['sample'] == 1){
                        $total++;
                    }else if ( defined('ALLOW_PURCHASE') && !ALLOW_PURCHASE ) {
                        wc_add_notice(__('Non-sample orders are not currently supported on this website.', 'woosample'), 'error');
                        return false;
                    }
                }
                if($total > $limit){
                    /* translators: %s: Free sample order limit. */
                    wc_add_notice( sprintf( __( 'Sorry, but you can only order a maximum of %s free samples', 'woosample' ), $limit, $total ), 'error' );
                    return false;
                }
                if(is_user_logged_in()) {
                    $customer = wp_get_current_user();
                    if ( !$this->canUserOrderSamples($customer) ){
                        wc_add_notice( sprintf( __( 'Sorry, you have already ordered some of our samples recently, which are of limited availability. Please <a href="/contact-us/?sku=Free+Samples">contact us</a> for more information.' , 'woosample' ) ), 'error' );
                        return false;
                    }
                }
                return $return;
            }
	  
      /**
       * add_to_cart_validation function.
       *
       * @access public
       * @param mixed $pass
       * @param mixed $product_id
       * @param mixed $quantity
       * @return void
       */
      function add_to_cart_validation( $pass, $product_id, $quantity, $variation_id = 0 ) {


        // se ci sono articoli nel carrello eseguiamo i controlli altrimenti se il carrello è vuoto aggiungiamo l'elemento senza controlli ulteriori
        if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
            $is_sample = empty($_REQUEST['sample']) ? false : true;
            // eseguiamo una validazione specifica solo se l'articolo aggiunto è un campione
            if ($is_sample){
                // l'articolo richiesto è un "campione" controlliamo che non sia già stato inserito nel carrello
                $cart_items = WC()->cart->get_cart();
                $unique_key = md5($product_id . 'sample');

                // TODO Set total samples $limit as a plugin option
                $total = 0;
                $limit = 3;
                foreach ($cart_items as $cart_id_key => $cart_item){
                    if ( isset($cart_item['unique_key']) && $cart_item['unique_key'] == $unique_key){
                        wc_add_notice( __( 'A sample of the same product is already present in your basket', 'woosample' ), 'error' );
                        return false;
                    }
                    if ($cart_item['product_id'] == $product_id){
                        wc_add_notice( __( 'You have already added this product to your basket, you cannot add a sample of the same item', 'woosample' ), 'error' );
                        return false;
                    }

                    if (!empty($cart_item['sample']) && $cart_item['sample'] === true){
                        $total++;
                    }
                }
                if($total >= $limit){
                    /* translators: %s: Free sample order limit. */
                    wc_add_notice( sprintf( __( 'Sorry, but you can only order a maximum of %s free samples', 'woosample' ), $limit ), 'error' );
                    return false;
                }

                if(is_user_logged_in()) {
                    $user = wp_get_current_user();
                    if ( !$this->canUserOrderSamples($user)) {
                        wc_add_notice(__('Sorry, you have already ordered samples recently. Please call us for more information.', 'woosample'), 'error');
                        return false;
                    }
                }

            }
            else if ( defined('ALLOW_PURCHASE') && !ALLOW_PURCHASE ) {
                wc_add_notice(__('Non-sample orders are not currently supported on this website.', 'woosample'), 'error');
                return false;
            }
        }
        // passiamo il valore impostato di default;
        return $pass;
      }
      /**
       * creates the tab for the administrator, where administered product sample.
       */
      public function product_write_panel_tab() {
        echo "<li><a class='added_sample' href=\"#sample_tab\"><span>" . __('Free Sample','woosample') . "</span></a></li>";
      }

		/**
		 * build the panel for the administrator.
		 */
		public function product_write_panel() {
        	global $post;
			$sample_enable = get_post_meta($post->ID, 'sample_enable', true) ? get_post_meta($post->ID, 'sample_enable', true) : false;
			if (in_array('woocommerce-chained-products/woocommerce-chained-products.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				$has_chained_products = get_post_meta($post->ID, '_chained_product_detail', true );
			} else {
				$has_chained_products = false;
			}

			/*
			    2019-12-30 TT
			    Changing 'default' to 'free' for 'sample_shipping_mode' and 'sample_price_mode'
			*/
			$sample_chained_enabled = get_post_meta($post->ID, 'sample_chained_enabled', true) ? get_post_meta($post->ID, 'sample_chained_enabled', true) : false;
			$sample_shipping_mode = get_post_meta($post->ID, 'sample_shipping_mode', true) ? get_post_meta($post->ID, 'sample_shipping_mode', true) : 'free';
			$sample_shipping = get_post_meta($post->ID, 'sample_shipping', true) ? get_post_meta($post->ID, 'sample_shipping', true) : 0;
			$sample_price_mode = get_post_meta($post->ID, 'sample_price_mode', true) ? get_post_meta($post->ID, 'sample_price_mode', true) : 'free';
			$sample_price = get_post_meta($post->ID, 'sample_price', true) ? get_post_meta($post->ID, 'sample_price', true) : 0;
			?>
			<div id="sample_tab" class="panel woocommerce_options_panel">
				<p class="form-field sample_enable_field ">
					<label for="sample_enable"><?php _e('Enable Free Sample', 'woosample');?></label>
					<input type="checkbox" class="checkbox" name="sample_enable" id="sample_enable" value="yes" <?php echo $sample_enable ? 'checked="checked"' : ''; ?>> <span class="description"><?php _e('Enable or disable sample option for this item.', 'woosample'); ?></span>
				</p>
			<?php if ($has_chained_products) { ?>
				<p class="form-field sample_chained_enabled_field ">
					<label for="sample_chained_enabled"><?php _e('Add chained products', 'woosample');?></label>
					<input type="checkbox" class="checkbox" name="sample_chained_enabled" id="sample_chained_enabled" value="yes" <?php echo $sample_chained_enabled ? 'checked="checked"' : ''; ?>> <span class="description"><?php _e('Add or not chained products as sample.', 'woosample'); ?></span>
				</p>
			<?php } ?>
                <!--
				<legend><?php _e('Sample Shipping', 'woosample'); ?></legend>
                <div class="options_group">
                    <input class="radio" id="sample_shipping_free" type="radio" value="free" name="sample_shipping_mode" <?php echo $sample_shipping_mode == 'free' ? 'checked="checked"' : ''; ?>>
                    <label class="radio" for="sample_shipping_free"><?php _e('free shipping for sample', 'woosample'); ?></label>
                </div>
				<div class="options_group">
					<input class="radio" id="sample_shipping_default" type="radio" value="default" name="sample_shipping_mode" <?php echo $sample_shipping_mode == 'default' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_shipping_default"><?php _e('use default product shipping methods', 'woosample'); ?></label>
				</div>
				<div class="options_group">
					<input class="radio" id="sample_shipping_custom" type="radio" value="custom" name="sample_shipping_mode" <?php echo $sample_shipping_mode == 'custom' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_shipping_custom"><?php _e('custom fee shipping', 'woosample'); ?></label>
					<p class="form-field sample_shipping_field clear">
						<label for="sample_shipping"><?php _e('set shipping fee', 'woosample'); ?></label>
						<input type="number" class="wc_input_price short" name="sample_shipping" id="sample_shipping" value="<?php echo $sample_shipping; ?>" step="any" min="0">
					</p>
				</div>
				<legend><?php _e('Sample price', 'woosample'); ?></legend>
                <div class="options_group">
                    <input class="radio" id="sample_price_free" type="radio" value="free" name="sample_price_mode" <?php echo $sample_price_mode == 'free' ? 'checked="checked"' : ''; ?>>
                    <label class="radio" for="sample_price_free"><?php _e('free', 'woosample'); ?></label>
                </div>
				<div class="options_group">
					<input class="radio" id="sample_price_default" type="radio" value="default" name="sample_price_mode" <?php echo $sample_price_mode == 'default' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_price_default"><?php _e('product default price', 'woosample'); ?></label>
				</div>
				<div class="options_group">
					<input class="radio" id="sample_price_custom" type="radio" value="custom" name="sample_price_mode" <?php echo $sample_price_mode == 'custom' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_price_custom"><?php _e('custom price', 'woosample'); ?></label>
					<p class="form-field sample_price_field clear">
						<label for="sample_price"><?php _e('set sample price', 'woosample'); ?></label>
						<input type="number" class="wc_input_price short" name="sample_price" id="sample_price" value="<?php echo $sample_price; ?>" step="any" min="0">
					</p>
				</div>
				-->
			</div>
			<?php
		}

      /*
       * build form to the administrator.
       */


      /**
       * updating the database post.
       */
      public function product_save_data($post_id, $post) {

        $sample_enable = filter_input(INPUT_POST, 'sample_enable');
        if (empty($sample_enable)) {
          delete_post_meta($post_id, 'sample_enable');
        }else{
          update_post_meta($post_id, 'sample_enable', true);
        }
        $sample_chained_enabled = filter_input(INPUT_POST, 'sample_chained_enabled');
        if (empty($sample_chained_enabled)) {
          delete_post_meta($post_id, 'sample_chained_enabled');
        }else{
          update_post_meta($post_id, 'sample_chained_enabled', true);
        }

		$sample_price_mode = filter_input(INPUT_POST, 'sample_price_mode');
        update_post_meta($post_id, 'sample_price_mode', $sample_price_mode);

		$sample_price = filter_input(INPUT_POST, 'sample_price');
        update_post_meta($post_id, 'sample_price', $sample_price);

		$sample_shipping_mode = filter_input(INPUT_POST, 'sample_shipping_mode');
        update_post_meta($post_id, 'sample_shipping_mode', $sample_shipping_mode);

		$sample_shipping = filter_input(INPUT_POST, 'sample_shipping');
        update_post_meta($post_id, 'sample_shipping', $sample_shipping);
      }

		public function product_sample_button() {
			global $post, $product;

            if ( ! $product->is_in_stock() ) {
                // no samples available
                return;
            }

			$is_sample = get_post_meta($post->ID, 'sample_enable',true)==1;
			if ($is_sample){
			?>
				<?php do_action('woocommerce_before_add_sample_to_cart_form'); ?>
				<form action="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" class="cart sample" method="post" enctype='multipart/form-data'>
				<?php do_action('woocommerce_before_add_sample_to_cart_button'); ?>
					<?php $btnclass = apply_filters('sample_button_class', "single_add_to_cart_button button single_add_sample_to_cart_button btn btn-default"); ?>
	      	      	<button type="submit" class="<?php echo $btnclass; ?>"><?php echo  __( 'Order Sample', 'woosample' ); ?></button>
	      	        <input type="hidden" name="sample" id="sample" value="true"/>
	      	        <input type="hidden" name="add-to-cart" id="sample_add_to_cart" value="<?php echo $product->get_id(); ?>">
				<?php do_action('woocommerce_after_add_sample_to_cart_button'); ?>
				</form>
				<?php do_action('woocommerce_after_add_sample_to_cart_form'); ?>
			<?php
			}
		}
	  
		function enqueue_scripts() {
			global $pagenow, $wp_scripts;
			$plugin_url = untrailingslashit(plugin_dir_url(__FILE__));
			if ( ! is_admin() ) {
				wp_enqueue_script('woocommerce-sample', $plugin_url . '/js/woocommerce-sample.js', array('jquery'), '1.0', true);
			}
			wp_enqueue_style('sample-styles', plugins_url('css/styles.css', __FILE__), [], false );
			/*
			if (is_admin() && ( $pagenow == 'post-new.php' || $pagenow == 'post.php' || $pagenow == 'edit.php' || 'edit-tags.php')) {
				// for admin enqueue
			}
			*/
		}

			function filter_woocommerce_sample_post_class( $classes, $product ) {
				// is_product() - Returns true on a single product page
				// NOT single product page, so return
				// if ( ! is_product() ) return $classes;
				if ( ! $product->is_in_stock() ) return $classes;

				$is_sample = get_post_meta($product->get_id(), 'sample_enable',true)==1;
				if ($is_sample) {
					// Add new class
					$classes[] = 'has-samples-available';
				}
				return $classes;
			}
      
    }//end of the class  
  }//end of the if, if the class exists

  /*
   * Instantiate plugin class and add it to the set of globals.
   */
  $woocommerce_sample_tab = new WooCommerce_Sample();

  $plugin = plugin_basename( __FILE__ );

} else {//end if,if installed woocommerce
  add_action('admin_notices', 'woosample_tab_error_notice');

  function woosample_tab_error_notice() {
    global $current_screen;
    if ($current_screen->parent_base == 'plugins') {
      echo '<div class="error"><p>' . sprintf(__('WooCommerce Sample requires <a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a> to be activated in order to work. Please install and activate <a href="%1$s" target="_blank">WooCommerce</a> first.','woosample'), admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce') ) . '</p></div>';
    }
  }
}

 /**
  * Enqueue plugin style-file
  */
  function woosample_add_scripts() {
    // Respects SSL, style-admin.css is relative to the current file
    wp_register_style( 'woosample-styles', plugins_url('css/style-admin.css', __FILE__) );
    wp_register_script( 'woosample-scripts', plugins_url('js/woocommerce-sample.js', __FILE__), array('jquery') );
    wp_enqueue_style( 'woosample-styles' );
    wp_enqueue_script( 'woosample-scripts' );
  }
  add_action( 'admin_enqueue_scripts', 'woosample_add_scripts' );

  /**
  * Set up localization
  */
  function woosample_textdomain() {
    load_plugin_textdomain( 'woosample', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
  }
  add_action('plugins_loaded', 'woosample_textdomain');

?>
