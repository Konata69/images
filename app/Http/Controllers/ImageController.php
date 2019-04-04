<?php

namespace App\Http\Controllers;

use App\Http\Controllers\File\Photo;
use App\Http\Controllers\File\Traits\Processing;
use App\Http\Requests\BlockImage;
use App\Http\Requests\ImageLoad;
use App\Models\Image;
use App\Services\ImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Jenssegers\ImageHash\Hash;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;
use Throwable;

class ImageController extends Controller
{
    use Processing;

    /** @var ImageHash $hasher */
    public $hasher;

    /** @var ImageService $image_service */
    public $image_service;

    /** @var Photo $photo */
    public $photo;

    public $hash_algo = 'sha256';

    public function __construct(Photo $photo, ImageService $image_service)
    {
        $this->hasher = new ImageHash(new DifferenceHash());
        $this->image_service = $image_service;
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
        $path = $this->image_service->makePath($request->only(Image::getAutoParamList()));

        // привести пришедшие ссылки/ссылку к массиву
        $url = $request->input('url');
        $url_list = $this->toList($url);

        // ответ с результатами обработки ссылок на изображения
        $data = [];

        // обработать список ссылок, пройтись по ссылкам и достать изображение
        foreach ($url_list as $url) {
            try {
                $image = $this->handleUrlImage($url, $path);
                // выделить отдельным списком ссылки на заблокированные изображения
                if ($image->is_blocked) {
                    $data['blocked_image_list'][] = $image->url;
                } else {
                    $image->src = url('/') . $image->src;
                    $image->thumb = url('/') . $image->thumb;
                    $data['image_list'][] = $image;
                }
            } catch (Throwable $e) {
                $error['url'] = $url;
                $error['message'] = $e->getMessage();
                $data['error_list'][] = $error;
            }
        }

        return response()->json($data);
    }

    /**
     * Экшен пометки изображения заблокированным по ссылке
     *
     * @param BlockImage $request
     *
     * @return JsonResponse
     *
     * @throws \HttpException
     */
    public function blockAction(BlockImage $request)
    {
        $url = $request->input('url');
        $path = '/image_blocked';

        // создать временный файл изображения, пометить заблокированным
        $image = $this->handleUrlImage($url, $path, false, true);
        // существующее изображение придет с выключенным флагом, новое - с включенным
        if (!$image->is_blocked) {
            // удалить превью изображения и переместить оригинал в папку к заблокированным изображениям
            File::move(public_path() . $image->src, public_path() . $path . '/' . basename($image->src));
            $this->photo->tempPhotoRemove(public_path() . $path . '/temp/' . $image->filename);
            if ($image->thumb) {
                $this->photo->tempPhotoRemove(public_path() . $image->thumb);
                $image->thumb = null;
            }
            $image->src = $path . '/' . basename($image->src);
            $image->is_blocked = true;
            $image->save();
        }
        $image->src = url('/') . $image->src;

        return response()->json($image);
    }

    /**
     * Получить информацию об изображения по переданным ссылкам
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function byUrlView(Request $request)
    {
        $url_list = $this->toList($request->input('url'));
        // получить список хешей
        $hash_list = [];
        $data = [];
        foreach ($url_list as $url) {
            try {
                $hash_list[] = hash_file($this->hash_algo, $url);
            } catch (Throwable $e) {
                $error['url'] = $url;
                $error['message'] = $e->getMessage();
                $data['error_list'][] = $error;
            }
        }

        $data = array_merge($this->findImageByHashList($hash_list), $data);

        return response()->json($data);
    }

    /**
     * Экшен запроса изображений по списку хешей
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function byHashView(Request $request)
    {
        // найти изображения по списку хешей, выделить ненайденные хеши
        $hash_list = $this->toList($request->input('hash'));
        $data = $this->findImageByHashList($hash_list);

        return response()->json($data);
    }

    /**
     * Поиск информации об изображениях по списку хешей
     *
     * @param array $hash_list
     *
     * @return array
     */
    public function findImageByHashList(array $hash_list): array
    {
        $image_list = Image::query()->whereIn('hash', $hash_list)->get();
        $not_found_hash_list = array_diff($hash_list, $image_list->pluck('hash')->toArray());

        // дополнить ссылки на изображения и превью (если есть)
        $image_list->map(function (Image $image) {
            $image->src = url('/') . $image->src;
            $image->thumb = !empty($image->thumb) ? url('/') . $image->thumb : $image->thumb;

            return $image;
        });

        // данные об изображениях
        $data = [];
        $data['image_list'] = $image_list;
        if ($not_found_hash_list) {
            $data['not_found_hash_list'] = $not_found_hash_list;
        }

        return $data;
    }

    /**
     * Привести переменную к массиву (массив с одним элементом)
     * В случае, если переменная является массивом - вернуть без изменений
     *
     * @param $var
     *
     * @return array
     */
    protected function toList($var): array
    {
        $list = [];
        if (!is_array($var)) {
            $list[] = $var;
        } else {
            $list = $var;
        }

        return $list;
    }

    /**
     * Обработать изображение по ссылке, скачать, если это возможно
     *
     * @param string $url
     * @param string $path
     * @param bool $create_thumb
     * @param bool $block_image блокировать ли изображение при обработке
     *
     * @return Image|Builder|Model|object|null
     *
     * @throws \HttpException
     */
    public function handleUrlImage(string $url, string $path, $create_thumb = true, $block_image = false)
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

        // получить хеш
        $hash = hash_file($this->hash_algo, $tmp_path);
        // получить прецептивный хеш изображения
        $image_hash = $this->hasher->hash($tmp_path);

        // проверить, есть ли хеш в базе
        $image = Image::query()->where('hash', $hash)->first();

        // если нашли изображение - сразу отдать
        if ($image) {
            // записать имя временного файла
            $image->filename = $filename;

            return $image;
        }

        // перед обработкой изображения проверяем на похожесть с заблокированными
        if (!$block_image) {
            $image_list = Image::query()->where('is_blocked', true)->get();
            $blocked_image = $this->image_service->searchBlocked($image_hash->toHex(), $image_list);
            // в случае похожести отдать инфу о том, что изображение заблокировано
            if ($blocked_image) {
                $image = new Image(['url' => $url, 'is_blocked' => true]);

                return $image;
            }
        }

        // обрезать и сжать изображение
        $file = $this->prepareFile($file);
        // переместить файл изображения из временной папки в обычную
        $this->photo->savePhoto($file, public_path() . $path . '/' . $filename);
        // удалить временный файл изображения
        $this->photo->tempPhotoRemove($tmp_path);

        // записать в базу данные изображения
        $image = Image::create([
            'hash' => $hash,
            'image_hash' => $image_hash->toHex(),
            'url' => $url,
            'is_blocked' => $block_image,
            'src' => $path . '/' . $filename,
        ]);

        // добавить превью, если необходимо
        if ($create_thumb) {
            $thumb_path = $path . '/thumb/' . $filename;
            $thumb_path_dir = public_path() . $path . '/thumb/';
            if (!file_exists($thumb_path_dir)) {
                mkdir($thumb_path_dir, 0777, true);
            }
            $this->photo->saveThumb($file, public_path() . $thumb_path);
            $image->thumb = $thumb_path;
            $image->save();
        }

        return $image;
    }
}
