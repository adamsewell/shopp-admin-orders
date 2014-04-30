<?php
if ( ! defined( 'ABSPATH' ) || class_exists( 'ShoppToolbox_Welcome' ) )
	return;
	
class ShoppToolbox_Welcome{

	function display_welcome(){
		$Updater = new ShoppToolbox_Updater();
		$license_key = get_option('toolbox_license_key');

		if(isset($_REQUEST['save_license']) && wp_verify_nonce($_REQUEST['stb_welcome_nonce'], 'nonce_save_license') && !empty($_REQUEST['license_key'])){
			$results = $Updater->activate_key($_REQUEST['license_key']);
			if(!$results){
					echo '<div class="error"><p>There was an error activating your site, please contact support</p></div>';
			}else{
				echo '<div class="updated"><p>Your license key is now activated for this site.</p></div>';
				$license_key = esc_attr($_REQUEST['license_key']);
			}
		}elseif(isset($_POST['remove_license']) && wp_verify_nonce($_REQUEST['stb_welcome_nonce'], 'nonce_save_license')){
			$Updater->deactivate_key($_REQUEST['license_key']);
			$license_key = '';

			echo '<div class="updated"><p>Your license key has been deactivated from this site.</p></div>';
		}
?>
		<div id="stb-welcome" class="wrap">
				<h2>Get Started with the Shopp Toolbox</h2>
		        <div  class="metabox-holder">
			        <div  class="postbox">
			            <div class="handlediv" title="Click to toggle">
			                <br />
			            </div>
			            <h3 class="hndle"><span>Thank You</span></h3>
			            <div class="inside">
			            	<p class="description">
			            		I wanted to start out with just a thank you. Thank you for supporting the Shopp Toolbox. We strive to produce useful and stable add-ons for the Shopp e-commerce plugin. If we can make your store just a tad easier to manage or if we can fill a gap that Shopp doesn't on it's own, then we've down our job. If you ever experience any issues with our plugins, please let us know. We will fix your issues. 
				            </p>
			            </div> <!--inside-->
			        </div><!--postbox-->
			    </div><!--metabox-holder-->
		        <div class="metabox-holder">
			        <div id="shopp_courtesy" class="postbox">
			            <div class="handlediv" title="Click to toggle">
			                <br />
			            </div>
			            <h3 class="hndle"><span>Support</span></h3>
			            <div class="inside">
			            	<p class="description">
			            		Please enter your license key in the form below. If your license is not activated on this site. We can not support it. Thank you. <strong>Note that once you've activated your license for one plugin, you don't need to activate it for any other plugin on this same site.</strong>
				            </p>
				            <form action="" method="post">
				            	<ul>
				            		<li>
				            			<?php if(empty($license_key)): ?>

				            				<p><span class="description ">License Key: </span> <input type="text" size="35" name="license_key" value="" /> <input type="submit" class="button-primary" value="Activate" /></p>
							            	<input type="hidden" name="save_license" value="true" />

				            			<?php else: ?>

				            				<p><span class="description ">License Key: </span> <input type="password" size="35" name="license_key" value="<?php echo $license_key; ?>" /> <input type="submit" class="button-secondary" value="Deactivate" /></p>
				            				<input type="hidden" name="remove_license" value="true" />
				            			<?php endif; ?>

				            		</li>
				            	</ul>
	                            <?php wp_nonce_field('nonce_save_license', 'stb_welcome_nonce'); ?>
				            </form>
				            <hr />
				            <p class="description">
				            	<strong>For support, please visit <a href="http://www.mygeeknc.com/shopp-toolbox/support">Shopp Toolbox Support</a></strong>
				            </p>
			            </div> <!--inside-->
			        </div><!--postbox-->
			    </div><!--metabox-holder-->
		</div>
<?php
	}
}