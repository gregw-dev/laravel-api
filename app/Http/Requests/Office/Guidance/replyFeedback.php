<?php

namespace App\Http\Requests\Office\Guidance;

use Illuminate\Foundation\Http\FormRequest;

class replyFeedback extends FormRequest
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
            "feedback_uuid" => "uuid|required|exists:soundblock_guidance_feedback,feedback_uuid",
            "text" => "string|required",
        ];
    }
}
