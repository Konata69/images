<?php

namespace App\Http\Controllers\Image;

use App\DTO\ImportUpdateDTO;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Jobs\ImageImportUpdate;
use App\Jobs\ImageLoad;
use App\Jobs\ImageLoadImport;
use App\Jobs\ImageMigrate;
use App\Models\Image\ImageAuto;
use App\Workers\ImageWorker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class QueueController extends Controller
{
    /**
     * Добавить изображение в очередь на загрузку (при добавлении через интерфейс)
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function load(Request $request)
    {
        $base_url = $request->getSchemeAndHttpHost();

        $image_id = $request->image_id;
        // тип изображения (авто/фотобанк)
        $image_type = $request->image_type;

        ImageLoad::dispatch($image_id, $image_type, $base_url)->onQueue('high');

        $data = ['success' => true];

        return response()->json($data);
    }

    /**
     * Добавить изображение в очередь на загрузку (при добавлении через импорт)
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function import(Request $request)
    {
        $base_url = $request->getSchemeAndHttpHost();

        $url_list = collect($request->url_list);
        $card_id = (int) $request->card_id;
        $auto_id = (int) $request->auto_id;

        ImageLoadImport::dispatch($url_list, $card_id, $auto_id, $base_url);

        $data = ['success' => true];

        return response()->json($data);
    }

    /**
     * Добавить изображения в очередь для миграции
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function migrate(Request $request)
    {
        try {
            $base_url = $request->getSchemeAndHttpHost();

            $image_id_list = collect($request->image_id_list)->map(function ($item) {
                return (int)$item;
            });
            $images = ImageAuto::whereIn('external_id', $image_id_list)->get()->pluck('external_id')->toArray();

            $diff = $image_id_list->diff($images);
            $diff->each(function ($item) use ($base_url) {
                ImageMigrate::dispatch($item, 'auto', $base_url);
            });

            $data = ['success' => true];
        } catch (Throwable $e) {
            (new Helper)->logError('image_migrate', $e->getMessage());
            (new Helper)->logError('image_migrate', $e->getTraceAsString());
        }

        return response()->json($data);
    }

    public function testImport(Request $request)
    {
        $base_url = $request->getSchemeAndHttpHost();
        $url_list = $request->url;
        $card_id = (int) $request->card_id;
        $auto_id = (int) $request->auto_id;

        $worker = ImageWorker::makeWithAutoService($base_url);
        $worker->loadByUrl($url_list, $card_id, $auto_id);
    }

    //TODO Убрать тестовый экшен
    public function testSendServiceUrl(ImageWorker $worker)
    {
        return $worker->testSendServiceUrl();
    }

    public function testMigrate(Request $request, ImageWorker $worker)
    {
        $image_id = (int) $request->image_id;

        $worker->loadIfNotHandled((int) $image_id);
    }

    public function importUpdate(Request $request)
    {
        $base_url = $request->getSchemeAndHttpHost();

        $import_update_dto = new ImportUpdateDTO(
            $request->feed_url,
            $request->auto_url,
            $request->card_id,
            $request->auto_id,
            $request->import_id
        );

        ImageImportUpdate::dispatch($import_update_dto, $base_url);
    }
}
