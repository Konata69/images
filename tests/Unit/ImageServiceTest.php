<?php

namespace Tests\Unit;

use App\Models\Image;
use App\Services\ImageService;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    /**
     * @var ImageService $image_service
     */
    public $image_service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->image_service = $this->app->make(ImageService::class);
    }

    public function testTranslit()
    {
        $input_str = 'Абв абв, абв. Абв-абв, абв_абв _абв';
        $output_str = 'abv_abv_abv_abv_abv_abv_abv_abv';

        $this->assertEquals($this->image_service->translit($input_str), $output_str);
    }

    public function testMakePath()
    {
        $param_list = [
            'mark' => 'марка',
            'model' => 'Модель',
            'body' => '',
            'generation' => null,
            'complectation' => '123',
            'color' => 'сер-бур-черный',
            'body_group' => 'body_group',
        ];

        $expected = '/image/marka/model/default/default/123/ser_bur_cherniy/body_group';

        $this->assertEquals($this->image_service->makePath($param_list), $expected);
    }

    public function testSearchBlocked()
    {
        $blocked_image_hash = '173c4bc9ebab8634';

        $image_blocked = new Image([
            'id' => 1,
            'image_hash' => '373c4bc9ebab8634',
        ]);

        $image = new Image([
            'id' => 2,
            'image_hash' => '94817445560e6b4d',
        ]);

        $image_list[] = $image_blocked;
        $image_list[] = $image;

        // отличие в 1 символ
        $result = $this->image_service->searchBlocked($blocked_image_hash, $image_list);
        $this->assertEquals($image_blocked, $result);

        // полное совпадение
        $blocked_image_hash = '373c4bc9ebab8634';
        $result = $this->image_service->searchBlocked($blocked_image_hash, $image_list);
        $this->assertEquals($image_blocked, $result);

        // отличие в 4 символа
        $blocked_image_hash = '11114bc9ebab8634';
        $result = $this->image_service->searchBlocked($blocked_image_hash, $image_list);
        $this->assertNull($result);
    }
}
