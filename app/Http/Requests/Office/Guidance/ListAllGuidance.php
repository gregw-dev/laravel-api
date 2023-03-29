<?php

namespace App\Http\Requests\Office\Guidance;

use Illuminate\Foundation\Http\FormRequest;

class ListAllGuidance extends FormRequest
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
            "per_page"    => "sometimes|integer|between:10,100",
            "search"      => "sometimes|string|max:255",
            "guide_ref"   => "sometimes|string|max:255",
            "title"       => "sometimes|string|max:255",
            "flag_active" => "sometimes|boolean"
        ];
    }
}
