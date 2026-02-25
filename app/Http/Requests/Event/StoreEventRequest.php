<?php

namespace App\Http\Requests\Event;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['required', new Enum(EventType::class)],
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'match_id' => ['required', 'integer', 'exists:matches,id'],
            'raid_id' => ['nullable', 'integer'],
            'half' => ['required', 'integer'],
            'raid_number' => ['nullable', 'integer'],
            'summary' => ['nullable', 'string']
        ];
    }
}
