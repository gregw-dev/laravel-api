<?php

namespace App\Http\Requests\Soundblock\Payments;

use Illuminate\Foundation\Http\FormRequest;

class BalanceHistory extends FormRequest
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
            "history_type" => "required|string|in:earned,withdrawal,all",
            "per_page" => "sometimes|int|between:10,100"
        ];
    }
}
