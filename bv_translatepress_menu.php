<?php
/*
Plugin Name: bV version of TranslatePress - Extra Languages Add-on
Plugin URI: https://translatepress.com/
Description: This plugin replaces the Extra languages Add-on plugin of TranslatePress
Version: 1.0.1
Author: Cozmoslabs, Razvan Mocanu, Lai Consulting Team
GitLab Plugin URI: https://gitlab.com/beauvoyage/bv_translatepress_menu
Author URI: https://translatepress.com/
License: GPL2

== Copyright ==
Copyright 2017 Cozmoslabs (www.cozmoslabs.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */



function trp_el_run(){

	/** Initialize update here in the main plugin file. It is a must **/
	// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
	define( 'TRP_EL_STORE_URL', 'https://translatepress.com' );
	// the name of your product. This should match the download name in EDD exactly
	define( 'TRP_EL_ITEM_NAME', 'Multiple Languages' );
	if( !class_exists( 'TRP_EDD_SL_Plugin_Updater' ) ) {
		// load our custom updater
		include( dirname( __FILE__ ) . '/includes/class-edd-sl-plugin-updater.php' );
	}
	// retrieve our license key from the DB
	$license_key = trim( get_option( 'trp_license_key' ) );
	// setup the updater
	$edd_updater = new TRP_EDD_SL_Plugin_Updater( TRP_EL_STORE_URL, __FILE__, array(
		'version' 	=> '1.0.1', 		// current version number
		'license' 	=> $license_key, 	// license key (used get_option above to retrieve from DB)
		'item_name' => TRP_EL_ITEM_NAME, 	// name of this plugin
		'item_id'   => '228',							
		'author' 	=> 'Cozmoslabs',  // author of this plugin
		'beta' 		=> false
	)
	);
	/** End the update initialization here **/

	require_once plugin_dir_path( __FILE__ ) . 'class-extra-languages.php';
	if ( class_exists( 'TRP_Translate_Press' ) ) {
		new TRP_Extra_Languages();
	}
}
add_action( 'plugins_loaded', 'trp_el_run', 0 );

add_filter( 'trp_settings_tabs', 'bv_remove_translatepress_tabs' ,100);
function bv_remove_translatepress_tabs($tabs){
	$user_role = wp_get_current_user()->roles;
	$whitelabel = !in_array( strtolower('administrator'), $user_role );
	//$tabs = array($tabs[0]);
	return $whitelabel?array($tabs[0]):$tabs;
}

#add_action('admin_menu','bv_rename_translatepress',100);
function bv_rename_translatepress(){
	global $submenu;
	//error_log(print_r('subMenu: '.json_encode($submenu),true));
	if (property_exists($submenu, 'options-general.php') ){
	    foreach($submenu['options-general.php'] as $key=>$value){
		//error_log(print_r('subMenu: '.json_encode($value),true));
		if($value[0]=='TranslatePress'){
			$submenu['options-general.php'][$key][0]='Translation';
			//error_log(print_r('subMenu: '.json_encode($submenu['options-general.php'][$key]),true));
		}
	    }
	}
//	error_log(print_r('Menu2: '.json_encode($submenu['options-general.php']),true));
}


