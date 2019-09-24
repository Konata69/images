<?php

namespace Tests\Feature;

use App\DTO\ImportUpdateDTO;
use App\Models\Image\Compare\Comparator;
use App\Workers\ImageWorker;
use App\Workers\ImportWorker;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ImportWorkerTest extends TestCase
{
    /**
     * @var ImportWorker
     */
    protected $import_worker;

    /**
     * @var ImportUpdateDTO
     */
    protected $dto;

    /**
     * @var Collection
     */
    protected $auto_image_hash;

    /**
     * @var Collection
     */
    protected $feed_image_hash;


    /**
     * @var Comparator
     */
    protected $comparator;

    /**
     * @var ImageWorker
     */
    protected $image_worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->import_worker = new ImportWorker();
        $url1 = 'https://i.pinimg.com/originals/9f/72/e6/9f72e618be8a0e7191f47e6591aaaac5.jpg';
        $url2 = 'https://www.avtovzglyad.ru/media/article/470fe986b4955ce9faf8b06818c79f841bd73c.jpg.740x555_q85_box-0%2C0%2C1024%2C768_crop_detail_upscale.jpg';
        $feed_url = [$url1, $url2];
        $auto_url = [
            ['id' => 654, 'url' => $url1],
            ['id' => 655, 'url' => $url2],
        ];

        $this->dto = new ImportUpdateDTO(
            $feed_url,
            $auto_url,
            1,
            123,
            67
        );

        // получить хеши изображений
        $this->auto_image_hash = $this->import_worker->getLocalImage($this->dto->auto_url);
        $this->feed_image_hash = $this->import_worker->getFeedImageHash($this->dto->feed_url);

        $this->comparator = new Comparator($this->auto_image_hash, $this->feed_image_hash);
        $this->image_worker = ImageWorker::makeWithAutoService();
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testUpdate()
    {
        $this->import_worker->update($this->dto);
    }

    public function testCloneCollectionChangeOrder()
    {
        $new_list = clone $this->feed_image_hash;

        $first = $this->feed_image_hash->shift();
        $this->feed_image_hash->push($first);

        $this->assertNotEquals($this->feed_image_hash, $new_list);
    }

    public function testCloneCollectionChangeItem()
    {
        $new_list = clone $this->feed_image_hash;
        //TODO клонирование коллекции не работает
        // Элементы коллекции копируются по ссылке (нужно по значению - deep clone)

        $this->feed_image_hash->first()->hash = 'new_hash';

        $this->assertEquals('new_hash', $this->feed_image_hash->first()->hash);
        $this->assertEquals('new_hash', $new_list->first()->hash);
        $this->assertNotEquals('new_hash_123', $new_list->first()->hash);

        $this->assertEquals($this->feed_image_hash, $new_list);
    }
}
