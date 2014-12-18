<?php
/*
  Plugin Name: Band Session Master
  Plugin URI: http://meso.tokyo/band-session-master
  Description: バンドセッション会向けプラグイン
  Author: mesotokyo
  Version: 0.1
  Author URI: http://meso.tokyo/
*/

require_once dirname(__FILE__) . '/google-api-php-client/src/Google/Client.php';
require_once dirname(__FILE__) . '/google-api-php-client/src/Google/Service/Drive.php';
require dirname(__FILE__) .  '/config.php';

class BandSessionMaster {
	function __construct($exportLink, $client, $secret, $token) {
		$this->exportLink = $exportLink;
		$this->createAccessToken($client, $secret, $token);
	}

	function createAccessToken($client_id, $secret, $token) {
		$client = new Google_Client();

		// Get your credentials from the console
		$client->setClientId($client_id);
		$client->setClientSecret($secret);
		$client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
		$client->setScopes(array('https://www.googleapis.com/auth/drive'));

		if ($token == '') {
			$authUrl = $client->createAuthUrl();

			//Request authorization
			print "Please visit:\n$authUrl\n\n";
			print "Please enter the auth code:\n";
			$authCode = trim(fgets(STDIN));

			// Exchange authorization code for access token
			$token = $client->authenticate($authCode);
		}
		$client->setAccessToken($token);
		$this->client = $client;
	}

	function createTable() {
		$workSheet = $this->getWorkSheet($this->exportLink);
		$result = "<div class='band-session-entry-list'><table>\n";
		$first_row = true;
		foreach($workSheet as $item) {
			$result = $result . "<tr>";
			if ($first_row) {
				foreach ($item as $cell) {
					$result = $result . "<th>$cell</th>";
				}
				$first_row = false;
			} else {
				foreach ($item as $cell) {
					$result = $result . "<td>$cell</td>";
				}
			}
			$result = $result . "</tr>\n";
		}
		$result = $result . "</table></div>\n";
		return $result;
	}

	function getWorkSheet($url) {
		$content = $this->downloadFile($url);
		if ($content === null) {
			return array();
		}
		$result = array();
		foreach (explode("\n", $content) as $line) {
			$result[] = str_getcsv($line);
		}
		return $result;
	}

	function sessionPlayers() {
		echo $this->createTable();
	}

	function downloadFile($url) {
		if ($url) {
			$auth = $this->client->getAuth();
			$request = $auth->sign(new Google_Http_Request($url, 'GET', null, null));
			$io = new Google_IO_Curl($client);
			$resp = $io->executeRequest($request);
			if ($resp[2] == 200) {
				return $resp[0];
			} else {
				// An error occurred.
				return null;
			}
		} else {
			// The file doesn't have any content stored on Drive.
			return null;
		}
	}
}

/*
global $exportLink;
global $client_id;
global $client_secret;
global $accessToken;
*/

$bandSession = new BandSessionMaster($exportLink, $client_id, $client_secret, $accessToken);

function entrylist_shortcode_handler($atts, $content=null) {
	global $bandSession;
	$bandSession->sessionPlayers();
}

add_shortcode('session-entry-list', 'entrylist_shortcode_handler');

