<?php

namespace App\DTO;

use Carbon\Carbon;

/**
 * Объект записи звонка
 */
class RecordDTO
{
    /**
     * @var string - содержимое файла записи в base64
     */
    public $file;

    /**
     * @var string - тип звонка
     * calltracking
     * autoru
     */
    public $type;

    /**
     * @var int - id звонка
     */
    public $call_id;

    /**
     * @var string - путь к файлу
     */
    public $path;

    /**
     * @var string - название файла
     */
    public $filename;

    /**
     * @param array $params
     *
     * @return static
     */
    public static function makeFromArray(array $params)
    {
        $dto = new static();
        $dto->file = base64_decode($params['file']);
        $dto->type = $params['type'];
        $dto->call_id = $params['call_id'];
        $dto->path = $params['path'];
        $dto->filename = $params['filename'];

        return $dto;
    }
}
