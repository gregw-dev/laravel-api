<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

class getCorrespondence extends FormRequest
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
            "uuid" => "required|uuid|exists:core_correspondence,correspondence_uuid",
            "email" => "required|email",
        ];
    }
}
