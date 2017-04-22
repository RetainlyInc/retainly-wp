<?php

/**
*@return string
* create shortcode for onclick popups
*/
function rapidology_on_click_intent( $atts, $content = null ) {
	extract(shortcode_atts(array(
		"optin_id" => '0',
	  	"display"	=>'inline'
	), $atts));
	return '<span style="display:'.$display.';" class="rad_rapidology_click_trigger_element"  data-optin_id="'.$optin_id.'">'.$content.'</span>';
}

add_shortcode("rapidology_on_click_intent", "rapidology_on_click_intent");


/**
 * @param string $wp
 * @param string $php
 * check for correct wp and php versions
 */
function rapid_version_check( $wp = '3.5', $php = '5.4' ) {
	global $wp_version;
	if ( version_compare( PHP_VERSION, $php, '<' ) )
		$php_check = 'PHP';
	if
	( version_compare( $wp_version, $wp, '<' ) )
		$wp_check = 'WordPress';


	if(isset($php_check)){
	?>
	<div class="error">
        	<p><?php _e( 'Retainly Notice: Your version of php is unsupported. You may notice some features may not work. Please upgrade to php 5.4 or higher.', 'rapidology' ); ?></p>
		</div>
	<?php
	}
	if(isset($wp_check)){
		?>
		<div class="error">
			<p><?php _e( 'Retainly Notice: Your version of Wordpress is unsupported. You may notice some features may not work. Please upgrade to WordPress 3.5 or higher.', 'rapidology' ); ?></p>
		</div>
		<?php
	}
}


/**
 * @param $name
 * @param $last_name
 * @return array
 * @description takes the first and last name field, runs so low level logic to decide which fields to drop them into
 *
 */

function rapidology_name_splitter($name, $last_name){

	$return_array=array(); //array of names to be returned
	if($last_name == ''){
		//check to see if firstname has a space, which is assumed to seperate first and last
		$first_space = stripos($name, ' '); //get first occurance of a space
		$second_space = strripos($name, ' '); // get second occurance of a space to check if 3 names were entered

		if($second_space > $first_space || $first_space > 0){
			$name_array = explode(' ', $name); //explode name into an array
			$first_name = array_shift($name_array);
			$name = $first_name;
			$last_name = implode(' ', $name_array); //implode all other names into a string and assign to last name
		}else{
			//set firstname equal to name as they look to have only entered 1 word for a name
			$name = $name;
			$last_name = '';//generic last name
		}

	}

	$return_array['name'] = $name;
	$return_array['last_name'] = $last_name;
	return $return_array;
}

//autoloader

function rapidology_ext_autoloader($class) {
	if(false !== strpos($class, 'rapidology')) {
		include RAD_RAPIDOLOGY_PLUGIN_DIR.'/includes/ext/'.$class.'/class.' . $class . '.php';
	}
}


function rename_plugin_folders_update()
{
	$new_file = WP_PLUGIN_DIR . '/rapidology-by-leadpages';
	$update_plugin_source = WP_PLUGIN_DIR . '/rapidology/rapidology_wp_updater';
	$update_plugin_dest = WP_PLUGIN_DIR . '/rapidology_updater2';
	if (!file_exists($new_file)) {
		global $wp_filesystem;
		if (empty($wp_filesystem)) {
			require_once(ABSPATH . '/wp-admin/includes/file.php');
			WP_Filesystem();
		}
		if(!file_exists($update_plugin_dest)) {
			$wp_filesystem->mkdir($update_plugin_dest);
			copy_dir($update_plugin_source, $update_plugin_dest, array('.DS_STORE'));
		}
			activate_plugin(plugin_basename($update_plugin_dest . '/rapidology_updater.php'));
	}

	$refresh = get_option('update_refresh');
	if($refresh == true){
		update_option('update_refresh', false);
		echo '<script> location.reload(); </script>';
	}
	//finally remove updater plugin
	if ( is_plugin_active( 'rapidology-by-leadpages/rapidology.php') ) {
		if(file_exists(	$update_plugin_dest = WP_PLUGIN_DIR . '/rapidology_updater2')){
			deactivate_plugins('rapidology_updater2/rapidology_updater.php');
			delTree($update_plugin_dest);
		}
	}
}

//add_filter( 'admin_init', 'rename_plugin_folders_update');



function delTree($dir) {
	$files = array_diff(scandir($dir), array('.','..'));
	foreach ($files as $file) {
		(is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
	}
	return rmdir($dir);
}
?>