<?php

namespace App\Http\Requests\Soundblock\Contributor;

use Illuminate\Foundation\Http\FormRequest;

class GetContributor extends FormRequest
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
            "account" => "required|uuid",
            "contributor" => "sometimes|uuid|exists:soundblock_contributors,contributor_uuid"
        ];
    }
}
