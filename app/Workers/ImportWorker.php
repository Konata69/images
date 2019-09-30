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
    /**
     * @var ImageWorker
     */
    protected $image_worker;

    /**
     * @param ImageWorker|null $image_worker
     */
    public function __construct(?ImageWorker $image_worker = null)
    {
        if ($image_worker instanceof ImageWorker) {
            $this->image_worker = $image_worker;
        } else {
            $this->image_worker = ImageWorker::makeWithAutoService();
        }
    }

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

        $path = $this->image_worker->getImageService()->makePath($import_update_dto->getPathParams());

        // связываем изображения из фида с существующими в бд
        $feed_image_hash = $this->link($feed_image_hash, $auto_image_hash);
        // догружаем недостающие изображения
        $feed_image_hash = $this->add($feed_image_hash, $auto_image_hash, $path);
        // удаляем лишние изображения
        $this->delete($feed_image_hash, $auto_image_hash);

        // дописываем сервисные ссылки
        $feed_image_hash->each(function (BaseImage $item) {
            $item->setServiceUrl();
        });
        // отправить обновленный список изображений
        $result = $this->image_worker->sendServiceUrlList($feed_image_hash->toArray(), $import_update_dto->auto_id);

        // обновить external_id у изображений
        $this->image_worker->addExternalId($feed_image_hash, $result);
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

    /**
     * Догрузить недостающие изображения
     *
     * @param Collection $feed_image_hash
     * @param Collection $auto_image_hash
     * @param string $path
     *
     * @return Collection
     */
    public function add(Collection $feed_image_hash, Collection $auto_image_hash, string $path): Collection
    {
        $feed_image_hash = $feed_image_hash->map(function (BaseImage $feed_item) use ($auto_image_hash, $path) {
            /** @var BaseImage $auto_item */
            $auto_item = $auto_image_hash->where('hash', $feed_item->hash)->first();

            // если нашли локальное изображение - возвращаем исходное изображение (из фида)
            if (!empty($auto_item)) {
                return $feed_item;
            }

            // загрузить и сохранить изображение в бд
            $new_image = $this->image_worker->getImageService()->loadSingle($feed_item, $path);

            return $new_image;
        });

        return  $feed_image_hash;
    }

    /**
     * Удалить лишние изображения (запись в бд и файлы)
     *
     * @param Collection $feed_image_hash
     * @param Collection $auto_image_hash
     *
     * @return Collection
     */
    public function delete(Collection $feed_image_hash, Collection $auto_image_hash): Collection
    {
        $auto_image_hash = $auto_image_hash->filter(function (BaseImage $auto_item) use ($feed_image_hash) {
            /** @var BaseImage $auto_item */
            $feed_item = $feed_image_hash->where('hash', $auto_item->hash)->first();

            // если нашли локальное изображение - оставляем элемент
            if (!empty($feed_item)) {
                return true;
            }

            // удалить файлы изображения и запись в бд
            $this->image_worker->getImageService()->removeByLocalId($auto_item->id);

            // удалить элемент из коллекции
            return false;
        });

        return  $auto_image_hash;
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
