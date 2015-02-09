<?php

require dirname(__FILE__) .  '/../config.php';
require_once dirname(__FILE__) . '/../band-session-master.php';

$bandSession = new BandSessionMaster($exportLink, $client_id, $client_secret, $accessToken);

$bandSession->fetchWorkSheet();
$bandSession->sessionEntries();
$bandSession->sessionMembers();

//$members = $bandSession->getMemberList();

//print_r($members);
//foreach ($members as $member => $parts) {
//	print_r($member);
//}

