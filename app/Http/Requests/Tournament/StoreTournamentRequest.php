<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class StoreTournamentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'organizer_name' => 'nullable|string',
            'organizer_phone' => 'nullable|string',
            'organizer_email' => 'nullable|string',
            'ground' => 'nullable|string',
            'city' => 'nullable|string',
            'start_date' => 'nullable|string',
            'end_date' => 'nullable|string',
            'category' => 'nullable|string',
            'status' => 'nullable|string',
        ];
    }
}
