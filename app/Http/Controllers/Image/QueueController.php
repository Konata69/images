<?php

namespace App\Http\Controllers\Image;

use App\Http\Controllers\Controller;
use App\Jobs\ImageLoad;
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
     * Тестовый экшен
     *
     * @param ImageWorker $worker
     *
     * @return JsonResponse
     */
    public function test(ImageWorker $worker)
    {
        $result = $worker->load(423);

        return $result;
    }
}