<?php

namespace App\Services\Image;

/**
 * Работа с файлами
 */
class FileService
{
    /**
     * Сохранить файл
     *
     * @param string $name - название файла с расширением
     * @param string $content - base64 строка с содержимым файла
     * @param array $path_data - параметры для формирования пути к файлу
     *
     * @return string - относительный путь до файла
     */
    public function saveFile(string $name, string $content, array $path_data): string
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
    public function getFileAbsolutePath(string $filename, array $path_data): string
    {
        $relative_path_directory = $this->image_service->makePath($path_data);
        $absolute_path_directory = public_path() . $relative_path_directory;
        $absolute_path_file = $absolute_path_directory . '/' . $filename;

        return $absolute_path_file;
    }

    /**
     * Получить абсолютный путь к директории
     *
     * @param array $path_data
     *
     * @return string
     */
    public function getDirectoryAbsolutePath(array $path_data): string
    {
        $relative_path_directory = $this->image_service->makePath($path_data);
        $absolute_path_directory = public_path() . $relative_path_directory;

        return $absolute_path_directory;
    }
}