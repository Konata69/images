<?php

namespace App\Http\Controllers\Image;

use App\Http\Controllers\Controller;
use App\Jobs\ImageLoad;
use App\Jobs\ImageMigrate;
use App\Models\Image\ImageAuto;
use App\Workers\ImageWorker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    /**
     * Добавить изображение в очередь на загрузку
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function load(Request $request)
    {
        $image_id = $request->image_id;

        ImageLoad::dispatch($image_id)->onQueue('high');

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
        $image_id_list = collect($request->image_id_list)->map(function ($item) {
            return (int) $item;
        });
        $images = ImageAuto::whereIn('external_id', $image_id_list)->get()->pluck('external_id')->toArray();

        $diff = $image_id_list->diff($images);
        $diff->each(function ($item) {
            ImageMigrate::dispatch($item);
        });

        $data = ['success' => true];

        return response()->json($data);
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

    /**
     * Тестовый экшен
     *
     * @param ImageWorker $worker
     */
    //TODO Убрать тестовый экшен
    public function test(ImageWorker $worker)
    {
        $worker->load(1422475);
    }
}