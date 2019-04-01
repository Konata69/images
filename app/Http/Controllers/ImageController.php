<?php

namespace App\Http\Controllers;

use App\Http\Controllers\File\Photo;
use App\Http\Controllers\File\Traits\Processing;
use App\Http\Requests\ImageLoad;
use App\Models\Image;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;
use Throwable;

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
     *
     * @return JsonResponse
     */
    public function loadAction(ImageLoad $request)
    {
        // из переданных параметров авто собираем путь сохранения файла
        // общий для всех изображений
        $path = $this->makePath($request->input());

        // приводим пришедшие ссылки/ссылку к массиву
        $url = $request->input('url');
        $urlList = [];
        if (is_string($url)) {
            $urlList[] = $url;
        } elseif (is_array($url)) {
            $urlList = $url;
        }

        /** @var array $data json ответ с результатами обработки ссылок на изображения */
        $data = [];

        // обработать список ссылок, пройтись по ссылкам и достать изображение
        foreach ($urlList as $url) {
            try {
                $image = $this->handleUrlImage($url, $path);
                $data['imageList'][] = $image;
            } catch (Throwable $e) {
                $error['url'] = $url;
                $error['message'] = $e->getMessage();
                $data['errorList'][] = $error;
            }
        }

        return response()->json($data);
    }

    /**
     * Обработать изображение по ссылке, скачать, если это возможно
     *
     * @param string $url
     * @param string $path
     *
     * @return Image|Builder|Model|object|null
     *
     * @throws \HttpException
     */
    public function handleUrlImage(string $url, string $path)
    {
        // скачать изображение по ссылке
        // создает временное изображение в файловой системе

        //TODO Вынести создание директорий отдельно

        //TODO Струтктурировать различные компоненты путей файлов
        // Возможно, выделить в отдельный класс для работы с ними
        $tmpPath = $this->photo->tempPhotoCreate($url, $path);

        if (!$tmpPath) {
            throw new \HttpException('Can\'t download file from url: ' . $url);
        }

        $file = new UploadedFile($tmpPath, basename($tmpPath));
        $filename = $file->getFilename();

        // получить хеш изображения
        $hash = $this->hasher->hash($tmpPath);

        // проверить, есть ли хеш в базе
        $image = Image::query()->where('hash', $hash->toHex())->first();

        // если изображение не найдено по хешу - создаем новое
        if (!$image) {
            // обрезать и сжать изображение
            $image = $this->prepareFile($file);

            // переместить файл изображения из временной папки в обычную
            $src = public_path() . $path . '/' . $filename;
            $this->photo->savePhoto($image, $src);

            // сделать превью изображение
            $thumb = public_path() . $path . '/thumb/' . $filename;
            $this->photo->saveThumb($image, $thumb);

            //удалить временный файл изображения
            $this->photo->tempPhotoRemove($tmpPath);

            // пишем в базу данные изображения
            $image = new Image();
            $image->hash = $hash->toHex();
            $image->url = $url;
            $image->is_blocked = false;
            $image->src = url('/') . $path . '/' . $filename;
            $image->thumb = url('/') . $path . '/thumb/' . $filename;
            $image->save();
        }

        //TODO Проверить, заблокировано ли изображение по хешу в базе данных

        return $image;
    }

    /**
     * Собрать путь хранения изображения из переданных параметров авто
     *
     * @param array $params
     *
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
