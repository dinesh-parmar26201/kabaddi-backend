<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class StoreTournamentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'gender' => 'nullable|string',
            'type' => 'nullable|string',
            'age_group' => 'nullable|string',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'organizer_name' => 'nullable|string',
            'organizer_phone' => 'nullable|string',
            'organizer_email' => 'nullable|string',
            'ground' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'state' => 'nullable|string',
            'start_date' => 'nullable|string',
            'end_date' => 'nullable|string',
            'category' => 'nullable|string',
            'status' => 'nullable|string',
            'qr_code'   => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'ground_type' => 'nullable|string',
        ];
    }
}
