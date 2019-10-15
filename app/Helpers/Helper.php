<?php

namespace App\Helpers;

class Helper
{
    /**
     * Пишем в лог ошибки
     *
     * @param string $file имя файла лога
     * @param string $content содержимое записи
     *
     * @return void
     */
    public function logError($file, $content)
    {
        $time = (new \DateTime())->format('[Y-m-d H:i:s] ');

        file_put_contents(storage_path() . '/logs/' . $file, $time . print_r($content, true) . "\n", FILE_APPEND);
    }
}
