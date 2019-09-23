<?php

namespace App\Workers;

use App\DTO\ImportUpdateDTO;
use App\Models\Image\BaseImage;
use App\Models\Image\Compare\Comparator;
use App\Models\Image\ImageAuto;
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
        // получить хеши изображений
        $auto_image_hash = $this->getLocalImage($import_update_dto->auto_url);
        $feed_image_hash = $this->getFeedImageHash($import_update_dto->feed_url);

        $comparator = new Comparator($auto_image_hash, $feed_image_hash);

        $add = $comparator->getAddList()->pluck('url')->toArray();
        $update = $comparator->getUpdateList();

        // грузим изображения из фида по ссылке в сервис, отдаем в проект
        $image_worker = ImageWorker::makeWithAutoService();
        if (!empty($add)) {
            $image_worker->loadByUrl($add, $import_update_dto->card_id, $import_update_dto->auto_id);
        }
        if (!empty($update->count())) {
            $image_worker->updateByUrl($update, $import_update_dto->card_id, $import_update_dto->auto_id);
        }
    }

    /**
     * Получить локальные модели изображений по external_id
     *
     * @param array $auto_url = [["id" => 1, "url" => 'url']]
     *
     * @return Collection<BaseImage>
     */
    protected function getLocalImage(array $auto_url): Collection
    {
        $auto_url = collect($auto_url);
        $external_id_list = $auto_url->pluck('id')->toArray();

        $result = ImageAuto::whereIn('external_id', $external_id_list)->get();

        return $result;
    }

    /**
     * Получить коллекцию изображений, созданных на основе урлов из фида
     *
     * @param array $image
     *
     * @return Collection
     */
    public function getFeedImageHash(array $image = []): Collection
    {
        $hash_algo = 'sha256';
        $feed_image_hash = new Collection();

        foreach ($image as $url) {
            $item = new ImageAuto([
                'url' => $url,
                'hash' => $this->getHashFile($hash_algo, $url),
            ]);
            $feed_image_hash->add($item);
        }

        return $feed_image_hash;
    }

    /**
     * Получить хеш файла по урлу
     *
     * @param string $hash_algo
     * @param string $url
     *
     * @return string
     */
    public function getHashFile(string $hash_algo, string $url): string
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
