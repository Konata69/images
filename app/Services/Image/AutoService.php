<?php

namespace App\Services\Image;

use App\Http\Controllers\File\Photo;
use App\Models\ImageAuto;
use Jenssegers\ImageHash\ImageHash;

class AutoService extends BaseService
{
    /**
     * @var ImageAuto
     */
    protected $model;

    public function __construct(Photo $photo, ImageHash $hasher)
    {
        parent::__construct($photo, $hasher);
        $this->model = new ImageAuto();
    }

    /**
     * Получить список параметров авто
     *
     * @return array
     */
    public function getAutoParamList(): array
    {
        return [
            'card_id',
            'auto_id',
        ];
    }

    /**
     * Сделать относительный путь для индивидуального изображения авто
     *
     * @param array $params
     * @return string
     */
    public function makePath(array $params): string
    {
        $card_id = $params['card_id'] ?? 0;
        $auto_id = $params['auto_id'] ?? 0;

        return "/image/auto/{$card_id}/{$auto_id}";
    }
}