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

/**
 * Whether the current user should see the unmodified TranslatePress UI.
 *
 * This was in_array( 'administrator', wp_get_current_user()->roles ). On
 * multisite a super admin usually holds no role on an individual subsite, so
 * roles is empty, the test failed, and the network administrator was shown
 * the white-labelled, tab-hidden UI. manage_options is held by subsite
 * administrators and super admins alike and still excludes editors and below,
 * so the outcome is unchanged for everyone except super admins.
 *
 * @return bool
 */
function bv_trp_user_sees_full_admin() {
	return current_user_can( 'manage_options' );
}

/**
 * How many translation languages this user's plan allows.
 *
 * @param int $user_id User id.
 * @return int Language limit; 0 when it cannot be determined.
 */
function bv_trp_language_limit( $user_id ) {
	// bv_get_required_level_status() lives in the voyage plugin and was called
	// unguarded; the two constants come from wp-config.php, and an undefined
	// constant is a fatal on PHP 8.
	$premium = defined( 'BV_TRANSLATION_LIMIT_PREMIUM' ) ? (int) BV_TRANSLATION_LIMIT_PREMIUM : 0;
	$basic   = defined( 'BV_TRANSLATION_LIMIT_BASIC' ) ? (int) BV_TRANSLATION_LIMIT_BASIC : 0;

	$limit = $basic;

	if ( function_exists( 'bv_get_required_level_status' ) ) {
		$limit = bv_get_required_level_status( $user_id, get_user_meta( $user_id, 'private_plan', true ) )
			? $premium
			: $basic;
	}

	// Site-wide override. Was get_option( 'bv_translation_limit', null ) ??
	// $limit, which falls through correctly for a missing option but not for
	// one saved as an empty string -- that is not null, so the limit silently
	// became ''.
	$override = get_option( 'bv_translation_limit', null );

	if ( null !== $override && '' !== $override && is_numeric( $override ) ) {
		$limit = (int) $override;
	}

	return $limit;
}

add_filter( 'trp_settings_tabs', 'bv_remove_translatepress_tabs' ,100);
/**
 * Show non-administrators only the first TranslatePress settings tab.
 *
 * This is the real white-label control; the JS that hides the tab strip is
 * cosmetic on top of it.
 *
 * @param array $tabs Settings tabs.
 * @return array
 */
function bv_remove_translatepress_tabs( $tabs ) {
	if ( bv_trp_user_sees_full_admin() ) {
		return $tabs;
	}

	// Was array( $tabs[0] ) unconditionally: an undefined-key warning and a
	// broken settings screen if the array were ever empty or not zero-indexed.
	if ( ! is_array( $tabs ) || ! isset( $tabs[0] ) ) {
		return $tabs;
	}

	return array( $tabs[0] );
}

/*
 * bv_rename_translatepress() lived here, renaming the "TranslatePress"
 * submenu entry to "Translation". Its add_action was commented out, so it
 * never ran -- and it would have fatalled on PHP 8 if enabled, because it
 * called property_exists( $submenu, 'options-general.php' ) where $submenu is
 * an array, and property_exists() rejects an array argument with a TypeError.
 * The correct test is isset( $submenu['options-general.php'] ).
 *
 * Removed rather than repaired: nothing referenced it, and the rename it
 * attempted is superseded by the tabs filter above.
 */


