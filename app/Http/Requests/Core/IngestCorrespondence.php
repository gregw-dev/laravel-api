<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

class IngestCorrespondence extends FormRequest
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
            "recipient" => "required|string",
            "sender" => "required|string",
            "from" => "required|string",
            "subject" => "required|string",
            "body-plain" => "required|string",
            "body-html" => "sometimes|string",
            "attachment-count" => "sometimes|integer",
            "timestamp" => "required|integer",
            "token" => "required|string",
            "signature" => "required|string",
            "message-headers" => "required|string",
            "content-id-map" => "sometimes|string",


        ];
    }
}
