<?php

namespace App\Jobs;

use App\DTO\ImportUpdateDTO;
use App\Workers\ImportWorker;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImageImportUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 360;

    public $tries = 2;

    /**
     * @var ImportUpdateDTO
     */
    protected $import_update_dto;

    /**
     * @param ImportUpdateDTO $import_update_dto
     */
    public function __construct(ImportUpdateDTO $import_update_dto)
    {
        $this->import_update_dto = $import_update_dto;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new ImportWorker())->update($this->import_update_dto);
    }
}
