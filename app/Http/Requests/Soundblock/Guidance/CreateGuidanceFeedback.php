<?php

namespace App\Http\Requests\Soundblock\Guidance;

use Illuminate\Foundation\Http\FormRequest;

class CreateGuidanceFeedback extends FormRequest
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
            "guide_ref" => "required|string|exists:soundblock_guidance,guide_ref",
            "user_feedback"    => "string|required"
        ];
    }
}
