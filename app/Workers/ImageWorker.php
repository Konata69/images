<?php

namespace App\Workers;

use App\Models\Image\BaseImage;
use App\Models\Image\ImageAuto;
use App\Services\BaseApiClient;
use App\Services\Image\AutoService;
use App\Services\Image\FileService;

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

    protected $file_service;

    public function __construct(BaseApiClient $api, AutoService $image_service, FileService $file_service)
    {
        $this->api = $api;
        $this->image_service = $image_service;
        $this->file_service = $file_service;
    }

    /**
     * Обработать изображение, если оно не создано во внешнем проекте
     *
     * @param int $image_id
     */
    public function loadIfNotHandled(int $image_id)
    {
        if (!$this->isImageHandled($image_id)){
            $this->load($image_id);
        }
    }

    /**
     * Проверить, обработано ли изображение (миграция)
     *
     * @param int $image_id
     *
     * @return bool
     */
    protected function isImageHandled(int $image_id): bool
    {
        $image = $this->image_service->getModel()->newQuery()->where('external_id', $image_id)->first();

        return !empty($image->migrated);
    }

    /**
     * Запрашивает файл изображения, сохраняет, отсылает ссылки на изображения
     *
     * @param int $image_id
     */
    public function load(int $image_id)
    {
        // получить изображение и информацию о нем из api
        $result = $this->getImageFromApi($image_id);

        // проверить на ошибки в ответе
        if (!$this->hasErrors($result)) {
            // сохранить файл и модель изображения
            $image = $this->image_service->saveFromBase64($result['data']['image']);
        } else {
            //TODO обработать ошибки
            // завершить таску, если невозможно получить изображение
            // повторить таску, если возможно получить изображение
            // для повтора таски бросить исключение
            return;
        }

        // второй запрос - отдать сервисные ссылки на изображение
        $image->external_id = $image_id;
        $image->src = url('/') . $image->src;
        $image->thumb = url('/') . $image->thumb;

        $result = $this->sendServiceUrl($image);
        if ($this->hasErrors($result)) {
            // в случае ошибок фейлим таску
            // бросить исключение
        }
        $image->setMigrated();
    }

    public function testSendServiceUrl()
    {
        $image_id = 1422458;
        $image = ImageAuto::find(46);
        $image->external_id = $image_id;

        return $this->sendServiceUrl($image);
    }

    /**
     * Отправить сервисные ссылки
     *
     * @param BaseImage $image
     *
     * @return array
     */
    protected function sendServiceUrl(BaseImage $image): array
    {
        // сделать запрос к autoxml на получение файла
        $url = 'http://127.0.0.1:8000/api/image-service/result';
        $data = [
            'image' => $image
        ];
        $header[] = 'X-Requested-With: XMLHttpRequest';

        $result = $this->api->post($url, $data, $header);

        return $result;
    }

    /**
     * Загрузить изображение по ссылке (например, из фида)
     *
     * @param string $url
     * @param int $auto_id
     */
    public function loadByUrl(string $url, int $auto_id)
    {
        //TODO Дописать метод загрузки изображения из урла
        return;

        // получить изображение по урлу
        $image = $this->getImageFromUrl($url);

        // сохранить изображение в сервисе
        $image = $this->image_service->save($image);

        // отправить ссылку на изображение и auto_id
        $this->sendServiceUrl($image);
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
        $hasErrors = !empty($response['data']['error'])
            || !empty($response['error'])
            || empty($response['data']['image']);

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
}