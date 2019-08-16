<?php

namespace App\Models\Image;

class ImageAuto extends BaseImage
{
    protected $table = 'image_auto';

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
