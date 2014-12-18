<?php
/*
  Plugin Name: Band Session Master
  Plugin URI: http://meso.tokyo/band-session-master
  Description: バンドセッション会向けプラグイン
  Author: mesotokyo
  Version: 0.1
  Author URI: http://meso.tokyo/
*/

require_once 'google-api-php-client/src/Google_Client.php';
require_once 'google-api-php-client/src/contrib/Google_DriveService.php';


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
			$request = new Google_HttpRequest($url, 'GET', null, null);
			$httpRequest = Google_Client::$io->authenticatedRequest($request);
			if ($httpRequest->getResponseHttpCode() == 200) {
				return $httpRequest->getResponseBody();
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

$bandSession = new BandSessionMaster($exportLink, $client_id, $client_secret, $accessToken);

function entrylist_shortcode_handler($atts, $content=null) {
	global $bandSession;
	$bandSession->sessionPlayers();
}

// Plugin initialize
add_option('band-session-master-oauth2-clent-id');
add_option('band-session-master-oauth2-clent-secret');
add_option('band-session-master-oauth2-access-token');
add_option('band-session-master-oauth2-export-link');

add_shortcode('session-entry-list', 'entrylist_shortcode_handler');

add_options_page('band-session-master settings', 'band-session-master title', '8', __FILE__, 'config_menu_page');


function config_menu_page() {
	$client_id = get_option('band-session-master-oauth2-clent-id');

	if ($_POST["bsm_hidden"] === 'Y') {
		$cliend_id = $_POST["client_id"];
		update_option('band-session-master-oauth2-clent-id', $client_id);
		?>
		<div class="updated">
			 <p><strong>Options saved.</strong></p>
		</div>
		<?php

	echo '<div class="wrap">';
	echo "<h2>band-session-master config</h2>";
	?>

	<form name="form1" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
		 <input type="hidden" name="bsm_hidden" value="Y">
		 <p>
		 config:
	<input type="text" name="client_id" value="<?php echo $client_id; ?>" size="20">
		 </p><hr />

		 <p class="submit">
		 <input type="submit" name="Submit" value="Update Options" />
		 </p>

		 </form>
		 </div>
		 
		 <?php
}