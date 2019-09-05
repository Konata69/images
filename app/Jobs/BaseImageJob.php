<?php

namespace App\Jobs;

use App\Services\Image\AutoService;
use App\Services\Image\PhotobankService;
use App\Workers\ImageWorker;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\App;

abstract class BaseImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 360;

    public $tries = 2;

    /**
     * @var int - id изображения во внешней бд
     */
    protected $image_id;

    /**
     * @var string - тип изображения (авто/фотобанк)
     */
    protected $image_type;

    /**
     * @var ImageWorker
     */
    protected $worker;

    /**
     * Create a new job instance.
     *
     * @param int $image_id
     * @param string $image_type
     */
    public function __construct(int $image_id, string $image_type)
    {
        $this->image_id = $image_id;
        $this->image_type = $image_type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->initWorker();
    }

    /**
     * Проинициализировать класс обработчик для заданного типа изображения
     *
     * @return void
     */
    protected function initWorker()
    {
        if ($this->image_type === 'auto') {
            $image_service = App::make(AutoService::class);
        } elseif ($this->image_type === 'photobank') {
            $image_service = App::make(PhotobankService::class);
        } else {
            $this->fail(new Exception('Invalid image type. Only "auto" and "photobank" allowed'));
            return;
        }

        $this->worker = App::make(ImageWorker::class, ['image_service' => $image_service]);
    }
}
