<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Monitor extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'check_interval'       => 'integer',
        'threshold'            => 'integer',
        'consecutive_failures' => 'integer',
        'last_checked_at'      => 'datetime',
    ];

    public function checks(): HasMany
    {
        return $this->hasMany(MonitorCheck::class);
    }

    // Uses eager-loaded counts from the repository when available, so no extra queries are fired during a list response.
    public function uptimePercentage(): ?float
    {
        $total = $this->checks_count ?? $this->checks()->count();

        if ($total === 0) {
            return null;
        }

        $up = $this->up_checks_count ?? $this->checks()->where('is_up', true)->count();

        return round(($up / $total) * 100, 2);
    }

    // A monitor is due if it has never been checked, or if enough time has passed since its last check based on check_interval.
    public function isDueForCheck(): bool
    {
        if ($this->last_checked_at === null) {
            return true;
        }

        return $this->last_checked_at->addMinutes($this->check_interval)->isPast();
    }
}
