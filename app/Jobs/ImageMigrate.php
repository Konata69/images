<?php

namespace App\Jobs;

use App\Workers\ImageWorker;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImageMigrate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param int $image_id
     */
    public function __construct(int $image_id)
    {
        $this->image_id = $image_id;
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
        $worker->loadIfNotHandled($this->image_id);
    }
}
