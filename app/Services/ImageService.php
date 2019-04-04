<?php

namespace App\Services;

use App\Models\Image;
use ElForastero\Transliterate\Transliterator;
use Illuminate\Support\Str;
use Jenssegers\ImageHash\Hash;
use Jenssegers\ImageHash\ImageHash;

class ImageService
{
    /**
     * @var Transliterator $transliterator
     */
    protected $transliterator;

    /**
     * @var ImageHash $hasher
     */
    protected $hasher;

    public function __construct(Transliterator $transliterator, ImageHash $hasher)
    {
        $this->transliterator = $transliterator;
        $this->hasher = $hasher;
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
        $path .= '/' . $params['mark'];
        $path .= '/' . $params['model'];
        $path .= '/' . $params['body'];
        $path .= '/' . $params['generation'];
        $path .= '/' . $params['complectation'];
        $path .= '/' . $params['color'];
        $path .= '/' . $params['body_group'];

        return $path;
    }

    /**
     * Найти похожее изображение среди заблокированных
     *
     * @param string $image_hash прецептивный хеш оригинала изображения в шестнадцатиричной системе счисления
     * @param Image[] $image_list
     *
     * @return Image|null модель похожего изображения, если оно найдено
     */
    public function searchBlocked(string $image_hash, array $image_list): ?Image
    {
        $image_hash = Hash::fromHex($image_hash);

        foreach ($image_list as $image) {
            $blocked_image_hash = Hash::fromHex($image->image_hash);
            $distance = $this->hasher->distance($image_hash, $blocked_image_hash);
            if ($distance <= 5) {
                return $image;
            }
        }

        return null;
    }
}