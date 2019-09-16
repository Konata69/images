<?php

namespace App\Workers;

use App\DTO\ImportUpdateDTO;
use Illuminate\Support\Collection;

/**
 * Обработчик задач импорта
 */
class ImportWorker
{
    //TODO Адаптировать код

    /**
     * Обновить изображения авто
     *
     * @param ImportUpdateDTO $import_update_dto
     */
    public function update(ImportUpdateDTO $import_update_dto)
    {
        //TODO реализовать

        $to_load_url = $this->getImageUrlToLoad($import_update_dto->feed_url, $import_update_dto->auto_url);
        $this->loadApi($item->auto, $to_load_url);
    }

    public function getImageUrlToLoad($feed_url, $auto_url): array
    {
        //получить индивидуальные фото авто из сервиса (с хешами)
        $auto_image_hash = $this->getLocalImage($auto_url);

        //получить хеши добавляемых изображений
        $feed_image_hash = $this->getFeedImageHash($feed_url);

        //выделить список изображений для загрузки
        $to_load = $this->getDiffImageList($feed_image_hash, $auto_image_hash);
        $to_load_url = collect($to_load)->pluck('url')->toArray();

        return $to_load_url;
    }

    public function loadApi(Auto $auto, array $src)
    {
        $image_service = new Api();

        $result = $image_service->import($src, $auto->card_id, $auto->id);

        return $result;
    }

    public function getLocalImage(array $image = []): Collection
    {
        $image_service = new Api();
        $response = $image_service->byUrlAuto($image);
        $local_image = collect($response['image'] ?? []);

        return $local_image;
    }
}