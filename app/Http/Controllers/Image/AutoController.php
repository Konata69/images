<?php

namespace App\Http\Controllers\Image;

use App\Services\Image\AutoService;

/**
 * Загрузка, запрос и блокировка индивидуальных изображений авто
 */
class AutoController extends BaseController
{
    /**
     * @param AutoService $image_service
     */
    public function __construct(AutoService $image_service)
    {
        parent::__construct($image_service);
    }
}
