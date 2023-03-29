<?php

namespace App\Http\Requests\Office\Correspondence;

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
            "subject"       => "required|string",
            "text"          => "required|string",
            "from"            => "required|uuid",
            "attachments"   => "sometimes|array",
            "attachments.*" => "required_with:attachments|file|max:10240",
            "to"          => "required|string",
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
