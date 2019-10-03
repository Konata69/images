<?php

namespace App\Http\Controllers\Calltracking;

use App\DTO\RecordDTO;
use App\Models\Calltracking\Record;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;

class RecordController extends Controller
{
    public function store(Request $request)
    {
        $record = RecordDTO::makeFromArray($request->record);

        $src = $this->saveFile($record);
        $model = $this->saveModel($src, $record->call_id, $record->type);

        return response()->json($model->file_path);
    }

    /**
     * Сохранить файл записи
     *
     * @param RecordDTO $record
     *
     * @return string - относительный путь к файлу записи
     */
    protected function saveFile(RecordDTO $record): string
    {
        $absolute_path = public_path($record->path);
        $filepath = $absolute_path . $record->filename;

        if (!File::exists($absolute_path)) {
            File::makeDirectory($absolute_path, 0777, true);
        }

        File::put($filepath, $record->file);

        return $record->path . $record->filename;
    }

    /**
     * Сохранить модель в бд
     *
     * @param string $filepath
     * @param int $external_id
     * @param string $type
     *
     * @return Record
     */
    protected function saveModel(string $filepath, int $external_id, string $type): Record
    {
        $model = Record::where('external_id', $external_id)->first();

        // если не нашли модель, создаем новую
        if (empty($model)) {
            $model = new Record();
            $model->external_id = $external_id;
            $model->type = $type;
        }

        // для существующей модели заменяем только путь к файлу
        $model->file_path = $filepath;
        $model->save();

        return $model;
    }
}
