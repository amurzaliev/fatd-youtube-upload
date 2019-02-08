<?php

use App\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require_once __DIR__ . './../vendor/autoload.php';

$googleClient = new Google_Client();

$logger = new Logger('app');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../app.log', Logger::INFO));
$logger->info("-----------------------> Start [get-access-token] \n");

$client = new Client($googleClient, $logger);
$client->getAccessToken();

echo $client->checkAccessTokenAndRefresh();
$logger->info("Done [get-access-token] \n");