<?php

namespace App\Workers;

use App\DTO\ImportUpdateDTO;
use App\Models\Image\Compare\Comparator;
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
        //загрузить изображения
        //отдать инфу о новом списке изображений

        // получить хеши изображений
        $auto_image_hash = (new FinderService(new ImageAuto()))->byUrlLocal($import_update_dto->auto_url)->toArray();
        $feed_image_hash = $this->getFeedImageHash($import_update_dto->feed_url)->toArray();

        //TODO Переписать компаратор на использование моделей изображений
        $comparator = Comparator::makeFromArray($auto_image_hash, $feed_image_hash);

        $add = $comparator->getAddList();
        $update = $comparator->getUpdateList();

        // loadByUrl - загружает новые изображения, но не обновляет старые при смене хеша

        // грузим изображения из фида по ссылке в сервис, отдаем в проект
        (ImageWorker::makeWithAutoService())->loadByUrl($url_to_load, $import_update_dto->card_id, $import_update_dto->auto_id);
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