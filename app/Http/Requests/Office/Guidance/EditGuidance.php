<?php

namespace App\Http\Requests\Office\Guidance;

use Illuminate\Foundation\Http\FormRequest;

class EditGuidance extends FormRequest
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
            "guide_ref" => "string|sometimes",
            "guide_title" => "string|sometimes",
            "guide_html" => "string|sometimes",
            "guide_rating" => "numeric|sometimes"
        ];
    }
}
