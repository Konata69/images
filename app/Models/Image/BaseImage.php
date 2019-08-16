<?php

namespace App\Models\Image;

use Illuminate\Database\Eloquent\Model;

/**
 * Базовый класс модели изображения
 */
class BaseImage extends Model
{
    public $timestamps = false;

    protected $table = 'image_auto';

    protected $fillable = [
        'url',
        'hash',
        'src',
        'thumb',
        'is_blocked',
        'image_hash'
    ];
}