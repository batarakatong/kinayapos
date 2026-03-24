<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('product');
        return [
            'name' => 'required|string',
            'sku' => 'required|string|unique:products,sku,' . $id,
            'barcode' => 'nullable|string|unique:products,barcode,' . $id,
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'is_global' => 'boolean',
            'track_stock' => 'boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
