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
        // из переданных параметров авто собрать путь сохранения файла
        // общий для всех изображений
        $path = $this->makePath($request->input());

        // привести пришедшие ссылки/ссылку к массиву
        $url = $request->input('url');
        $url_list = [];
        if (is_string($url)) {
            $url_list[] = $url;
        } elseif (is_array($url)) {
            $url_list = $url;
        }

        // ответ с результатами обработки ссылок на изображения
        $data = [];

        // обработать список ссылок, пройтись по ссылкам и достать изображение
        foreach ($url_list as $url) {
            try {
                $image = $this->handleUrlImage($url, $path);
                $image->src = url('/') . $image->src;
                $image->thumb = url('/') . $image->thumb;
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
    public function handleUrlImage(string $url, string $path, $createThumb = true)
    {
        //TODO Струтктурировать различные компоненты путей файлов
        // Возможно, выделить в отдельный класс для работы с ними

        // скачать изображение по ссылке
        // создать временное изображение в файловой системе
        $tmp_path = $this->photo->tempPhotoCreate($url, $path);

        if (!$tmp_path) {
            throw new \HttpException('Can\'t download file from url: ' . $url);
        }

        $file = new UploadedFile($tmp_path, basename($tmp_path));
        $filename = $file->getFilename();

        // получить хеш изображения
        $hash = $this->hasher->hash($tmp_path);

        // проверить, есть ли хеш в базе
        $image = Image::query()->where('hash', $hash->toHex())->first();

        // если изображение не найдено по хешу - создать новое
        if (!$image) {
            // обрезать и сжать изображение
            $file = $this->prepareFile($file);
            // переместить файл изображения из временной папки в обычную
            $this->photo->savePhoto($file, public_path() . $path . '/' . $filename);
            // удалить временный файл изображения
            $this->photo->tempPhotoRemove($tmp_path);

            // записать в базу данные изображения
            $image = Image::create([
                'hash' => $hash->toHex(),
                'url' => $url,
                'is_blocked' => false,
                'src' => $path . '/' . $filename,
            ]);

            // добавить превью, если необходимо
            if ($createThumb) {
                $thumb_path = $path . '/thumb/' . $filename;
                $thumb_path_dir = public_path() . $path . '/thumb/';
                if (!file_exists($thumb_path_dir)) {
                    mkdir($thumb_path, 0777, true);
                }
                $this->photo->saveThumb($file, public_path() . $thumb_path);
                $image->thumb = $thumb_path;
                $image->save();
            }
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
