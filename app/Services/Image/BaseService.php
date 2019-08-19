<?php

namespace App\Services\Image;

use App\Http\Controllers\File\Photo;
use App\Http\Controllers\File\Traits\Processing;
use App\Models\Image\BaseImage;
use App\Models\Image\ImageAuto;
use App\Models\Image\ImagePhotobank;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Jenssegers\ImageHash\Hash;
use Jenssegers\ImageHash\ImageHash;
use Throwable;

/**
 * Вспомогательные методы работы с изображениями
 *
 * @package App\Services
 */
abstract class BaseService
{
    //TODO Заменить на сервисный класс
    use Processing;

    /**
     * @var BaseImage
     */
    protected $model;

    /**
     * @var string алгоритм хеширования файлов изображений
     */
    protected $hash_algo = 'sha256';

    /**
     * @var Photo работа с файлами изображений (старый код)
     */
    //TODO Убрать использование класса
    protected $photo;

    /**
     * @var FileService работа с файлами изображений (новый сервис)
     */
    protected $file_service;

    /**
     * @var ImageHash $hasher Расчет прецептивного хеша изображения
     */
    protected $hasher;

    public function __construct(Photo $photo, ImageHash $hasher, FileService $file_service)
    {
        $this->photo = $photo;
        $this->hasher = $hasher;
        $this->file_service = $file_service;
    }

    abstract public function makePath(array $path_params): string;

    /**
     * Получить список параметров авто
     *
     * @return array
     */
    abstract public function getAutoParamList(): array;

    /**
     * Сохранить изображение из бинарной строки в base64
     *
     * @param array $image - содержит название файла с расширением, контент файла и данные для относительного пути
     */
    public function saveFromBase64(array $image)
    {
        $relative_path = $this->makePath($image['path_data']);
        $src = $this->file_service->saveFile($image['filename'], $image['content'], $relative_path);

        // создать модель изображения
        $model = $this->model->newInstance();
        $model->src = $src;

        // создать превью изображения
//        $this->
    }

    /**
     * Сохранить изображение
     *
     * @param $image
     */
    public function save($image)
    {

    }

    /**
     * Загрузка изображений по списку ссылок
     *
     * @param array $url_list
     * @param string $path
     * @param bool $block_image
     *
     * @return array
     */
    public function load(array $url_list, string $path, bool $block_image)
    {
        // ответ с результатами обработки ссылок на изображения
        $data = [
            'image' => [],
            'blocked' => [],
            'error' => [],
        ];

        // обработать список ссылок, пройтись по ссылкам и достать изображение
        foreach ($url_list as $url) {
            try {
                $image = $this->handleUrlImage($url, $path, $block_image);
                $this->createThumb($image);
                // выделить отдельным списком ссылки на заблокированные изображения
                if ($image->is_blocked) {
                    $data['blocked'][] = $image->url;
                } else {
                    $image->src = url('/') . $image->src;
                    $image->thumb = url('/') . $image->thumb;
                    $data['image'][] = $image;
                }
            } catch (Throwable $e) {
                $data['error'][] = $this->errorItem($url, $e->getMessage());
            }
        }

        return $data;
    }

    /**
     * Загрузка изображения напрямую
     *
     * @param UploadedFile $file
     * @param string $path
     *
     * @return Model
     */
    public function upload(UploadedFile $file, string $path)
    {
        $pathname_tmp = $file->getPathname();
        $filename = $file->getClientOriginalName();
        $directory = public_path() . $path . '/';

        $image = $this->model->newQuery()->where([
            'url' => url('/') . $path . '/' . $filename,
        ])->first();

        if (!empty($image)) {
            return $image;
        }

        $hash = hash_file($this->hash_algo, $pathname_tmp);
        // получить прецептивный хеш изображения
        $image_hash = $this->hasher->hash($pathname_tmp);

        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        // обрезать и сжать изображение
        $file = $this->prepareFile($file);
        // переместить файл изображения из временной папки в обычную
        $this->photo->savePhoto($file, $directory . $filename);

        // записать в базу данные изображения
        $image = $this->model::create([
            'hash' => $hash,
            'image_hash' => $image_hash->toHex(),
            'url' => url('/') . $path . '/' . $filename,
            'is_blocked' => false,
            'src' => $path . '/' . $filename,
        ]);

        $this->createThumb($path, $filename, $image, $file);

        return $image;
    }

    /**
     * Удалить изображение по внутренней ссылке (src)
     *
     * @param string $src
     *
     * @return array - статус удаления
     *
     * @throws Exception
     */
    public function remove(string $src): array
    {
        $parsed = parse_url($src);
        $src = $parsed['path'];
        $result = [
            'row_found' => false,
            'image_deleted' => false,
            'thumb_deleted' => false,
            'row_deleted' => false,
        ];

        if (!empty($src)) {
            $image = $this->model->newQuery()->where('src', $src)->first();

            if (!empty($image)) {
                // нашли изображение - удаляем файлы и запись в бд
                $result['row_found'] = true;
                $result['image_deleted'] = $this->removeFile($src);
                $result['thumb_deleted'] = $this->removeFile($image->thumb);

                $result['row_deleted'] = (bool) $image->delete();
            } else {
                // не нашли - считаем, что файлов нет, помечаем как удаленные
                $result['image_deleted'] = true;
                $result['thumb_deleted'] = true;
                $result['row_deleted'] = true;
            }
        }

        return $result;
    }

    /**
     * Блокировать изображение по ссылке
     *
     * @param string $url
     *
     * @return Model
     *
     * @throws \HttpException
     */
    public function block(string $url): Model
    {
        $path = '/image_blocked';

        // создать временный файл изображения, пометить заблокированным
        $image = BaseService::handleUrlImage($url, $path, true);

        // существующее изображение придет с выключенным флагом, новое - с включенным
        if (!$image->is_blocked) {
            // удалить превью изображения и переместить оригинал в папку к заблокированным изображениям
            File::move(public_path() . $image->src, public_path() . $path . '/' . basename($image->src));

            if ($image->thumb) {
                $this->photo->tempPhotoRemove(public_path() . $image->thumb);
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
     * Поиск изображений по списку ссылок
     *
     * @param array $url_list
     *
     * @return array
     */
    public function byUrl(array $url_list): array
    {
        // получить список хешей
        $hash_list = [];
        $data = [];
        // список соответствия хешей урлам $hash => $url
        $hash_url = [];

        foreach ($url_list as $url) {
            try {
                $hash = hash_file($this->hash_algo, $url);
                $hash_list[] = $hash;
                $hash_url[$hash] = $url;
            } catch (Throwable $e) {
                $data['error'][] = $this->errorItem($url, $e->getMessage());
            }
        }

        $data = array_merge($this->findImageByHashList($hash_list), $data);

        if (!empty($data['not_found'])) {
            $data['not_found'] = $this->addUrlToHash($data['not_found'], $hash_url);
        }

        return $data;
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
        $image_list = $this->model->newQuery()->whereIn('hash', $hash_list)->get();
        $not_found_hash_list = array_diff($hash_list, $image_list->pluck('hash')->toArray());

        // дополнить ссылки на изображения и превью (если есть)
        $image_list->map(function ($image) {
            $image->src = url('/') . $image->src;
            $image->thumb = !empty($image->thumb) ? url('/') . $image->thumb : $image->thumb;

            return $image;
        });

        // данные об изображениях
        $data = [];
        $data['image'] = $image_list;

        if ($not_found_hash_list) {
            $data['not_found'] = $not_found_hash_list;
        }

        return $data;
    }

    /**
     * Найти похожее изображение среди заблокированных
     *
     * @param string $image_hash прецептивный хеш оригинала изображения в шестнадцатиричной системе счисления
     * @param array $blocked_image_hash_list - список прецептивных хешей заблокированных изображений
     *
     * @return string|null
     */
    protected function searchBlocked(string $image_hash, array $blocked_image_hash_list): ?string
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
    protected function getBlockedImageHashList()
    {
        return $this->model->newQuery()
            ->select('image_hash')
            ->where('is_blocked', true)
            ->pluck('image_hash')
            ->toArray();
    }

    /**
     * Обработать изображение по ссылке, скачать, если это возможно
     *
     * @param string $url
     * @param string $path
     * @param bool $block_image блокировать ли изображение при обработке
     *
     * @return ImagePhotobank|Builder|Model|object|null
     *
     * @throws \HttpException
     */
    protected function handleUrlImage(string $url, string $path, $block_image = false)
    {
        // получить хеш
        $hash = hash_file($this->hash_algo, $url);

        // проверить, есть ли хеш в базе
        $image = $this->model->newQuery()->where('hash', $hash)->first();

        // если нашли изображение - сразу отдать
        if ($image) {
            return $image;
        }

        // скачать изображение по ссылке
        // создать временное изображение в файловой системе
        //TODO убрать создание временного файла
        // Использовалось для расчета прецептивного хеша
        // Хеш можно посчитать по ссылке
        $tmp_path = $this->photo->tempPhotoCreate($url, $path);

        if (!$tmp_path) {
            throw new \HttpException('Can\'t download file from url: ' . $url);
        }

        $file = new UploadedFile($tmp_path, basename($tmp_path));
        $filename = $file->getFilename();

        // получить прецептивный хеш изображения
        $image_hash = $this->hasher->hash($tmp_path);

        // перед обработкой изображения проверяем на похожесть с заблокированными
        if (!$block_image) {
            $blocked_image = $this->searchBlocked($image_hash->toHex(), $this->getBlockedImageHashList());

            // в случае похожести отдать инфу о том, что изображение заблокировано
            if ($blocked_image) {
                $image = $this->model->newInstance(['url' => $url, 'is_blocked' => true]);

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
        $image = $this->model::create([
            'hash' => $hash,
            'image_hash' => $image_hash->toHex(),
            'url' => $url,
            'is_blocked' => $block_image,
            'src' => $path . '/' . $filename,
        ]);

        return $image;
    }

    /**
     * Обьединить урлы и хеши в один список
     *
     * @param array $hash_list
     * @param array $hash_url
     *
     * @return array
     */
    protected function addUrlToHash(array $hash_list, array $hash_url): array
    {
        $hash_url_list = [];
        foreach ($hash_list as $hash) {
            if (!empty($hash_url[$hash])) {
                $hash_url_list[] = [
                    'url' => $hash_url[$hash],
                    'hash' => $hash,
                ];
            }
        }
        return $hash_url_list;
    }

    /**
     * Получить отдельную запись ошибки
     *
     * @param string $url ссылка на исходное изображение
     * @param string $msg текст ошибки
     *
     * @return array
     */
    protected function errorItem(string $url, string $msg): array
    {
        return [
            'url' => $url,
            'message' => $msg
        ];
    }

    /**
     * Добавить к изображению превью
     *
     * @param BaseImage $image - модель изображения
     *
     * @return void
     */
    //TODO Выделить работу с файлом и вынести в файл сервис
    protected function createThumb(BaseImage $image)
    {
        $path = dirname($image->src);
        $filename = basename($image->src);
        $file = Image::make($image->src);

        $thumb_path = $path . '/thumb/' . $filename;
        $thumb_path_dir = public_path() . $path . '/thumb/';

        if (!file_exists($thumb_path_dir)) {
            mkdir($thumb_path_dir, 0777, true);
        }

        $this->photo->saveThumb($file, public_path() . $thumb_path);
        $image->thumb = $thumb_path;
        $image->save();
    }

    /**
     * Удаляем файл изображения
     *
     * @param string $pathname путь к файлу изображения
     *
     * @return bool
     */
    //TODO Вынести в файл сервис
    protected function removeFile(string $pathname): bool
    {
        $dir = public_path();

        $result = false;

        if (file_exists($dir . $pathname)) {
            $result = unlink($dir . $pathname);
        }

        return $result;
    }
}