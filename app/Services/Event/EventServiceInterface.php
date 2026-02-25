<?php

namespace App\Services\Event;

use App\Models\EventLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EventServiceInterface
{
    public function list(array $filters): LengthAwarePaginator;
    public function store(array $data): EventLog;
    public function update(EventLog $event, array $data): EventLog;
}
