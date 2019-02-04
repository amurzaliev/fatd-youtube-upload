<?php

namespace App;

use Google_Client;
use Google_Service_YouTube;

class Client
{
    const CLIENT_SECRET = './../client_secret.json';
    const ACCESS_TOKEN = './../access_token.json';

    /** @var Google_Client */
    private $googleClient;

    /** @var string */
    private $accessToken;

    public function __construct(Google_Client $googleClient)
    {
        $this->googleClient = $googleClient;
        $this->googleClient->setApplicationName('API Youtube Uploader');
        $this->googleClient->addScope(Google_Service_YouTube::YOUTUBE);
        $this->googleClient->setAuthConfig(self::CLIENT_SECRET);
        $this->googleClient->setAccessType('offline');

        if (file_exists(self::ACCESS_TOKEN)) {
            $this->accessToken = json_decode(file_get_contents(self::ACCESS_TOKEN), true);
            $this->googleClient->setAccessToken($this->accessToken);
        }
    }

    public function getAccessToken()
    {
        if (!$this->accessToken) {
            $authUrl = $this->googleClient->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code ([GET] ?code=): ';
            $authCode = trim(fgets(STDIN));
            $this->accessToken = $this->googleClient->fetchAccessTokenWithAuthCode($authCode);

            file_put_contents(self::ACCESS_TOKEN, json_encode($this->accessToken));
            printf("Credentials saved to %s\n", self::ACCESS_TOKEN);
        }

        $this->googleClient->setAccessToken($this->accessToken);
    }

    public function checkAccessTokenAndRefresh()
    {
        if ($this->googleClient->isAccessTokenExpired()) {
            $this->googleClient->fetchAccessTokenWithRefreshToken($this->googleClient->getRefreshToken());
            file_put_contents(self::ACCESS_TOKEN, json_encode($this->googleClient->getAccessToken()));
            return "Credentials is refreshed to" . self::ACCESS_TOKEN . "\n";
        }

        return "Access token is fresh";
    }

    public function getGoogleClient()
    {
        return $this->googleClient;
    }
}