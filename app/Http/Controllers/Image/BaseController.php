<?php

namespace App\Http\Controllers\Image;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlockImage;
use App\Http\Requests\ImageLoad;
use App\Services\Image\BaseService;
use App\Services\Image\BlockService;
use App\Services\Image\FinderService;
use Exception;
use HttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Загрузка, запрос и блокировка изображений
 */
class BaseController extends Controller
{
    /**
     * Сервис для работы с изображениями
     *
     * @var BaseService $image_service
     */
    protected $image_service;

    /**
     * @param BaseService $image_service
     */
    public function __construct(BaseService $image_service)
    {
        $this->image_service = $image_service;
    }

    /**
     * Экшен загрузки изображений по ссылкам
     *
     * @param ImageLoad $request
     *
     * @return JsonResponse
     */
    public function loadAction(ImageLoad $request)
    {
        // из переданных параметров авто собрать путь сохранения файла
        // общий для всех изображений
        $path_params = $this->image_service->getAutoParamList();
        $path = $this->image_service->makePath($request->only($path_params));
        $url_list = (array) $request->url;

        // ответ с результатами обработки ссылок на изображения
        $data = $this->image_service->load($url_list, $path);

        return response()->json($data);
    }

    /**
     * Экшен загрузки изображения напрямую
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uploadAction(Request $request)
    {
        $file = $request->file('image');

        $path_params = $this->image_service->getAutoParamList();
        $path = $this->image_service->makePath($request->only($path_params));

        $image = $this->image_service->upload($file, $path);

        $image->setServiceUrl();

        return response()->json($image);
    }

    /**
     * Экшен удаления изображения по внутренней ссылке
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function removeAction(Request $request)
    {
        $data = $this->image_service->remove((int) $request->id);

        return response()->json($data);
    }

    /**
     * Экшен пометки изображения заблокированным по ссылке
     *
     * @param BlockImage $request
     *
     * @return JsonResponse
     *
     * @throws HttpException
     */
    public function blockAction(BlockImage $request)
    {
        $url = $request->input('url');

        // блокируем изображение по ссылке и получаем информациою об изображении
        $image = BlockService::block($url);

        return response()->json($image);
    }

    /**
     * Получить информацию об изображения по переданным ссылкам
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function byUrlView(Request $request)
    {
        $url_list = (array) $request->url;
        $data = FinderService::byUrl($url_list);

        return response()->json($data);
    }

    /**
     * Экшен запроса изображений по списку хешей
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function byHashView(Request $request)
    {
        $hash_list = (array) $request->hash;
        $data = FinderService::findImageByHashList($hash_list);

        return response()->json($data);
    }
}
