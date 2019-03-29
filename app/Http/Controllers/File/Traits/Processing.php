<?php

namespace App\Http\Controllers\File\Traits;

/**
 * Модуль для обработки изображений 
 * http://image.intervention.io/
 */

use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
/**
 * Модуль для сжатия изображений
 * https://github.com/spatie/laravel-image-optimizer 
 */
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

/**
 * Набор функций для обработки изображений
 *
 * @author Andrew
 */
trait Processing {

    /**
     * Изменяем размер для большого изображения
     * 
     * @param Image $img экземпляр фасада Image с конкретным изображением
     * @return Image экземпляр фасада Image с конкретным изображением после обработки
     */
    protected function resizeBigPhoto($img)
    {
        if ($img->width() > 1920) {
            $img->resize(1920, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        } elseif ($img->height() > 1440) {
            $img->resize(null, 1440, function ($constraint) {
                $constraint->aspectRatio();
            });
        }

        return $img;
    }

    /**
     * Обрезаем изображение под 4:3
     * 
     * @param Image $img экземпляр фасада Image с конкретным изображением
     * @return Image экземпляр фасада Image с конкретным изображением после обработки
     */
    protected function cropFormat($img)
    {
        $w = $img->width();
        $h = $img->height();

        if (round(($w / $h) * 100) !== 75) {
            if ($w > $h) {
                $w = round($h / 0.75);
            } else {
                $h = round($w * 0.75);
            }

            $img->crop($w, $h);
        }

        return $img;
    }

    /**
     * Поэтапная подготовка файла
     * 
     * @param UploadedFile $file экземпляр класса загруженного файла
     * @return Image экземпляр фасада Image с конкретным изображением после обработки
     */
    protected function prepareFile($file)
    {
        $img = Image::make($file);

        $resize_img = $this->resizeBigPhoto($img);
        $crop_img = $this->cropFormat($resize_img);

        return $crop_img;
    }

    /**
     * Сохраняем и оптимизируем изображение
     * 
     * @param Image $img экземпляр фасада Image с конкретным изображением
     * @param string $img_path путь к сохраняемому изображению
     * @return string путь к сохраненному изображению
     */
    protected function saveImage($img, $img_path)
    {
        $img->encode('jpg', 85)->save($img_path, 85);

        ImageOptimizer::optimize($img_path);

        return $img_path;
    }

}
