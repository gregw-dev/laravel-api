<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

class CreateCorrespondence extends FormRequest {
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
            "email"         => "required|email",
            "subject"       => "required|string",
            "json"          => "required|string",
            "attachments"   => "sometimes|required|array",
            "attachments.*" => "required_with:attachments|file",
            "users" => "sometimes|array",
            "users.*" => "required_with:users|uuid|exists:users,user_uuid",
            "groups" => "sometimes|array",
            "groups.*" => "required_with:groups|uuid|exists:core_auth_groups,group_uuid",
            "emails" => "sometimes|array",
            "emails.*" => "required_with:emails|email",
            "confirmation_page" => "required|url"
        ];
    }
}
