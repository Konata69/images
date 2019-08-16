<?php

namespace App\Services\Image;

use Illuminate\Support\Facades\File;

/**
 * Обработка файлов изображений
 */
class FileService
{
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
     * Получить абсолютный путь к файлу
     *
     * @param string $filename
     * @param string $relative_path
     *
     * @return string
     */
    public function getFileAbsolutePath(string $filename, string $relative_path): string
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
    public function getDirectoryAbsolutePath(string $relative_path): string
    {
        return public_path() . $relative_path;
    }
}