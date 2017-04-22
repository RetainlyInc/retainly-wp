<?php
if ( ! class_exists( 'RAD_Dashboard' ) ) {
	require_once( RAD_RAPIDOLOGY_PLUGIN_DIR . 'rapidology.php' );
}


/**
 * Class rapidology_retainly
 *
 * @version 1.0.0
 */
class rapidology_retainly extends RAD_Rapidology {

	/**
	 * Constructor
	 */
	public function __contruct(){
		parent::__construct();
		$this->permissionsCheck();
	}

	/**
	 * This is the form field that is used on the admin settings page
	 *
	 * @param string $form_fields
	 * @param string $service
	 * @param array $field_values
	 *
	 * @return string
	 */
	public function draw_retainly_form($form_fields, $service, $field_values) {
		$form_fields .= sprintf( '
					<div class="rad_dashboard_account_row">
						<label for="%1$s">%2$s</label>
						<input type="password" value="%3$s" id="%1$s">%4$s
					</div>',
			esc_attr( 'api_key_' . $service ),
			__( 'API key', 'rapidology' ),
			( '' !== $field_values && isset( $field_values['api_key'] ) ) ? esc_attr( $field_values['api_key'] ) : '',
			RAD_Rapidology::generate_hint( sprintf(
				'<a href="https://support.retainly.co/portal/home" target="_blank">%1$s</a>',
				__( 'Click here for more information', 'rapidology' )
			), false
			)
		);
		return $form_fields;
	}

	/**
	 * Retrieves the lists via Retainly API and updates the data in DB
	 *
	 * @param $api_key
	 * @param string $name
	 *
	 * @return string|void
	 */
	public function get_retainly_lists( $api_key, $name='') {

		if ( ! class_exists( 'Retainly_Rapidology' ) ) {
			require_once( RAD_RAPIDOLOGY_PLUGIN_DIR . 'subscription/retainly/retainly.php' );
		}

		$retainly = new Retainly_Rapidology($api_key);

		$retval = $retainly->call( 'segments' );

		if ( is_wp_error( $retval ) ) {
			return "Error connecting to Retainly: " . $retval->get_error_message();
		}

		$lists = $this->all_retainly_lists($retval);

		$this->update_account( 'retainly', sanitize_text_field( $name ), array(
			'lists'         => $lists,
			'api_key'       => sanitize_text_field( $api_key ),
			'is_authorized' => 'true',
		) );

		return 'success';
	}

	/**
	 * Format list data for saving to database
	 *
	 * @param $returnedLists
	 *
	 * @return array
	 */
	private function all_retainly_lists( $returnedLists ) {
		$current_lists = array();
		foreach ( $returnedLists as $list ) {
			$current_lists[ $list['id'] ]['id']                = sanitize_text_field( $list['id'] );
			$current_lists[ $list['id'] ]['name']              = sanitize_text_field( $list['name'] );
			$current_lists[ $list['id'] ]['subscribers_count'] = sanitize_text_field( $list['subscribers_count'] );
			$current_lists[ $list['id'] ]['growth_week']       = $this->calculate_growth_rate( 'retainly_' . $list->id );
		}

		return $current_lists;
	}

	/**
	 * Subscribes to Retainly
	 *
	 * @param string $api_key
	 * @param string $list_id
	 * @param string $email
	 * @param string $name
	 * @param string $last_name
	 * @return string "success" or error message
	 */
	public function subscribe_retainly( $api_key, $list_id, $email, $name = '', $last_name = '' ) {

		if ( ! class_exists( 'Retainly_Rapidology' ) ) {
			require_once( RAD_RAPIDOLOGY_PLUGIN_DIR . 'subscription/retainly/retainly.php' );
		}

		$retainly = new Retainly_Rapidology( $api_key );

		$args = array(
			'name'    => $name,
			'email'   => $email,
			'list_id' => $list_id,
			'source'  => 'WP'	
		);

		$retval = $retainly->call( 'contacts', json_encode($args), 'POST' );

		if ( $retval == 'success' ) {
			$error_message = 'success';
		} else {
			$error_message = 'failed';
		}

		return $error_message;
	}

}