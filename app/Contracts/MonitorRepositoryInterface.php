<?php

namespace App\Contracts;

use App\Models\Monitor;
use Illuminate\Database\Eloquent\Collection;

interface MonitorRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): Monitor;

    public function create(array $data): Monitor;

    public function allDueForCheck(): Collection;
}
