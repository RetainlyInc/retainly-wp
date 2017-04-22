<?php
function rad_marketing_page(){
	$imageurl = RAD_PLUGIN_IMAGE_DIR;
	$html = <<<BOH
	<div class="marketing_header">
		<h1>Welcome to Retainly Plugin</h1>
		<p>Glad to see you here. Hope the below links will be useful to you.</p>
	</div>
	<div class="marketing_wrapper">
	<div class="rapidology_marketing_main">
			<div id="video_course">
				<div class="box_header">
					<p>Bounce Rate-Should I be Worried? 🏈</p>
				</div>
				<div class="rapidology_marketing_content">
						<p>Bounce Rate is the percentage of visitors that exit your website after viewing only one page.</p>
						<div><a href = "https://blog.retainly.co/bounce-rate-worried/" target="_blank">Read</a></div>
				</div>
			</div>

			<div id="live_training">
				<div class="box_header">
					<p>Adaptive Learning in Marketing Automation</p>
				</div>
				<div class="rapidology_marketing_content">
						<p>Adaptive Learning in Marketing automation is about introducing the right product or the right communication to the right customer at the right time through the right channel to satisfy the customer’s evolving demands.</p>
					<div>
						<a href = "https://blog.retainly.co/adaptive-learning-in-marketing-automation/" target="_blank">Read</a>
					</div>
				</div>
			</div>
	</div>
BOH;

	return $html;
}

include_once('marketing_sidebar.php');

$main = rad_marketing_page();
$sidebar = rapidology_marketing_sidebar();
echo $main;
echo $sidebar;