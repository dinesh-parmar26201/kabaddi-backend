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
            'phone'     => 'required|numeric|digits:10',
            'dob'       => 'required|date_format:Y/m/d',
            'state'     => 'required|string|max:255',
            'country'   => 'required|string|max:255',
            'photo'     => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'fcm_token' => 'nullable|string|max:255',
        ];
    }
}
