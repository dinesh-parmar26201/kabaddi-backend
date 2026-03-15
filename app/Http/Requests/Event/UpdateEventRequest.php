<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\EventType;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['sometimes', new Enum(EventType::class)],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'match_id' => ['nullable', 'integer', 'exists:matches,id'],
            'raid_id' => ['nullable', 'integer', 'exists:raids,id'],
            'half' => ['nullable', 'integer'],
            'raid_number' => ['nullable', 'integer'],
            'summary' => ['nullable', 'string'],
            'score_after_raid' => ['nullable', 'array'],
        ];
    }
}
