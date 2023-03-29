<?php

namespace App\Http\Requests\Office\Soundblock\Report;

use Illuminate\Foundation\Http\FormRequest;

class GetReportsMetadata extends FormRequest
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
            "status" => "sometimes|string|in:Completed,Processing,Failed",
            "date" => "sometimes|date|date_format:Y-m",
            "platform" => "sometimes|uuid|exists:soundblock_data_platforms,platform_uuid",
            "per_page" => "sometimes|integer"
        ];
    }
}
