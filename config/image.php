<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Intervention Image supports "GD Library" and "Imagick" to process images
    | internally. You may choose one of them according to your PHP
    | configuration. By default PHP's "GD Library" implementation is used.
    |
    | Supported: "gd", "imagick"
    |
    */

    'driver' => 'imagick',

    // алгоритм хеширования файлов изображений
    'hash_algo' => env('IMAGE_HASH_ALGO', 'sha256'),

    // куда отправлять результат обработки изображений
    'target_url' => env('IMAGE_TARGET_URL', 'https://newautoxml.4px.tech/'),

];
