<?php

namespace App\Workers;

use App\Models\ImageAuto;
use App\Models\ImagePhotobank;
use App\Services\BaseApiClient;
use App\Services\Image\AutoService;
use Illuminate\Http\JsonResponse;

/**
 * Обработчик задач по загрузке изображений
 */
class ImageWorker
{
    /**
     * @var BaseApiClient - работа через со сторонним сервисом
     */
    protected $api;

    /**
     * @var AutoService - сервис изображений авто
     */
    protected $image_service;

    public function __construct(BaseApiClient $api, AutoService $image_service)
    {
        $this->api = $api;
        $this->image_service = $image_service;
    }

    /**
     * Запрашивает файл изображения, сохраняет, отсылает ссылки на изображения
     *
     * @param int $image_id
     *
     * @return JsonResponse
     */
    public function load(int $image_id)
    {
        // получить изображение и информацию о нем из api
        $result = $this->getImageFromApi($image_id);

        // проверить на ошибки в ответе
        if (!$this->hasErrors($result)) {
            // сохранить файл и модель изображения
            $image = $this->save($result['data']['image']);
        } else {
            //TODO обработать ошибки
            // завершить таску, если невозможно получить изображение
            // повторить таску, если возможно получить изображение
            // для повтора таски бросить исключение
            return;
        }

        // второй запрос - отдать сервисные ссылки на изображение
        $result = $this->sendServiceUrl($image);
        if ($this->hasErrors($result)) {
            // бросить исключение
        }

    }

    /**
     * Проверить ответ на наличие ошибок и изображения
     *
     * @param array $response
     *
     * @return bool
     */
    protected function hasErrors(array $response): bool
    {
        $hasErrors = empty($response['data']['error'])
            && empty($response['error'])
            && !empty($response['data']['image']);

        return $hasErrors;
    }

    /**
     * Получить информацию об изображении из api
     *
     * @param int $image_id
     *
     * @return array
     */
    protected function getImageFromApi(int $image_id)
    {
        // сделать запрос к autoxml на получение файла
        $url = 'http://127.0.0.1:8000/api/image-service/image';
        $data = ['image_id' => $image_id];
        $header[] = 'X-Requested-With: XMLHttpRequest';

        // первый запрос - на получение файла изображения
        $result = $this->api->post($url, $data, $header);

        return $result;
    }

    /**
     * Из переданных данных изображения сделать модель и сохранить изображение в файл
     *
     * @param array $image
     *
     * @return ImageAuto|ImagePhotobank - модель изображения
     */
    protected function save(array $image)
    {
        //TODO Сделать вызов из сервиса (изображений или файлов)
        $src = $this->saveFile($image['filename'], $image['content'], $image['path_data']);
        // создать модель изображения (частный)

    }
}