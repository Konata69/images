<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageLoad extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'mark' => 'nullable|string',
            'model' => 'nullable|string',
            'body' => 'nullable|string',
            'generation' => 'nullable|string',
            'complectation' => 'nullable|string',
            'color' => 'nullable|string',
            'url' => 'required',
        ];
    }
}
