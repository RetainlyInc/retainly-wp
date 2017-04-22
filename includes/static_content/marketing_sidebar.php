<?php
function rapidology_marketing_sidebar($remove = false){
	$imageurl = RAD_PLUGIN_IMAGE_DIR;
	if($remove === true){
		$class = ' non_marketing_page';
	}else{
		$class =' ';
	}
	$html = <<<boh
boh;

	return $html;
}
