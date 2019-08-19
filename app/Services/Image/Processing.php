<?php

namespace App\Services\Image;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Image;
use Intervention\Image\Facades\Image as ImageFacade;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

/**
 * Набор функций для обработки изображений
 *
 * @author Andrew
 */
class Processing
{
    /**
     * Поэтапная подготовка файла
     *
     * @param UploadedFile $file экземпляр класса загруженного файла
     *
     * @return Image экземпляр фасада Image с конкретным изображением после обработки
     */
    public function prepareFile($file)
    {
        $img = ImageFacade::make($file);

        $resize_img = $this->resizeBigPhoto($img);
        $crop_img = $this->cropFormat($resize_img);

        return $crop_img;
    }


    /**
     * Сохраняем обработанное изображение на диск
     *
     * @param Image $img экземпляр фасада Image с конкретным изображением
     * @param string $path путь до обработанного изображения включая имя файла
     *
     * @return string путь к сохраненному изображению
     */
    public function savePhoto(Image $img, string $path): string
    {
        return $this->saveImage($img, $path);
    }

    /**
     * Сохраняем превью изображения на диск
     *
     * @param Image $img экземпляр фасада Image с конкретным изображением
     * @param string $path путь до превью изображения включая имя файла
     *
     * @return string путь к сохраненному изображению
     */
    public function saveThumb(Image $img, string $path): string
    {
        $img->resize(400, 300)->crop(400, 300);

        return $this->saveImage($img, $path);
    }

    /**
     * Сохраняем и оптимизируем изображение
     *
     * @param Image $img экземпляр фасада Image с конкретным изображением
     * @param string $img_path путь к сохраняемому изображению
     *
     * @return string путь к сохраненному изображению
     */
    protected function saveImage($img, $img_path)
    {
        $img->encode('jpg', 85)->save($img_path, 85);

        ImageOptimizer::optimize($img_path);

        return $img_path;
    }

    /**
     * Изменяем размер для большого изображения
     *
     * @param Image $img экземпляр фасада Image с конкретным изображением
     *
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
     *
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
}
