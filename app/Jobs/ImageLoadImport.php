<?php

namespace App\Jobs;

use App\Services\Image\AutoService;
use App\Workers\ImageWorker;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\App;

/**
 * Задача по загрузке изображений при ипорте
 */
class ImageLoadImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 360;

    /**
     * @var array - список url по которому скачиваем изображение
     */
    protected $url_list;

    /**
     * @var int - id авто во внешней бд
     */
    protected $card_id;

    /**
     * @var int - id авто во внешней бд
     */
    protected $auto_id;

    /**
     * @var string
     */
    protected $base_url;

    /**
     * Create a new job instance.
     *
     * @param array $url_list
     * @param int $card_id
     * @param int $auto_id
     * @param string $base_url
     */
    public function __construct(array $url_list, int $card_id, int $auto_id, string $base_url)
    {
        $this->url_list = $url_list;
        $this->auto_id = $auto_id;
        $this->card_id = $card_id;
        $this->base_url = $base_url;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $worker = ImageWorker::makeWithAutoService($this->base_url);
        $worker->loadByUrl($this->url_list, $this->card_id, $this->auto_id);
    }
}
