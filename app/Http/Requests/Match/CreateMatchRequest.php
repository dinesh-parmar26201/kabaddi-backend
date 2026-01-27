<?php

namespace App\Http\Requests\Match;

use Illuminate\Foundation\Http\FormRequest;

class CreateMatchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'team_a_id' => 'required',
            'team_b_id' => 'required',
            'start_date' => 'nullable',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'venue' => 'nullable',
            'ground_name' => 'nullable',
            'organizer_phone' => 'nullable',
            'organizer_email' => 'nullable',
            'teams' => 'nullable|array',
        ];
    }
}
