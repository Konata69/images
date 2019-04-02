<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    public $timestamps = false;

    public $filename;

    protected $fillable = ['url', 'hash', 'src', 'thumb', 'is_blocked'];
}
