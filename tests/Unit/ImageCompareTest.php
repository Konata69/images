<?php

namespace Tests\Unit;

use App\Models\Image\Compare\Comparator;
use App\Models\Image\Compare\Item;
use Tests\TestCase;

class ImageCompareTest extends TestCase
{
    public function testGetAddList()
    {
        $item1 = new Item('url1', 'hash1');
        $item2 = new Item('url2', 'hash2');
        $item3 = new Item('url3', 'hash3');
        $item4 = new Item('url4', 'hash4');
        $old = [$item1, $item2, $item4];
        $new = [$item1, $item2, $item3];

        $compare = new Comparator($old, $new);

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

        $compare = new Comparator($old, $new);

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

        $compare = new Comparator($old, $new);

        $this->assertEquals([$item4], array_values($compare->getDeleteList()->toArray()));
    }
}
