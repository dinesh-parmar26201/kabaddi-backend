<?php

namespace App\Http\Requests\Match;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchPlayerCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'team_id' => 'required|integer|exists:teams,id',
            'player_id' => 'required|integer|exists:users,id',
            'green_card' => 'nullable|boolean',
            'yellow_card' => 'nullable|boolean',
            'red_card' => 'nullable|boolean',
        ];
    }
}
