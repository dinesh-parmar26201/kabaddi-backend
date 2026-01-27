<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class AddTournamentTeamsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'team_ids' => 'required|array',
            'team_ids.*' => 'required|integer',
        ];
    }
}
