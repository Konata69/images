<?php

namespace Tests\Unit\ImportWorker;

use App\Models\Image\ImageAuto;
use App\Workers\ImportWorker;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AddTest extends TestCase
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
        $auto_item2 = new ImageAuto(['url' => 'url2', 'hash' => 'hash1', 'image_hash' => 'image_hash2']);
        $auto_item2->id = 2;
        $this->auto_image_hash->add($auto_item1);
        $this->auto_image_hash->add($auto_item2);

        // новые модели (не из бд)
        $this->feed_image_hash = new Collection();
        $feed_item1 = new ImageAuto(['url' => 'url1', 'hash' => 'hash1']);
        $feed_item2 = new ImageAuto(['url' => 'url2', 'hash' => 'hash2']);
        $feed_item3 = new ImageAuto(['url' => 'url3', 'hash' => 'hash3']);
        $this->feed_image_hash->add($feed_item1);
        $this->feed_image_hash->add($feed_item2);
        $this->feed_image_hash->add($feed_item3);

        $this->import_worker = new ImportWorker();
    }

    public function testAdd()
    {
        // догрузить недостающие изображения и сохранить их в бд
        $this->feed_image_hash = $this->import_worker->add($this->feed_image_hash, $this->auto_image_hash);

        $feed_last = $this->feed_image_hash->last();

        $this->assertEquals(3, $feed_last->id);
    }
}
