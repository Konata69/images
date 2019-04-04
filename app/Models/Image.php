<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    public $timestamps = false;

    public $filename;

    protected $fillable = ['url', 'hash', 'src', 'thumb', 'is_blocked'];

    /**
     * Получить список параметров авто
     *
     * @return array
     */
    public static function getAutoParamList(): array
    {
        return [
            'mark',
            'model',
            'body',
            'generation',
            'complectation',
            'color',
            'body_group',
        ];
    }
}
