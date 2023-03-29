<?php

namespace App\Http\Requests\Office\Soundblock\Payment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWithdrawal extends FormRequest
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
            "withdrawal_uuid" => "required|string|exists:soundblock_payments_music_users_balance,row_uuid",
            "withdrawal_status" => "required|string|in:Cancelled,Completed"
        ];
    }
}
