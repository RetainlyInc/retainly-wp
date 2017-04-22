<?php

/**
 * @return array
 * @description list of all email providers supported in Rapidology. This list is used whenc creating a new optin
 * and selecting a new provider. Please keep it alphabetical
 */
$email_providers_array = array(
	'retainly'			=>  'Retainly',
);


//setup new array for creating a new provider when creating a new optin
//setup default selection
$email_providers_new_optin = array(
	'empty'		=> __('Select One...', 'rapidology')
);
//loop through providers and add them to array. adding wordpress function for internationalization
foreach ($email_providers_array as $key => $value){
	$email_providers_new_optin[$key] = __( $value , 'rapidology');
}

//providers to show name fields on when creating optins
$show_name_fields =  array(
	'retainly'
);


/**
 * Get all optins that are valid for rapidbars for dropdown in the admin
 */

$all_optins = get_option('rad_rapidology_options');

$valid_optins = array(
	'nopopup'	=> 'Select Optin'
);
//array to check optin type against
$allowed_optins = array(
	'pop_up',
	'flyin'
);
$i=0;
unset($all_optins['accounts']);
unset($all_optins['db_version']);
//echo '<pre>';print_r($all_optins);die();
if(sizeof($all_optins) > 0 && is_array($all_optins)) {
	foreach ($all_optins as $optin => $options) {
		if (isset($options['optin_type']) && in_array($options['optin_type'], $allowed_optins) && $options['optin_status'] == 'active' && isset($options['display_on'][0]) && $options['display_on'][0] == 'everything') {
			$valid_optins[$optin] = $options['optin_name'];
			$i++;
		}
	}
}
/**
 * Rapidbar position and sticky or not
 */

$rapidbar_position = array(
	'stickytop'			=> 'Sticky on top',
	'nonsticktop'		=> 'Static top',
);
?>