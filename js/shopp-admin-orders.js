jQuery(document).ready(function($){

	//Defaults
	$('.variant-container').hide();
	$('#stb_admin_shipping_methods').hide();
	$('#customer-container').hide();

	//Add the click event handler to our newly created dom objects
	$('table.cart').on('click', '.remove-cart-item', function(event){
		$('#product-search-loading').show();
		remove_cart_item($(this).attr('id'));
	});

	//Quantity Changes
	$('table.cart').on('change', 'input[name$="[qty]"]', function(){
		//Add new quantity to cart and update the cart list
		$('#product-search-loading').show();
		update_cart();
	});

	//retotal the cart on state/country change
	$('#update_cart').click(function(e){
		e.preventDefault();
		$('#update-cart-loading').show();
		update_cart();
	});

	//Apply the Chosen javascript library to our select's
	$('#product-search').chosen({
		no_results_text: 'No Product Matched',
		width: '525px'
	}).change(function(){
		$('#product-search-loading').show();
		add_cart_item(Number($(this).val()));
	});

	$('#variant-search').chosen({
		no_results_text: 'No Product Matched',
		width: '525px'
	}).change(function(){
		$('#variant-search-loading').show();
		add_variant_cart_item(Number($('#product-search').val()), Number($(this).val()));
	});

	$('#customer-search').chosen({
		no_results_text: 'No Customer Found',
		width: '525px'
	}).change(function(){
		var value = $(this).val();

		if(value == 'create_new_customer'){
			window.location = shopp_admin_orders_vars.create_new_customer_url;
		}else{
			$('#customer-search-loading').show();
			load_customer_info(Number(value));
			$('#customer-container').slideDown();
		}
	});
	
	$(document).on('change', '#shipping-methods input.shipmethod', function() {
		load_shipping_methods($(this).val());
	});

	/*****************************
			Functions
	****************************/
	function add_cart_item(product_id){
		var variation_check_data = {
			action: 'get_variations',
			nonce: shopp_admin_orders_vars.shopp_admin_orders_nonce,
			id: Number(product_id)
		};

		
		$.post(ajaxurl, variation_check_data, function(response){
			$('#variant-search').find('option').remove().end().append('<option value=""></option>');
			$('#variant-search').trigger("chosen:updated");
			
			var data = $.parseJSON(response);
			if(data != false && data.length > 0) {
				//show our variations menu
				$('.variant-container').slideDown();
				// render variations
				for(var x in data) {
				    $('#variant-search').append('<option value="' + data[x]['id'] + '">' + data[x]['label'] + '</option>');
				}

				$('#variant-search').trigger("chosen:updated");
				$('#product-search-loading').hide();
			} else {
				var item_data = {
					action: 'add_cart_product',
					nonce: shopp_admin_orders_vars.shopp_admin_orders_nonce,
					id: Number(product_id)
				};

				$.post(ajaxurl, item_data, function(p_response){
					var p_data = $.parseJSON(p_response);
					$('table.cart tbody').html(p_data.table_cart);
					$('#display_cart').html(p_data.meta_cart);
					$('#shipping-methods').html(p_data.shipping_methods);
					$('#product-search-loading').hide();
					$('#stb_admin_shipping_methods').slideDown();

				});	
			}
		});
	}
	
	function add_variant_cart_item(product_id, variant_id) {
		var item_data = {
			action: 'add_cart_variant',
			nonce: shopp_admin_orders_vars.shopp_admin_orders_nonce,
			product: Number(product_id),
			id: Number(variant_id)
		};
		
		$.post(ajaxurl, item_data, function(response){
			var data = $.parseJSON(response);

			$('table.cart tbody').html(data.table_cart);
			$('#display_cart').html(data.meta_cart);
			$('#shipping-methods').html(data.shipping_methods);
			$('#variant-search-loading').hide();
			$('#stb_admin_shipping_methods').slideDown();
			$('.variant-container').slideUp();

		});
	}

	function remove_cart_item(item_id){
		var item_data = {
			action: 'remove_cart_product',
			nonce: shopp_admin_orders_vars.shopp_admin_orders_nonce,
			id: Number(item_id)
		};

		$.post(ajaxurl, item_data, function(response){
			var data = $.parseJSON(response);

			$('table.cart tbody').html(data.table_cart);
			$('#display_cart').html(data.meta_cart);
			$('#product-search-loading').hide();
			$('#shipping-methods').html(data.shipping_methods);
		});
	}

	function update_cart(){
		var item_data = {
			action: 'update_cart',
			nonce: shopp_admin_orders_vars.shopp_admin_orders_nonce,
			data: $('#cart').serialize()
		};

		$.post(ajaxurl, item_data, function(response){
			var data = $.parseJSON(response);

			$('table.cart tbody').html(data.table_cart);
			$('#display_cart').html(data.meta_cart);
			$('#shipping-methods').html(data.shipping_methods);
			$('#product-search-loading').hide();
			$('#customer-search-loading').hide();
			$('#update-cart-loading').hide();


		});
	}

	function load_customer_info(customer_id){
		var customer_data = {
			action: 'load_customer',
			nonce: shopp_admin_orders_vars.shopp_admin_orders_nonce,
			customer_id: Number(customer_id)
		};

		$.post(ajaxurl, customer_data, function(response){
			var r = $.parseJSON(response);

			//grab the states based on the country
			var countries = {
				action: 'get_customer_states',
				nonce: shopp_admin_orders_vars.shopp_admin_orders_nonce,
				billing_country: r['billing-country'],
				billing_state: r['billing-state'],
				shipping_country: r['shipping-country'],
				shipping_state: r['shipping-state']
			};

			$.post(ajaxurl, countries, function(response){
				var html = $.parseJSON(response);

				$('#billing-state').html(html.billing_states);
				$('#shipping-state').html(html.shipping_states);
			});

			//populate all of the fields on the customer form
			$.each(r, function(key, value){
				$('[name="'+key+'"]').val(value);
			});


			//finally, update all our carts
			var cart_update = {
				action: 'check_cart',
				nonce: shopp_admin_orders_vars.shopp_admin_orders_nonce
			};

			$.post(ajaxurl, cart_update, function(response){
				var data = $.parseJSON(response);

				$('table.cart tbody').html(data.table_cart);
				$('#display_cart').html(data.meta_cart);
			});

			//hide the indicator
			$('#customer-search-loading').hide();
		});
	}
	
	function load_shipping_methods(shipping_method) {
		var method_data = {
			action: 'shopp_ship_costs',
			method: shipping_method
		};
		
		$.get(ajaxurl, method_data, function(sm_response){
			var item_data = {
				action: 'update_cart',
				id: '',
				nonce: shopp_admin_orders_vars.shopp_admin_orders_nonce,
				qty: null
			};
			
			$.post(ajaxurl, item_data, function(response){
				var data = $.parseJSON(response);
	
				$('table.cart tbody').html(data.table_cart);
				$('#display_cart').html(data.meta_cart);
			});
		});
	}
});