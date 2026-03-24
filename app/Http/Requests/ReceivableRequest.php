<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceivableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'sale_id'     => 'nullable|exists:sales,id',
            'amount'      => 'required|numeric|min:0.01',
            'due_date'    => 'nullable|date',
            'note'        => 'nullable|string',
        ];
    }
}
