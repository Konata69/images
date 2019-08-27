<?php

namespace App\Jobs;

use App\Workers\ImageWorker;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImageLoadImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    /**
     * @var string - url по которому скачиваем изображение
     */
    protected $url;

    /**
     * @var int - id авто во внешней бд
     */
    protected $auto_id;

    /**
     * Create a new job instance.
     *
     * @param string $url
     * @param int $auto_id
     */
    public function __construct(string $url, int $auto_id)
    {
        $this->url = $url;
        $this->auto_id = $auto_id;
    }

    /**
     * Execute the job.
     *
     * @param ImageWorker $worker
     *
     * @return void
     */
    public function handle(ImageWorker $worker)
    {
        $worker->loadByUrl($this->url, $this->auto_id);
    }
}
