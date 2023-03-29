<?php

namespace App\Http\Requests\Office\Correspondence;

use Illuminate\Foundation\Http\FormRequest;

class RemoveCorrespondenceEmail extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
        "email"            => "required|email",
        "correspondence_uuid" => "required|uuid|exists:core_correspondence,correspondence_uuid",
        ];


    }
}
