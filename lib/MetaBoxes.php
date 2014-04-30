<?php

class MetaBoxes extends ShoppAdminOrders{

	function display_cart_meta(){
?>
		<div id="display_cart">
			<?php $cart = AdminOrderFunctions::check_meta_cart(); ?>

			<?php if($cart): ?>
				<?php echo $cart; ?>
			<?php else: ?>
				<ul>
					<li>Subtotal: <strong><span class="subtotal">$0.00</span></strong></li>
					<li>Shipping: <strong><span class="shipping">$0.00</span></strong></li>
					<li>Tax: <strong><span class="tax">$0.00</span></strong></li>
					<li>Total: <strong><span class="total">$0.00</span></strong></li>
				</ul>
			<?php endif; ?>
		</div>
		<div id="meta_controls">
			<ul>
				<li>
					<button type="submit" id="update_cart" name="update_cart" class="button-secondary" value="true">Update Cart</button>
					<button type="submit" id="empty_cart" name="empty_cart" class="button-secondary" value="true">Empty Cart</button>		
					<img src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" class="waiting" id="update-cart-loading" style="display: none;" />				
				</li>
			</ul>
		<hr />
			<ul>
				<li>
					<input type="submit" class="button-primary" value="Place Order" name="submit" />	
					<input type="hidden" name="save_order" value="true" />
				</li>
			</ul>

		</div>
<?php
	}

	function display_products(){
?>
		<div class="products">
			<p>
				<select name="product-search" data-placeholder="Choose a Product" id="product-search">
					<option value=""></option>
					<?php echo menuoptions(AdminOrderFunctions::get_products(), null, true); ?> 
				</select>
				<img src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" class="waiting" id="product-search-loading" style="display: none;" />
			</p>
		</div>
		<div class="variant-container">
			<p>
				<select name="variant-search" data-placeholder="Choose a Variant" id="variant-search">
					<option value=""></option>
				</select>
				<img src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" class="waiting" id="variant-search-loading" style="display: none;" />				
			</p>
		</div>
		<div id="product-container">
			<table class="cart">
				<thead>
					<tr class="header">
						<th scope="col" class="remove">Remove</th>
						<th scope="col" class="item">Cart Item</th>
						<th scope="col">Quantity</th>
						<th scope="col" class="money">Price</th>
						<th scope="col" class="money">Subtotal</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						$cart = AdminOrderFunctions::check_cart();
						if($cart) echo $cart;
					?>
				</tbody>
			</table>
		</div>
<?php
	}
	
	function display_customer(){
		$countries = AdminOrderFunctions::get_countries();
?>
		<div class="customer">
			<p>
				<select name="customer-search" data-placeholder="Choose a Customer" id="customer-search">
					<option value=""></option>
					<option value="create_new_customer">---Create New Customer---</option>
					<?php echo menuoptions(AdminOrderFunctions::get_customers(), null, true); ?> 
				</select>
				<img src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" class="waiting" id="customer-search-loading" style="display: none;" />
			</p>
		</div>

		<div id="customer-container">
			<div id="billing-information">
				<ul>
					<li>
						<strong>Billing Information</strong>
					</li>
					<li>
						<input type="text" name="billing-name" id="billing-name" title="Billing Name" class="required" value="" />
					</li>
					<li>
						<input type="text" name="billing-address" id="billing-address" title="Billing Address" class="required" value="" />
					</li>
					<li>
						<input type="text" name="billing-xaddress" id="billing-xaddress" title="Billing Address 2" class="required" value="" />
					</li>
					<li>
						<input type="text" name="billing-city" id="billing-city" title="Billing City" class="required" value="" />
					</li>
					<li>
						<select name="billing-state" id="billing-state">
						</select>
					</li>
					<li>
						<input type="text" name="billing-postcode" id="billing-postcode" title="Billing Country" class="required" value="" />
					</li>
					<li>
						<select name="billing-country" id="billing-country">
							<?php echo menuoptions($countries, null, true); ?> 
						</select>
					</li>
				</ul>
			</div>

			<div id="shipping-information">
				<ul>
					<li>
						<strong>Shipping Information</strong>
					</li>
					<li>
						<input type="text" name="shipping-name" id="shipping-name" title="Shipping Name" class="required" value="" />
					</li>
					<li>
						<input type="text" name="shipping-address" id="shipping-address" title="Shipping Address" class="required" value="" />
					</li>
					<li>
						<input type="text" name="shipping-xaddress" id="shipping-xaddress" title="Shipping Address 2" class="required" value="" />
					</li>
					<li>
						<input type="text" name="shipping-city" id="shipping-city" title="Shipping City" class="required" value="" />
					</li>
					<li>
						<select name="shipping-state" id="shipping-state">
						</select>
					</li>
					<li>
						<input type="text" name="shipping-postcode" id="shipping-postcode" title="Shipping Country" class="required" value="" />
					</li>
					<li>
						<select name="shipping-country" id="shipping-country">
							<?php echo menuoptions($countries, null, true); ?> 
						</select>
					</li>
				</ul>
			</div>
		</div>
<?php
	}

	function display_billing_information(){
?>
		<div id="billing-information">
			<ul>
				<li>
					<label for="billing-address">Street Address</label>
					<input type="text" name="billing-address" id="billing-address" size="30" title="Billing Address" class="required" value="<?php //echo ShoppOrder()->Billing->address; ?>">
				</li>
				<li>
					<label for="billing-xaddress">Street Address 2</label>
					<input type="text" name="billing-xaddress" id="billing-xaddress" size="30" title="Billing Address 2" class="required" value="<?php //echo ShoppOrder()->Billing->xaddress; ?>">
				</li>
				<li>
					<label for="billing-city">City</label>
					<input type="text" name="billing-city" id="billing-city" size="30" title="Billing City" class="required" value="<?php //echo ShoppOrder()->Billing->city; ?>">
				</li>
				<li>
					<label for="billing-state">State</label>
					<input type="text" name="billing-state" id="billing-state" size="30" title="Billing State" class="required" value="<?php //echo ShoppOrder()->Billing->state; ?>">
				</li>
				<li>
					<label for="billing-postcode">Zipcode</label>
					<input type="text" name="billing-postcode" id="billing-postcode" size="30" title="Billing Country" class="required" value="<?php //echo ShoppOrder()->Billing->postcode; ?>">
				</li>
				<li>
					<label for="billing-country">Country</label>
					<input type="text" name="billing-country" id="billing-country" size="30" title="Billing Country" class="required" value="<?php //echo ShoppOrder()->Billing->country; ?>">
				</li>
			</ul>
		</div>

<?php
	}

	function display_shipping_information(){
?>
		<div id="shipping-information">
			<ul>
				<li>
					<label for="shipping-address">Shipping Address</label>
					<input type="text" name="shipping-address" id="shipping-address" size="30" title="Shipping Address" class="required" />
				</li>
				<li>
					<label for="shipping-xaddress">Street Address 2</label>
					<input type="text" name="shipping-xaddress" id="shipping-xaddress" size="30" title="Shipping Address 2" class="required" />
				</li>
				<li>
					<label for="shipping-city">City</label>
					<input type="text" name="shipping-city" id="shipping-city" size="30" title="Shipping City" class="required" />
				</li>
				<li>
					<label for="shipping-state">State</label>
					<input type="text" name="shipping-state" id="shipping-state" size="30" title="Shipping State" class="required" />
				</li>
				<li>
					<label for="shipping-country">Country</label>
					<input type="text" name="shipping-country" id="shipping-country" size="30" title="Shipping Country" class="required" />
				</li>				
			</ul>
		</div>
<?php
	}
	
	function display_shipping_methods(){
?>
		<div id="shipping-methods">
			<ul>
				<?php while(shopp('shipping','methods')): ?>
					<li>
						<span>
							<label><?php shopp('shipping','method-selector'); ?>
							<?php shopp('shipping','method-name'); ?> &mdash;
							<strong><?php shopp('shipping','method-cost'); ?></strong><br />
							<small><?php shopp('shipping','method-delivery'); ?></small></label>
						</span>
					</li>
				<?php endwhile; ?>			
			</ul>
		</div>
<?php
	}

	function display_payment_information(){
?>
		<ul>
			<li>
				<input type="text" name="payment[card_num]" size="16" value="" /> 
				<input type="text" name="payment[exp_mon]" maxlength="2" size="2" value="" /> / 
				<input type="text" name="payment[exp_year]" maxlength="2" size="2" value="" />
			</li>
		</ul>
<?php
	}
}