<?php

namespace App\Services\Image;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Jenssegers\ImageHash\Hash;

class BlockService
{
    /**
     * Блокировать изображение по ссылке
     *
     * @param string $url
     *
     * @return Model
     */
    public function block(string $url)
    {
        //TODO refactoring
        return;
        $path = '/image_blocked';

        // создать временный файл изображения, пометить заблокированным
        //TODO Убрать создание временного файла
//        $image = BaseService::handleUrlImage($url, $path, true);

        // существующее изображение придет с выключенным флагом, новое - с включенным
        if (!$image->is_blocked) {
            // удалить превью изображения и переместить оригинал в папку к заблокированным изображениям
            File::move(public_path() . $image->src, public_path() . $path . '/' . basename($image->src));

            if ($image->thumb) {
                //TODO Убрать зависимость от photo
//                $this->photo->tempPhotoRemove(public_path() . $image->thumb);
                $image->thumb = null;
            }

            $image->src = $path . '/' . basename($image->src);
            $image->is_blocked = true;
            $image->save();
        }

        $image->src = url('/') . $image->src;

        return $image;
    }

    /**
     * Найти похожее изображение среди заблокированных
     *
     * @param string $image_hash прецептивный хеш оригинала изображения в шестнадцатиричной системе счисления
     * @param array $blocked_image_hash_list - список прецептивных хешей заблокированных изображений
     *
     * @return string|null
     */
    public function searchBlocked(string $image_hash, array $blocked_image_hash_list): ?string
    {
        $image_hash = Hash::fromHex($image_hash);

        foreach ($blocked_image_hash_list as $blocked_image_hash) {
            $blocked_image_hash = Hash::fromHex($blocked_image_hash);
            $distance = $this->hasher->distance($image_hash, $blocked_image_hash);

            if ($distance <= 5) {
                return $blocked_image_hash->toHex();
            }
        }

        return null;
    }

    /**
     * Получить список прецептивных хешей заблокированных изображений
     *
     * @return array
     */
    public function getBlockedImageHashList()
    {
        return $this->model->newQuery()
            ->select('image_hash')
            ->where('is_blocked', true)
            ->pluck('image_hash')
            ->toArray();
    }
}