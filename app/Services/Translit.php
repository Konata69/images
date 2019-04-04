<?php

namespace App\Services;

use ElForastero\Transliterate\Transliterator;
use Illuminate\Support\Str;

class Translit
{
    /**
     * @var Transliterator $transliterator
     */
    protected $transliterator;

    public function __construct(Transliterator $transliterator)
    {
        $this->transliterator = $transliterator;
    }

    /**
     * Транлитерировать строку в snake_case
     *
     * @param string $str
     *
     * @return string
     */
    public function translit(string $str): string
    {
        $str = $this->transliterator->make($str);
        $str = Str::slug($str, '_');

        return $str;
    }
}