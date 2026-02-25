<?php

namespace App\Http\Controllers\Event;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\EventLog;
use App\Services\Event\EventServiceInterface;

class EventController extends Controller
{
    public function __construct(
        protected EventServiceInterface $eventService
    ) {}

    public function index(Request $request)
    {
        $events = $this->eventService->list($request->all());

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    public function store(StoreEventRequest $request)
    {
        $event = $this->eventService->store($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully',
            'data' => $event
        ], 201);
    }

    public function update(UpdateEventRequest $request, EventLog $event)
    {
        $event = $this->eventService->update($event, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'data' => $event
        ]);
    }
}

