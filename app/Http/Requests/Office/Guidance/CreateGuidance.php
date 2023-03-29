<?php

namespace App\Http\Requests\Office\Guidance;

use Illuminate\Foundation\Http\FormRequest;

class CreateGuidance extends FormRequest
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
            "guide_ref" => "string|required|unique:soundblock_guidance,guide_ref",
            "guide_title" => "string|required",
            "guide_html" => "string|required",
            "guide_rating" => "numeric|required"
        ];
    }
}
