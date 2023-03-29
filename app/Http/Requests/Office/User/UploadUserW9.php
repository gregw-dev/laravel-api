<?php

namespace App\Http\Requests\Office\User;

use Illuminate\Foundation\Http\FormRequest;

class UploadUserW9 extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return (true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return ([
            "user" => "uuid|exists:users,user_uuid",
            "form" => "required|file|mimes:pdf",
        ]);
    }

}
