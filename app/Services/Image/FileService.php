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
        //TODO Вместо ручной кодировки использовать Intervention make
        $content = base64_decode($content);
        $directory_absolute_path = $this->getDirectoryAbsolutePath($relative_path);

        if (!File::exists($directory_absolute_path)) {
            File::makeDirectory($directory_absolute_path, 0777, true);
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
        $src_path = public_path() . $src;
        $path = dirname($src);
        $filename = basename($src);
        $file = Image::make($src_path);

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
     * @param bool $generate_name - флаг генерации имени файла
     * true - сгенерировать новое имя,
     * false - использовать имя файла
     *
     * @return string - относительный путь к файлу изображения
     */
    public function prepareAndSavePhoto($src, string $path, bool $generate_name = false): string
    {
        // обрезать и сжать изображение
        $image = $this->processing->prepareFile($src);
        // сгенерировать новое имя файла или получить текущее
        $filename = $generate_name ? $this->generateFilename($image->extension) : $image->basename;

        $directory_absolute_path = $this->getDirectoryAbsolutePath($path);
        if (!File::exists($directory_absolute_path)) {
            File::makeDirectory($directory_absolute_path, 0777, true);
        }

        // сохранить файл изображения на диск
        $this->processing->savePhoto($image, public_path() . $path . '/' . $filename);

        return $path . '/' . $filename;
    }

    /**
     * Сгенерировать новое имя файла
     *
     * @param string|null $extension - расширение файла
     *
     * @return string
     */
    protected function generateFilename(?string $extension): string
    {
        return uniqid() . '.' . ($extension ?? 'jpg');
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

    /**
     * Удаляем файл изображения
     *
     * @param string $pathname путь к файлу изображения
     *
     * @return bool
     */
    public static function removeFile(string $pathname): bool
    {
        $dir = public_path();

        $result = false;

        if (file_exists($dir . $pathname)) {
            $result = unlink($dir . $pathname);
        }

        return $result;
    }
}