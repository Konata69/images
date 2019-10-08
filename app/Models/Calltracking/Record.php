<?php

namespace App\Models\Calltracking;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель записи звонка
 */
class Record extends Model
{
    protected $table = 'calltracking_record';

    public $timestamps = false;
}
