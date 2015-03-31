<?php
/*
  Plugin Name: Band Session Master
  Plugin URI: http://meso.tokyo/band-session-master
  Description: バンドセッション会向けプラグイン
  Author: mesotokyo
  Version: 0.1
  Author URI: http://meso.tokyo/
*/

require_once dirname(__FILE__) . '/band-session-master.php';
require dirname(__FILE__) .  '/config.php';
require dirname(__FILE__) . '/config-page.php';

$bandSession = new BandSessionMaster($exportLink,
				     get_option('bsmaster_google_client_id'),
				     get_option('bsmaster_google_client_secret'),
				     get_option('bsmaster_google_access_token'));

function entrylist_shortcode_handler($atts, $content=null) {
	global $bandSession;
	$bandSession->fetchWorkSheet();
	return $bandSession->sessionEntries();
}

function memberlist_shortcode_handler($atts, $content=null) {
	global $bandSession;
	$bandSession->fetchWorkSheet();
	return $bandSession->sessionMembers();
}


add_shortcode('session-entry-list', 'entrylist_shortcode_handler');
add_shortcode('session-member-list', 'memberlist_shortcode_handler');

/* 管理画面 */
add_action('admin_menu', 'band_session_master_menu');

function band_session_master_menu() {
	add_options_page('Band Session Master',
			 'Band Session Master',
			 'manage_options',
			 'band-session-master-options',
			 'band_session_master_options');
}


