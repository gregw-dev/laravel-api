<?php

namespace App\Http\Requests\Office\Soundblock\Payment;

use Illuminate\Foundation\Http\FormRequest;

class GetWithdrawal extends FormRequest
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
            "withdrawal_status" => "sometimes|string|in:Pending,Cancelled,Completed",
            "per_page" => "sometimes|int|between:10,100"
        ];
    }
}
