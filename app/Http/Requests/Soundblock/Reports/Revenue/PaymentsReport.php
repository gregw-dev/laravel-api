<?php

namespace App\Http\Requests\Soundblock\Reports\Revenue;

use Illuminate\Foundation\Http\FormRequest;

class PaymentsReport extends FormRequest
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
            "date_start"    => "sometimes|string|date|date_format:Y-m",
            "date_end"      => "sometimes|string|date|date_format:Y-m|after_or_equal:date_start",

            "account_uuid"  => "required|string|max:255|exists:soundblock_accounts,account_uuid",
            "project_uuid"  => "sometimes|string|max:255|exists:soundblock_projects,project_uuid",

            "platform_uuid" => "sometimes|string|max:255|exists:soundblock_data_platforms,platform_uuid"
        ];
    }
}
