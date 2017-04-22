<?php

// Prevent loading this file directly and/or if the class is already defined
if ( ! defined( 'ABSPATH' ) || class_exists( 'WPGitHubUpdater' ) || class_exists( 'WP_GitHub_Updater' ) )
	return;

/**
 *
 *
 * @version 1.6
 * @author Joachim Kudish <info@jkudish.com>
 * @link http://jkudish.com
 * @package WP_GitHub_Updater
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright (c) 2011-2013, Joachim Kudish
 *
 * GNU General Public License, Free Software Foundation
 * <http://creativecommons.org/licenses/GPL/2.0/>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
class Rapidology_GitHub_Updater {

	/**
	 * GitHub Updater version
	 */
	const VERSION = 1.6;

	/**
	 * @var $config the config for the updater
	 * @access public
	 */
	var $config;

	/**
	 * @var $missing_config any config that is missing from the initialization of this instance
	 * @access public
	 */
	var $missing_config;

	/**
	 * @var $github_data temporiraly store the data fetched from GitHub, allows us to only load the data once per class instance
	 * @access private
	 */
	private $github_data;


	/**
	 * Class Constructor
	 *
	 * @since 1.0
	 * @param array $config the configuration required for the updater to work
	 * @see has_minimum_config()
	 * @return void
	 */
	public function __construct( $config = array() ) {

		require_once( plugin_dir_path( __FILE__ ) . "Parsedown.php" );
		$defaults = array(
			'slug' => plugin_basename( __FILE__ ),
			'proper_folder_name' => dirname( plugin_basename( __FILE__ ) ),
			'sslverify' => true,
			'access_token' => '',
			'changelog',
			'githubAPIResult',
		);

		$this->config = wp_parse_args( $config, $defaults );
		//print_r($this->config);die();
		// if the minimum config isn't set, issue a warning and bail
		if ( ! $this->has_minimum_config() ) {
			$message = 'The GitHub Updater was initialized without the minimum required configuration, please check the config in your plugin. The following params are missing: ';
			$message .= implode( ',', $this->missing_config );
			_doing_it_wrong( __CLASS__, $message , self::VERSION );
			return;
		}

		$this->set_defaults();


		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'api_check' ) );
		// Hook into the plugin details screen
		if(strpos($_SERVER['SCRIPT_NAME'], 'plugin-install.php')) {
			if (isset($_GET['tab']) && strpos($_GET['tab'], 'rapidology') > 0) {
				add_filter('plugins_api', array($this, 'get_plugin_info'), 10, 3);
			}
		}

		if(isset($_GET['tab'])) {
			if(isset($_GET['plugin']) && $_GET['plugin'] == 'rapidology') {
				add_filter('plugins_api', array($this, 'get_plugin_info'), 10, 3);
			}
		}

		add_filter( 'upgrader_post_install', array( $this, 'upgrader_post_install' ), 10, 3 );
		// set timeout
		add_filter( 'http_request_timeout', array( $this, 'http_request_timeout' ) );

			// set sslverify for zip download
			add_filter('http_request_args', array($this, 'http_request_sslverify'), 10, 2);


	}

	public function has_minimum_config() {

		$this->missing_config = array();

		$required_config_params = array(
			'api_url',
			'raw_url',
			'github_url',
			'zip_url',
			'requires',
			'tested',
			'readme',
		);

		foreach ( $required_config_params as $required_param ) {
			if ( empty( $this->config[$required_param] ) )
				$this->missing_config[] = $required_param;
		}

		return ( empty( $this->missing_config ) );
	}


	/**
	 * Check wether or not the transients need to be overruled and API needs to be called for every single page load
	 *
	 * @return bool overrule or not
	 */
	public function overrule_transients() {
		return ( defined( 'WP_GITHUB_FORCE_UPDATE' ) && WP_GITHUB_FORCE_UPDATE );
	}


	/**
	 * Set defaults
	 *
	 * @since 1.2
	 * @return void
	 */
	public function set_defaults() {
		if ( !empty( $this->config['access_token'] ) ) {

			// See Downloading a zipball (private repo) https://help.github.com/articles/downloading-files-from-the-command-line
			extract( parse_url( $this->config['zip_url'] ) ); // $scheme, $host, $path

			$zip_url = $scheme . '://api.github.com/repos' . $path;
			$zip_url = add_query_arg( array( 'access_token' => $this->config['access_token'] ), $zip_url );

			$this->config['zip_url'] = $zip_url;
		}


		if ( ! isset( $this->config['new_version'] ) )
			$this->config['new_version'] = $this->get_new_version();

		if ( ! isset( $this->config['last_updated'] ) )
			$this->config['last_updated'] = $this->get_date();

		if ( ! isset( $this->config['description'] ) )
			$this->config['description'] = $this->get_description();
		if ( ! isset( $this->config['changelog'] ) )
			$this->config['changelog'] = $this->get_changelog();

		$plugin_data = $this->get_plugin_data();
		if ( ! isset( $this->config['plugin_name'] ) )
			$this->config['plugin_name'] = $plugin_data['Name'];

		if ( ! isset( $this->config['version'] ) )
			$this->config['version'] = $plugin_data['Version'];

		if ( ! isset( $this->config['author'] ) )
			$this->config['author'] = $plugin_data['Author'];

		if ( ! isset( $this->config['homepage'] ) )
			$this->config['homepage'] = $plugin_data['PluginURI'];

		if ( ! isset( $this->config['readme'] ) )
			$this->config['readme'] = 'README.md';

	}


	/**
	 * Callback fn for the http_request_timeout filter
	 *
	 * @since 1.0
	 * @return int timeout value
	 */
	public function http_request_timeout() {
		return 2;
	}

	/**
	 * Callback fn for the http_request_args filter
	 *
	 * @param unknown $args
	 * @param unknown $url
	 *
	 * @return mixed
	 */
	public function http_request_sslverify( $args, $url ) {
		if ( $this->config[ 'zip_url' ] == $url )
			$args[ 'sslverify' ] = $this->config[ 'sslverify' ];

		return $args;
	}


	/**
	 * Get New Version from GitHub
	 *
	 * @since 1.0
	 * @return int $version the version number
	 */
	public function get_new_version() {
		//$version = get_site_transient( md5($this->config['slug']).'_new_version' );
		if ( $this->overrule_transients() || ( !isset( $version ) || !$version || '' == $version ) ) {
			$raw_response = $this->remote_get( trailingslashit( $this->config['raw_url'] ) . basename( $this->config['slug'] ) );

			if ( is_wp_error( $raw_response ) )
				$version = false;

			if (is_array($raw_response)) {
				if (!empty($raw_response['body']))
					preg_match( '/.*Version\:\s*(.*)$/mi', $raw_response['body'], $matches );
			}

			if ( empty( $matches[1] ) )
				$version = false;
			else
				$version = $matches[1];

			// back compat for older readme version handling
			// only done when there is no version found in file name
			if ( false === $version ) {
				$raw_response = $this->remote_get( trailingslashit( $this->config['raw_url'] ) . $this->config['readme'] );

				if ( is_wp_error( $raw_response ) )
					return $version;

				preg_match( '#^\s*`*~Current Version\:\s*([^~]*)~#im', $raw_response['body'], $__version );

				if ( isset( $__version[1] ) ) {
					$version_readme = $__version[1];
					if ( -1 == version_compare( $version, $version_readme ) )
						$version = $version_readme;
				}
			}

			// refresh every 6 hours
			//if ( false !== $version )
			//	set_site_transient( md5($this->config['slug']).'_new_version', $version, 60*60*6 );
		}

		return $version;
	}


	/**
	 * Interact with GitHub
	 *
	 * @param string $query
	 *
	 * @since 1.6
	 * @return mixed
	 */
	public function remote_get( $query ) {
		if ( ! empty( $this->config['access_token'] ) )
			$query = add_query_arg( array( 'access_token' => $this->config['access_token'] ), $query );

		$raw_response = wp_remote_get( $query, array(
			'sslverify' => $this->config['sslverify']
		) );

		return $raw_response;
	}


	/**
	 * Get GitHub Data from the specified repository
	 *
	 * @since 1.0
	 * @return array $github_data the data
	 */
	public function get_github_data() {
		if ( isset( $this->github_data ) && ! empty( $this->github_data ) ) {
			$github_data = $this->github_data;
		} else {
			$github_data = get_site_transient( md5($this->config['slug']).'_github_data' );

			if ( $this->overrule_transients() || ( ! isset( $github_data ) || ! $github_data || '' == $github_data ) ) {
				$github_data = $this->remote_get( $this->config['api_url'] );

				if ( is_wp_error( $github_data ) )
					return false;

				$github_data = json_decode( $github_data['body'] );

				// refresh every 6 hours
				set_site_transient( md5($this->config['slug']).'_github_data', $github_data, 60*60*6 );
			}

			// Store the data in this class instance for future calls
			$this->github_data = $github_data;
		}

		return $github_data;
	}


	/**
	 * Get update date
	 *
	 * @since 1.0
	 * @return string $date the date
	 */
	public function get_date() {
		$_date = $this->get_github_data();
		return ( !empty( $_date->updated_at ) ) ? date( 'Y-m-d', strtotime( $_date->updated_at ) ) : false;
	}


	/**
	 * Get plugin description
	 *
	 * @since 1.0
	 * @return string $description the description
	 */
	public function get_description() {
		$_description = <<<BOD
		Rapidology is the only 100% free WordPress plugin that lets you quickly create beautiful email opt-in forms, popups, and widgets—no design or coding skills required. We offer over 100 mobile responsive templates and six different display types for growing your email newsletter.
		<br />
		Download this free WordPress plugin and add these six types of email list-builders to your website:
		<br />
    	<li>Popup Opt-In Forms: Prompt your visitors to opt in without annoying them. You can set Rapidology’s popup opt-in forms to appear automatically after a specific amount of time, after visitors reach a particular point on your page, or even after visitors leave a comment or make a purchase.</li>
    	<li>Slide-In Opt-In Forms: The slide-in form is the popup’s smooth, subtle cousin. It slides in at the bottom of your visitor’s screen, and can be set to appear after a specific time or at a specific point on the page.</li>
    	<li>Widget Opt-In Forms: Use widget forms to create attractive opt-in forms for your sidebar, footer, or any other widget-friendly areas on your site.</li>
    	<li>Protected Content Opt-In Forms: Offering valuable content in exchange for an email address is one of the most effective ways to grow your email list. Protected content forms allow you to offer content your visitors can “unlock” by opting in.</li>
    	<li>Below Content Opt-In Forms: You can use “Below Content” forms to place an opt-in opportunity at the end of your blog posts or pages. Visitors who have read an entire post are highly engaged, so this is an effective way to turn that engagement into a conversion.</li>
    	<li>Inline Opt-In Forms: Want to insert an opt-in form in the middle of a blog post, rather than the end? Inline forms make it easy. You can display these forms virtually anywhere you’d like on any post or page on your website.</li>
		<li>You can find out how all of your opt-in forms are performing right inside your Rapidology dashboard. We provide elegant visualizations of your conversion rates, impressions, subscriber counts, and more, so you can track your performance at a glance.</li>
		<li>You can also run split tests with Rapidology’s built-in split testing feature to discover what resonates with your audience and maximize your conversion rates.</li>
BOD;
		return $_description;
	}

	public function get_changelog(){
		$this->getRepoReleaseInfo();
		if(isset($this->config['githubAPIResult']->message) && strpos($this->config['githubAPIResult']->message, 'API rate limit exceeded') >= 0){
			return '';
		}
		$_changelog = class_exists( "Parsedown" )
			? Parsedown::instance()->parse( $this->config['githubAPIResult']->body )
			: $this->config['githubAPIResult']->body;
		return $_changelog;

	}

	/**
	 * Get Plugin data
	 *
	 * @since 1.0
	 * @return object $data the data
	 */
	public function get_plugin_data() {
		include_once ABSPATH.'/wp-admin/includes/plugin.php';
		$data = get_plugin_data( WP_PLUGIN_DIR.'/'.$this->config['slug'] );
		return $data;
	}


	/**
	 * Hook into the plugin update check and connect to GitHub
	 *
	 * @since 1.0
	 * @param object  $transient the plugin data transient
	 * @return object $transient updated plugin data transient
	 */
	public function api_check( $transient ) {

		// Check if the transient contains the 'checked' information
		// If not, just return its value without hacking it
	
		// check the version and decide if it's new
		$update = version_compare( $this->config['new_version'], $this->config['version'] );
		$this->config['old_version'] = $this->config['version'];
		if ( 1 === $update ) {
			$response = new stdClass;
			$response->new_version = $this->config['new_version'];
			$response->slug = $this->config['proper_folder_name'];
			$response->url = add_query_arg( array( 'access_token' => $this->config['access_token'] ), $this->config['github_url'] );
			$response->package = $this->config['zip_url'];

			// If response is false, don't alter the transient
			if ( false !== $response )
				$transient->response[ $this->config['slug'] ] = $response;
		}

		return $transient;
	}


	/**
	 * Get Plugin info
	 *
	 * @since 1.0
	 * @param bool    $false  always false
	 * @param string  $action the API function being performed
	 * @param object  $args   plugin arguments
	 * @return object $response the plugin info
	 */
	public function get_plugin_info( $false, $action, $response ) {
		

		$response->slug = $this->config['slug'];
		$response->plugin_name  = $this->config['plugin_name'];
		$response->name  = $this->config['plugin_name'];
		$response->version = $this->config['new_version'];
		$response->author = $this->config['author'];
		$response->homepage = $this->config['homepage'];
		$response->requires = $this->config['requires'];
		$response->tested = $this->config['tested'];
		$response->downloaded   = 0;
		$response->last_updated = $this->config['last_updated'];
		$response->sections = array( 'description' => $this->config['description'], 'changelog' => $this->config['changelog'] );
		$response->download_link = $this->config['zip_url'];
		return $response;
	}


	/**
	 * Upgrader/Updater
	 * Move & activate the plugin, echo the update message
	 *
	 * @since 1.0
	 * @param boolean $true       always true
	 * @param mixed   $hook_extra not used
	 * @param array   $result     the result of the move
	 * @return array $result the result of the move
	 */
	public function upgrader_post_install( $true, $hook_extra, $result ) {
		
		global $wp_filesystem;

		// Move & Activate
		$proper_destination = WP_PLUGIN_DIR.'/'.$this->config['proper_folder_name'];
		$wp_filesystem->move( $result['destination'], $proper_destination );
		$result['destination'] = $proper_destination;
		$activate = activate_plugin( WP_PLUGIN_DIR.'/'.$this->config['slug'] );

		// Output the update message
		$fail  = __( 'The plugin has been updated, but could not be reactivated. Please reactivate it manually.', 'github_plugin_updater' );
		$success = __( 'Plugin reactivated successfully.', 'github_plugin_updater' );

		return $result;

	}

	public function download_file(){
		//get file info
		$url = 'https://rapidology.com/download/rapidology.zip';
		$args=array(
			'timeout'	=> 20
		);
		$response = wp_remote_get($url);
		print_r($response);die();
		$zip = $response['body'];
		// In the header info is the name of the XML or CVS file. I used preg_match to find it
		preg_match("/.datafeed_([0-9]*)\../", $response['headers']['content-disposition'], $match);

		$file = WP_CONTENT_DIR."/upgrade/datafeed_".$match[1].".zip";
		$fp = fopen($file, "w");
		fwrite($fp, $zip);
		fclose($fp);

		WP_Filesystem();
		if (unzip_file($file, WP_CONTENT_DIR."/upgrade")) {
		// Now that the zip file has been used, destroy it
			unlink($file);
			return true;
		} else {
			return false;
		}

	}

	public function getRepoReleaseInfo() {
		// Only do this once
		if ( !empty( $this->githubAPIResult ) ) {
			return;
		}

		// Query the GitHub API
		$url = $this->config['release_url'];

		// We need the access token for private repos
		if ( !empty( $this->accessToken ) ) {
			$url = add_query_arg( array( "access_token" => $this->accessToken ), $url );
		}

		// Get the results
		$this->config['githubAPIResult'] = wp_remote_retrieve_body( wp_remote_get( $url ) );


		if ( !empty( $this->config['githubAPIResult']) ) {
			$this->config['githubAPIResult'] = json_decode( $this->config['githubAPIResult'] );
		}

		// Use only the latest release
		if ( is_array( $this->config['githubAPIResult'] ) ) {
			$this->config['githubAPIResult'] = $this->config['githubAPIResult'][0];
		}


	}


}