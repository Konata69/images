<?php

namespace App\Jobs;

use App\Workers\ImageWorker;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Задача по загрузке изображений при ипорте
 */
class ImageLoadImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    /**
     * @var array - список url по которому скачиваем изображение
     */
    protected $url_list;

    /**
     * @var int - id авто во внешней бд
     */
    protected $auto_id;

    /**
     * Create a new job instance.
     *
     * @param array $url_list
     * @param int $auto_id
     */
    public function __construct(array $url_list, int $auto_id)
    {
        $this->url_list = $url_list;
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
        $worker->loadByUrl($this->url_list, $this->auto_id);
    }
}
