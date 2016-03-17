<?php

require_once dirname(__FILE__) . '/google-api-php-client/src/Google/Client.php';
require_once dirname(__FILE__) . '/google-api-php-client/src/Google/Service/Drive.php';

class BandSessionMaster {
	function __construct($exportLink, $client, $secret, $token, $request=False) {
		$this->exportLink = $exportLink;
		$this->createAccessToken($client, $secret, $token,$request);
	}

	function createAccessToken($client_id, $secret, $token, $request) {
		$client = new Google_Client();

		// Get your credentials from the console
		$client->setClientId($client_id);
		$client->setClientSecret($secret);
		$client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
		$client->setScopes(array('https://www.googleapis.com/auth/drive'));

		if ($token == '') {
			if ($request) {
				$authUrl = $client->createAuthUrl();

				//Request authorization
				//print "Please visit:\n$authUrl\n\n";
				//print "Please enter the auth code:\n";
				//$authCode = trim(fgets(STDIN));

				// Exchange authorization code for access token
				//$token = $client->authenticate($authCode);
				return;
			} else {
				throw new Exception('invalid token');
			}
		}
		$client->setAccessToken($token);
		$this->client = $client;
	}

	function fetchWorkSheet($url="") {
		if ($url === "") {
			$url = $this->exportLink;
		}
		$this->workSheet = $this->getWorkSheet($url);
	}

	function sessionEntryHistory() {
		$entries = array();
		$count = 0;
		foreach ($this->workSheet as $items) {
			$count++;
			if ($count == 1) {
				continue;
			}
			if (!array_key_exists($items[0], $entries)) {
				$entries[$items[0]] = array(
											"player" => "",
											"songs" => array(),
											"date" => "",
											"comment" => "",
											);
			}
			// エントリー主
			if ($items[1] != '') {
				$entries[$items[0]]["player"] = $items[1];
			}
			// 曲名
			if ($items[2] != '') {
				array_push($entries[$items[0]]["songs"],
						   array($items[2], $items[3], $items[4]));
			}
			// 日付
			if ($items[5] != '') {
				$entries[$items[0]]["date"] = $items[5];
			}
			// コメント
			if ($items[6] != '') {
				$entries[$items[0]]["comment"] = $items[6];
			}
		}

		$result = "<div class=\"entry-history\">\n";
		
		foreach (array_reverse($entries) as $item) {
			$result = $result . $this->format_entry($item);
		};
		$result = $result . "</div>\n";
		return $result;
	}
	function format_entry($item) {
		$player = $item["player"];
		$date = $item["date"];
		$comment = $item["comment"];
		$songs = $item["songs"];
		
		$result = "<div class='entry'>\n";
		$text = "<p class='author'><span class='date'>${date}</span>：${player}さんが次の曲にエントリーしました</p><ul>";
		foreach ($songs as $song) {
			$text = $text . "<li>${song[0]}（${song[2]}、${song[1]}）</li>";
		}
		$text = $text . "</ul><p class='comment'>${player}さんのコメント：「${comment}」</p>";
		$result = $result . $text . "</div>\n";
		return $result;
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
		$urls = array();
		$part_pattern = '/[A-Za-z\.]+/';
		foreach ($workSheet as $items) {
			if ($first_row) {
				$first_row = false;

				// パートとindexの対応付けを作る
				for ($i = 0; $i < count($items); $i++) {
					if ($items[$i] === "URL" || $items[$i][0] === '#') {
					}
					else if (preg_match($part_pattern, $items[$i], $part)) {
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

	function createTimeTable() {
		$header_row = 1;
		$begin_row = 2;
		$end_row = 15;
		$title_col = 2;
		$workSheet = $this->workSheet;
		$result = "<div class=\"band-session-time-table\"><table>\n";
		$current_row = 1;
		$header = array();
		foreach ($workSheet as $item) {
			if ($current_row === $header_row) {
				$result = $result . "<tr>";
				foreach ($item as $cell) {
					$result = $result . "<th>$cell</th>";
				}
				$result = $result . "</tr>";
			} else {
				$result = $result . "<tr>";
				foreach ($item as $cell) {
					if ($item[$title_col - 1][0] === '[') {
						$result = $result . "<th>$cell</th>";
					} else {
						$result = $result . "<td>$cell</td>";
					}
				}
				$result = $result . "</tr>";
			}
		}
		return $result;
	}

	function createEntryTable($header_l=1, $begin_l=2, $end_l=20, $count=12) {
		$header_row = $header_l;
		$begin_row = $begin_l;
		$end_row = $end_l;

		$begin_count = 0;
		$end_count = $count;

		$workSheet = $this->workSheet;
		$result = "<div class=\"band-session-entry-list\"><table>\n";

		$counter = $begin_count;

		$current_row = 0;
		$header = array();
		foreach ($workSheet as $item) {
			$current_row++;
			if ($current_row > $end_row) {
				break;
			}
			$result = $result . "<tr>";
			if ($current_row === $header_row) {
				$header = $item;
				$result = $result . "<th>#</th>";
				foreach ($item as $cell) {
					if ($cell === "URL" || $cell[0] === '#') {
						continue;
					}
					$result = $result . "<th>$cell</th>";
				}
			} else if (($current_row < $begin_row) || ($current_row > $end_row)) {
				$continue;
			} else if (($current_row < $begin_row) || ($current_row > $end_row)) {
				$continue;
			} else {
				$counter++;
				if (($current_row < $begin_row) || ($counter > $end_count)) {
					$result = $result . "<th></th>";
				} else {
					$result = $result . "<th>" . ($counter). "</th>";
				}
				// scan url
				$url = "";
				for ($i = 0; $i < count($item); $i++) {
					if ($header[$i] === "URL") {
						$url = $item[$i];
					}
				}
				for ($i = 0; $i < count($item); $i++) {
					$cell = $item[$i];
					if ($header[$i] === "URL" || $header[$i][0] === '#') {
						continue;
					}
					if ($url !== "") {
						$cell = "<a href='$url'>$cell</a>";
						$url = "";
					}
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

	function sessionEntries($header, $start, $end, $count) {
		return $this->createEntryTable($header, $start, $end, $count);
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
