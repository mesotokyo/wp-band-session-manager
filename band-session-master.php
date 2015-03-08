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
		$head = "<div class=\"band-session-member-list\"><dl>\n";
		$foot = '</dl></div>';
		$outputs = array();

		// 譲渡可の処理
		$entries_new = array();
		foreach ($entries as $member => $songs) {
			$name = $member;
			if (preg_match('/（(.*)）/', $member, $matches) === 1) {
				$name = $matches[1] . '（譲渡可）';
			}
			$entries_new[$name] = implode('、', $songs);
		}
			
		// メンバー一覧をソート
		ksort($entries_new);
		foreach ($entries_new as $name => $songs) {
			$outputs[] = '<dt>' . $name . "</dt>\n";
			$outputs[] = '<dd>' . $songs . "</dd>\n";
		}
		return $head . implode($outputs) . $foot;
	}

	function sessionMembers() {
		return $this->createMemberList();
	}

	function getMemberList() {
		$title_column = 1;
		$workSheet = $this->workSheet;
		$first_row = TRUE;
		$entries = array();
		$parts = array();
		$part_pattern = '/[A-Za-z\.]+/';
		foreach ($workSheet as $items) {
			if ($first_row) {
				$first_row = false;

				// パートとindexの対応付けを作る
				for ($i = 0; $i < count($items); $i++) {
					if (preg_match($part_pattern, $items[$i], $part)) {
						$parts[$i] = $part[0];
					} else {
						$parts[$i] = NULL;
					}
				}
				continue;
			}

			$title = $items[$title_column];
			for ($i = 0; $i < count($items); $i++) {
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
		$begin_count = 3;
		$end_count = 17;
		$workSheet = $this->workSheet;
		$result = "<div class=\"band-session-entry-list\"><table>\n";
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
				if (($counter >= $begin_count) && ($counter < $end_count)) {
				$result = $result . "<th>" . ($counter - $begin_count + 1). "</th>";
				} else {
				$result = $result . "<th></th>";
				}
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
		return $this->createEntryTable();
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
