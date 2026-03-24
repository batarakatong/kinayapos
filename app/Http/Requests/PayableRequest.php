<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'amount'      => 'required|numeric|min:0.01',
            'due_date'    => 'nullable|date',
            'note'        => 'nullable|string',
        ];
    }
}
