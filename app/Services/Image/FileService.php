<?php

namespace App\Services\Image;

use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

/**
 * Обработка файлов изображений
 */
class FileService
{
    /**
     * @var Processing $processing - обработка изображений
     */
    protected $processing;

    public function __construct(Processing $processing)
    {
        $this->processing = $processing;
    }

    /**
     * Сохранить файл
     *
     * @param string $name - название файла с расширением
     * @param string $content - base64 строка с содержимым файла
     * @param string $relative_path - относительный путь к файлу
     *
     * @return string - относительный путь до файла
     */
    public function saveFile(string $name, string $content, string $relative_path): string
    {
        $content = base64_decode($content);

        if (!File::exists($relative_path)) {
            File::makeDirectory($relative_path, 0777, true);
        }

        $absolute_path_file = $this->getFileAbsolutePath($name, $relative_path);
        File::replace($absolute_path_file, $content);

        return $relative_path . '/' . $name;
    }

    /**
     * Создать файл превью из изображения по относительному пути к файлу
     *
     * @param string $src - относительный путь к файлу изображения
     *
     * @return string - относительный путь к файлу превью
     */
    public function makeThumb(string $src): string
    {
        $path = dirname($src);
        $filename = basename($src);
        $file = Image::make($src);

        $thumb_path = $path . '/thumb/' . $filename;
        $thumb_path_dir = public_path() . $path . '/thumb/';

        if (!file_exists($thumb_path_dir)) {
            mkdir($thumb_path_dir, 0777, true);
        }

        $this->processing->saveThumb($file, public_path() . $thumb_path);

        return $thumb_path;
    }

    /**
     * Подготовить и сохранить фото
     *
     * @param mixed $src - источник фото (url)
     * @param string $path - относительный путь для сохранения на диск
     *
     * @return string - относительный путь к файлу изображения
     */
    public function prepareAndSavePhoto($src, string $path): string
    {
        // обрезать и сжать изображение
        $image = $this->processing->prepareFile($src);
        $filename = uniqid() . '.' . ($image->extension ?? 'jpg');
        // сохранить файл изображения на диск
        $path = $this->processing->savePhoto($image, public_path() . $path . '/' . $filename);

        return $path;
    }

    /**
     * Получить абсолютный путь к файлу
     *
     * @param string $filename
     * @param string $relative_path
     *
     * @return string
     */
    protected function getFileAbsolutePath(string $filename, string $relative_path): string
    {
        return public_path() . $relative_path . '/' . $filename;
    }

    /**
     * Получить абсолютный путь к директории
     *
     * @param string $relative_path
     *
     * @return string
     */
    protected function getDirectoryAbsolutePath(string $relative_path): string
    {
        return public_path() . $relative_path;
    }
}