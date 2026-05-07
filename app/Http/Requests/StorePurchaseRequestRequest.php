<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'unit_id'         => 'required|exists:units,id',
            'product_name'    => 'required|string|max:150',
            'quantity'        => 'required|numeric|min:0.001',
            'unit_of_measure' => 'required|in:un,kg,g,l,ml,cx',
            'notes'           => 'nullable|string|max:500',
        ];
    }
}