<?php

namespace App\Http\Requests\Match;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'venue' => 'nullable',
            'ground_name' => 'nullable',
            'organizer_phone' => 'nullable',
            'organizer_email' => 'nullable',
            'status' => 'nullable|string',
            'toss_winner_team_id' => 'nullable|exists:teams,id',
            'toss_decision' => 'nullable|string',
        ];
    }
}

