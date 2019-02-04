<?php

use App\Client;
use App\Uploader;

require_once __DIR__ . './../vendor/autoload.php';

$googleClient = new Google_Client();

$client = new Client($googleClient);
$client->checkAccessTokenAndRefresh();

$uploader = new Uploader($client);
$uploader->uploadVideos();