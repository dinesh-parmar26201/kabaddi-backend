<?php

namespace App\Http\Requests\Match;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchTeamCourtRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'team_id' => 'required|exists:teams,id',
            'court_side' => 'required|string',
        ];
    }

    public function getTeamId(): int
    {
        return $this->input('team_id');
    }

    public function getCourtSide(): string
    {
        return $this->input('court_side');
    }
}

