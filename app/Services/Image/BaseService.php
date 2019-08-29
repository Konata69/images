<?php

namespace App\Services\Image;

use App\Models\Image\BaseImage;
use Exception;
use Illuminate\Http\UploadedFile;
use Jenssegers\ImageHash\ImageHash;
use Throwable;

/**
 * Вспомогательные методы работы с изображениями
 *
 * @package App\Services
 */
abstract class BaseService
{
    /**
     * @var BaseImage
     */
    protected $model;

    /**
     * @var string алгоритм хеширования файлов изображений
     */
    protected $hash_algo;

    /**
     * @var FileService работа с файлами изображений (новый сервис)
     */
    protected $file;

    /**
     * @var FinderService - сервис поиска изображений по урлу и хешу
     */
    protected $finder;

    /**
     * @var ImageHash $hasher Расчет прецептивного хеша изображения
     */
    protected $hasher;

    public function __construct(ImageHash $hasher, FileService $file, FinderService $finder)
    {
        $this->hasher = $hasher;
        $this->file = $file;
        $this->finder = $finder;
        $this->hash_algo = config('image.hash_algo');
    }

    /**
     * @return BaseImage
     */
    public function getModel()
    {
        return $this->model;
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
     *
     * @return BaseImage
     */
    public function saveFromBase64(array $image)
    {
        $relative_path = $this->makePath($image['path_data']);
        $src = $this->file->saveFile($image['filename'], $image['content'], $relative_path);

        // создать модель изображения
        $model = $this->makeImageModelFromSrc($src);
        $model->external_id = $image['image_id'];
        // записать в бд
        $model = $this->model->newQuery()->updateOrCreate(['url' => url('/') . $src], $model->attributesToArray());

        // создать превью изображения
        $this->createThumb($model);

        return $model;
    }

    /**
     * Загрузка изображений по списку ссылок
     *
     * @param array $url_list
     * @param string $path
     *
     * @return array
     */
    public function load(array $url_list, string $path)
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
                $image = $this->handleUrlImage($url, $path);
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
     * @return BaseImage
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

        $this->file->prepareAndSavePhoto($file, $directory . $filename);

        // записать в базу данные изображения
        $image = $this->model::create([
            'hash' => $hash,
            'image_hash' => $image_hash->toHex(),
            'url' => url('/') . $path . '/' . $filename,
            'is_blocked' => false,
            'src' => $path . '/' . $filename,
        ]);

        $this->createThumb($image);

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
                $result['image_deleted'] = FileService::removeFile($src);
                $result['thumb_deleted'] = FileService::removeFile($image->thumb);

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
     * Обработать изображение по ссылке, скачать, если это возможно
     *
     * @param string $url
     * @param string $path
     *
     * @return BaseImage
     */
    protected function handleUrlImage(string $url, string $path)
    {
        // инициализировать модель с хешем и прецептивным хешем
        $image = $this->makeImageModelFromUrl($url);

        // если нашли изображение по хешу - сразу отдать
        if ($image = $this->finder->byHash($image->hash)) {
            return $image;
        }

        //TODO Добавить проверку на блокировку изборажения

        $src = $this->file->prepareAndSavePhoto($image->url, $path, true);
        $image->src = $src;
        $image->save();

        return $image;
    }

    /**
     * Инициализировать модель изображения по ссылке
     *
     * @param string $url
     *
     * @return BaseImage
     */
    protected function makeImageModelFromUrl(string $url): BaseImage
    {
        $image = $this->model->newInstance([
            'url' => $url,
            'hash' => hash_file($this->hash_algo, $url),
            'image_hash' => $this->hasher->hash($url)->toHex(),
        ]);

        return $image;
    }

    /**
     * Создать модель изображения из относительного пути к файлу
     *
     * @param string $src
     *
     * @return BaseImage
     */
    protected function makeImageModelFromSrc(string $src): BaseImage
    {
        $model = $this->model->newInstance();
        $model->src = $src;
        $model->url = url('/') . $src;
        $model->hash = hash_file($this->hash_algo, public_path() . $src);

        return $model;
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
    protected function createThumb(BaseImage $image)
    {
        $path = $this->file->makeThumb($image->src);
        $image->thumb = $path;
        $image->save();
    }
}