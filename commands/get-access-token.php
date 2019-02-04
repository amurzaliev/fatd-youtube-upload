<?php

use App\Client;

require_once __DIR__ . './../vendor/autoload.php';

$googleClient = new Google_Client();

$client = new Client($googleClient);
$client->getAccessToken();

echo $client->checkAccessTokenAndRefresh();