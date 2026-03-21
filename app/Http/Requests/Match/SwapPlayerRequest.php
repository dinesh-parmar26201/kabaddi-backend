<?php

namespace App\Http\Requests\Match;

use Illuminate\Foundation\Http\FormRequest;

class SwapPlayerRequest extends FormRequest
{
    public function rules()
    {
        return [
            'team_id' => ['required', 'exists:teams,id'],
            'in_player_id' => ['required', 'exists:users,id'],
            'out_player_id' => ['required', 'exists:users,id'],
        ];
    }
}
