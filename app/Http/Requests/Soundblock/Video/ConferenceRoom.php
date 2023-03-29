<?php

namespace App\Http\Requests\Soundblock\Video;

use App\Rules\Soundblock\Conference\IsValidAccountUuid;
use App\Rules\Soundblock\Conference\IsValidProjectUuid;
use Illuminate\Foundation\Http\FormRequest;

class ConferenceRoom extends FormRequest
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
    public function rules(): array
    {
        return [
            "account_uuid"      => ["required_without:project_uuid", "string", new IsValidAccountUuid],
            "project_uuid"      => ["required_without:account_uuid", "string", new IsValidProjectUuid],
        ];
    }
}
