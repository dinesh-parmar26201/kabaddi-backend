<?php

namespace App\Http\Requests\Raid;

use Illuminate\Foundation\Http\FormRequest;

class StoreSkipRaidRequest extends FormRequest
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
            'event_summary' => ['nullable', 'string'],
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
