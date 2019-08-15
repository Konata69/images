<?php

namespace App\Workers;

use App\Models\ImageAuto;
use App\Models\ImagePhotobank;
use App\Services\BaseApiClient;
use App\Services\Image\AutoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

/**
 * Обработчик задач по загрузке изображений
 */
class ImageWorker
{
    /**
     * @var BaseApiClient - работа через со сторонним сервисом
     */
    protected $api;

    /**
     * @var AutoService - сервис изображений авто
     */
    protected $image_service;

    public function __construct(BaseApiClient $api, AutoService $image_service)
    {
        $this->api = $api;
        $this->image_service = $image_service;
    }

    /**
     * Запрашивает файл изображения, сохраняет, отсылает ссылки на изображения
     *
     * @param int $image_id
     * @param array $path_data
     *
     * @return JsonResponse
     */
    public function load(int $image_id, array $path_data)
    {
        // сделать запрос к autoxml на получение файла
        $url = 'http://127.0.0.1:8000/api/image-service/image';
        $data = ['image_id' => $image_id];
        $header[] = 'X-Requested-With: XMLHttpRequest';

        // первый запрос - на получение файла изображения
        $result = $this->api->post($url, $data, $header);

        if (empty($result['data']['error'])
            && empty($result['error'])
            && !empty($result['data']['image'])) {

            // сохранить файл и модель изображения
            $image = $this->save($result['data']['image']);

        } else {
            // обработать ошибки
        }

        // второй запрос - отдать сервисные ссылки на изображение

        return response()->json($data);
    }

    /**
     * Из переданных данных изображения сделать модель и сохранить изображение в файл
     *
     * @param array $image
     *
     * @return ImageAuto|ImagePhotobank - модель изображения
     */
    protected function save(array $image)
    {
        $src = $this->saveFile($image['filename'], $image['content'], $image['path_data']);
        // создать модель изображения (частный)

    }

    /**
     * Сохранить файл
     *
     * @param string $name - название файла с расширением
     * @param string $content - base64 строка с содержимым файла
     * @param array $path_data - параметры для формирования пути к файлу
     *
     * @return string - относительный путь до файла
     */
    //TODO Вынести в сервис
    protected function saveFile(string $name, string $content, array $path_data): string
    {
        $content = base64_decode($content);

        $absolute_path_file = $this->getFileAbsolutePath($name, $path_data);
        $relative_path_directory = $this->image_service->makePath($path_data);

        if (!File::exists($relative_path_directory)) {
            File::makeDirectory($relative_path_directory, 0777, true);
        }

        File::replace($absolute_path_file, $content);

        return $relative_path_directory . '/' . $name;
    }

    /**
     * Получить абсолютный путь к файлу
     *
     * @param string $filename
     * @param array $path_data
     *
     * @return string
     */
    //TODO Вынести в сервис
    protected function getFileAbsolutePath(string $filename, array $path_data): string
    {
        $relative_path_directory = $this->image_service->makePath($path_data);
        $absolute_path_directory = public_path() . $relative_path_directory;
        $absolute_path_file = $absolute_path_directory . '/' . $filename;

        return $absolute_path_file;
    }

    /**
     * @param array $path_data
     *
     * @return string
     */
    //TODO Вынести в сервис
    protected function getDirectoryAbsolutePath(array $path_data): string
    {
        $relative_path_directory = $this->image_service->makePath($path_data);
        $absolute_path_directory = public_path() . $relative_path_directory;

        return $absolute_path_directory;
    }
}