<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayablePayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,transfer,qris',
            'note'   => 'nullable|string',
        ];
    }
}
