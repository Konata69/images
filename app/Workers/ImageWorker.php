<?php

namespace App\Workers;

use App\Services\BaseApiClient;

class ImageWorker
{
    /**
     * @var BaseApiClient
     */
    protected $api;

    public function __construct(BaseApiClient $api)
    {
        $this->api = $api;
    }

    public function load(int $image_id)
    {
        // сделать запрос к autoxml на получение файла
        $url = 'http://127.0.0.1:8000/api/image-service/image';
        $data = ['image_id' => $image_id];
        $header[] = 'X-Requested-With: XMLHttpRequest';

        $result = $this->api->post($url, $data, $header);

        return response()->json($result['data']);
    }
}