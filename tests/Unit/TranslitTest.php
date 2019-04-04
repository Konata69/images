<?php

namespace Tests\Unit;

use App\Services\Translit;
use Tests\TestCase;

class TranslitTest extends TestCase
{
    /**
     * @var Translit $translit
     */
    public $translit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translit = $this->app->make(Translit::class);
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->assertTrue(true);
    }

    public function testTranslit()
    {
        $input_str = 'Абв абв, абв. Абв-абв, абв_абв _абв';
        $output_str = 'abv_abv_abv_abv_abv_abv_abv_abv';

        $this->assertEquals($this->translit->translit($input_str), $output_str);
    }
}
