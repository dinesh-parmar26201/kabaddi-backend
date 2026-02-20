<?php

namespace App\Http\Requests\Raid;

use Illuminate\Foundation\Http\FormRequest;

class StoreRaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'half' => ['required', 'integer', 'in:1,2'],

            'raid_team_id' => ['required', 'integer', 'exists:teams,id'],
            'raider_id' => ['required', 'integer', 'exists:users,id'],

            'outcome' => ['required', 'string', 'in:successful,unsuccessful,empty'],

            'bonus_point' => ['nullable', 'boolean'],
            'super_raid' => ['nullable', 'boolean'],
            'super_tackle' => ['nullable', 'boolean'],
            'raider_lineout' => ['nullable', 'boolean'],
            'all_out' => ['nullable', 'boolean'],

            'technical_point_team_id' => ['nullable', 'integer', 'exists:teams,id'],

            'defenders' => ['nullable', 'array', 'exists:users,id'],

            'tackler' => ['nullable', 'integer','exists:users,id'],
            'defender_lineouts' => ['nullable', 'array', 'exists:match_players,id'],
        ];
    }

    // public function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {

    //         if (
    //             $this->input('raid_outcome') === 'unsuccessful' &&
    //             empty($this->input('raid_tacklers')) &&
    //             empty($this->input('defender_lineouts'))
    //         ) {
    //             $validator->errors()->add(
    //                 'raid_outcome',
    //                 'Unsuccessful raid must have tacklers or defender lineout.'
    //             );
    //         }
    //     });
    // }
}
