<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class CreateAccountOffice extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return (true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        return ([
            "name"     => "required|string",
            "email"    => "required|email|unique:users_contact_emails,user_auth_email",
            "password" => "required|min:6",
        ]);
    }
}
