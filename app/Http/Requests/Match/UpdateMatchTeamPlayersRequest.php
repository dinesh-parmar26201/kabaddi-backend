<?php

namespace App\Http\Requests\Match;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchTeamPlayersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'team_id' => 'required|exists:teams,id',
            'players' => 'required|array',
            'players.*.id' => 'required|exists:users,id',
            'players.*.is_substitute' => 'nullable|boolean',
        ];
    }

    public function getTeamId(): int
    {
        return $this->input('team_id');
    }

    public function getPlayers(): array
    {
        return $this->input('players', []);
    }
}

