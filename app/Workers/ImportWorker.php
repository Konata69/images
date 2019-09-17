<?php

namespace App\Workers;

use App\DTO\ImportUpdateDTO;
use App\Models\Image\ImageAuto;
use App\Services\Image\FinderService;
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

        //есть два списка изображений: новые и текущие
        //выбрать те изображения, которые догружаем (одинаковые ссылки, разные хеши либо новые ссылки)
        //выбрать те изображения, которые удаляем (нужно ли?)
        //загрузить изображения
        //отдать инфу о новом списке изображений

        // отбираем изображения для загрузки
        $to_load_url = $this->getImageUrlToLoad($import_update_dto->feed_url, $import_update_dto->auto_url);

        // в идеале нужно три списка: добавление, обновление, удаление

        // грузим изображения в сервис, отдаем в проект
        (ImageWorker::makeWithAutoService())->loadByUrl($to_load_url, $import_update_dto->card_id, $import_update_dto->auto_id);
    }

    public function getImageUrlToLoad($feed_url, $auto_url): array
    {
        //получить индивидуальные фото авто из сервиса (с хешами)
        $auto_image_hash = (new FinderService(new ImageAuto()))->byUrlLocal($auto_url);

        //получить хеши добавляемых изображений
        $feed_image_hash = $this->getFeedImageHash($feed_url);

        //выделить список изображений для загрузки
        $to_load = $this->getDiffImageList($feed_image_hash, $auto_image_hash);
        $to_load_url = collect($to_load)->pluck('url')->toArray();

        return $to_load_url;
    }

    public function getFeedImageHash(array $image = []): Collection
    {
        $hash_algo = 'sha256';
        $feed_image_hash = [];

        foreach ($image as $url) {
            $feed_image_hash[] = [
                'url' => $url,
                'hash' => $this->getHashFile($hash_algo, $url),
            ];
        }

        return collect($feed_image_hash);
    }

    public function getDiffImageList(Collection $new_image_list, Collection $old_image_list): array
    {
        foreach ($new_image_list as $new_image) {
            $tmp_image = $old_image_list->firstWhere('hash', $new_image['url']);

            if (empty($tmp_image)) {
                $to_load[] = $new_image;
            }
        }

        return $to_load ?? [];
    }

    public function getHashFile($hash_algo, $url)
    {
        $arrContextOptions = [
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ];

        return hash($hash_algo, file_get_contents($url, false, stream_context_create($arrContextOptions)));
    }
}