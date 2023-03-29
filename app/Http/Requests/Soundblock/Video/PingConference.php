<?php

namespace App\Http\Requests\Soundblock\Video;



use Illuminate\Foundation\Http\FormRequest;


class PingConference extends FormRequest
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
            "participant_uuid" => "required|uuid|exists:soundblock_conferences_participants,participant_uuid",
        ];
    }
}
