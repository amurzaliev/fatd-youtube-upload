<?php

namespace App;

use Google_Client;
use Google_Service_YouTube;
use Google_Http_MediaFileUpload;
use Google_Service_YouTube_Video;
use Google_Service_YouTube_VideoStatus;
use Google_Service_YouTube_VideoSnippet;
use Psr\Log\LoggerInterface;

class Uploader
{
    const DATA = __DIR__ . '/../data.json';
    const TMP_PATH = __DIR__ . '/../tmp/';

    /** @var int */
    private $uploadLimit;

    /** @var Google_Client */
    private $googleClient;

    /** @var Google_Service_YouTube */
    private $youtubeService;

    /** @var array */
    private $data;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Client $client, LoggerInterface $logger, array $params)
    {
        $this->googleClient = $client->getGoogleClient();
        $this->youtubeService = new Google_Service_YouTube($this->googleClient);
        $this->data = json_decode(file_get_contents(self::DATA), true);
        $this->logger = $logger;
        $this->uploadLimit = $params['upload_limit'];
    }

    public function uploadVideos()
    {
        $limit = 1;
        foreach ($this->data as $id => $videoData) {
            if (null === $videoData['youtube_id'] && $limit <= $this->uploadLimit) {

                if (null !== $videoData['s3_bucket_fullhd']) {
                    $videoPathAWS = "https://s3.amazonaws.com/jw-video-migrate/{$videoData['s3_bucket_fullhd']}/{$videoData['media_id']}.mp4";
                } else {
                    $videoPathAWS = "https://s3.amazonaws.com/jw-video-migrate/{$videoData['s3_bucket']}/{$videoData['media_id']}.mp4";
                }

                $videoPath = self::TMP_PATH . "{$videoData['media_id']}.mp4";

                if (!file_exists($videoPath)) {
                    copy($videoPathAWS, $videoPath);
                }

                $result = $this->uploadVideo($videoData, $videoPath);

                unlink($videoPath);

                if ($result['success']) {
                    $this->data[$id]['youtube_id'] = $result['youtube_id'];
                    echo "{$videoData['media_id']} is downloaded with youtube_id: {$result['youtube_id']}\n";
                    $this->logger->info("{$videoData['media_id']} is downloaded with youtube_id: {$result['youtube_id']} (count: {$limit})\n");
                    file_put_contents(self::DATA, json_encode($this->data, JSON_PRETTY_PRINT));
                }

                if (!$result['success'] && !$result['quota_error']) {
                    echo "Error while uploading: {$videoData['media_id']}\n";
                    $this->logger->error("Error while uploading: {$videoData['media_id']}\n");
                }

                if (!$result['success'] && $result['quota_error']) {
                    echo "The request cannot be completed because you have exceeded your quota.\n";
                    $this->logger->error("The request cannot be completed because you have exceeded your quota.\n");
                    break;
                }

                $limit++;
            }
        }
    }

    private function uploadVideo(array $videoData, string $videoPath)
    {
        try {
            $snippet = new Google_Service_YouTube_VideoSnippet();
            $snippet->setTitle($videoData['title']);
            $snippet->setDescription($videoData['title']);
            $snippet->setCategoryId(22);

            $status = new Google_Service_YouTube_VideoStatus();
            $status->privacyStatus = "unlisted";

            $video = new Google_Service_YouTube_Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);

            $chunkSizeBytes = 1 * 1024 * 1024;
            $this->googleClient->setDefer(true);
            $insertRequest = $this->youtubeService->videos->insert("status,snippet", $video);

            $media = new Google_Http_MediaFileUpload(
                $this->googleClient,
                $insertRequest,
                'video/*',
                null,
                true,
                $chunkSizeBytes
            );
            $media->setFileSize(filesize($videoPath));

            $status = false;
            $handle = fopen($videoPath, "rb");
            while (!$status && !feof($handle)) {
                $chunk = fread($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);
            }

            fclose($handle);

            $this->googleClient->setDefer(false);

            return [
                'success'     => true,
                'quota_error' => false,
                'youtube_id'  => $status['id']
            ];
        } catch (\Exception $e) {
            $result = [
                'success'     => false,
                'quota_error' => false,
                'youtube_id'  => null
            ];

            $response = json_decode($e->getMessage(), true);

            if ($response['error']['code'] === 403) {
                $result['quota_error'] = true;
            }

            return $result;
        }
    }
}