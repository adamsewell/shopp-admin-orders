<?php	
/*
	Shopp Toolbox Updater Class
	Adam Sewell - Shopp Toolbox
	Version: 0.4
*/

if ( ! defined( 'ABSPATH' ) || class_exists( 'ShoppToolbox_Updater' ) )
	return;

class ShoppToolbox_Updater{

	function __construct($args = array()){

		extract(wp_parse_args($args));
		$this->basename = $basename;
		$this->product_name = $product_name;
		$this->update_url = 'http://shopptoolbox.com/license/';

		/****************************************/
		/*
			For testing purposes only.
		*/
		//set_site_transient('update_plugins', null);
		/****************************************/
		add_filter( 'http_request_args', array( &$this, 'disable_wporg_request' ), 5, 2 );
		add_filter( 'pre_set_site_transient_update_plugins', array(&$this, 'check_for_updates'));
		add_filter( 'plugins_api_result', array( &$this, 'plugins_api_result' ), 10, 3 );
	}

	/**
	 * Disable request to wp.org plugin repository
	 * @link http://markjaquith.wordpress.com/2009/12/14/excluding-your-plugin-or-theme-from-update-checks/
	 * @since 0.1.2
	 */
	public function disable_wporg_request( $r, $url ){

		/* If it's not a plugin request, bail early */
		if ( 0 !== strpos( $url, 'http://api.wordpress.org/plugins/update-check' ) )
			return $r;

		/* this plugin slug */
		$plugin_slug = dirname( $this->basename );

		/* unserialize data */
		$plugins = unserialize( $r['body']['plugins'] );

		/* default value */
		$to_disable = '';

		/* check if plugins object is set */
		if  ( isset( $plugins->plugins ) ){

			$all_plugins = $plugins->plugins;

			/* loop all plugins */
			foreach ( $all_plugins as $plugin_base => $plugin_data ){

				/* only if the plugin have the same folder */
				if ( dirname( $plugin_base ) == $plugin_slug ){

					/* get plugin to disable */
					$to_disable = $plugin_base;
				}
			}
		}
		/* unset this plugin only */
		if ( !empty( $to_disable ) )
			unset( $plugins->plugins[ $to_disable ] );

		/* serialize it back */
		$r['body']['plugins'] = serialize( $plugins );
		return $r;
	}

	function plugins_api_result($res, $action, $args){

		if(isset($args->slug) && $args->slug == $this->basename && $action == 'plugin_information'){

			$args = array(
				'action' => 'plugin_information',
				'plugin_name' => $this->basename,
				'license_key' => get_option('toolbox_license_key')
			);

			$results = $this->send($args);

			if(is_wp_error($results)){
				$res = new WP_Error( 'plugins_api_failed', '<p>' . __( 'An Unexpected HTTP Error occurred during the API request. Shopp Toolbox.', 'text-domain' ) . '</p><p><a href="?" onclick="document.location.reload(); return false;">' . __( 'Try again', 'text-domain' ) . '</a></p>', $request->get_error_message() );
			}else{

				$requested_data = maybe_unserialize( $results );

				if(is_object($requested_data) && !empty($requested_data)){
					$plugin_info = get_plugin_data(plugin_dir_path(dirname(dirname(__FILE__))) . $this->basename, false);

					/* Create plugin info data object */
					$info = new stdClass;

					/* Data from repo */
					$info->version = $requested_data->version;
					$info->download_link = $requested_data->download_link;
					$info->requires = $requested_data->requires;
					$info->tested = $requested_data->tested;
					$info->sections = $requested_data->sections;

					/* Data from plugin */
					$info->slug = $this->basename;
					$info->author = $plugin_info['Author'];
					$info->uri = $this->update_url;

					/* Other data needed */
					$info->external = true;
					$info->downloaded = 0;

					/* Feed plugin information data */
					$res = $info;
				}else{
					$res = new WP_Error( 'plugins_api_failed', __( 'An unknown error occurred', 'text-domain' ), wp_remote_retrieve_body( $request ) );
				}
			}
		}

		return $res;
	}

	function check_for_updates($transient){

		if(is_admin()){
			if(empty($transient->checked))
				return $transient;

			if(!get_option('toolbox_license_key'))
				return $transient;

			$plugin_info = get_plugin_data(plugin_dir_path(dirname(dirname(__FILE__))) . $this->basename, false);

			$args = array(
				'action' => 'check-updates',
				'plugin_name' => $this->basename,
				'product_name' => $this->product_name,
				'version' => $plugin_info['Version'],
				'license_key' => get_option('toolbox_license_key')
			);

			$response = unserialize($this->send($args));

			if(false !== $response){
				$transient->response[$this->basename] = $response;
			}		
		}


		return $transient;
	}

	function set_license_key($license){
		if(!get_option('toolbox_license_key')){
			return update_option('toolbox_license_key', $license);
		}
		return false;
	}

	function remove_license_key($license){
		if(get_option('toolbox_license_key')){
			return delete_option('toolbox_license_key');
		}
		return false;
	}

	function activate_key($license = ''){
		$license = (!empty($license) ? $license : get_option('toolbox_license_key'));
		$args = array(
			'action' => 'activate-license',
			'plugin_name' => $this->basename,
			'license_key' => $license
		);

		$results = $this->send($args);

		if(str_true($results)){
			$this->set_license_key($license);
			return true;
		}
		
		return false;
	}

	function deactivate_key($license = ''){
		$license = (!empty($license) ? $license : get_option('toolbox_license_key'));
		$args = array(
			'action' => 'deactivate-license',
			'plugin_name' => $this->basename,
			'license_key' => $license
		);

		$results = $this->send($args);

		var_dump(str_true($results));
		
		if(str_true($results)){
			$this->remove_license_key($license);
			return true;
		}

		return false;
	}

	function send($args){
		global $wp_version;

		$response = wp_remote_post($this->update_url, array('body' => $args, 'user-agent' => 'WordPress/'.$wp_version.'; '.get_home_url()));		

		//echo '<pre>'; print_r($response); echo '</pre>';

		if(is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200){
			return false;
		}
		
		$body = wp_remote_retrieve_body($response);

		if($body){

			if(strtolower($body) === 'true' || strtolower($body) === 'false'){
				return (bool)$body;
			}

			return $body;
		}else{
			return false;
		}
	}
}