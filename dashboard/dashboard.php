<?php
/**
 * RAD_Dashboard class
 * Generates the dashboard and supports saving/retrieving options data including import/export options.
 * Following variables should be set during class construction:
 *	_options_pagename - 'rad_dashboard_options_pagename',
 *
 *	plugin_class_name - 'rad_dashboard_plugin_class_name',
 *
 *	save_button_text - 'rad_dashboard_save_button_text',
 *
 *	plugin_name - 'rad_dashboard_plugin_name',
 *
 * All other action hooks and filters described before each function where applicable
 *
 * Important: dashboard_save_settings() function should be registered as wp_ajax action in the plugin which uses this class to avoid conflicts.
 * 	Action name should be following - 'wp_ajax_<plugin_class_name>_save_settings'
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'RAD_DASHBOARD_DIR', trailingslashit( dirname(__FILE__) ) );
define( 'RAD_DASHBOARD_PLUGIN_URI', plugins_url( '', __FILE__ ) );
define( 'RAD_PLUGIN_IMAGE_DIR', plugins_url('', dirname(__FILE__)  ).'/images');

class RAD_Dashboard {
	var $class_version = '2.0.0';
	var $protocol;

	function __construct( $args ) {
		//define filterable variables
		$this->_options_pagename = isset( $args['rad_dashboard_options_pagename'] ) ? $args['rad_dashboard_options_pagename'] : 'rad_dashboard';
		$this->plugin_class_name = isset( $args['rad_dashboard_plugin_class_name'] ) ? $args['rad_dashboard_plugin_class_name'] : '';
		$this->save_button_text = isset( $args['rad_dashboard_save_button_text'] ) ? $args['rad_dashboard_save_button_text'] : __( 'Save Changes', 'rad_dashboard' );
		$this->plugin_name = isset( $args['rad_dashboard_plugin_name'] ) ? $args['rad_dashboard_plugin_name'] : 'rad_dashboard';
		$this->options_path = isset( $args['rad_dashboard_options_path'] ) ? $args['rad_dashboard_options_path'] : RAD_DASHBOARD_DIR . 'includes/options.php';
		$this->top_level_page = isset( $args['rad_dashboard_options_page'] ) ? $args['rad_dashboard_options_page'] : 'tools';

		$this->protocol = is_ssl() ? 'https' : 'http';
		$this->dashboard_options = $this->get_options_array();
		$this->include_options();

		add_action( 'plugins_loaded', array( $this, 'add_class_localization' ) );
		add_action( 'wp_ajax_rad_dashboard_generate_warning', array( $this, 'generate_modal_warning' ) );
		add_action( 'wp_ajax_rad_dashboard_execute_live_search', array( $this, 'execute_live_search' ) );
		add_action( 'wp_ajax_rad_dashboard_activate_screen', array( $this, 'rad_dashboard_activate_screen' ) );
		add_action( 'admin_init', array( $this, 'set_post_types' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'dashboard_scripts' ) );
		add_action( 'admin_init', array( $this, 'process_settings_export' ) );
		add_action( 'admin_init', array( $this, 'process_settings_import' ) );
	}

	/**
	 * Adds class localization
	 * Domain: rad_dashboard
	 *
	 * @return void
	 */
	function add_class_localization() {
		load_plugin_textdomain( 'rad_dashboard', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	function get_options_array() {
		return get_option( 'rad_' . $this->plugin_name . '_options' ) ? get_option( 'rad_' . $this->plugin_name . '_options' ) : array();
	}

	public static function load_fonts_class() {
		if ( ! class_exists( 'RAD_Dashboard_Fonts' ) ) {
			require_once( RAD_DASHBOARD_DIR . 'includes/google_fonts.php' );
		}

		$fonts_class = new RAD_Dashboard_Fonts();

		return $fonts_class;
	}

	function permissionsCheck()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
    }
	function include_options() {
		require( $this->options_path );

		$this->dashboard_sections = $rad_all_sections;
		$this->assigned_options = $rad_assigned_options;
	}

	function update_option( $update_array ) {
		//we need to update current version of options, not cached version
		$dashboard_options = $this->get_options_array();

		$updated_options = array_merge( $dashboard_options, $update_array );
		update_option( 'rad_' . $this->plugin_name . '_options', $updated_options );
	}

	/**
	 * Removes option from the database based on the $option_key
	 * @return void
	 */
	function remove_option( $option_key ) {
		//we need to remove options from the current version of options, not cached version
		$dashboard_options = $this->get_options_array();

		if ( isset( $dashboard_options[$option_key] ) ) {
			unset( $dashboard_options[$option_key] );
			update_option( 'rad_' . $this->plugin_name . '_options', $dashboard_options );
		}
	}

	function dashboard_scripts( $hook ) {
		if ( "{$this->top_level_page}_{$this->_options_pagename}" !== $hook ) {
			return;
		}

		wp_enqueue_script( 'rad-dashboard-mce-js', RAD_DASHBOARD_PLUGIN_URI . '/js/tinymce/js/tinymce/tinymce.min.js', array( 'jquery' ), $this->class_version, true );
		wp_enqueue_style( 'rad-dashboard-css', RAD_DASHBOARD_PLUGIN_URI . '/css/rad_dashboard.css', array(), $this->class_version );
		wp_enqueue_script( 'rad-dashboard-js', RAD_DASHBOARD_PLUGIN_URI . '/js/rad_dashboard.js', array( 'jquery' ), $this->class_version, true );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();

		wp_localize_script( 'rad-dashboard-js', 'dashboardSettings', array(
			'dashboard_nonce'  => wp_create_nonce( 'dashboard_nonce' ),
			'search_nonce'     => wp_create_nonce( 'search_nonce' ),
			'ajaxurl'          => admin_url( 'admin-ajax.php', $this->protocol ),
			'save_settings'    => wp_create_nonce( 'save_settings' ),
			'generate_warning' => wp_create_nonce( 'generate_warning' ),
			'plugin_class'     => $this->plugin_class_name,
		) );
	}

	/**
	 * Generates the array of post types and categories registered in WordPress
	 * @return void
	 */
	function set_post_types() {
		$default_post_types = array( 'post', 'page' );
		$theme_name = wp_get_theme();
		$final_categories = array();

		$custom_post_types = get_post_types( array(
			'public'   => true,
			'_builtin' => false,
		) );

		if ( ( $key = array_search( 'wysijap', $custom_post_types ) ) !== false) {
			unset( $custom_post_types[$key] );
		}

		$this->dashboard_post_types = array_merge( $default_post_types, $custom_post_types );

		$categories = get_categories( array(
			'hide_empty' => 0,
		) );

		foreach ( $categories as $key => $value ) {
			$final_categories[$value->term_id] = $value->name;
		}

		$this->dashboard_categories['post'] = $final_categories;

		foreach ( $this->dashboard_post_types as $post_type ) {
			$taxonomy_name = '';
			$cats_array = array();

			switch ( $post_type ) {

				case 'project' :
					$taxonomy_name = 'project_category';
					break;

				case 'product' :
					$taxonomy_name = 'product_cat';
					break;

				case 'listing' :
					if ( 'Explorable' === $theme_name ) {
						$taxonomy_name = 'listing_type';
					} else {
						$taxonomy_name = 'listing_category';
					}
					break;

				case 'event' :
						$taxonomy_name = 'event_category';
					break;

				case 'gallery' :
					$taxonomy_name = 'gallery_category';
					break;
			}

			if ( '' !== $taxonomy_name && taxonomy_exists( $taxonomy_name ) ) {
				$cats_array = get_categories( 'taxonomy=' . $taxonomy_name . '&hide_empty=0' );
				if ( ! empty( $cats_array ) ) {
					$cats_array_final = array();

					foreach( $cats_array as $single_cat ) {
						$cats_array_final[$single_cat->cat_ID] = $single_cat->cat_name;
					}

					$this->dashboard_categories[$post_type] = $cats_array_final;
				}
			}
		}
	}

	/**
	 * Generates the output for the hint in dashboard options
	 * @return string
	 */
	function generate_hint( $text, $escape, $noPadding = false ) {
		$output = sprintf(
			'<span class="rad_dashboard_more_info rad_dashboard_icon %2$s">
				<span class="rad_dashboard_more_text">%1$s</span>
			</span>',
			true === $escape ? esc_html( $text ) : $text,
			true === $noPadding ? ' no_padding' :''
		);

		return $output;
	}

	/**
	 * Generates modal warning window for internal messages. Works via php or via Ajax
	 * Ok_link could be a link to particular tab in dashboard, external link or empty
	 */
	function generate_modal_warning( $message = '', $ok_link = '#', $hide_close = false, $ok_text = '', $custom_button_text = '', $custom_button_link = '#', $custom_button_class = '' ) {
		$ajax_request = isset( $_POST[ 'message' ] ) ? true : false;

		if ( true === $ajax_request ){
			if(! wp_verify_nonce( $_POST['generate_warning_nonce'] , 'generate_warning' )){
                die(-1);
            }
		}

		$message = isset( $_POST[ 'message' ] ) ? sanitize_text_field( stripslashes( $_POST[ 'message' ] ) ) : sanitize_text_field( $message );
		$ok_link = isset( $_POST[ 'ok_link' ] ) ? $_POST[ 'ok_link' ] : $ok_link;
		$hide_close = isset( $_POST[ 'hide_close' ] ) ? (bool) $_POST[ 'hide_close' ] : (bool) $hide_close;
		$ok_text = isset( $_POST[ 'ok_text' ] ) ? $_POST[ 'ok_text' ] : $ok_text;
		$custom_button_text = isset( $_POST[ 'custom_button_text' ] ) ? $_POST[ 'custom_button_text' ] : $custom_button_text;
		$custom_button_link = isset( $_POST[ 'custom_button_link' ] ) ? $_POST[ 'custom_button_link' ] : $custom_button_link;
		$custom_button_class = isset( $_POST[ 'custom_button_class' ] ) ? $_POST[ 'custom_button_class' ] : $custom_button_class;

		$result = sprintf(
			'<div class="rad_dashboard_networks_modal rad_dashboard_warning">
				<div class="rad_dashboard_inner_container">
					<div class="rad_dashboard_modal_header">%4$s</div>
					<div class="dashboard_icons_container">
						%1$s
					</div>
					<div class="rad_dashboard_modal_footer"><a href="%3$s" class="rad_dashboard_ok rad_dashboard_warning_button%6$s">%2$s</a>%5$s</div>
				</div>
			</div>',
			esc_html( $message ),
			'' == $ok_text ? esc_html__( 'Ok', 'rad_dashboard' ) : $ok_text,
			esc_url( $ok_link ),
			false === $hide_close ? '<span class="rad_dashboard_close"></span>' : '',
			'' != $custom_button_text ?
				sprintf(
					'<a href="%1$s" class="rad_dashboard_custom_btn rad_dashboard_warning_button%3$s">%2$s</a>',
					esc_url( $custom_button_link ),
					esc_html( $custom_button_text ),
					'' !== $custom_button_class
						? ' ' . esc_attr( $custom_button_class )
						: ''
				)
				: '',
			'' !== $custom_button_text ? ' rad_dashboard_2_btns' : ''
		);

		if ( $ajax_request ){
			echo $result;
			die;
		} else {
			return $result;
		}
	}

	/**
	 * Handles ajax request for save_settings button
	 * @return string
	 */
	function dashboard_save_settings( $options = array() ) {
		if(! wp_verify_nonce( $_POST['save_settings_nonce'], 'save_settings' )){
            die(-1);
        }
		$options = $_POST['options'];
		$option_sub_title = isset( $_POST['options_sub_title'] ) ? $_POST['options_sub_title'] : '';
		$error_message = $this->process_and_update_options( $options, $option_sub_title );
		die( $error_message );
	}

	/**
	 * Handles options array and import options into DataBase.
	 * $sub_array variable toggles between 2 option formats:
	 *	1) false -  [option_1, option_2, ... , option_n]
	 * 	2) true -  key_1[option_1, option_2, ... , option_n], key_2[option_1, option_2, ... , option_n], ... , key_n[option_1, option_2, ... , option_n]
	 *
	 *	@return string
	 */
	function prepare_import_settings( $options = array(), $sub_array = false ) {
		//if options stored in sub_arrays, then we need to go through each sub_array and save the data for each of them
		if ( true === $sub_array ) {
			foreach ( $options as $subtitle => $values ) {
				$error_message = $this->process_and_update_options( $values, $subtitle );
			}
		} else {
			 $error_message = $this->process_and_update_options( $options );
		}

		return $error_message;
	}

	/**
	 *
	 * supposed to check whether network is authorized or not
	 * verdict should be overriden from plugin using 'rad_<plugin_name>_authorization_verdict' filter
	 * FALSE will be returned by default
	 *
	 * @return bool
	 */
	function api_is_network_authorized( $network ) {
		$is_authorized = apply_filters( 'rad_' . $this->plugin_name . '_authorization_verdict', false, $network );

		return (bool) $is_authorized;
	}

	/**
	 *
	 * Activates the plugin and saves the code
	 *
	 */
	function rad_dashboard_activate_screen() {
		$code = ! empty( $_POST['code'] ) ? sanitize_text_field( $_POST['code'] ) : '';

		update_option('rad_rapidology_activated', 'rapidology_activated');
		update_option('rad_rapidology_activated_code', $code);

		die();
	}

	/**
	 *
	 * Executes live search through the posts/pages and returns the output to jQuery
	 *
	 * @return string
	 */
	function execute_live_search() {
		if(! wp_verify_nonce( $_POST['dashboard_search'] , 'search_nonce' )){
            die(-1);
        }

		$search_string = ! empty( $_POST['dashboard_live_search'] ) ? sanitize_text_field( $_POST['dashboard_live_search'] ) : '';
		$page          = ! empty( $_POST['dashboard_page'] ) ? sanitize_text_field( $_POST['dashboard_page'] ) : 1;
		$post_type     = ! empty( $_POST['dashboard_post_type'] ) ? sanitize_text_field( $_POST['dashboard_post_type'] ) : 'any';
		$full_content  = ! empty( $_POST['dashboard_full_content'] ) ? sanitize_text_field( $_POST['dashboard_full_content'] ) : 'true';

		$args['s']       = $search_string;
		$args['pagenum'] = $page;

		$results = $this->posts_query( $args, $post_type );
		if ( 'true' === $full_content ) {
			$output = '<ul class="rad_dashboard_search_results">';
		} else {
			$output = '';
		}

		if ( empty( $results ) ) {
			if ( 'true' === $full_content ) {
				$output .= sprintf(
					'<li class="rad_dashboard_no_res">%1$s</li>',
					esc_html__( 'No results found', 'rapidology' )
				);
			}
		} else {
			foreach( $results as $single_post ) {
				$output .= sprintf(
					'<li data-post_id="%2$s">[%3$s] - %1$s</li>',
					esc_html( $single_post['title'] ),
					esc_attr( $single_post['id'] ),
					esc_html( $single_post['post_type'] )
				);
			}
		}

		if ( 'true' === $full_content ) {
			$output .= '</ul>';
		}

		die( $output );
	}

	/**
	 *
	 * Retrieves the posts from WP based on search criteria. Used for live posts search.
	 * This function is based on the internal WP function "wp_link_query" from /wp-includes/class-wp-editor.php
	 *
	 * @return array
	 */
	function posts_query( $args = array(), $include_post_type = '' ) {
		if ( 'only_pages' === $include_post_type ) {
			$pt_names = array( 'page' );
		} elseif ( 'any' === $include_post_type || 'only_posts' === $include_post_type ) {
			$dashboard_post_types = ! empty( $this->dashboard_post_types ) ? $this->dashboard_post_types : array();
			$pt_names = array_values( $dashboard_post_types );

			if ( 'only_posts' === $include_post_type ) {
				unset( $pt_names[1] );
			}
		} else {
			$pt_names = $include_post_type;
		}

		$query = array(
			'post_type'              => $pt_names,
			'suppress_filters'       => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
			'post_status'            => 'publish',
			'posts_per_page'         => 20,
		);

		$args['pagenum'] = isset( $args['pagenum'] ) ? absint( $args['pagenum'] ) : 1;

		if ( isset( $args['s'] ) ) {
			$query['s'] = $args['s'];
		}

		$query['offset'] = $args['pagenum'] > 1 ? $query['posts_per_page'] * ( $args['pagenum'] - 1 ) : 0;

		$get_posts = new WP_Query;
		$posts = $get_posts->query( $query );
		if ( ! $get_posts->post_count ) {
			return false;
		}

		$results = array();
		foreach ( $posts as $post ) {
			$results[] = array(
				'id'        => (int) $post->ID,
				'title'     => trim( esc_html( strip_tags( get_the_title( $post ) ) ) ),
				'post_type' => $post->post_type,
			);
		}

		wp_reset_postdata();

		return $results;
	}

	/**
	 * Processes and saves options array into Database
	 * $option_sub_title variable toggles between 2 option formats:
	 *	1) '' -  [option_1, option_2, ... , option_n]
	 * 	2) '<subtitle>' -  <subtitle>[option_1, option_2, ... , option_n]
	 *
	 * Supports 'rad_<plugin_name>_after_save_options' hook
	 *
	 * @return string
	 */
	function process_and_update_options( $options, $option_sub_title = '' ) {
        $this->permissionsCheck();
		$this->dashboard_options = $this->get_options_array();
		$dashboard_options = $this->dashboard_options;
		$dashboard_sections = $this->dashboard_sections;
		$dashboard_options_assigned = $this->assigned_options;

		$error_message = '';
		$dashboard_options_temp = array();
		if ( ! is_array( $options ) ) {
			$processed_array = str_replace( array( '%5B', '%5D' ), array( '[', ']' ), $options );
			parse_str( $processed_array, $output );
			$array_prefix = true;
		} else {
			$output = $options;
			$array_prefix = false;
		}

		if ( isset( $dashboard_sections ) ) {
			foreach ( $dashboard_sections as $key => $value ) {
				$current_section = $key;
				if ( isset( $value[ 'contents' ] ) ) {
					foreach( $value[ 'contents' ] as $key => $value ) {
						$options_prefix = $current_section . '_' . $key;
						$options_array = $dashboard_options_assigned[$current_section . '_' . $key . '_options'];
						if ( isset( $options_array ) ) {
							foreach( $options_array as $option ) {
								$current_option_name = '';

								if ( isset( $option[ 'name' ] ) ) {
									if ( '' !== $option_sub_title ) {
										$current_option_name = $option[ 'name' ];
									} else {
										$current_option_name = $options_prefix . '_' . $option[ 'name' ];
									}
								}

								//determine where the value is stored and set appropriate value as current
								if ( true === $array_prefix ) {
									$current_option_value = isset( $output['rad_dashboard'][ $current_option_name ] ) ? $output['rad_dashboard'][ $current_option_name ] : false;
								} else {
									$current_option_value = isset( $output[ $current_option_name ] ) ? $output[ $current_option_name ] : false;
								}
								if ( isset( $option[ 'validation_type' ] ) ) {
									switch( $option[ 'validation_type' ] ) {
										case 'simple_array' :
											$dashboard_options_temp[ $current_option_name ] = ! empty( $current_option_value )
												? array_map( 'sanitize_text_field', $current_option_value )
												: array();
										break;

										case 'simple_text':
											$dashboard_options_temp[ $current_option_name ] = ! empty( $current_option_value )
												? sanitize_text_field( stripslashes( $current_option_value ) )
												: '';

												if ( function_exists ( 'icl_register_string' ) && isset( $option[ 'is_wpml_string' ] ) ) {
													$wpml_option_name = '' !== $option_sub_title
														? $current_option_name . '_' . $option_sub_title
														: $option_sub_title;
													icl_register_string( $this->plugin_name, $wpml_option_name, sanitize_text_field( $current_option_value ) );
												}
										break;

										case 'boolean' :
											$dashboard_options_temp[ $current_option_name ] = ! empty( $current_option_value )
												? in_array( $current_option_value, array( '1', false ) )
													? sanitize_text_field( $current_option_value )
													: false
												: false;
										break;

										case 'number' :
											$dashboard_options_temp[ $current_option_name ] = intval( stripslashes( ! empty( $current_option_value )
													? absint( $current_option_value )
													: ''
											) );
										break;

										case 'complex_array' :
											if ( isset( $current_option_name ) && '' != $current_option_name ) {
												if ( ! empty( $current_option_value ) && is_array( $current_option_value ) ) {
													foreach ( $current_option_value as $key => $value ) {
														foreach ( $value as $_key => $_value ) {
															$value[ $_key ] = sanitize_text_field( $_value );
														}

														$current_option_value[ $key ] = $value;
													}

													$dashboard_options_temp[ $current_option_name ] = $current_option_value;
												}
											}
										break;

										case 'url' :
											if ( isset( $current_option_name ) && '' != $current_option_name ) {
												$dashboard_options_temp[ $current_option_name ] = ! empty( $current_option_value )
													? esc_url_raw( stripslashes( $current_option_value ) )
													: '';
											}
										break;

										case 'html' :
											if ( isset( $current_option_name ) && '' != $current_option_name ) {
												$dashboard_options_temp[ $current_option_name ] = ! empty( $current_option_value )
													? stripslashes( esc_html( $current_option_value ) )
													: '';

												if ( function_exists ( 'icl_register_string' ) && isset( $option[ 'is_wpml_string' ] ) ) {
													$wpml_option_name = '' !== $option_sub_title
														? $current_option_name . '_' . $option_sub_title
														: $option_sub_title;
													icl_register_string( $this->plugin_name, $wpml_option_name, esc_html( $current_option_value ) );
												}
											}
										break;
									} // end switch
								}

								do_action( 'rad_' . $this->plugin_name . '_after_save_options', $dashboard_options_temp, $current_option_name, $option, $output );
							} // end foreach( $options_array as $option )
						} //if ( isset( $options_array ) )
					} // end foreach( $value[ 'contents' ] as $key => $value )
				} // end if ( isset( $value[ 'contents' ] ) )
			} // end foreach ( $dashboard_sections as $key => $value )
		} //end if ( isset( $dashboard_sections ) )

		if ( '' !== $option_sub_title ) {
			$final_array[$option_sub_title] = $dashboard_options_temp;
		} else {
			$final_array = $dashboard_options_temp;
		}

		RAD_Dashboard::update_option( $final_array );

		if ( ! empty( $final_array[ 'sharing_locations_manage_locations' ] ) && empty( $final_array[ 'sharing_networks_networks_sorting' ] ) ) {
			$error_message = $this->generate_modal_warning( __( 'Please select social networks in "Social Sharing / Networks" settings', 'rad_dashboard' ), '#tab_et_social_tab_content_sharing_networks' );
		}

		return $error_message;
	}

	/**
	 * Generates dashboard page based on the options from options.php file.
	 * Supports following hooks:
	 *	'rad_<plugin_name>_after_main_options'
	 *	'rad_<plugin_name>_after_header_options'
	 *	'rad_<plugin_name>_after_save_button'
	 *	'rad_<plugin_name>_header_start'
	 *	'rad_<plugin_name>_header_end'
	 *
	 * @return array
	 */
	function generate_options_page( $sub_array = '' ) {
        $this->permissionsCheck();
		include_once(RAD_RAPIDOLOGY_PLUGIN_DIR.'includes/static_content/marketing_sidebar.php');
		$this->dashboard_options = $this->get_options_array();
		$dashboard_options = $this->dashboard_options;
		$dashboard_sections = $this->dashboard_sections;
		$dashboard_options_assigned = $this->assigned_options;
		$dashboard_post_types = $this->dashboard_post_types;
		$dashboard_categories = $this->dashboard_categories;

		printf(
			'<div id="rad_dashboard_wrapper_outer" class="%1$s">
				<div id="rad_dashboard_wrapper" class="rad_dashboard">',
			esc_attr( $this->plugin_class_name )
		);

		// activate screen if not activated
		$activated = get_option('rad_rapidology_activated');

		if ($activated !== 'rapidology_activated') {
			echo '	';
		} else {

		printf(
					'<div id="rad_dashboard_header">
						<div id="rad_dashboard_logo" class="rad_dashboard_icon_%1$s icon-retainly-bg"></div>
						<ul>',
			esc_attr( $this->plugin_name )
		);

		echo '
						</ul>
					</div>
					<div class="clearfix"></div>

					<div id="rad_dashboard_navigation">
						<ul>';

		$menu_count = 0;
		if ( isset( $dashboard_sections ) ) {
			foreach ( $dashboard_sections as $key => $value ) {
				if ( $key !== 'header') {
					$current_section = $key;
					foreach( $value as $key => $value ) {
						if ( $key != 'title' ){
							printf( '<ul class="rad_dashboard_%1$s_nav">',
								esc_attr( $current_section )
							);
							foreach( $value as $key => $value ) {
								printf(
									'<li class="rad_dashboard_tab_content_side_%2$s">
										<a href="#tab_rad_dashboard_tab_content_%1$s_%2$s" id="rad_dashboard_tab_content_%1$s_%2$s" class="">
											<span>%3$s</span>
										</a>
									</li>',
									esc_attr( $current_section ),
									esc_attr( $key ),
									esc_html( $value )
								);
							}
							echo '
							</ul>
						</li>';
						} // end else
					} // end foreach( $value as $key => $value )
				} // end if ( $key !== 'header')
			} //end foreach ( $dashboard_sections as $key => $value )
		} // end if ( isset( $dashboard_sections ) )
		echo '	</ul>
				</div>
				<div id="rad_dashboard_content">';

					echo '<form id="rad_dashboard_options" enctype="multipart/form-data">';

		settings_fields( 'rad_dashboard_settings_group' );
		if ( isset( $dashboard_sections ) ) {
			foreach ( $dashboard_sections as $key => $value ) {
				$current_section = $key;
				if ( $key !== 'header' ) {
					foreach( $value[ 'contents' ] as $key => $value ) {
						$current_location = $key;
						$options_prefix = $current_section . '_' . $key;
						$options_array = $dashboard_options_assigned[$current_section . '_' . $key . '_options'];
						$sidebar_section = 'sidebar' == $key ? true : false;
						printf(
							'<div class="rad_dashboard_tab_content rad_dashboard_tab_content_%1$s_%2$s">',

							esc_attr( $current_section ),
							esc_attr( $key )
						);
						foreach( $options_array as $option) {
							$current_option_name = '';
							$hint_output = '';
							if ( isset( $option[ 'name' ] ) ) {
								if ( '' !== $sub_array ) {
									$current_option_name = $option[ 'name' ];
								} else {
									$current_option_name = $options_prefix . '_' . $option[ 'name' ];
								}
							}

							if ( '' !== $sub_array ) {
								$current_option_value = isset( $dashboard_options[$sub_array][$current_option_name] ) ? $dashboard_options[$sub_array][$current_option_name] : '';

								if ( ! isset( $dashboard_options[$sub_array][$current_option_name] ) && isset( $option[ 'default' ] ) ) {
									$current_option_value = isset( $option[ 'default_' . $current_location ] ) ? $option[ 'default_' . $current_location ] : $option[ 'default' ];
								}
							} else {
								$current_option_value = isset( $dashboard_options[ $current_option_name ] ) ? $dashboard_options[ $current_option_name ] : '';

								if ( ! isset( $dashboard_options[ $current_option_name ] ) && isset( $option[ 'default' ] ) ) {
									$current_option_value = isset( $option[ 'default_' . $current_location ] ) ? $option[ 'default_' . $current_location ] : $option[ 'default' ];
								}
							}

							if ( isset( $option[ 'hint_text' ] ) ) {
								$escape = isset( $option[ 'hint_text_with_links' ] ) ? (bool) true : (bool) false;

								$hint_output = $this->generate_hint( $option[ 'hint_text' ], $escape );
							}

							switch( $option[ 'type' ] ) {
								case 'select_shape' :
									printf(
										'<div class="rad_dashboard_row rad_dashboard_selection%2$s%4$s"%3$s%5$s>
											<h2>%1$s</h2>
											<div style="clear:both;"></div>',
										esc_html( $option[ 'title' ] ),
										isset( $option[ 'display_if' ] ) ? ' rad_dashboard_hidden_option' : '',
										isset( $option[ 'display_if' ] ) ? ' data-condition="' . esc_attr( $option[ 'display_if' ] ) .  '"': '',
										isset( $option[ 'class' ] ) ? ' ' . esc_attr( $option[ 'class' ] ) : '',
										isset( $option[ 'display_if' ] ) ? ' data-triggers_count="0"': '' //#5
									);
									foreach ( $option[ 'value' ] as $shape ){
										printf(
											'<div class="rad_dashboard_shape rad_dashboard_icon rad_dashboard_single_selectable %1$s">
												<div class="rad_dashboard_shape_tile rad_dashboard_icon rad_dashboard_shape_%2$s"></div>
												<input type="radio" class="rad_dashboard[%3$s]" name="rad_dashboard[%3$s]" value="%2$s" %4$s style="position: absolute; z-index: -1; visibility: hidden;">
											</div>',
											$shape === $current_option_value ? 'rad_dashboard_selected' : '',
											esc_attr( $shape ),
											esc_attr( $current_option_name ),
											checked( $current_option_value, $shape, false )
									   );
									}
									echo '</div>';
								break;

								case 'select' :
									$current_option_list = isset( $option[ 'value_'. $current_location ] ) ? $option[ 'value_'. $current_location ] : $option[ 'value' ];
									if ( isset( $option[ 'filter'] ) ) {
										$current_option_list = apply_filters( $option[ 'filter'], $current_option_list );
									}
									printf(
										'<li class="select%3$s%5$s%7$s"%4$s%6$s%8$s>
											<p>%1$s</p>
											<select name="rad_dashboard[%2$s]">',
										isset( $option['title_' . $current_location] ) ? esc_html( $option['title_' . $current_location] ) : esc_html( $option['title'] ),
										esc_attr( $current_option_name ),
										isset( $option[ 'display_if' ] ) ? ' rad_dashboard_hidden_option rad_dashboard_triggered_option' : '',
										isset( $option[ 'display_if' ] ) ? ' data-condition="' . esc_attr( $option[ 'display_if' ] ) .  '"': '',
										isset( $option[ 'conditional' ] ) ? ' rad_dashboard_conditional' : '',
										( isset( $option[ 'conditional' ] )
											? sprintf( ' data-enables="%1$s"',
												'' !== $sub_array ?
													esc_attr( $option[ 'conditional' ] )
													: esc_attr( $options_prefix . '_' . $option[ 'conditional' ] )
											)
											: ''
										),
										isset( $option[ 'class' ] ) ? ' ' . esc_attr( $option[ 'class' ] ) : '',
										isset( $option[ 'display_if' ] ) ? ' data-triggers_count="0"': '' //#8
									);

									foreach ( $current_option_list as $actual_value => $display_value ) {
										printf( '<option class ="%4$s" value="%1$s" %2$s>%3$s</option>',
											esc_attr( $actual_value ),
											selected( $actual_value, $current_option_value, false ),
											esc_html( $display_value ),
											isset($option['conditional'][$actual_value]) ? $option['conditional'][$actual_value] : ''
										);
									}

									echo '
											</select>';

									echo $hint_output;

									echo '
										</li>';
								break;

								case 'checkbox' :
									printf( '
										<li class="rad_dashboard_checkbox clearfix%5$s%6$s%9$s"%4$s%7$s%8$s>
											<p>%1$s</p>
											<input type="checkbox" id="rad_dashboard[%2$s]" name="rad_dashboard[%2$s]" value="1" %3$s>
											<label for="rad_dashboard[%2$s]"></label>',
										isset( $option['title_' . $current_location] ) ? esc_html( $option['title_' . $current_location] ) : esc_html( $option['title'] ),
										esc_attr( $current_option_name ),
										checked( $current_option_value, 1, false ),
										( isset( $option[ 'conditional' ] )
											? sprintf( ' data-enables="%1$s"',  '' !== $sub_array
												? esc_attr( $option[ 'conditional' ] )
												: esc_attr( $options_prefix . '_' . $option[ 'conditional' ] )
											)
											: ''
										),
										isset( $option[ 'conditional' ] ) ? ' rad_dashboard_conditional' : '',
										isset( $option[ 'display_if' ] ) ? ' rad_dashboard_hidden_option rad_dashboard_triggered_option' : '',
										isset( $option[ 'display_if' ] ) ? ' data-condition="' . esc_attr( $option[ 'display_if' ] ) .  '"': '',
										isset( $option[ 'display_if' ] ) ? ' data-triggers_count="0"': '',
										isset( $option[ 'class' ] ) ? ' ' . esc_attr( $option[ 'class' ] ) : '' //#9
									);

									echo $hint_output;

									echo '
										</li>';
								break;

								case 'checkbox_set' :
									$checkboxes_array = isset( $option[ 'value' ] ) ? $option[ 'value' ] : array();
									$current_option_value = isset( $current_option_value ) ? $current_option_value : array();

									if ( ! empty( $checkboxes_array ) ) {
										$i = 0;
										foreach ( $checkboxes_array as $value => $label ) {
											printf( '
												<li class="rad_dashboard_checkbox%6$s%8$s"%7$s>
													<input type="checkbox" id="rad_dashboard[%1$s][%4$s]" name="rad_dashboard[%1$s][]" value="%3$s" %2$s>
													<label for="rad_dashboard[%1$s][%4$s]"></label>
													<p>%5$s</p>
												</li>',
												esc_attr( $current_option_name ),
												checked( in_array( $value, $current_option_value ), true, false ),
												esc_attr( $value ),
												esc_attr( $i ),
												esc_attr( $label ), //#5
												isset( $option[ 'conditional' ][$value] ) ? ' rad_dashboard_conditional' : '',
												( isset( $option[ 'conditional' ][$value] )
													? sprintf( ' data-enables="%1$s"', esc_attr( $option[ 'conditional' ][$value] ) )
													: ''
												),
												isset( $option[ 'class' ] )
													? ' ' . esc_attr( $option[ 'class' ] ) . ' ' . esc_attr( $option[ 'class' ] ) . '_' . esc_attr( $value )
													: '' //#8
											);
											$i++;
										}
									}
								break;

								case 'input_field' :
									printf(
										'<li class="input clearfix%4$s%7$s"%5$s%10$s>
											<p>%1$s</p>
											<input type="%9$s" name="rad_dashboard[%2$s]" value="%3$s" placeholder="%6$s"%8$s>',
										isset( $option['title_' . $current_location] )
											? esc_html( $option['title_' . $current_location] )
											: esc_html( $option['title'] ),
										esc_attr( $current_option_name ),
										esc_attr( $current_option_value ),
										isset( $option[ 'display_if' ] ) ? ' rad_dashboard_hidden_option' : '',
										isset( $option[ 'display_if' ] ) ? ' data-condition="' . esc_attr( $option[ 'display_if' ] ) .  '"': '', //#5
										'number' == $option[ 'subtype' ] ? '0' : $option[ 'placeholder' ],
										'text' == $option[ 'subtype' ] ? ' rad_dashboard_longinput' : '',
										( isset( $option['class'] )
											? sprintf( ' class="%1$s"', esc_attr( $option['class'] ) )
											: ''
										),
										( isset( $option['hide_contents'] )
											? 'password'
											: 'text'
										),
										isset( $option[ 'display_if' ] ) ? ' data-triggers_count="0"': '' //#10
									);

									echo $hint_output;

									echo '
										</li>';
								break;

								case 'checkbox_posts' :
									echo '
									<li>
										<ul class="inline">';
									$i = 0;
									$current_option_value = '' == $current_option_value ? array() : $current_option_value;
									$checkbox_array = 'post_types' === $option['subtype'] ? $dashboard_post_types : $dashboard_categories['post'];

									$post_types = ! empty( $option['value'] ) ? $option['value'] : $checkbox_array;
									$array_of_saved_cats = isset( $current_option_value['previously_saved'] ) ? explode( ',', $current_option_value['previously_saved'] ) : array();

									foreach ( $post_types as $post_type => $id ){
										if ( 'post_cats' === $option['subtype'] ) {
											if ( ! isset( $current_option_value['previously_saved'] ) ) {
												$is_checked = true;
											} else {
												if ( isset( $current_option_value['auto_select'] ) && '1' === $current_option_value['auto_select'] ) {
													$is_checked = !in_array( $post_type, $array_of_saved_cats ) ? true : in_array( $post_type, $current_option_value );
												} else {
													$is_checked = in_array( $post_type, $current_option_value );
												}
											}
										}

										$conditional_class = '';
										$conditional_data = '';

										if ( 'post_types' === $option['subtype'] ) {
											if ( isset( $option[ 'conditional' ]['any_post'] ) && 'page' !== $id ) {
												$conditional_class = ' rad_dashboard_conditional';
												$conditional_data = sprintf( ' data-enables="%1$s"', esc_attr( $option['conditional']['any_post'] ) );
											}

											$conditional_class = isset( $option['conditional'][$id] ) ? ' rad_dashboard_conditional' : $conditional_class;
											$conditional_data = isset( $option['conditional'][$id] )
												? sprintf( ' data-enables="%1$s"', esc_attr( $option['conditional'][$id] ) )
												: $conditional_data;
										}

										printf( '
											<li class="rad_dashboard_checkbox%6$s"%7$s>
												<input type="checkbox" id="rad_dashboard[%1$s][%4$s]" name="rad_dashboard[%1$s][]" value="%3$s" %2$s>
												<label for="rad_dashboard[%1$s][%4$s]"></label>
												<p>%5$s</p>
											</li>',
											esc_attr( $current_option_name ),
											'post_types' === $option['subtype']
												? checked( in_array( $id, $current_option_value ), true, false )
												: checked( $is_checked, true, false ),
											'post_types' === $option['subtype'] ? esc_attr( $id ) : esc_attr( $post_type ),
											esc_attr( $i ),
											( 'post_cats' === $option['subtype'] && isset( $option['include_custom'] ) )
												? esc_attr( $id ) . __( ' ( post )', 'rapidology' )
												: esc_attr( $id ),
											esc_attr( $conditional_class ),
											$conditional_data
										);
										$i++;
									}

									if ( isset( $option['include_custom'] ) && 'post_cats' === $option['subtype'] ) {
										foreach ( $dashboard_post_types as $post_type ) {
											if ( 'post' != $post_type && 'page' != $post_type ) {
												if ( ! empty( $dashboard_categories[$post_type] ) ) {
													foreach ( $dashboard_categories[$post_type] as $cat_id => $cat_name ) {
														if ( ! isset( $current_option_value['previously_saved'] ) ) {
															$is_checked = true;
														} else {
															if ( isset( $current_option_value['auto_select'] ) && '1' === $current_option_value['auto_select'] ) {
																$is_checked = ! in_array( $cat_id, $array_of_saved_cats ) ? true : in_array( $cat_id, $current_option_value );
															} else {
																$is_checked = in_array( $cat_id, $current_option_value );
															}
														}
														printf( '
															<li class="rad_dashboard_checkbox%6$s"%7$s>
																<input type="checkbox" id="rad_dashboard[%1$s][%4$s]" name="rad_dashboard[%1$s][]" value="%3$s" %2$s>
																<label for="rad_dashboard[%1$s][%4$s]"></label>
																<p>%5$s</p>
															</li>',
															esc_attr( $current_option_name ),
															checked( $is_checked, true, false ),
															esc_attr( $cat_id ),
															esc_attr( $i ),
															esc_html( $cat_name ) . ' ( ' . $post_type . ' )',
															esc_attr( $conditional_class ),
															$conditional_data
														);
														$i++;
													}
												}
											}
										}
									}

									if ( 'post_cats' === $option['subtype'] ) {
										$current_option_value['auto_select'] = isset( $current_option_value['auto_select'] ) ? $current_option_value['auto_select'] : 0;
										$current_option_value['auto_select'] = ! isset( $current_option_value['previously_saved'] ) ? 1 : $current_option_value['auto_select'];
										$cat_id_array = array();

										printf( '
											<li class="rad_dashboard_checkbox rad_dashboard_auto_select">
												<input type="checkbox" id="rad_dashboard[%1$s][auto_select]" name="rad_dashboard[%1$s][auto_select]" value="1" %2$s>
												<label for="rad_dashboard[%1$s][auto_select]"></label>
												<p>%3$s</p>
											</li>',
											esc_attr( $current_option_name ),
											checked( $current_option_value['auto_select'], 1, false ),
											__( 'Automatically Check Categories Created In Future', 'rad_dashboard' )
										);

										foreach ( $checkbox_array as $id => $name ) {
											$cat_id_array[] = $id;
										}

										if ( isset( $option['include_custom'] ) ) {
											foreach ( $dashboard_post_types as $post_type ) {
												if ( 'post' != $post_type && 'page' != $post_type ) {
													if ( ! empty( $dashboard_categories[$post_type] ) ) {
														foreach ( $dashboard_categories[$post_type] as $cat_id => $cat_name ) {
															$cat_id_array[] = $cat_id;
														}
													}
												}
											}
										}

										$current_option_value['previously_saved'] = implode( ',', $cat_id_array );

										printf( '
											<li>
												<input type="hidden" id="rad_dashboard[%1$s][previously_saved]" name="rad_dashboard[%1$s][previously_saved]" value="%2$s" />
											</li>',
											esc_attr( $current_option_name ),
											$current_option_value['previously_saved']
										);
									}

									echo '
										</ul>
										<div style="clear:both;"></div>
									</li>';
								break;

								case 'section_start' :
									if(isset($option['hint_text'])) {
										$hint_output = $this->generate_hint( $option['hint_text'], $escape, true );
									}else{
										$hint_output = '';
									}
									printf(
										'%5$s<div class="rad_dashboard_form rad_dashboard_row%2$s%7$s"%3$s%4$s%8$s>
											%1$s %9$s
											%6$s
											<div style="clear:both;"></div>
											<ul>',
										isset( $option[ 'title' ] ) ? sprintf( '<h2>%1$s</h2>', esc_html( $option[ 'title' ] ) ) : '',
										isset( $option[ 'display_if' ] ) ? ' rad_dashboard_hidden_option' : '',
										isset( $option[ 'display_if' ] ) ? ' data-condition="' . esc_attr( $option[ 'display_if' ] ) .  '"': '',
										( isset( $current_option_name ) && '' != $current_option_name )
											? sprintf( ' data-name="rad_dashboard[%1$s]"', esc_attr( $current_option_name ) )
											: '',
										( isset( $option[ 'sub_section' ] ) && true == $option[ 'sub_section' ] )
											? '<li class="rad_dashboard_auto_height">'
											: '', //#5
										isset( $option[ 'subtitle' ] )
											? sprintf('<p class="rad_dashboard_section_subtitle">%1$s</p>', esc_html( $option[ 'subtitle' ] ) )
											: '',
										isset( $option[ 'class' ] ) ? ' ' . esc_attr( $option[ 'class' ] ) : '',
										isset( $option[ 'display_if' ] ) ? ' data-triggers_count="0"': '', //#8
										$hint_output
									);
								break;

								case 'section_end' :
									printf( '
												</ul>
											</div>
										%1$s',
										( isset( $option[ 'sub_section' ] ) && true == $option[ 'sub_section' ] ) ? '</li>' : ''
									);
								break;

								case 'text' :
									$button_class_text = (! empty( $option[ 'button_text' ]) ? str_replace( ' ', '_', $option[ 'button_text' ]) : '');
									$button_class_text = (strlen($button_class_text) > 0)? strtolower($button_class_text) : '';
									$button_text = (! empty( $option[ 'button_text' ]) ? $option[ 'button_text' ] : '');

									printf(
										'<li class="rad_dashboard_auto_height%6$s%10$s"%7$s%8$s>
											%9$s
											<textarea placeholder="%1$s" rows="%2$s" id="rad_dashboard_%4$s" name="rad_dashboard[%4$s]"%5$s>%3$s</textarea>%11$s
										</li>',
										esc_attr( $option[ 'placeholder' ] ),
										esc_attr( $option[ 'rows' ] ),
										esc_html( $current_option_value ),
										esc_attr( $current_option_name ),
										( isset( $option['class'] )
											? sprintf( ' class="%1$s"', esc_attr( $option['class'] ) )
											: ''
										), //#5
										isset( $option[ 'display_if' ] ) ? ' rad_dashboard_hidden_option' : '',
										isset( $option[ 'display_if' ] ) ? ' data-condition="' . esc_attr( $option[ 'display_if' ] ) .  '"': '',
										isset( $option[ 'display_if' ] ) ? ' data-triggers_count="0"': '',
										! empty( $option[ 'title' ] ) ? sprintf( '<p>%1$s</p>', esc_html( $option[ 'title' ] ) ) : '',
										! empty( $option[ 'title' ] ) ? ' rad_dashboard_text_with_title' : '', //#10
										! empty( $option[ 'button_text' ]) ? '<button class="rad_rapidology_textarea_button rad_rapidology_'.$button_class_text.'">'.$button_text.'</button>' : ''#11
									);
								break;

								case 'main_title' :

									printf(
										'<div class="rad_dashboard_row rad_dashboard_selection%4$s">
											<h1>%1$s</h1>
											%2$s
											%3$s
										</div>',
										esc_html( $option[ 'title' ] ),
										isset( $option[ 'subtitle' ] )
											? sprintf('<p style="padding-bottom: 1em;">%1$s</p>', esc_html( $option[ 'subtitle' ] ) )
											: '',
										isset( $option[ 'subtitle2' ] )
											? sprintf('<p>%1$s</p>', esc_html( $option[ 'subtitle2' ] ) )
											: '',
										isset( $option[ 'class' ] )	? ' ' . esc_attr( $option[ 'class' ] ) : ''
									);
								break;

								case 'note' :
									printf(
										'<div class="rad_dashboard_row rad_dashboard_note">
											<h2>%1$s</h2>
											<p>
												<span>%2$s</span>
											</p>
										</div>',
										esc_html__( 'Note:', 'rad_dashboard' ),
										esc_html( $option[ 'text' ] )
									);
								break;

								case 'color_picker' :
									printf(
										'<li class="input clearfix rad_dashboard_color_picker%5$s%8$s"%6$s%7$s>
											<p>%4$s</p>
											<input class="rad-dashboard-color-picker" type="text" maxlength="7" placeholder="%1$s" name=rad_dashboard[%2$s] value="%3$s" />
										</li>',
										esc_attr( $option[ 'placeholder' ] ),
										esc_attr( $current_option_name ),
										esc_attr( $current_option_value ),
										esc_html( $option[ 'title' ] ),
										isset( $option[ 'display_if' ] ) ? ' rad_dashboard_hidden_option' : '', // #5
										isset( $option[ 'display_if' ] ) ? ' data-condition="' . esc_attr( $option[ 'display_if' ] ) .  '"': '',
										isset( $option[ 'display_if' ] ) ? ' data-triggers_count="0"' : '',
										isset( $option[ 'class' ] ) ? ' ' . esc_attr( $option[ 'class' ] ) : '' //#8
									);
								break;

								case 'live_search' :
									if ( '' === $current_option_value ) {
										$current_option_value_array = array();
									} else {
										$current_option_value_array =  is_array( $current_option_value ) ? $current_option_value : explode( ',', $current_option_value );
									}

									$selected_posts_list = '';
									if ( ! empty( $current_option_value_array ) ) {
										$selected_posts = get_posts( array(
												'post__in'       => $current_option_value_array,
												'post_type'      => 'any',
												'posts_per_page' => -1,
											)
										);

										if ( ! empty( $selected_posts ) ) {
											foreach( $selected_posts as $single_post ) {
												$selected_posts_list .= sprintf( '
													<span data-post_id="%3$s">[%1$s] -  %2$s<span class="rad_dashboard_menu_remove"></span></span>',
													esc_html( $single_post->post_type ),
													esc_html( $single_post->post_title ),
													esc_attr( $single_post->ID )
												);
											}
										}
									}

									printf( '
										<li class="rad_dashboard_selected">%1$s</li>
										<li class="rad_dashboard_live_fields">
											<input type="text" class="rad_dashboard_search_posts" placeholder="%4$s" data-post_type="%5$s"/><span class="spinner"></span>
											<input type="hidden" id="rad_dashboard[%2$s]" name="rad_dashboard[%2$s]" value="%3$s" />
										</li>
										<li class="rad_dashboard_live_search_res">
											<ul class="rad_dashboard_search_results"></ul>
										</li>',
										$selected_posts_list,
										esc_attr( $current_option_name ),
										is_array( $current_option_value ) ? esc_attr( implode( ',', $current_option_value ) ) : esc_attr( $current_option_value ),
										esc_attr( $option[ 'placeholder' ] ),
										esc_attr( $option[ 'post_type' ] ) // supported post types: any, only_pages, only_posts, <post_type_name>
									);
								break;

								case 'image_upload' :
									printf( '
										<li class="rad_dashboard_upload_image%7$s"%8$s%9$s>
											<p>%6$s</p>
											<input name="rad_dashboard[%1$s][url]" type="text" class="rad-dashboard-upload-field" value="%2$s" />
											<input type="hidden" class="rad-dashboard-upload-id" name="rad_dashboard[%1$s][id]" value="%10$s">
											<input type="button" class="button button-upload rad-dashboard-upload-button" value="%3$s" data-choose="%4$s" data-update="%5$s" data-type="image" />
										</li>',
										esc_attr( $current_option_name ),
										isset( $current_option_value['url'] ) ? esc_attr( $current_option_value['url'] ) : '',
										esc_attr( $option['button_text'] ),
										esc_attr( $option['wp_media_title'] ),
										esc_attr( $option['wp_media_button'] ), //#5
										isset( $option['title_' . $current_location] ) ? esc_html( $option['title_' . $current_location] ) : esc_html( $option['title'] ),
										isset( $option[ 'display_if' ] ) ? ' rad_dashboard_hidden_option' : '',
										isset( $option[ 'display_if' ] ) ? ' data-condition="' . esc_attr( $option[ 'display_if' ] ) .  '"': '',
										isset( $option[ 'display_if' ] ) ? ' data-triggers_count="0"': '',
										isset( $current_option_value['id'] ) ? esc_attr( $current_option_value['id'] ) : '' //#10
									);
								break;

								case 'hidden_option' :
									if ( 'array' == $option[ 'subtype' ] ) {
										$current_option_value = '' == $current_option_value ? array() : $current_option_value;
										foreach ( $current_option_value as $single_value ) {
											printf( '<input name="rad_dashboard[%1$s][]" type="hidden" value="%2$s" />',
												esc_attr( $current_option_name ),
												esc_attr( $single_value )
											);
										}
									} else {
										printf( '<input name="rad_dashboard[%1$s]" id="rad_dashboard_%1$s" type="hidden" value="%2$s" />',
											esc_attr( $current_option_name ),
											esc_attr( $current_option_value )
										);
									}
								break;

								case 'button' :
									printf(
										'<li class="rad_dashboard_action_button">
											<a href="%1$s" class="rad_dashboard_icon %2$s">%3$s</a>
											<span class="spinner"></span>
										</li>',
										esc_url( $option[ 'link' ] ),
										esc_html( $option[ 'class' ] ),
										( true == $option[ 'authorize' ] && $this->api_is_network_authorized( $option[ 'action' ] ) )
											? __( 'Re-Authorize', 'rad_dashboard' ) :
											esc_html( $option[ 'title' ] )
									);
								break;

								case 'font_select' :
									$fonts_class = RAD_Dashboard::load_fonts_class();

									$current_option_list = $fonts_class->et_get_google_fonts();

									if ( isset( $option[ 'filter'] ) ) {
										$current_option_list = apply_filters( $option[ 'filter'], $current_option_list );
									}

									printf(
										'<li class="select%3$s%5$s%7$s"%4$s%6$s%8$s>
											<p>%1$s</p>
											<select name="rad_dashboard[%2$s]">',
										isset( $option['title_' . $current_location] )
											? esc_html( $option['title_' . $current_location] )
											: esc_html( $option['title'] ),
										esc_attr( $current_option_name ),
										isset( $option[ 'display_if' ] )
											? ' rad_dashboard_hidden_option rad_dashboard_triggered_option'
											: '',
										isset( $option[ 'display_if' ] ) ? ' data-condition="' . esc_attr( $option[ 'display_if' ] ) .  '"': '',
										isset( $option[ 'conditional' ] ) ? ' rad_dashboard_conditional' : '',
										( isset( $option[ 'conditional' ] )
											? sprintf( ' data-enables="%1$s"',  '' !== $sub_array
												? esc_attr( $option[ 'conditional' ] )
												: esc_attr( $options_prefix . '_' . $option[ 'conditional' ] )
											)
											: ''
										),
										isset( $option[ 'class' ] ) ? ' ' . esc_attr( $option[ 'class' ] ) : '',
										isset( $option[ 'display_if' ] ) ? ' data-triggers_count="0"': '' //#8
									);

									foreach ( $current_option_list as $font_name => $font_details ) {
										printf( '<option value="%1$s" class="rad_dashboard_font_%1$s" %2$s>%3$s</option>',
											esc_attr( $font_name ),
											selected( $font_name, $current_option_value, false ),
											esc_html( $font_name )
										);
									}

									echo '
											</select>';

									echo $hint_output;

									echo '</li>';
								break;

							} // end switch

							do_action( 'rad_' . $this->plugin_name . '_after_main_options', $option, $current_option_value );
						} // end foreach( $options_array as $option)

						echo '</div>';
					} // end foreach( $value['contents'] as $key => $value )
				} // end if ( $key !== 'header')
			} // end foreach ( $dashboard_sections as $key => $value )
		} // end if ( isset( $dashboard_sections ) )
		do_action( 'rad_' . $this->plugin_name . '_after_save_button' );
		printf(
			'<div class="rad_dashboard_save_changes %3$s">
				<button class="rad_dashboard_icon"%2$s>%1$s</button>
				<span class="spinner"></span>
			</div>
			<input type="hidden" name="action" value="save_dashboard" />',
			esc_html__( $this->save_button_text ),
			'' !== $sub_array
				? sprintf( 'data-subtitle="%1$s"', esc_attr( $sub_array ) )
				: '',
	 		apply_filters( 'rad_' . $this->plugin_name . '_save_button_class', '' )
		);



		echo '</form>';

		if ( isset( $dashboard_sections[ 'header' ][ 'contents' ] ) ) {
			do_action( 'rad_' . $this->plugin_name . '_header_start' );

			foreach ( $dashboard_sections[ 'header' ][ 'contents' ] as $key => $value ) {

				$options_array = $dashboard_options_assigned['header_' . $key . '_options'];
				printf(
					'<div class="rad_dashboard_tab_content rad_dashboard_tab_content_header_%1$s rad_dashboard_header_option">',
					esc_attr( $key )
				);
				if ( isset( $options_array ) ) {
					foreach( $options_array as $option ) {
						switch( $option[ 'type' ] ) {
							case 'import_export' :
								printf(
									'<div class="rad_dashboard_form rad_dashboard_row">
										<h1>%1$s</h1>
										<p>%2$s</p>
									</div>
									<div class="rad_dashboard_import_form rad_dashboard_row">
										<h2>%3$s</h2>
										<p class="rad_dashboard_section_subtitle">%4$s</p>
										<form method="post">
											<input type="hidden" name="rad_dashboard_action" value="export_settings" />
											<p>',
									esc_html( $option[ 'title' ] ),
									__( sprintf( 'You can either export your %1$s Settings or import settings from another install of %1$s below.', esc_html( ucfirst( $this->plugin_name ) ) ), 'rad_dashboard' ),
									__( sprintf( 'Export %1$s Settings', esc_html( ucfirst( $this->plugin_name ) ) ), 'rad_dashboard' ),
									__( 'Export the plugin settings for this site as a .json file. This allows you to easily import the configuration into another site.', 'rad_dashboard' )
								);

								wp_nonce_field( 'rad_dashboard_export_nonce', 'rad_dashboard_export_nonce' );

								printf(
									'			<button class="rad_dashboard_icon rad_dashboard_icon_importexport" type="submit" name="submit_export" id="submit_export">' . __( 'Export', 'rad_dashboard' ) . '</button>
											</p>
										</form>
									</div>

									<div class="rad_dashboard_form rad_dashboard_row">
										<h2>%1$s</h2>
										<div class="rad_dashboard_import_form rad_dashboard_row">
											<p class="rad_dashboard_section_subtitle">%2$s</p>
											<form method="post" enctype="multipart/form-data" action="%4$s.php?page=%3$s#tab_rad_dashboard_tab_content_header_importexport">
												<input type="file" name="import_file"/>',
									sprintf( __( 'Import %1$s Settings', 'rad_dashboard' ), esc_html( ucfirst( $this->plugin_name ) ) ),
									__( 'Import the plugin settings from a .json file. This file can be obtained by exporting the settings on another site using the form above.', 'rad_dashboard' ),
									$this->_options_pagename,
									'toplevel_page' == $this->top_level_page ? 'admin' : $this->top_level_page
								);

								wp_nonce_field( 'rad_dashboard_import_nonce', 'rad_dashboard_import_nonce' );

								echo '
											<button class="rad_dashboard_icon rad_dashboard_icon_importexport" type="submit" name="submit_import" id="submit_import">' . __( 'Import', 'rad_dashboard' ) . '</button>
											<input type="hidden" name="rad_dashboard_action" value="import_settings" />
										</form>
									</div>
								</div>';

							break;

						} // end switch

						do_action( 'rad_' . $this->plugin_name . '_after_header_options', $option, $dashboard_options );
					} // end foreach( $options_array as $option )
				} // end if ( isset( $options_array ) )

				echo '</div><!-- .rad_dashboard_tab_content_header_ -->';

			} // end foreach ( $dashboard_sections[ 'header' ][ 'contents' ] as $key => $value )

			do_action( 'rad_' . $this->plugin_name . '_header_end' );
		} // end if ( isset( $dashboard_sections[ 'header' ][ 'contents' ] ) )
			echo rapidology_marketing_sidebar(true);
		} // activate screen end
		echo '</div></div>';

	}

	/**
	 * Removes unneeded options from the export file. Array of options can be modified using 'rad_<plugin_name>_export_exclude' filter.
	 * @return array
	 */
	function remove_site_specific_fields( $settings ) {
		$remove_options = apply_filters( 'rad_' . $this->plugin_name . '_export_exclude', array(
			'access_tokens',
			'db_version',
		) );

		foreach ( $remove_options as $option ) {
			if ( isset( $settings[$option] ) ) {
				unset( $settings[$option] );
			}
		}

		return $settings;
	}

	function process_settings_export() {
        $this->permissionsCheck();
		if( empty( $_POST[ 'rad_dashboard_action' ] ) || 'export_settings' !== $_POST[ 'rad_dashboard_action' ] ) {
			return;
		}

		if( ! wp_verify_nonce( $_POST[ 'rad_dashboard_export_nonce' ], 'rad_dashboard_export_nonce' ) ) {
			return;
		}

		if( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$dashboard_options = $this->dashboard_options;

		ignore_user_abort( true );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $this->plugin_name . '-settings-export-' . date( 'm-d-Y' ) . '.json' );
		header( "Expires: 0" );

		echo json_encode( $this->remove_site_specific_fields( $dashboard_options ) );
		exit;
	}


	/**
	 * Processes .json file with settings and import settings into the database.
	 * Supports settings in 2 formats:
	 * 	1) [option_1, option_2, ... , option_n]
	 * 	2) key_1[option_1, option_2, ... , option_n], key_2[option_1, option_2, ... , option_n], ... , key_n[option_1, option_2, ... , option_n]
	 * Works with 1 format by default, format can be changed using 'rad_<plugin_name>_import_sub_array' filter. Set to TRUE to enable 2 format.
	 * Import array can be modified before importing data using 'rad_<plugin_name>_import_array' filter
	 */
	function process_settings_import() {
        $this->permissionsCheck();
		if( empty( $_POST[ 'rad_dashboard_action' ] ) || 'import_settings' !== $_POST[ 'rad_dashboard_action' ] ) {
			return;
		}

		if( ! wp_verify_nonce( $_POST[ 'rad_dashboard_import_nonce' ], 'rad_dashboard_import_nonce' ) ) {
			return;
		}

		if( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$end_array = explode( '.', $_FILES[ 'import_file' ][ 'name' ] );
		$extension = end( $end_array );
		$import_file = $_FILES[ 'import_file' ][ 'tmp_name' ];

		if ( empty( $import_file ) ) {
			echo $this->generate_modal_warning( __( 'Please select .json file for import', 'rad_dashboard' ) );
			return;
		}

		if ( $extension !== 'json' ) {
			echo $this->generate_modal_warning( __( 'Please provide valid .json file', 'rad_dashboard' ) );
			return;
		}

		// Retrieve the settings from the file and convert the json object to an array.
		$dashboard_settings = (array) json_decode( file_get_contents( $import_file ), true );
		$sub_array = apply_filters( 'rad_' . $this->plugin_name . '_import_sub_array', false );

		$error_message = $this->prepare_import_settings( apply_filters( 'rad_' . $this->plugin_name . '_import_array', $dashboard_settings ), $sub_array );

		if ( ! empty( $error_message ) ) {
			echo $this->generate_modal_warning( $error_message );
		} else {
			$options_page = 'toplevel_page' === $this->top_level_page ? 'admin' : $this->top_level_page;
			echo $this->generate_modal_warning( __( 'Options imported successfully.', 'rad_dashboard' ), admin_url( $options_page . '.php?page=' . $this->_options_pagename ), true );
		}
	}
}
