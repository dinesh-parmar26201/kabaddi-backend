<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateTeamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'       => 'sometimes|string',
            'logo'       => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'city'       => 'nullable|string',
            'players' => 'nullable|array',
            'players.*.id' => 'exists:users,id|distinct',
            'players.*.is_captain' => 'nullable|boolean',
        ];
    }

    protected function passedValidation()
    {
        $this->validateCaptainCount();
    }

    private function validateCaptainCount()
    {
        if ($this->has('players')) {
            $captainCount = collect($this->input('players'))->where('is_captain', true)->count();
            if ($captainCount > 1) {
                throw ValidationException::withMessages([
                    'players' => ['Only one captain is allowed per team.']
                ]);
            }
        }
    }
}
