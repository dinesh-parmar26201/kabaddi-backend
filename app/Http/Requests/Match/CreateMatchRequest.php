<?php

namespace App\Http\Requests\Match;

use Illuminate\Foundation\Http\FormRequest;

class CreateMatchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'teams' => 'nullable|array',
            'teams_a_id' => 'nullable|exists:teams,id',
            'teams_b_id' => 'nullable|exists:teams,id',
            'teams_a_tshirt_color' => 'nullable|string',
            'teams_b_tshirt_color' => 'nullable|string',
            'teams_a_court_side' => 'nullable|string',
            'teams_b_court_side' => 'nullable|string',
            'tournament_id' => 'nullable|exists:tournaments,id',
            'tournament_match_no' => 'nullable|integer',
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

    public function getTeamA(): ?array
    {
        return $this->input('teams_a_id') ? [
            'id' => $this->input('teams_a_id'),
            'tshirt_color' => $this->input('teams_a_tshirt_color'),
            'court_side' => $this->input('teams_a_court_side'),
        ] : null;
    }

    public function getTeamB(): ?array
    {
        return $this->input('teams_b_id') ? [
            'id' => $this->input('teams_b_id'),
            'tshirt_color' => $this->input('teams_b_tshirt_color'),
            'court_side' => $this->input('teams_b_court_side'),
        ] : null;
    }
}
