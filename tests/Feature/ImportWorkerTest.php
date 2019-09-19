<?php

namespace Tests\Feature;

use App\DTO\ImportUpdateDTO;
use App\Workers\ImportWorker;
use Tests\TestCase;

class ImportWorkerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testUpdate()
    {
        $import_worker = new ImportWorker();
        $feed_url = [];
        $auto_url = [];

        //TODO !В auto_url не передаем external_id изображений
        // Пытаемся получить изображения по урлу
        // Возможны коллизии (если в несколько авто взяты одинаковые изображения из фотобанка - урл будет одинаковым)
        $dto = new ImportUpdateDTO(
            $feed_url,
            $auto_url,
            1,
            123,
            67
        );

        $import_worker->update($dto);
    }
}
