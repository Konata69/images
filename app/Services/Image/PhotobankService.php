<?php

namespace App\Services\Image;

use App\Models\Image\ImagePhotobank;
use ElForastero\Transliterate\Transliterator;
use Illuminate\Support\Str;
use Jenssegers\ImageHash\ImageHash;

/**
 * Сервис для работы с изображениями из фотобанка
 */
class PhotobankService extends BaseService
{
    /**
     * @var ImagePhotobank
     */
    protected $model;

    /**
     * Транслитератор
     *
     * @var Transliterator $transliterator
     */
    protected $transliterator;

    public function __construct(ImageHash $hasher, FileService $file_service, Transliterator $transliterator)
    {
        parent::__construct($hasher, $file_service);
        $this->model = new ImagePhotobank();
        $this->transliterator = $transliterator;
    }

    /**
     * Получить список параметров авто
     *
     * @return array
     */
    public function getAutoParamList(): array
    {
        return [
            'mark',
            'model',
            'body',
            'generation',
            'complectation',
            'color',
        ];
    }

    /**
     * Собрать путь хранения изображения из переданных параметров авто
     *
     * @param array $params
     *
     * @return string
     */
    public function makePath(array $params): string
    {
        // транслитерация в snake_case
        $params = array_map(function ($param) {
            return !empty($param) ? $this->translit($param) : 'default';
        }, $params);

        $path = '/image';
        $path .= '/' . ($params['mark'] ?? 'default');
        $path .= '/' . ($params['model'] ?? 'default');
        $path .= '/' . ($params['body'] ?? 'default');
        $path .= '/' . ($params['generation'] ?? 'default');
        $path .= '/' . ($params['complectation'] ?? 'default');
        $path .= '/' . ($params['color'] ?? 'default');

        return $path;
    }

    /**
     * Транлитерировать строку в snake_case
     *
     * @param string $str
     *
     * @return string
     */
    public function translit(string $str): string
    {
        $str = $this->transliterator->make($str);
        $str = Str::slug($str, '_');

        return $str;
    }
}