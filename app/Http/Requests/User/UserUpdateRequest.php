<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => 'required|string|max:255',
            'jersey_no' => 'nullable|integer|min:0',
            'phone'     => 'required|numeric|digits:10',
            'dob'       => 'required|date_format:Y/m/d',
            'state'     => 'required|string|max:255',
            'country'   => 'required|string|max:255',
            'photo'     => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'fcm_token' => 'nullable|string|max:255',
            'role'      => 'required|string|in:Raider,Defender,All-Rounder',
            'positions' => [
                'nullable',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    $role = $this->input('role');
                    if (!in_array($role, ['Raider', 'Defender', 'All-Rounder'])) {
                        return;
                    }

                    if ($role === 'Raider') {
                        $allowedPositions = ['Left Raider', 'Right Raider', 'Both-Side Raider'];
                        if (count($value) !== 1) {
                            $fail('The Raider role must have exactly one position.');
                            return;
                        }
                    } elseif ($role === 'Defender') {
                        $allowedPositions = ['Left Corner', 'Right Corner', 'Left Cover', 'Right Cover'];
                        if (count($value) !== 1) {
                            $fail('The Defender role must have exactly one position.');
                            return;
                        }
                    } else { // All-Rounder
                        $allowedPositions = [
                            'Left Raider',
                            'Right Raider',
                            'Both-Side Raider',
                            'Left Corner',
                            'Right Corner',
                            'Left Cover',
                            'Right Cover'
                        ];
                    }

                    foreach ($value as $pos) {
                        if (!in_array($pos, $allowedPositions)) {
                            $fail("The position '{$pos}' is invalid for the selected role: '{$role}'.");
                        }
                    }
                }
            ],
        ];
    }
}
