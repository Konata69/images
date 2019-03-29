<?php

namespace App\Http\Controllers;

use App\Http\Controllers\File\Photo;
use App\Http\Controllers\File\Traits\Processing;
use App\Http\Requests\ImageLoad;
use App\Models\Image;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;

class ImageController extends Controller
{
    use Processing;

    /** @var ImageHash $hasher */
    public $hasher;

    /** @var Photo $photo */
    public $photo;

    public function __construct(Photo $photo)
    {
        $this->hasher = new ImageHash(new DifferenceHash());
        $this->photo = $photo;
    }

    /**
     * Экшен загрузки изображений по ссылкам
     *
     * @param ImageLoad $request
     */
    public function loadAction(ImageLoad $request)
    {
        // из переданных параметров авто собираем путь сохранения файла
        // общий для всех изображений
        $path = $this->makePath($request->input());

        $url = $request->input('url');

        // обработать единственную ссылку
        if (is_string($url)) {
            $image = $this->handleUrlImage($url, $path);
        }

        // обработать список ссылок
        if (is_array($url)) {
            $imageList = [];
            // пройтись по ссылкам и достать изображение
            foreach ($request->input('url') as $url) {
                $imageList[] = $this->handleUrlImage($url, $path);
            }
        }

        // отдать данные об изображении
    }

    /**
     * Обработать изображение по ссылке, скачать, если это возможно
     *
     * @param $url
     * @param $path
     * @return Image|Builder|Model|object|null
     */
    public function handleUrlImage($url, $path)
    {
        // скачать изображение по ссылке
        // создает временное изображение в файловой системе
        $tmpPath = $this->photo->tempPhotoCreate($url, $path);

        if (!$tmpPath) {
            //TODO файл не скачался - обработать
        }

        $file = new UploadedFile($tmpPath, basename($tmpPath));

        // получить хеш изображения
        $hash = $this->hasher->hash($tmpPath);

        // проверить, есть ли хеш в базе
        $image = Image::query()->where('hash', $hash->toHex())->first();

        // если изображение не найдено по хешу - создаем новое
        if (!$image) {
//            $image = new Image();

            // обрезать и сжать изображение
//            $image = $this->prepareFile($file);

            // переместить файл изображения из временной папки в обычную
//            $path['src'] = $this->savePhoto($image, $path);

            //TODO Сделать превью изображение
//            $path['thumb'] = $this->saveThumb($image, $path);

            //удалить временный файл изображения
            $this->photo->tempPhotoRemove($tmpPath);

            // пишем в базу данные изображения
        }

        //TODO Проверить, заблокировано ли изображение по хешу в базе данных

        return $image;
    }

    /**
     * Собрать путь хранения изображения из переданных параметров авто
     *
     * @param array $params
     * @return string
     */
    public function makePath(array $params): string
    {
        $path = '/image';
        $path .= '/' . $params['mark'];
        $path .= '/' . $params['model'];
        $path .= '/' . $params['body'];
        $path .= '/' . $params['generation'];
        $path .= '/' . $params['complectation'];
        $path .= '/' . $params['color'];

        return $path;
    }

}
