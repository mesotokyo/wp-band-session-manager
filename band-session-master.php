<?php

require_once dirname(__FILE__) . '/google-api-php-client/src/Google/Client.php';
require_once dirname(__FILE__) . '/google-api-php-client/src/Google/Service/Drive.php';

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

	function fetchWorkSheet() {
		$this->workSheet = $this->getWorkSheet($this->exportLink);
	}

	function createMemberList() {
		$entries = $this->getMemberList();
		$head = '<div class="member-list"><dl>\n';
		$foot = '</dl></div>';
		$outputs = array();
		foreach ($entries as $member => $songs) {
			$name = $member;
			if (preg_match('/（(.*)）/', $member, $matches) === 1) {
				$name = $matches[1] . '（譲渡可）';
			}

			$outputs[] = '<dt>' . $name . '</dt>\n';
			$outputs[] = '<dd>' . implode('、', $songs) . '</dd>\n';
		}
		return $head . implode($outputs) . $foot;
	}

	function sessionMembers() {
		echo $this->createMemberList();
	}

	function getMemberList() {
		$workSheet = $this->workSheet;
		$first_row = TRUE;
		$entries = array();
		$parts = array();
		$part_pattern = '/[A-Za-z\.]+/';
		foreach ($workSheet as $items) {
			if ($first_row) {
				$first_row = false;

				// パートとindexの対応付けを作る
				for ($i = 1; $i < count($items); $i++) {
					if (preg_match($part_pattern, $items[$i], $part)) {
						$parts[$i] = $part[0];
					}
				}
				continue;
			}

			$title = $items[0];
			for ($i = 1; $i < count($items); $i++) {
				$part = $parts[$i];
				if ($part === NULL) {
					continue;
				}

				if ($items[$i] === '') {
					continue;
				}
				if ($items[$i] === '-') {
					continue;
				}

				$members = explode('/', $items[$i]);
				foreach ($members as $member) {
					if (!array_key_exists($member, $entries)) {
						$entries[$member] = array();
					}
					$entries[$member][] = $title . '（' . $part . '）';
				}
			}
		}
		return $entries;
	}

	function createEntryTable() {
		$workSheet = $this->workSheet;
		$result = "<div class='band-session-entry-list'><table>\n";
		$first_row = true;
		$counter = 0;
		foreach ($workSheet as $item) {
			$result = $result . "<tr>";
			if ($first_row) {
				$result = $result . "<th>#</th>";
				foreach ($item as $cell) {
					$result = $result . "<th>$cell</th>";
				}
				$first_row = false;
			} else {
				$result = $result . "<th>" . $counter . "</th>";
				foreach ($item as $cell) {
					$result = $result . "<td>$cell</td>";
				}
			}
			$counter++;
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

	function sessionEntries() {
		echo $this->createEntryTable();
	}

	function downloadFile($url) {
		if ($url) {
			$auth = $this->client->getAuth();
			$request = $auth->sign(new Google_Http_Request($url, 'GET', null, null));
			$io = new Google_IO_Curl($this->client);
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
