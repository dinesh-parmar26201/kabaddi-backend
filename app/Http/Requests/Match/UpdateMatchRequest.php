<?php

namespace App\Http\Requests\Match;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'start_date' => 'nullable',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'venue' => 'nullable',
            'ground_name' => 'nullable',
            'status' => 'nullable',
            'teams' => 'nullable|array',
        ];
    }
}

