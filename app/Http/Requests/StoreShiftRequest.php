<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'unit_id'    => 'nullable|exists:units,id',
            'user_id'    => 'required|exists:users,id',
            'start_at'   => 'required|date',
            'end_at'     => 'required|date|after:start_at',
            'type'       => 'required|in:work,vacation,leave,holiday',
            'station_id' => 'nullable|exists:stations,id',
            'notes'      => 'nullable|string|max:500',
        ];
    }
}
