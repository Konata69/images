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
        $image_worker = ImageWorker::makeWithAutoService();

        // связываем изображения из фида с существующими в бд
        $feed_image_hash = $this->link($feed_image_hash, $auto_image_hash);

        $feed_image_hash = $this->add($feed_image_hash, $auto_image_hash);

//        $add = $comparator->getAddList()->pluck('url')->toArray();
//        $update = $comparator->getUpdateList();

        // грузим изображения из фида по ссылке в сервис, отдаем в проект
//        if (!empty($add)) {
//            $image_worker->loadByUrl($add, $import_update_dto->card_id, $import_update_dto->auto_id);
//        }
//        if (!empty($update->count())) {
//            $image_worker->updateByUrl($update, $import_update_dto->card_id, $import_update_dto->auto_id);
//        }

        // отправить обновленный список изображений
        $result = $image_worker->sendServiceUrlList($new_list);

        // обновить external_id у изображений
        $image_worker->addExternalId($new_list, $result);
    }

    /**
     * Связать модели, полученные из фида с моделями из бд
     *
     * @param Collection $feed_image_hash
     * @param Collection $auto_image_hash
     *
     * @return Collection
     */
    public function link(Collection $feed_image_hash, Collection $auto_image_hash): Collection
    {
        $feed_image_hash = $feed_image_hash->map(function (BaseImage $feed_item) use ($auto_image_hash) {
            /** @var BaseImage $auto_item */
            $auto_item = $auto_image_hash->where('hash', $feed_item->hash)->first();

            // если не нашли локальное изображение - возвращаем исходное изображение (из фида)
            if (empty($auto_item)) {
                return $feed_item;
            }

            $auto_item->hash = $feed_item->hash;

            return $auto_item;
        });

        return $feed_image_hash;
    }

    public function add(Collection $feed_image_hash, Collection $auto_image_hash): Collection
    {
        $image_worker = ImageWorker::makeWithAutoService();

        $feed_image_hash = $feed_image_hash->map(function (BaseImage $feed_item) use ($auto_image_hash, $image_worker) {
            /** @var BaseImage $auto_item */
            $auto_item = $auto_image_hash->where('hash', $feed_item->hash)->first();

            // если не нашли локальное изображение - возвращаем исходное изображение (из фида)
            if (!empty($auto_item)) {
                return $feed_item;
            }

            // загрузить и сохранить изображение в бд
            $image_worker->getImageService()->loadSingle($feed_item->url, $path);
        });

        return  $feed_image_hash;
    }

    /**
     * Получить локальные модели изображений по external_id
     *
     * @param array $auto_url = [["id" => 1, "url" => 'url']]
     *
     * @return Collection<BaseImage>
     */
    public function getLocalImage(array $auto_url): Collection
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
