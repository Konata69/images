<?php

namespace App\Services\Image;

use App\Models\ImageAuto;
use App\Models\ImagePhotobank;
use ElForastero\Transliterate\Transliterator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Throwable;

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

    public function __construct(Transliterator $transliterator)
    {
        parent::__construct();
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