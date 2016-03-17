<?php

require dirname(__FILE__) .  '/test_config.php';
require_once dirname(__FILE__) . '/../band-session-master.php';

$bandSession = new BandSessionMaster("", $client_id, $client_secret, $accessToken);


// $workSheet = unserialize(file_get_contents($test_data_file2));

$target_url = html_entity_decode($exportLink2);
$bandSession->fetchWorkSheet($target_url);
print($bandSession->sessionEntryHistory());
