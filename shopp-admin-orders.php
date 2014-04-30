<?php	 	 
/*
Plugin Name: Shopp Administrative Orders
Plugin URI: http://shopptoolbox.com
Description: This plugin allows the administrator of a Shopp web store to create orders manually. This plugin is part of the <a href="http://www.mygeeknc.com/shopp-toolbox/">Shopp Toolbox</a>.
Version: 1.0.6
Author: ShoppToolbox.com
Author URI: http://shopptoolbox.com
*/

if(!defined('ABSPATH')) die();

require('lib/Functions.php');
require('lib/MetaBoxes.php');
require('lib/AJAX.php');
require('lib/Update.php');
require('lib/welcome.php');

$ShoppAdminOrders = new ShoppAdminOrders();

class ShoppAdminOrders{
	
	function __construct(){
		add_action('admin_menu', array(&$this, 'add_menu'), 99);
		add_action('admin_notices', array(&$this, 'notices'));
		add_action('init', array(&$this, 'updater'));
		add_action('admin_enqueue_scripts', array(&$this, 'load_js'));

		add_action('wp_ajax_add_cart_product', array('ShoppAdminAJAX', 'ajax_add_cart_product'));
		add_action('wp_ajax_get_variations', array('ShoppAdminAJAX', 'ajax_get_variations'));
		add_action('wp_ajax_add_cart_variant', array('ShoppAdminAJAX', 'ajax_add_cart_variant'));
		add_action('wp_ajax_remove_cart_product', array('ShoppAdminAJAX', 'ajax_remove_cart_product'));
		add_action('wp_ajax_update_cart', array('ShoppAdminAJAX', 'ajax_update_cart'));
		add_action('wp_ajax_check_cart', array('ShoppAdminAJAX', 'ajax_check_cart'));
		add_action('wp_ajax_load_customer', array('ShoppAdminAJAX', 'ajax_load_customer'), 10);
		add_action('wp_ajax_get_customer_states', array('ShoppAdminAJAX', 'ajax_get_customer_states'), 10);
	}

	function load_js($hook){
		if('shopp-toolbox_page_shopp-admin-orders' != $hook)
			return;

		wp_enqueue_script('jquery');
		wp_enqueue_script('chosen', plugins_url('js/chosen.jquery.min.js', __FILE__), array( 'jquery' ), false, true );
		wp_enqueue_script('shopp-admin-orders', plugins_url('js/shopp-admin-orders.js', __FILE__), array('jquery'));
		wp_localize_script('shopp-admin-orders', 'shopp_admin_orders_vars', array('shopp_admin_orders_nonce' => wp_create_nonce('shopp_admin_orders_nonce'), 'create_new_customer_url' => admin_url('admin.php?page=shopp-customers&id=new')));
	}

	function load_css(){
		wp_enqueue_style('shopp-admin-orders-css', plugins_url('css/shopp-admin-orders.css', __FILE__));
	}

	function add_menu(){
		global $menu;
		$position = 52;
		while (isset($menu[$position])) $position++;

		if(!$this->toolbox_menu_exist()){
			add_menu_page('Shopp Toolbox', 'Shopp Toolbox', 'shopp_menu', 'shopp-toolbox', array('ShoppToolbox_Welcome', 'display_welcome'), plugin_dir_url(__FILE__) . 'img/toolbox.png', $position);
			$page = add_submenu_page('shopp-toolbox', 'Shopp Toolbox', 'Get Started', 'shopp_menu', 'shopp-toolbox', array('ShoppToolbox_Welcome', 'display_welcome'));
	        add_action( 'admin_print_styles-'.$page, array(&$this, 'load_css'));
		}

		$page = add_submenu_page('shopp-toolbox', 'Administrative Orders', 'Admin Orders', 'shopp_menu', 'shopp-admin-orders', array(&$this, 'display_settings'));

		//build our settings page too
        add_action( 'admin_print_styles-'.$page, array(&$this, 'load_css'));

        if(!is_ssl()){
        	if(!SHOPP_NOSSL){
				echo '<div class="error"><p><strong>Shopp Administrative Orders</strong>: You can not use this plugin unless SSL is enabled for the Administrative interface. See <a href="http://codex.wordpress.org/Administration_Over_SSL#To_Force_SSL_Logins_and_SSL_Admin_Access">this before proceeding</a> </p></div>';
				return false;
        	}
        }

        add_meta_box('stb_admin_cart_meta', 'Cart', array('MetaBoxes', 'display_cart_meta'), $page, 'side', 'core');
        add_meta_box('stb_admin_existing_customer', 'Customer', array('MetaBoxes', 'display_customer'), $page, 'normal', 'core');
        add_meta_box('stb_admin_order_products', 'Order Details', array('MetaBoxes', 'display_products'), $page, 'normal', 'core');
        add_meta_box('stb_admin_shipping_methods', 'Shipping Methods', array('MetaBoxes', 'display_shipping_methods'), $page, 'normal', 'core');

        //add_meta_box('stb_admin_payment_information', 'Payment Details', array('MetaBoxes', 'display_payment_information'), $page, 'side', 'core');
		//add_meta_box('stb_admin_promo', 'Promomotions', array('MetaBoxes', 'display_promo_box'), $page, 'side','core');
	}

	function toolbox_menu_exist(){
        global $menu;

        $return = false;
        foreach($menu as $menus => $item){
            if($item[0] == 'Shopp Toolbox'){
                $return = true;
            }
        }
        return $return;
    }

	function updater(){
		$args = array(
            'basename' => plugin_basename( __FILE__ ), //required
            'product_name' => 'shopp-admin-orders',  //post slug - must match
        );
        new ShoppToolbox_Updater($args);
	}

	function notices(){
		if(!is_plugin_active('shopp/Shopp.php')){
			echo '<div class="error"><p><strong>Shopp Administrative Orders</strong>: It is highly recommended to have the <a href="http://www.shopplugin.net">Shopp Plugin</a> active before using any of the Shopp Toolbox plugins.</p></div>';
		}
	}

	function display_settings(){
		if(isset($_REQUEST['empty_cart'])){

			if(shopp_cart_items_count() > 0){
				shopp_empty_cart();
			}

			echo '<div class="updated"><p>The cart has been emptied.</p></div>';
			
		}elseif(isset($_REQUEST['save_order'])){
			$cart_count = shopp_cart_items_count();
			if(empty($cart_count)){
				echo '<div class="updated"><p>You have no items in the cart.</p></div>';
			}else{
				$Purchase = AdminOrderFunctions::process_order($_REQUEST);

				if(!$Purchase){
					echo '<div class="error"><p>There was a problem submitting the order.</p></div>';
				}else{
					echo '<div class="updated"><p>Order #'.$Purchase->id.' order was successfully submitted.</p></div>';
				}
			}
		}
?>

		<div id="shopp-admin-orders" class="wrap">
			<h2>Administrative Orders</h2>
			<noscript>
				<div class="error"><p><strong>Shopp Administrative Orders</strong>: Whoa - it looks like Javascript is not working. You're going to have issues until it's fixed. Contact Support!</p></div>
			</noscript>
			<div class="description">
	            <p>This plugin allows you to add Shopp orders directly from the administrative interface. It requires Javascript to be fully functional, or you're going to have a bad time.</p>
	        </div>
			<form id="cart" action="" method="post">
              		<div id="poststuff" class="metabox-holder has-right-sidebar">
              			<div id="side-info-column" class="inner-sidebar">
							<?php do_meta_boxes('shopp-toolbox_page_shopp-admin-orders', 'side', null); ?>
						</div>

						<div id="post-body" class="has-sidebar">
						<div id="post-body-content" class="has-sidebar-content">
							<div id="titlediv">
								<div id="titlewrap">
								</div>
								<div class="inside">
									<?php do_meta_boxes('shopp-toolbox_page_shopp-admin-orders', 'normal', null); ?>
								</div>
							</div>
						</div>
						</div>

					</div>
                <?php wp_nonce_field('nonce_save_settings', 'stb_admin_order_nonce'); ?>
		    </form>
		</div>
<?php
	}
}
