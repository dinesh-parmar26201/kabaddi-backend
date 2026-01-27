<?php

namespace App\Http\Requests\Match;

use Illuminate\Foundation\Http\FormRequest;

class TossRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'toss_winner_team_id' => 'required',
            'toss_decision' => 'required',
        ];
    }
}
