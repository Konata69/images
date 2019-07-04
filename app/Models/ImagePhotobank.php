<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Изображение из фотобанка
 *
 * @package App\Models
 */
class ImagePhotobank extends Model
{
    public $timestamps = false;

    protected $fillable = ['url', 'hash', 'src', 'thumb', 'is_blocked', 'image_hash'];
}
