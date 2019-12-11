<?php

namespace App\Workers;

use App\Helpers\Helper;
use App\Models\Image\BaseImage;
use App\Models\Image\ImageAuto;
use App\Services\BaseApiClient;
use App\Services\Image\AutoService;
use App\Services\Image\BaseService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

/**
 * Обработчик задач по загрузке изображений
 */
class ImageWorker
{
    /**
     * @var string - базовый урл сервиса, с которым общаемся
     */
    protected $base_url;

    /**
     * @var BaseApiClient - работа через со сторонним сервисом
     */
    protected $api;

    /**
     * @var BaseService - сервис изображений авто
     */
    protected $image_service;

    public function __construct(BaseApiClient $api, BaseService $image_service, string $base_url)
    {
        $this->api = $api;
        $this->image_service = $image_service;
        $this->base_url = $base_url . '/';
    }

    public static function makeWithAutoService(string $base_url): ImageWorker
    {
        $image_service = App::make(AutoService::class);
        /** @var ImageWorker $worker */
        $worker = App::make(ImageWorker::class, [
            'image_service' => $image_service,
            'base_url' => $base_url,
        ]);

        return $worker;
    }

    public function getImageService(): BaseService
    {
        return $this->image_service;
    }

    public function setImageService(BaseService $service)
    {
        $this->image_service = $service;
    }

    /**
     * Обработать изображение, если оно не создано во внешнем проекте
     *
     * @param int $image_id
     * @param string $image_type
     *
     * @throws Exception
     */
    public function loadIfNotHandled(int $image_id, string $image_type)
    {
        if ($this->isImageHandled($image_id)){
            return;
        }

        $image = $this->loadImage($image_id, $image_type);
        $this->sendServiceUrlMigrate($image, $image_type);
        $image->setMigrated();
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
     * Загрузить изображение в сервис
     *
     * @param int $image_id
     * @param string $image_type
     *
     * @return BaseImage
     *
     * @throws Exception
     */
    public function loadImage(int $image_id, string $image_type)
    {
        // получить изображение и информацию о нем из api
        $result = $this->getImageFromApi($image_id, $image_type);

        // проверить на ошибки в ответе
        if (!$this->hasErrors($result)) {
            // сохранить файл и модель изображения
            $image = $this->image_service->saveFromBase64($result['data']['image']);
        } else {
            //TODO обработать возможные ошибки
            $is_written = (new Helper)->logError('image_migrate', 'loadImage failed');
            if ((bool) $is_written === false) {
                throw new Exception('is_written ' . print_r($is_written, true));
            }
            (new Helper)->logError('image_migrate', print_r($result, true));
            throw new Exception('Image can not be loaded');
        }

        // второй запрос - отдать сервисные ссылки на изображение
        $image->external_id = $image_id;
        $image->setServiceUrl();

        return $image;
    }

    /**
     * Запрашивает файл изображения, сохраняет, отсылает ссылки на изображения
     *
     * @param int $image_id
     * @param string $image_type
     *
     * @throws Exception
     */
    public function load(int $image_id, string $image_type)
    {
        $image = $this->loadImage($image_id, $image_type);

        $result = $this->sendServiceUrl($image, $image_type);
        if ($this->hasErrors($result)) {
            // в случае ошибок фейлим таску, бросаем исключение
        }
        $image->setMigrated();
    }

    /**
     * Загрузить изображение по ссылке (например, из фида)
     *
     * @param array $url_list
     * @param int $card_id
     * @param int $auto_id
     */
    public function loadByUrl(array $url_list, int $card_id, int $auto_id)
    {
        // загрузить изображение
        $path = [
            'card_id' => $card_id,
            'auto_id' => $auto_id,
        ];
        $path = $this->image_service->makePath($path);

        // сохранить изображение в сервисе
        $data = $this->image_service->load($url_list, $path);

        // отправить ссылку на изображение и auto_id
        $result = $this->sendServiceUrlList($data['image'], $auto_id);

        // дописать external_id в модели
        $this->addExternalId($data['image'], $result);
    }

    /**
     * Обновить существующее изображение по ссылке (например, из фида)
     * и вернуть коллекцию обновленных изображений
     *
     * @param Collection $image
     * @param int $card_id
     * @param int $auto_id
     *
     * @return Collection
     */
    public function updateByUrl(Collection $image, int $card_id, int $auto_id): Collection
    {
        // загрузить изображение
        $path = [
            'card_id' => $card_id,
            'auto_id' => $auto_id,
        ];
        $path = $this->image_service->makePath($path);

        // сохранить изображение в сервисе
        $data = $this->image_service->update($image, $path);

        return $data;
    }

    /**
     * Дописать external_id в модели
     *
     * @param Collection $image_list
     * @param $response
     */
    public function addExternalId(Collection $image_list, $response)
    {
        //TODO Рефакторинг метода
        $image_list_external = collect($response['data']['image']);
        foreach ($image_list as $image) {
            $image_external = $image_list_external->where('feed_url', $image->url)->first();
            $image->external_id = $image_external['id'];
            unset($image->service_src);
            unset($image->service_thumb);
            $image->save();
        }
    }

    public function testSendServiceUrl()
    {
        $image_id = 1422458;
        $image = ImageAuto::find(46);
        $image->external_id = $image_id;

        return $this->sendServiceUrl($image, 'auto');
    }

    /**
     * Отправить сервисные ссылки
     *
     * @param BaseImage $image
     * @param string $image_type
     *
     * @return array
     */
    protected function sendServiceUrl(BaseImage $image, string $image_type): array
    {
        // сделать запрос к autoxml на получение файла
        $url = $this->base_url . 'api/image-service/result';
        $data = [
            'image' => $image,
            'image_type' => $image_type,
        ];
        $header[] = 'X-Requested-With: XMLHttpRequest';

        $result = $this->api->post($url, $data, $header);

        return $result;
    }

    /**
     * Отправить сервисные ссылки
     *
     * @param array $image_list
     * @param int $auto_id
     *
     * @return array
     */
    public function sendServiceUrlList(array $image_list, int $auto_id): array
    {
        // сделать запрос к autoxml на получение файла
        $url = $this->base_url . 'api/image-service/result-import';
        $data = [
            'image_list' => collect($image_list)->toJson(),
            'auto_id' => $auto_id,
        ];
        $header[] = 'X-Requested-With: XMLHttpRequest';

        $result = $this->api->post($url, $data, $header);

        return $result;
    }

    /**
     * Отправить сервисные ссылки при переносе
     *
     * @param BaseImage $image
     * @param string $image_type
     *
     * @return array
     */
    protected function sendServiceUrlMigrate(BaseImage $image, string $image_type): array
    {
        // сделать запрос к autoxml на получение файла
        $url = $this->base_url . 'api/image-service/result-migrate';
        $data = [
            'image' => $image,
            'image_type' => $image_type,
        ];
        $header[] = 'X-Requested-With: XMLHttpRequest';

        $result = $this->api->post($url, $data, $header);

        return $result;
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
     * @param string $image_type
     *
     * @return array
     */
    protected function getImageFromApi(int $image_id, string $image_type)
    {
        // сделать запрос к autoxml на получение файла
        $url = $this->base_url . 'api/image-service/image';
        $data = [
            'image_id' => $image_id,
            'image_type' => $image_type,
        ];
        $header[] = 'X-Requested-With: XMLHttpRequest';

        // первый запрос - на получение файла изображения
        $result = $this->api->post($url, $data, $header);

        return $result;
    }
}
