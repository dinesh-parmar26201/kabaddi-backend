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
            'name' => 'required|string|max:255',
            'phone'     => 'required|string|max:10',
            'dob'       => 'required|date',
            'state'     => 'required|string|max:255',
            'country'   => 'required|string|max:255',
            'photo'     => 'nullable|url',
            'fcm_token' => 'nullable|string|max:255',
        ];
    }
}

