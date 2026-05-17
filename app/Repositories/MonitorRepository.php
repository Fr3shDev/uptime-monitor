<?php

namespace App\Repositories;

use App\Contracts\MonitorRepositoryInterface;
use App\Models\Monitor;
use Illuminate\Database\Eloquent\Collection;

class MonitorRepository implements MonitorRepositoryInterface
{
    /**
     * Fetch all monitors and use withCount to load both the total
     * check count and the number of successful checks in a single
     * query
     */
    public function all(): Collection
    {
        return Monitor::withCount([
            'checks',
            'checks as up_checks_count' => fn($q) => $q->where('is_up', true),
        ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findById(int $id): Monitor
    {
        return Monitor::findOrFail($id);
    }

    public function create(array $data): Monitor
    {
        return Monitor::create($data);
    }

    public function allDueForCheck(): Collection
    {
        return Monitor::all()
            ->filter(fn(Monitor $monitor) => $monitor->isDueForCheck())
            ->values();
    }
}
