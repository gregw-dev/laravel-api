<?php

namespace App\Http\Requests\Office\Correspondence;

use Illuminate\Foundation\Http\FormRequest;

class CorrespondenceUser extends FormRequest {
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
        "user_uuid"            => "required|array|min:1",
        "user_uuid.*"            => "required|uuid|exists:users,user_uuid",
        "correspondence_uuid" => "required|uuid|exists:core_correspondence,correspondence_uuid",
        ];


    }
}
