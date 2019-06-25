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
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;
use Throwable;

/**
 * Загрузка, запрос и блокировка изображений
 *
 * @package App\Http\Controllers
 */
class ImageController extends Controller
{
    use Processing;

    /**
     * Расчет прецептивного хеша
     *
     * @var ImageHash $hasher
     */
    public $hasher;

    /**
     * Вспомогательные методы для работы с изображениями
     *
     * @var ImageService $image_service
     */
    public $image_service;

    /**
     * Работа с файлами изображений
     *
     * @var Photo $photo
     */
    public $photo;

    /**
     * Алгоритм хеширования изображений
     *
     * @var string $hash_algo
     */
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

        $url_list = (array) $request->url;

        // ответ с результатами обработки ссылок на изображения
        $data = [];

        // обработать список ссылок, пройтись по ссылкам и достать изображение
        foreach ($url_list as $url) {
            try {
                $image = $this->handleUrlImage($url, $path);
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
        $url_list = (array) $request->url;

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

        return response()->json($data);
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
     * Экшен запроса изображений по списку хешей
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function byHashView(Request $request)
    {
        // найти изображения по списку хешей, выделить ненайденные хеши
        $hash_list = (array) $request->hash;
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
        $image_list = Image::whereIn('hash', $hash_list)->get();
        $not_found_hash_list = array_diff($hash_list, $image_list->pluck('hash')->toArray());

        // дополнить ссылки на изображения и превью (если есть)
        $image_list->map(function (Image $image) {
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
        // получить хеш
        $hash = hash_file($this->hash_algo, $url);

        // проверить, есть ли хеш в базе
        $image = Image::where('hash', $hash)->first();

        // если нашли изображение - сразу отдать
        if ($image) {
            return $image;
        }

        // скачать изображение по ссылке
        // создать временное изображение в файловой системе
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
            $blocked_image = $this->image_service->searchBlocked($image_hash->toHex(), Image::getBlockedImageHashList());

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
            $this->createThumb($path, $filename, $image, $file);
        }

        return $image;
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
     * Создать превью изображения: файл превью и путь до него в модель изображения
     *
     * @param string $path
     * @param string $filename
     * @param Image $image
     * @param \Intervention\Image\Facades\Image $file
     */
    public function createThumb(string $path, string $filename, Image $image, $file)
    {
        $thumb_path = $path . '/thumb/' . $filename;
        $thumb_path_dir = public_path() . $path . '/thumb/';

        if (!file_exists($thumb_path_dir)) {
            mkdir($thumb_path_dir, 0777, true);
        }

        $this->photo->saveThumb($file, public_path() . $thumb_path);
        $image->thumb = $thumb_path;
        $image->save();
    }
}
