<?php

namespace Tests\Unit;

use App\Models\Image\Compare\Comparator;
use App\Models\Image\Compare\Item;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Tests\TestCase;
use Throwable;

class ImageCompareTest extends TestCase
{
    public function testMakeFromArray()
    {
        $item1 = ['url' => 'url1', 'hash' => 'hash1'];
        $item2 = ['url' => 'url2', 'hash' => 'hash2'];
        $arr = [$item1, $item2];

        $comparator = FakeComparator::makeFromArray($arr, $arr);

        $this->assertInstanceOf(Comparator::class, $comparator);
        $this->assertInstanceOf(Collection::class, $comparator->new);
        $this->assertInstanceOf(Collection::class, $comparator->old);
    }

    public function testMakeFromArrayException()
    {
        $item1 = ['url' => 'url1', 'hash' => 'hash1'];
        $item2 = ['url' => 'url2'];
        $arr = [$item1, $item2];

        $this->expectException(Throwable::class);

        FakeComparator::makeFromArray($arr, $arr);
    }

    public function testConstructorTypeException()
    {
        $old = new Collection();
        $new = new Collection();
        $item1 = $item1 = ['url' => 'url1', 'hash' => 'hash1'];
        $old->add($item1);

        $this->expectException(InvalidArgumentException::class);

        new Comparator($old, $new);
    }

    public function testGetAddList()
    {
        $item1 = new Item('url1', 'hash1');
        $item2 = new Item('url2', 'hash2');
        $item3 = new Item('url3', 'hash3');
        $item4 = new Item('url4', 'hash4');
        $old = [$item1, $item2, $item4];
        $new = [$item1, $item2, $item3];

        $compare = new Comparator(collect($old), collect($new));

        $this->assertEquals([$item3], array_values($compare->getAddList()->toArray()));
    }

    public function testGetUpdateList()
    {
        $item1 = new Item('url1', 'hash1');
        $item2 = new Item('url2', 'hash2');
        $item3 = new Item('url3', 'hash3');
        $item4 = new Item('url4', 'hash4');
        $old = [$item1, $item2, $item4];
        $item2new = new Item('url2', 'hash5');
        $new = [$item1, $item2new, $item3];

        $compare = new Comparator(collect($old), collect($new));

        $this->assertEquals([$item2new], array_values($compare->getUpdateList()->toArray()));
    }

    public function testGetDeleteList()
    {
        $item1 = new Item('url1', 'hash1');
        $item2 = new Item('url2', 'hash2');
        $item3 = new Item('url3', 'hash3');
        $item4 = new Item('url4', 'hash4');
        $old = [$item1, $item2, $item4];
        $new = [$item1, $item2, $item3];

        $compare = new Comparator(collect($old), collect($new));

        $this->assertEquals([$item4], array_values($compare->getDeleteList()->toArray()));
    }
}

class FakeComparator extends Comparator
{
    public $old;
    public $new;
}