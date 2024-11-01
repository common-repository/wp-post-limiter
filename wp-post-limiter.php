<?php
/*
Plugin Name: WP Post Limiter
Plugin URI: http://saquery.com/wordpress
Description: WP Post Limiter
Version: 1.0
Author: Stephan Ahlf
Author URI: http://saquery.com
*/

/*
Copyright 2009-2011 Stephan Ahlf 

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

	global $saq_postlimit_options;
	$saq_postlimit_options = get_option("saq_postlimit_options");
	if(!$saq_postlimit_options) {
		$saq_postlimit_options = array();
		$saq_postlimit_options["default_limit"] = 1;
		update_option("saq_postlimit_options",$saq_postlimit_options);
	} 


	
	function saqPostLimitOptions(){
		global $saq_postlimit_options;
		if(isset($_POST['saq_save'])){
			$default_limit = (int)$_POST['default_limit'];
				$saq_postlimit_options["default_limit"] = $default_limit;
				update_option("saq_postlimit_options",$saq_postlimit_options);
				echo '<div class="updated fade"><p>Options saved succesful.</p></div>';
		}
		print '
		<div class="wrap">
		<h2>WP Post Limiter Settings</h2>
		<form method="post" id="saq_save">
		<fieldset class="options">
		<p>Default Post Limit:</p>
		<input name="default_limit" type="text" id="default_limit" value="'.$saq_postlimit_options["default_limit"].'" style="text-align:center;" size="2" maxlength="2" /> 
		</fieldset>
		<input type="submit" name="saq_save" />
		</form>
		</div>';
	}

	function saqGetPostCountInfo(){
		global $wpdb;
		return $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status='publish' and post_author = ".wp_get_current_user()->ID);
	}

  	function saqPostLimiter_admin_menu(){
		global $menu, $submenu,$saq_postlimit_options;
		
		add_submenu_page('options-general.php', 'Post Limiter', 'Post Limiter', 'administrator', __FILE__, 'saqPostLimitOptions' );
		if (wp_get_current_user()->ID!=1) {
			$numposts = saqGetPostCountInfo();
			foreach ($menu as $index => $item) {
				if ($item[2] == 'index.php' ){
					//unset($menu[$index]);
					if (saqPostLimiter_isPage('index')) {
						wp_redirect(get_option('siteurl') . '/wp-admin/profile.php');
						exit;
					}
				}
				if (!empty($submenu[$item[2]]))
					foreach ($submenu[$item[2]] as $subindex => $subitem) 
						if ($numposts>=$saq_postlimit_options["default_limit"] && $subitem[2] == 'post-new.php') unset($submenu[$item[2]][$subindex]);
			}
		}
	}

	function saqPostLimiter_favorite_actions($menu){
		global $saq_postlimit_options;
		if (wp_get_current_user()->ID!=1) {
			if (saqGetPostCountInfo()>=$saq_postlimit_options["default_limit"]){
				foreach ($menu as $index => $item) {
					unset($menu[$index]);
				}
			}
		}
		return $menu;
	}

	function saqPostLimiter_isPage($p){
		$curr = strtolower($_SERVER["REQUEST_URI"]);
		if ($curr == '/wp-admin/') $curr.="index.php";
		return basename($curr)==$p.'.php';
	}

	function saqPostLimiter(){
		global $saq_postlimit_options;
		if (wp_get_current_user()->ID!=1) {
			if (saqPostLimiter_isPage('post-new')) {

				$postCount=saqGetPostCountInfo();
				if ($postCount>=$saq_postlimit_options["default_limit"]){
					print '<p>Permission denied. Your account is limited to '.$saq_postlimit_options["default_limit"].' posts. Your personal Postcount: '.$postCount.'. <a href="javascript_void(0);" onclick="history.back()">Back...</a></p>';
					exit;
				}
			}
		}
	}


	add_filter('admin_init', 'saqPostLimiter');
	add_action('admin_menu', 'saqPostLimiter_admin_menu');
	add_action('favorite_actions', 'saqPostLimiter_favorite_actions');

?>
