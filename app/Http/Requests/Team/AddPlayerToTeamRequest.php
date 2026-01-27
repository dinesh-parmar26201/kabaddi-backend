<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

class AddPlayerToTeamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'player_id' => 'required|integer|exists:users,id',
            'is_captain' => 'nullable|boolean',
        ];
    }
}
