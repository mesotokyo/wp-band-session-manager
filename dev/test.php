<?php

require dirname(__FILE__) .  '/test_config.php';
require_once dirname(__FILE__) . '/../band-session-master.php';

$bandSession = new BandSessionMaster("", $client_id, $client_secret, $accessToken);

//print($exportLink);
$target_url = html_entity_decode($exportLink);
//print($target_url);
$bandSession->fetchWorkSheet($target_url);

//print_r($bandSession->workSheet);
print($bandSession->sessionEntries());
print($bandSession->sessionMembers());

$target_url = html_entity_decode($exportLink2);
$bandSession->fetchWorkSheet($target_url);
print_r($bandSession->workSheet);

//$members = $bandSession->getMemberList();

//print_r($members);
//foreach ($members as $member => $parts) {
//	print_r($member);
//}

