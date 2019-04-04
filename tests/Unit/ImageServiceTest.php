<?php

namespace Tests\Unit;

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
}
