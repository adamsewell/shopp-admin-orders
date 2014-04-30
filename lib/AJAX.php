<?php
class ShoppAdminAJAX extends ShoppAdminOrders{
	
	function ajax_add_cart_product(){
		if(!wp_verify_nonce($_POST['nonce'], 'shopp_admin_orders_nonce')) wp_die('Security Check');

		if(isset($_POST['id'])){			
			$result = shopp_add_cart_product(absint($_POST['id']));
			if($result){
				echo ShoppAdminAJAX::update_html_cart();
			}
		}
		die();
	}
	
	function ajax_get_variations() {
		if(!wp_verify_nonce($_POST['nonce'], 'shopp_admin_orders_nonce')) wp_die('Security Check');
		
		if(isset($_POST['id'])) {
			$product_variants = shopp_product_variants(absint($_POST['id']));
			
			if(count($product_variants) > 0) {
				echo json_encode($product_variants);
			} else {
				echo json_encode(false);
			}
		}
		die();
	}
	
	function ajax_add_cart_variant() {
		if(!wp_verify_nonce($_POST['nonce'], 'shopp_admin_orders_nonce')) wp_die('Security Check');
		
		if(isset($_POST['id']) && isset($_POST['product'])) {
			$result = shopp_add_cart_product(absint($_POST['product']), 1, absint($_POST['id']));
			if($result){
				echo ShoppAdminAJAX::update_html_cart();
			}
		}
		die();
	}
	
	function ajax_remove_cart_product(){
		if(!wp_verify_nonce($_POST['nonce'], 'shopp_admin_orders_nonce')) wp_die('Security Check');
	
		if(isset($_POST['id'])){
			shopp_rmv_cart_item(absint($_POST['id']));
			echo ShoppAdminAJAX::update_html_cart(); 
		}
		die();
	}

	function ajax_check_cart(){
		if(!wp_verify_nonce($_POST['nonce'], 'shopp_admin_orders_nonce')) wp_die('Security Check');
		
		$Order = ShoppOrder();
		$Order->Cart->changed(true);
		$Order->Cart->totals();	

		echo ShoppAdminAJAX::update_html_cart();

		die();
	}

	function ajax_update_cart(){
		if(!wp_verify_nonce($_POST['nonce'], 'shopp_admin_orders_nonce')) wp_die('Security Check');
	
		parse_str($_POST['data'], $cart_data);

		$customer = absint($cart_data['customer-search']);

		if(@is_array($cart_data['products'])){
			foreach($cart_data['products'] as $cart_item => $cart_item_info){
				$item = shopp_cart_item($cart_item);
				$item->quantity = $cart_item_info['qty'];
			}	
		}

		$billing_data = array(
			'address' => $cart_data['billing-address'],
			'xaddress' => $cart_data['billing-xaddress'],
			'city' => $cart_data['billing-city'],
			'state' => $cart_data['billing-state'],
			'postcode' => $cart_data['billing-postcode']
		);

		$shipping_data = array(
			'address' => $cart_data['shipping-address'],
			'xaddress' => $cart_data['shipping-xaddress'],
			'city' => $cart_data['shipping-city'],
			'state' => $cart_data['shipping-state'],
			'postcode' => $cart_data['shipping-postcode']
		);

		//Update our customer records
		$shipping = shopp_add_customer_address($customer, $shipping_data, 'shipping');
		$billing = shopp_add_customer_address($customer, $billing_data, 'billing');

		//Set our current Order object to 
		$Order = ShoppOrder();
		$Order->Billing = shopp_address($customer, 'billing');
		$Order->Shipping = shopp_address($customer, 'shipping');
		$Order->Cart->changed(true);
		$Order->Cart->totals();

		echo ShoppAdminAJAX::update_html_cart();
		die();
	}

	function ajax_load_customer(){
		if(!wp_verify_nonce($_POST['nonce'], 'shopp_admin_orders_nonce')) wp_die('Security Check');

		if(isset($_POST['customer_id'])){
			$customer = shopp_customer(absint($_POST['customer_id']));
			$customer_addresses = shopp_customer_addresses(absint($_POST['customer_id']));

			$Order = ShoppOrder();
			
			$Order->Customer->copydata($customer,"",array());
			$Order->Customer->login = true;
			$Order->Billing = $customer_addresses['billing'];
			$Order->Billing->card = "";
			$Order->Billing->cardexpires = "";
			$Order->Billing->cardholder = "";
			$Order->Billing->cardtype = "";
			$Order->Shipping = $customer_addresses['shipping'];
			if (empty($Order->Shipping->id))
				$Order->Shipping->copydata(ShoppOrder()->Billing);
			
			$Order->Cart->changed(true);
			$Order->Cart->totals();

			$return = array();
			$return['firstname'] = $Order->Customer->firstname;
			$return['lastname'] = $Order->Customer->lastname;
			$return['email'] = $Order->Customer->email;
			$return['company'] = $Order->Customer->company;
			$return['phone'] = $Order->Customer->phone;
			$return['billing-name'] = $Order->Billing->name;
			$return['billing-address'] = $Order->Billing->address;
			$return['billing-xaddress'] = $Order->Billing->xaddress;
			$return['billing-city'] = $Order->Billing->city;
			$return['billing-state'] = $Order->Billing->state;
			$return['billing-country'] = $Order->Billing->country;
			$return['billing-postcode'] = $Order->Billing->postcode;
			
			$return['shipping-name'] = $Order->Shipping->name;
			$return['shipping-address'] = $Order->Shipping->address;
			$return['shipping-xaddress'] = $Order->Shipping->xaddress;
			$return['shipping-city'] = $Order->Shipping->city;
			$return['shipping-state'] = $Order->Shipping->state;
			$return['shipping-country'] = $Order->Shipping->country;
			$return['shipping-postcode'] = $Order->Shipping->postcode;


			//Try something else


			echo json_encode($return);
		}
		
		die();
	}

	function ajax_get_customer_states(){
		if(!wp_verify_nonce($_POST['nonce'], 'shopp_admin_orders_nonce')) wp_die('Security Check');

		if(isset($_POST['billing_country'])){
			$billing_states = AdminOrderFunctions::get_billing_states($_POST['billing_country']);
		}

		if(isset($_POST['shipping_country'])){
			$shipping_states = AdminOrderFunctions::get_shipping_states($_POST['shipping_country']);
		}

		$billing_states_html = menuoptions($billing_states, $_POST['billing_state'], true);
		$shipping_states_html = menuoptions($shipping_states, $_POST['shipping_state'], true);

		$return = array('billing_states' => $billing_states_html, 'shipping_states' => $shipping_states_html);

		echo json_encode($return);

		die();	
	}


	function update_html_cart(){
		$table_cart = '';
		$meta_cart = '';
		$Cart = ShoppOrder()->Cart;

		//For the table cart
		$items = shopp_cart_items();
		
		foreach($items as $key => $item){
			$table_cart .= '<tr id="'.esc_attr($key).'">';
				$table_cart .= '<td>';
					$table_cart .= '<a href="javascript:void(0)" id="'.esc_attr($key).'" class="remove-cart-item"><img src="'.plugins_url('img/delete.png', dirname(__FILE__)).'" /></a>';
				$table_cart .= '</td>';
				$table_cart .= '<td>';
					$table_cart .= esc_attr($item->name);
					if(isset($item->option->label)) $table_cart .= ' ('. $item->option->label .')';
				$table_cart .= '</td>';
				$table_cart .= '<td>';
					$table_cart .= '<input type="text" size="3" name="products['.esc_attr($key).'][qty]" value="'.absint($item->quantity).'" />';
				$table_cart .= '</td>';
				$table_cart .= '<td>';
					$table_cart .= money($item->unitprice);
				$table_cart .= '</td>';
				$table_cart .= '<td>';
					$table_cart .= money($item->quantity * $item->unitprice);
				$table_cart .= '</td>';
			$table_cart .= '</tr>';
		}

		//For the total metabox
		$meta_cart .= '<ul>';
			$meta_cart .= '<li>Subtotal: <strong><span class="subtotal">'.money($Cart->total('order')).'</span></strong></li>';
			$meta_cart .= '<li>Shipping: <strong><span class="shipping">'.money($Cart->total('shipping')).'</span></strong></li>';
			$meta_cart .= '<li>Tax: <strong><span class="tax">'.money($Cart->total('tax')).'</span></strong></li>';
			$meta_cart .= '<li>Total: <strong><span class="total">'.money($Cart->total()).'</span></strong></li>';
		$meta_cart .= '</ul>';
		
		$shipping_methods .= '<ul>';
			while(shopp('shipping','methods')) {
				$shipping_methods .= '<li><span><label>' . shopp('shipping','method-selector','return=true').' ';
					$shipping_methods .= shopp('shipping','method-name','return=true') . '&mdash;';
					$shipping_methods .= '<strong>' . shopp('shipping','method-cost','return=true') . '</strong><br />';
					$shipping_methods .= '<small>' . shopp('shipping','method-delivery','return=true') . '</small></label></span>';
				$shipping_methods .= '</li>';
			}	
		$shipping_methods .= '</ul>';

		$return = array('table_cart' => $table_cart, 'meta_cart' => $meta_cart, 'shipping_methods' => $shipping_methods);
		return json_encode($return);
	}
}