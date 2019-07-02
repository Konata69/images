<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageAuto extends Model
{
    public $timestamps = false;

    protected $fillable = ['url', 'hash', 'src', 'thumb', 'is_blocked', 'image_hash'];

    /**
     * Получить список прецептивных хешей заблокированных изображений
     *
     * @return array
     */
    //TODO убрать дублируемый метод (наследование или трейт, или хелпер, или сервис)
    public static function getBlockedImageHashList()
    {
        return static::query()
            ->select('image_hash')
            ->where('is_blocked', true)
            ->pluck('image_hash')
            ->toArray();
    }
}
