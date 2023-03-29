<?php

namespace App\Http\Requests\Soundblock\Payments;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Soundblock\Payment\Withdrawal as WithdrawalRule;

class Withdrawal extends FormRequest
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
            "withdrawal_method" => "required|string|in:banking,paymethod",
            "withdrawal_uuid" => ["required", "uuid", new WithdrawalRule($this->all())],
            "withdrawal_amount" => "required|numeric|min:10"
        ];
    }
}
