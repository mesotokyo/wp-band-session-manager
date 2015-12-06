<?php

require dirname(__FILE__) .  '/test_config.php';
require_once dirname(__FILE__) . '/../band-session-master.php';

$bandSession = new BandSessionMaster("", $client_id, $client_secret, $accessToken);

$target_url = html_entity_decode($exportLink);
$bandSession->fetchWorkSheet($target_url);
print_r($bandSession->workSheet);
file_put_contents($test_data_file1, serialize($bandSession->workSheet));

$target_url = html_entity_decode($exportLink2);
$bandSession->fetchWorkSheet($target_url);
print_r($bandSession->workSheet);
file_put_contents($test_data_file2, serialize($bandSession->workSheet));

