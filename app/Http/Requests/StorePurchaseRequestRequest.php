<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'unit_id'       => 'nullable|exists:units,id',
            'product_name'  => 'required|string|max:150',
            'quantity_text' => 'nullable|string|max:100',
            'notes'         => 'nullable|string|max:500',
        ];
    }
}