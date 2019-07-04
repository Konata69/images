<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Изображение
 *
 * @package App\Models
 */
class ImagePhotobank extends Model
{
    public $timestamps = false;

    protected $fillable = ['url', 'hash', 'src', 'thumb', 'is_blocked', 'image_hash'];

    /**
     * Получить список параметров авто
     *
     * @return array
     */
    public static function getAutoParamList(): array
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
     * Получить список прецептивных хешей заблокированных изображений
     *
     * @return array
     */
    public static function getBlockedImageHashList()
    {
        return static::query()
            ->select('image_hash')
            ->where('is_blocked', true)
            ->pluck('image_hash')
            ->toArray();
    }
}
