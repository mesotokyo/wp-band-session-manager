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
require dirname(__FILE__) . '/config-page.php';


function createSession() {
	$bandSession = new BandSessionMaster("",
		get_option('bsmaster_google_client_id'),
		get_option('bsmaster_google_client_secret'),
		get_option('bsmaster_google_access_token'));
	return $bandSession;
}

function entrylist_shortcode_handler($atts, $content=null) {
	$target_url = html_entity_decode($atts['url']);
	$header = $atts['header'] ? intval($atts['header']) : 1;
	$start = $atts['start'] ? intval($atts['start']) : 2;
        $end = $atts['end'] ? intval($atts['end']) : 20;
	$count = $atts['count'] ? intval($atts['count']) : 12;
	$bandSession = createSession();
	$bandSession->fetchWorkSheet($target_url);
	return $bandSession->sessionEntries($header, $start, $end, $count);
}

function memberlist_shortcode_handler($atts, $content=null) {
	$target_url = html_entity_decode($atts['url']);
	$bandSession = createSession();
	$bandSession->fetchWorkSheet($target_url);
	return $bandSession->sessionMembers();
}

function entryhistory_shortcode_handler($atts, $content=null) {
	$target_url = html_entity_decode($atts['url']);
	$bandSession = createSession();
	$bandSession->fetchWorkSheet($target_url);
	return $bandSession->sessionEntryHistory();
}


add_shortcode('session-entry-list', 'entrylist_shortcode_handler');
add_shortcode('session-member-list', 'memberlist_shortcode_handler');
add_shortcode('session-entry-history', 'entryhistory_shortcode_handler');

/* 管理画面 */
add_action('admin_menu', 'band_session_master_menu');

function band_session_master_menu() {
	add_options_page('Band Session Master',
			 'Band Session Master',
			 'manage_options',
			 'band-session-master-options',
			 'band_session_master_options');
}


