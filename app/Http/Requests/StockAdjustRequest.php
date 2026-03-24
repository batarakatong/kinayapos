<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric',
            'type' => 'required|in:in,out,adjust,purchase,sale',
            'note' => 'nullable|string',
        ];
    }
}
