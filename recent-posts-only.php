<?php
/*
Plugin Name: Recent Posts Only
Description: Restricts posts displayed on the home page to a specified length of time. Options are in Settings > Reading.
Author: Daniel Berkman
Version: 1.0.1
Author URI: http://www.inexi.com
*/

/**
 * This string prevents hacks from accessing the file directly.
 */
defined('ABSPATH') or die("Cannot access pages directly.");

// Main hook and filtering function

add_action( 'pre_get_posts', 'recentpostsonly_home_hook' );

function recentpostsonly_home_hook( $query ) {
	if (is_home()) {
		$options = get_option('recentpostsonly');
		if ($options <> null && $options[tspan] <> 'none' && $query->is_main_query()) {
				global $options_recentpostsonly;
				$options_recentpostsonly = $options;
				add_filter( 'posts_where', 'recentpostsonly_filter_where', 10, 2 );
} } }
function recentpostsonly_filter_where( $where, $query ) {
	remove_filter( 'posts_where', 'recentpostsonly_filter_where', 10, 2 );
	global $options_recentpostsonly;
	if ($options_recentpostsonly[tspan] == 'year_cal') $where .= " AND post_date >= '".date('Y')."-01-01'";
	else {
		$rpo_days = 1000;
		if ($options_recentpostsonly[tspan] == 'year_days') $rpo_days = 365;
		elseif ($options_recentpostsonly[tspan] == 'other') $rpo_days = $options_recentpostsonly[other];
		$where .= " AND post_date > '" . date('Y-m-d', strtotime('-'.$rpo_days.' days')) . "'";
	} 
	return $where;
}

// Admin options page


add_action( 'admin_init', 'recentpostsonly_options' );

function recentpostsonly_options() {
	add_settings_section('recentpostsonly_settings_section','Recent Posts Only','recentpostsonly_section_callback','reading');
	add_settings_field('recentpostsonly','Select the time span for posts shown on the home page','recentpostsonly_options_callback','reading','recentpostsonly_settings_section');
	add_settings_field('recentpostsonly_other', ' ', 'recentpostsonly_section_callback', 'reading','recentpostsonly_settings_section');
	register_setting('reading','recentpostsonly','recentpostsonly_options_validate');
}
function recentpostsonly_section_callback(){
	echo '';
}
function recentpostsonly_options_callback() {
	$options = get_option('recentpostsonly');
	echo '<input type="radio" name="recentpostsonly[tspan]" value="none" '.(($options[tspan]=='none')?'checked="checked" ':"").'/> No restriction<br />';
	echo '<input type="radio" name="recentpostsonly[tspan]" value="year_cal" '.(($options[tspan]=='year_cal')?'checked="checked" ':"").'/> Current calendar year (only '.date('Y').')<br />';
	echo '<input type="radio" name="recentpostsonly[tspan]" value="year_days" '.(($options[tspan]=='year_days')?'checked="checked" ':"").'/> Past year (365 days)<br />';
	echo '<input type="radio" name="recentpostsonly[tspan]" value="other" '.(($options[tspan]=='other')?'checked="checked" ':"").'/> Other: <input type="text" name="recentpostsonly[other]" size="3" value="'.$options[other].'" /> days';
}
function recentpostsonly_options_validate($input) {
	$options = get_option('recentpostsonly');
	$valid = array();
	$valid[tspan] = $input[tspan];
	if(is_numeric($input[other])) $valid[other] = (int)$input[other];
	else $valid[other] = $options[other];
	return $valid;
}

//Plugin init
function recentpostsonly_activator() {
	global $wpdb;
	if (function_exists('is_multisite') && is_multisite()) {
		// check if it is a network activation - if so, run the activation function for each blog id
		if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1)) {
	                $old_blog = $wpdb->blogid;
			// Get all blog ids
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				recentpostsonly_activate();
			}
			switch_to_blog($old_blog);
			return;
		}	
	} 
	recentpostsonly_activate();		

}
function recentpostsonly_activate() {
	if(!get_option('recentpostsonly')) {
		$options = array();
		$options[tspan] = 'none';
		$options[other] = '';
		update_option('recentpostsonly',$options);
} }
register_activation_hook( __FILE__, 'recentpostsonly_activator' );
?>
