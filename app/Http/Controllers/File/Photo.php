<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\File;
use App\Http\Controllers\Controller;
use Exception;
use Intervention\Image\Image;

class Photo extends Controller
{
    use File\Traits\Processing;

    /**
     * Сохраняем файл
     *
     * @param string $source ссылка на изображение
     * @param string $target путь к изображению
     *
     * @return bool если файл скачан true
     */
    public function downloadFile($source, $target)
    {
        $curl = curl_init();
        $file = fopen($target, 'wb');

        curl_setopt($curl, CURLOPT_FILE, $file);
        curl_setopt($curl, CURLOPT_URL, $source);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        $error = curl_error($curl);

        curl_exec($curl);
        curl_close($curl);
        fclose($file);

        return empty($error);
    }

    /**
     * Сохраняем обработанное изображение на диск
     *
     * @param Image|\Intervention\Image\Facades\Image $img экземпляр фасада Image с конкретным изображением
     * @param string $path путь до обработанного изображения включая имя файла
     *
     * @return string путь к сохраненному изображению
     */
    public function savePhoto(Image $img, string $path): string
    {
        return $this->saveImage($img, $path);
    }

    /**
     * Сохраняем фотографию по ссылке во временный файл
     *
     * @param string $src ссылка на изображение
     * @param string $path путь сохранения файла
     *
     * @return mixed путь ко временному файлу если загрузка изображения удалась или false
     */
    public function tempPhotoCreate($src, $path)
    {
        $path = public_path() . $path . '/temp/';
        $name = uniqid() . '.jpg';

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        try {
            $res = $this->downloadFile($src, $path . $name);
        } catch (Exception $e) {
            $res = null;
        }

        if ($res) {
            return $path . $name;
        }

        return false;
    }

    /**
     * Удаляем временное изображение
     *
     * @param string $src путь к файлу изображения на диске
     *
     * @return bool
     */
    public function tempPhotoRemove($src)
    {
        $result = false;

        if (file_exists($src)) {
            $result = unlink($src);
        }

        return $result;
    }
}
