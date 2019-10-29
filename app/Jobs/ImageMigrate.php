<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImageMigrate extends BaseImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param int $image_id
     * @param string $image_type
     * @param string $base_url
     */
    public function __construct(int $image_id, string $image_type, string $base_url)
    {
        parent::__construct($image_id, $image_type, $base_url);
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

        $this->worker->loadIfNotHandled($this->image_id, $this->image_type);
    }
}
