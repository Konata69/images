<?php

namespace App\Models\Image\Compare;

class Item
{
    public $url;
    public $hash;

    /**
     * @param string $url
     * @param string $hash
     */
    public function __construct(string $url, string $hash)
    {
        $this->url = $url;
        $this->hash = $hash;
    }
}