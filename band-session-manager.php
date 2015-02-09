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

$bandSession = new BandSessionMaster($exportLink, $client_id, $client_secret, $accessToken);

function entrylist_shortcode_handler($atts, $content=null) {
	global $bandSession;
	$bandSession->sessionPlayers();
}

add_shortcode('session-entry-list', 'entrylist_shortcode_handler');

