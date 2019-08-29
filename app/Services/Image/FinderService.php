<?php

namespace App\Services\Image;

use App\Models\Image\BaseImage;
use Throwable;

class FinderService
{
    /**
     * @var BaseImage $model
     */
    protected $model;

    /**
     * @var string алгоритм хеширования файлов изображений
     */
    protected $hash_algo;

    public function __construct(BaseImage $model)
    {
        $this->model = $model;
        $this->hash_algo = config('image.hash_algo');
    }

    /**
     * Поиск информации об изображениях по списку хешей
     *
     * @param array $hash_list
     *
     * @return array
     */
    //TODO Выделить логику сбора данных ответов в ResponseService
    public function byHashList(array $hash_list): array
    {
        $image_list = $this->model->newQuery()->whereIn('hash', $hash_list)->get();
        $not_found_hash_list = array_diff($hash_list, $image_list->pluck('hash')->toArray());

        // дополнить ссылки на изображения и превью (если есть)
        $image_list->map(function ($image) {
            $image->src = url('/') . $image->src;
            $image->thumb = !empty($image->thumb) ? url('/') . $image->thumb : $image->thumb;

            return $image;
        });

        // данные об изображениях
        $data = [];
        $data['image'] = $image_list;

        if ($not_found_hash_list) {
            $data['not_found'] = $not_found_hash_list;
        }

        return $data;
    }

    /**
     * Поиск изображений по списку ссылок
     *
     * @param array $url_list
     *
     * @return array
     */
    //TODO Выделить логику сбора данных ответов в ResponseService
    public function byUrl(array $url_list): array
    {
        // получить список хешей
        $hash_list = [];
        $data = [];
        // список соответствия хешей урлам $hash => $url
        $hash_url = [];

        foreach ($url_list as $url) {
            try {
                $hash = hash_file($this->hash_algo, $url);
                $hash_list[] = $hash;
                $hash_url[$hash] = $url;
            } catch (Throwable $e) {
                $data['error'][] = $this->errorItem($url, $e->getMessage());
            }
        }

        $data = array_merge($this->byHashList($hash_list), $data);

        if (!empty($data['not_found'])) {
            $data['not_found'] = $this->addUrlToHash($data['not_found'], $hash_url);
        }

        return $data;
    }

    /**
     * Найти изображение по хешу изображения из ссылки
     *
     * @param string $hash
     *
     * @return BaseImage|null
     */
    public function byHash(string $hash): ?BaseImage
    {
        // проверить, есть ли хеш в базе
        $image = $this->model->newQuery()->where('hash', $hash)->first();

        return $image;
    }
}