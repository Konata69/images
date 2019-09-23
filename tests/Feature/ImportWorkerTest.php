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
        $url1 = 'https://i.pinimg.com/originals/9f/72/e6/9f72e618be8a0e7191f47e6591aaaac5.jpg';
        $url2 = 'https://www.avtovzglyad.ru/media/article/470fe986b4955ce9faf8b06818c79f841bd73c.jpg.740x555_q85_box-0%2C0%2C1024%2C768_crop_detail_upscale.jpg';
        $feed_url = [$url1, $url2];
        $auto_url = [
            ['id' => 654, 'url' => $url1],
            ['id' => 655, 'url' => $url2],
        ];

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
