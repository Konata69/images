<?php

namespace App\Models\Image;

use Illuminate\Database\Eloquent\Model;

/**
 * Базовый класс модели изображения
 *
 * @property int|null $external_id
 * если int - есть связанное изображение в основном проекте
 * если null - этого изображения нет в основном проекте
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
        'image_hash',
        'external_id',
        'migrated',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
        'external_id' => 'integer',
        'migrated' => 'boolean',
    ];

    public function getMigratedAttribute($value)
    {
        return !empty($value);
    }

    /**
     * Пометить изображение как перемещенное
     *
     * @param bool $value
     */
    public function setMigrated(bool $value = true)
    {
        $this->migrated = $value;
        $this->save();
    }

    /**
     * Пометить изображение заблокированным
     *
     * @param bool $value
     */
    public function setBlocked(bool $value = true)
    {
        $this->is_blocked = $value;
        $this->save();
    }

    /**
     * заполнить сервисные ссылки у модели
     */
    public function setServiceUrl()
    {
        $this->service_src = url('/') . $this->src;
        $this->service_thumb = url('/') . $this->thumb;
    }
}