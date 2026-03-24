<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => $this->isMethod('POST') ? 'required|string|max:191' : 'sometimes|string|max:191',
            'phone'   => 'nullable|string|max:50',
            'email'   => 'nullable|email|max:191',
            'address' => 'nullable|string',
            'note'    => 'nullable|string',
        ];
    }
}
