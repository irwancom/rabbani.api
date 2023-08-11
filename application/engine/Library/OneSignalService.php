<?php

namespace Library;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Redis\Producer\PushNotificationProducer;

class OneSignalService
{
    const BATCH_SIZE = 60;

    private $appId;
    private $appKey;
    private $client;

    public function __construct($appId = 'db861fb4-3979-4003-878c-f8317276673c', $appKey = 'MTI1YTdlMTMtMmNjZi00NTBmLTkwMDUtYzczMTcxYzgyZjgx')
    {
        $this->appId = $appId;
        $this->appKey = $appKey;
        $this->client = new Client([
            'base_uri' => 'https://onesignal.com/',
        ]);
    }

    /**
     * push notifications via onesignal
     *
     * @param string $title
     * @param string $message
     * @param [] $targets
     * @param [] $extras
     * @param [] $options
     */
    public function pushNotification($title = null, $message, $targets = [], $extras = [], $options = [])
    {
        /* if ($extras['silent']) {
            $extras['data']['silent'] = true;
            $title = null;
            $message = '';
        } */
        $playerIds = $targets['playerIds'];

        $notificationIds = [];

        $playerIdsCount = count($playerIds);
        if ($playerIdsCount > 0) {
            if ($playerIdsCount > 0) {
                $requests = $this->_push($title, $message, ['playerIds' => $playerIds], $extras, $options);

                $notificationIds = $this->execute($requests, 15);
            }
        }

        return $notificationIds;
    }

    /**
     * @param [] $requests
     * @param int $concurrency
     *
     * @return [int] $notificationIds
     */
    private function execute($requests, $concurrency)
    {
        $notificationIds = [];
        $pool = new Pool($this->client, $requests, [
            'concurrency' => $concurrency,
            'fulfilled' => function ($response, $index) use (&$notificationIds) {
                $content = json_decode($response->getBody()->getContents());
                if ($content->id) {
                    $notificationIds[] = $content->id;
                }
            }
        ]);
        $pool->promise()->wait();

        return $notificationIds;
    }

    private function _push($title, $message, $targets = [], $extras = [], $options = [])
    {
        $requests = [];

        /* if (isset($targets['filters'])) {
            $request = $this->_doPush($title, $message, $targets['filters'], [], $extras, $options);
            if ($request) {
                $requests[] = $request;
            }
        } else if (isset($targets['playerIds'])) {
            $batch = ceil(count($targets['playerIds']) / 2000);

            for ($i = 0; $i < $batch; $i++) {
                $offset = $i * 2000;

                $playerIds = array_slice($targets['playerIds'], $offset, 2000);
                $request = $this->_doPush($title, $message, [], $playerIds, $extras, $options);
                if ($request) {
                    $requests[] = $request;
                }
            }
        } */
        $batch = ceil(count($targets['playerIds']) / 2000);

        for ($i = 0; $i < $batch; $i++) {
            $offset = $i * 2000;

            $playerIds = array_slice($targets['playerIds'], $offset, 2000);
            $request = $this->_doPush($title, $message, [], $playerIds, $extras, $options);
            if ($request) {
                $requests[] = $request;
            }
        }

        yield function () use ($requests) {
            foreach ($requests as $request) {
                return $request;
            }
        };
    }

    private function _doPush($title, $message, $filters = [], $playerIds = [], $extras = [], $options = [])
    {
        $data = [
            'app_id' => $this->appId,
            'contents' => ['en' => $message]
        ];

        if (count($filters) > 0) {
            $data['filters'] = $filters;
        }
        if (count($playerIds) > 0) {
            $data['include_player_ids'] = $playerIds;
        }

        if ($title) {
            $data['headings'] = ['en' => $title];
        }

        if (isset($extras['data'])) {
            if (count($extras['data']) > 0) {
                $data['data'] = $extras['data'];
            }
        }

        $data = array_merge($data, $options);
        try {
            return $this->client->requestAsync('POST', 'api/v1/notifications', [
                'headers' => [
                    'Content-Type' =>'application/json',
                    'Authorization' => 'Basic '. $this->appKey,
                ],
                'body' => json_encode($data),
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Publish to redis
     **/
    public function publishPushNotification ($appId = null, $appKey = null, $userId = null, $title = null, $message = null, $notificationId = null, $extras = null, $playerIds = null) {
        $producer = new PushNotificationProducer;
        $producer->appId = $appId;
        $producer->appKey = $appKey;
        $producer->playerIds = $playerIds;
        $producer->userId = $userId;
        $producer->title = $title;
        $producer->message = $message;
        $producer->notificationId = $notificationId;
        $producer->extras = $extras;
        $action = $producer->send();
        return $action;
    }
}
