<?php


namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'       => 'required|string',
            'logo'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'city'       => 'nullable|string',
        ];
    }
}
