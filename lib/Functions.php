<?php 

class AdminOrderFunctions extends ShoppAdminOrders{

	function check_cart(){
		$cart_count = shopp_cart_items_count();
		if(!empty($cart_count)){
			//For the table cart
			$items = shopp_cart_items();
			foreach($items as $key => $item){
				$table_cart .= '<tr id="'.esc_attr($key).'"">';
					$table_cart .= '<td>';
						$table_cart .= '<a href="javascript:void(0)" id="'.esc_attr($key).'" class="remove-cart-item"><img src="'.plugins_url('img/delete.png', dirname(__FILE__)).'" /></a>';
					$table_cart .= '</td>';
					$table_cart .= '<td>';
						$table_cart .= esc_attr($item->name);
						if(isset($item->option->label)) $table_cart .= ' ('. $item->option->label .')';
					$table_cart .= '</td>';
					$table_cart .= '<td>';
						$table_cart .= '<input type="text" size="3" name="products['.esc_attr($item->product).'][qty]" value="'.absint($item->quantity).'" />';
					$table_cart .= '</td>';
					$table_cart .= '<td>';
						$table_cart .= money($item->unitprice);
					$table_cart .= '</td>';
					$table_cart .= '<td>';
						$table_cart .= money($item->quantity * $item->unitprice);
					$table_cart .= '</td>';
				$table_cart .= '</tr>';
			}
			return $table_cart;
		}

		return false;
	}

	function check_meta_cart(){
		$Totals = ShoppOrder()->Cart->Totals;
		$cart_count = shopp_cart_items_count();
		
		if(!empty($cart_count)){
			$meta_cart .= '<ul>';
				$meta_cart .= '<li>Subtotal: <strong><span class="subtotal">'.money($Totals->subtotal).'</span></strong></li>';
				$meta_cart .= '<li>Shipping: <strong><span class="shipping">'.money($Totals->shipping).'</span></strong></li>';
				$meta_cart .= '<li>Tax: <strong><span class="tax">'.money($Totals->tax).'</span></strong></li>';
				$meta_cart .= '<li>Total: <strong><span class="total">'.money($Totals->total).'</span></strong></li>';
			$meta_cart .= '</ul>';

			return $meta_cart;
		}

		return false;
	}

	function process_order($request){
		global $wpdb;

		$customer_id = absint($request['customer-search']);

		//update our customer records in case the customer requested a change in billing or shipping address
		$Purchase = shopp_add_order($customer_id);

		if(!$Purchase){
			return false;
		}
		
		//Overrides the redirect to the frontend
		remove_action('shopp_authed_order_event', array(ShoppOrder(),'success'));
		
		shopp_add_order_event($Purchase->id, 'authed', array(
			'txnid' => time(),
   			'gateway' => $Purchase->gateway,
    		'amount' => $Purchase->total,
    		'paytype' => 'api',
    		'paymethod' => 'AdminOrder',
    		'payid' => '1111',
    		'capture' => true
		));


		//replicating the Order::success function
		ShoppOrder()->purchase = ShoppOrder()->inprogress;
		ShoppOrder()->inprogress = false;
		do_action('shopp_order_success',ShoppPurchase());
		Shopping::resession();

		return $Purchase;			
	}

	function get_products(){
		global $wpdb;

		$results = array();

		$r = $wpdb->get_results($wpdb->prepare("SELECT post.id, post.post_title, price.price, price.saleprice, price.sale FROM $wpdb->posts AS post LEFT JOIN ".$wpdb->prefix."shopp_price AS price ON post.id = price.product WHERE post_type = 'shopp_product' AND post_status = 'publish' ORDER BY price"));
		
		foreach($r as $result){
			$price = $result->price;
			if('on' == $result->sale){
				$price = $result->saleprice;
			}
			$results[$result->id] = $result->post_title . ' ('.money($price).')';
		}

		return $results;
	}

	function get_countries(){
		//Jon Davis - Shopp 1.2
		$countries = array(''=>'&nbsp;');
		$countrydata = Lookup::countries();
		foreach ($countrydata as $iso => $c) {
			$countries[$iso] = $c['name'];
		}
		return $countries;
	}

	function get_billing_states($country){
		//Jon Davis - Shopp 1.2
		$regions = Lookup::country_zones();
		return array_merge(array(''=>'&nbsp;'),(array)$regions[$country]);
	}

	function get_shipping_states($country){
		//Jon Davis - Shopp 1.2
		$regions = Lookup::country_zones();
		return array_merge(array(''=>'&nbsp;'),(array)$regions[$country]);

	}

	function get_customers(){
		global $wpdb;

		$results = array();

		$r = $wpdb->get_results($wpdb->prepare("SELECT id, firstname, lastname, email FROM ".$wpdb->prefix."shopp_customer ORDER BY firstname"));
		
		foreach($r as $result){
			$results[$result->id] = $result->firstname . ' ' . $result->lastname . '  ('.$result->email.')';
		}

		return $results;	
	}
}