<?php

namespace Tests\Unit;

use App\Services\Image\PhotobankService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    //TODO Поправить тесты после рефакторинга

    /**
     * @var PhotobankService
     */
    protected $photobank_service;

    /**
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->photobank_service = $this->app->make(PhotobankService::class);
    }

    public function testTranslit()
    {
        $input_str = 'Абв абв, абв. Абв-абв, абв_абв _абв';
        $output_str = 'abv_abv_abv_abv_abv_abv_abv_abv';

        $this->assertEquals($this->photobank_service->translit($input_str), $output_str);
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
        ];

        $expected = '/image/marka/model/default/default/123/ser_bur_cherniy';

        $this->assertEquals($this->photobank_service->makePath($param_list), $expected);
    }

    public function testSearchBlocked()
    {
        $this->markTestSkipped('Починить проверку поиска заблокированных изображений');

        $blocked_image_hash = '173c4bc9ebab8634';

        $image_hash_list[1] = '373c4bc9ebab8634';
        $image_hash_list[2] = '94817445560e6b4d';

        // отличие в 1 символ
        $result = $this->image_service->searchBlocked($blocked_image_hash, $image_hash_list);
        $this->assertEquals($image_hash_list[1], $result);

        // полное совпадение
        $blocked_image_hash = '373c4bc9ebab8634';
        $result = $this->image_service->searchBlocked($blocked_image_hash, $image_hash_list);
        $this->assertEquals($image_hash_list[1], $result);

        // отличие в 4 символа
        $blocked_image_hash = '11114bc9ebab8634';
        $result = $this->image_service->searchBlocked($blocked_image_hash, $image_hash_list);
        $this->assertNull($result);
    }
}
