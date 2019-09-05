<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Обработки загрузки изображений через интерфейс
 */
class ImageLoad extends BaseImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param int $image_id
     * @param string $image_type
     */
    public function __construct(int $image_id, string $image_type)
    {
        parent::__construct($image_id, $image_type);
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws Exception
     */
    public function handle()
    {
        parent::handle();

        $this->worker->load($this->image_id, $this->image_type);
    }
}
