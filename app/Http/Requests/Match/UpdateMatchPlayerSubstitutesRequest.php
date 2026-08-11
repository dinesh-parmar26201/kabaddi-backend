<?php

namespace App\Http\Requests\Match;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchPlayerSubstitutesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'players.*.id' => 'exists:users,id|distinct',
            'players.*.is_substitute' => 'nullable|boolean',
        ];
    }

    public function getSubPlayers(): array
    {
        return $this->input('players', []);
    }
}
