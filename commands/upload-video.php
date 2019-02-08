<?php

use App\Client;
use App\Uploader;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require_once __DIR__ . './../vendor/autoload.php';
$params =  include(__DIR__ . './../params.php');

$googleClient = new Google_Client();

$logger = new Logger('app');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../app.log', Logger::INFO));
$logger->info("-----------------------> Start [upload-video] \n");

$client = new Client($googleClient, $logger);
$client->checkAccessTokenAndRefresh();

$uploader = new Uploader($client, $logger, $params);
$uploader->uploadVideos();
$logger->info("End [upload-video] \n");