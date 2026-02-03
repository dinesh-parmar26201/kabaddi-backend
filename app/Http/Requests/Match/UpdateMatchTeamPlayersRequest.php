<?php

namespace App\Http\Requests\Match;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchTeamPlayersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'team_id' => 'required|exists:teams,id',
            'main_players' => 'required|array|exists:users,id',
            'sub_players' => 'nullable|array|exists:users,id',
            'captain_id' => 'nullable|exists:users,id',
        ];
    }

    public function getTeamId(): int
    {
        return $this->input('team_id');
    }

    public function getMainPlayers(): array
    {
        return $this->input('main_players', []);
    }

    public function getSubPlayers(): array
    {
        return $this->input('sub_players', []);
    }

    public function getCaptainId(): ?int
    {
        return $this->input('captain_id');
    }
}

