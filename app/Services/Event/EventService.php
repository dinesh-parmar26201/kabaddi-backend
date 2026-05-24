<?php

namespace App\Services\Event;

use App\Models\EventLog;
use App\Services\Event\EventServiceInterface;
use App\Services\Scoreboard\ScoreboardServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EventService implements EventServiceInterface
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = EventLog::with('raid');

        if (isset($filters['match_id'])) {
            $query->where('match_id', $filters['match_id']);
        }

        if (isset($filters['team_id'])) {
            $query->where('team_id', $filters['team_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $perPage = (int) request()->get('per_page', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        $paginator = $query->orderByDesc('id')->paginate($perPage);
        $paginator->getCollection()->transform(function ($eventLog) {
            $eventLog->raid_dto = $eventLog->raid_dto;
            return $eventLog;
        });
        return $paginator;
    }

    public function store(array $data): EventLog
    {
        $event = EventLog::create([
            'type' => $data['event_type'],
            'team_id' => $data['team_id'] ?? null,
            'match_id' => $data['match_id'],
            'half' => $data['half'] ?? null,
            'raid_number' => $data['raid_number'] ?? null,
            'summary' => $data['summary'] ?? null,
        ]);

        $scoreService = app(ScoreboardServiceInterface::class);
        $scoreboard = $scoreService->getMatchScoreboard($data['match_id']);
        $event->score_after_raid = $scoreboard->teamBreakdowns ?? [];
        $event->save();

        return $event->refresh();
    }

    public function update(EventLog $event, array $data): EventLog
    {
        $event->update([
            'type' => $data['event_type'] ?? $event->type,
            'team_id' => $data['team_id'] ?? $event->team_id,
            'match_id' => $data['match_id'] ?? $event->match_id,
            'raid_id' => $data['raid_id'] ?? $event->raid_id,
            'half' => $data['half'] ?? $event->half,
            'raid_number' => $data['raid_number'] ?? $event->raid_number,
            'summary' => $data['summary'] ?? $event->summary,
        ]);

        $scoreService = app(ScoreboardServiceInterface::class);
        $scoreboard = $scoreService->getMatchScoreboard($data['match_id']);
        $event->score_after_raid = $scoreboard->teamBreakdowns ?? [];
        $event->save();

        return $event->refresh();
    }
}
