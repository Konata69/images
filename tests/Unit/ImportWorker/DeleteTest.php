<?php

namespace Tests\Unit\ImportWorker;

use App\Models\Image\ImageAuto;
use App\Services\Image\AutoService;
use App\Workers\ImageWorker;
use App\Workers\ImportWorker;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeleteTest extends TestCase
{
    /**
     * @var ImportWorker
     */
    protected $import_worker;

    /**
     * @var Collection
     */
    protected $auto_image_hash;

    /**
     * @var Collection
     */
    protected $feed_image_hash;

    protected function setUp(): void
    {
        parent::setUp();

        // модели, которые лежат в бд сервиса
        $this->auto_image_hash = new Collection();
        $auto_item1 = new ImageAuto(['url' => 'url1', 'hash' => 'hash1', 'image_hash' => 'image_hash1']);
        $auto_item1->id = 1;
        $auto_item2 = new ImageAuto(['url' => 'url2', 'hash' => 'hash2', 'image_hash' => 'image_hash2']);
        $auto_item2->id = 2;
        $auto_item3 = new ImageAuto(['url' => 'url3', 'hash' => 'hash3', 'image_hash' => 'image_hash3']);
        $auto_item3->id = 3;
        $this->auto_image_hash->add($auto_item1);
        $this->auto_image_hash->add($auto_item2);
        $this->auto_image_hash->add($auto_item3);

        // новые модели (не из бд)
        $this->feed_image_hash = new Collection();
        $feed_item1 = new ImageAuto(['url' => 'url1', 'hash' => 'hash1']);
        $feed_item2 = new ImageAuto(['url' => 'url2', 'hash' => 'hash2']);
        $this->feed_image_hash->add($feed_item1);
        $this->feed_image_hash->add($feed_item2);

        $stub = $this->createMock(AutoService::class);

        $stub->method('removeByLocalId')
            ->willReturn(null);

        $image_worker = ImageWorker::makeWithAutoService();
        $image_worker->setImageService($stub);

        $this->import_worker = new ImportWorker($image_worker);
    }

    public function testDelete()
    {
        // удалить лишние изображения
        $this->auto_image_hash = $this->import_worker->delete($this->feed_image_hash, $this->auto_image_hash);

        $deleted_item = $this->auto_image_hash->where('id', 3)->first();

        // проверяем только манипуляции с коллекциями
        // не проверяем файлы и записи в бд
        $this->assertEquals(null, $deleted_item);
        $this->assertEquals(2, $this->auto_image_hash->count());
        $this->assertEquals($this->feed_image_hash->count(), $this->auto_image_hash->count());
    }
}
