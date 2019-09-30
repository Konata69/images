<?php

namespace App\DTO;

class ImportUpdateDTO
{
    /**
     * @var array
     */
    public $feed_url;

    /**
     * @var array
     */
    public $auto_url;

    /**
     * @var int
     */
    public $card_id;

    /**
     * @var int
     */
    public $auto_id;

    /**
     * @var int
     */
    public $import_id;

    /**
     * @param array $feed_url
     * @param array $auto_url
     * @param int $card_id
     * @param int $auto_id
     * @param int $import_id
     */
    public function __construct(array $feed_url, array $auto_url, int $card_id, int $auto_id, int $import_id)
    {
        $this->feed_url = $feed_url;
        $this->auto_url = $auto_url;
        $this->card_id = $card_id;
        $this->auto_id = $auto_id;
        $this->import_id = $import_id;
    }

    /**
     * Получить параметры пути
     *
     * @return array
     */
    public function getPathParams(): array
    {
        $path = [
            'card_id' => $this->card_id,
            'auto_id' => $this->auto_id,
        ];

        return $path;
    }
}
