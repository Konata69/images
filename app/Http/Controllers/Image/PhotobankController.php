<?php

namespace App\Http\Controllers\Image;

use App\Services\Image\PhotobankService;

/**
 * Загрузка, запрос и блокировка изображений из фотобанка
 */
class PhotobankController extends BaseController
{
    /**
     * @param PhotobankService $image_service
     */
    public function __construct(PhotobankService $image_service)
    {
        parent::__construct($image_service);
    }
}
