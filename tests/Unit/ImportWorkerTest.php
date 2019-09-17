<?php

namespace Tests\Unit;

use App\Models\Image\ImageCompare;
use Tests\TestCase;

class ImportWorkerTest extends TestCase
{
    public function testGetUpdateList()
    {
        $old = [1,2,3];
        $new = [1,2,3,4];
        $compare = new ImageCompare($old, $new);

        $this->assertEquals([4], $compare->getUpdateList());
    }
}
