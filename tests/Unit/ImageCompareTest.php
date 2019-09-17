<?php

namespace Tests\Unit;

use App\Models\Image\Compare\Comparator;
use Tests\TestCase;

class ImageCompareTest extends TestCase
{
    public function testGetUpdateList()
    {
        $old = [1,2,3];
        $new = [1,2,3,4];
        $compare = new Comparator($old, $new);

        $this->assertEquals([4], $compare->getUpdateList());
    }
}
