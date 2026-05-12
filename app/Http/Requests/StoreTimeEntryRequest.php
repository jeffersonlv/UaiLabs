<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id'            => 'sometimes|exists:users,id',
            'unit_id'            => 'required|exists:units,id',
            'type'               => 'required|in:clock_in,clock_out',
            'recorded_at'        => 'required|date',
            'original_entry_id'  => 'nullable|exists:time_entries,id',
            'justification'      => 'required|string|min:5',
        ];
    }
}