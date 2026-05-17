<?php

namespace App\Jobs;

use App\Mail\MonitorDownMail;
use App\Mail\MonitorUpMail;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class CheckMonitorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // A failed check is valid data. We should not retry silently
    public int $tries = 1;

    public function __construct(
        private readonly Monitor $monitor,
    ) {}

    public function handle(): void
    {
        [$statusCode, $responseTimeMs, $isUp] = $this->pingUrl();

        MonitorCheck::create([
            'monitor_id'       => $this->monitor->id,
            'status_code'      => $statusCode,
            'response_time_ms' => $responseTimeMs,
            'is_up'            => $isUp,
            'checked_at'       => now(),
        ]);

        $this->updateMonitorStatus($isUp);
    }

    /**
     * Hit the URL and return [statusCode, responseTimeMs, isUp].
     *
     * On any connection error or timeout we return [0, null, false]
     * so the check is still recorded as a failure.
     *
     * @return array{int, int|null, bool}
     */
    private function pingUrl(): array
    {
        $start = microtime(true);

        try {
            $response       = Http::timeout(10)->get($this->monitor->url);
            $responseTimeMs = (int) round((microtime(true) - $start) * 1000);
            $statusCode     = $response->status();
            $isUp           = $statusCode >= 200 && $statusCode < 400;

            return [$statusCode, $responseTimeMs, $isUp];
        } catch (\Throwable) {
            return [0, null, false];
        }
    }

    /**
     * Update status and consecutive_failures on the monitor,
     * then send a notification email only when the status transitions
     * (up → down, or down → up). Repeated failures do not re-send.
     */
    private function updateMonitorStatus(bool $isUp): void
    {
        $previousStatus = $this->monitor->status;

        if ($isUp) {
            $this->monitor->update([
                'status'               => 'up',
                'consecutive_failures' => 0,
                'last_checked_at'      => now(),
            ]);

            // Only notify on recovery, not on every successful check
            if ($previousStatus === 'down') {
                Mail::to(config('uptime.notification_email'))
                    ->send(new MonitorUpMail($this->monitor));
            }

            return;
        }

        // Failure path — increment the counter
        $failures         = $this->monitor->consecutive_failures + 1;
        $thresholdReached = $failures >= $this->monitor->threshold;
        $justWentDown     = $thresholdReached && $previousStatus !== 'down';

        $this->monitor->update([
            'status'               => $thresholdReached ? 'down' : $previousStatus,
            'consecutive_failures' => $failures,
            'last_checked_at'      => now(),
        ]);

        if ($justWentDown) {
            Mail::to(config('uptime.notification_email'))
                ->send(new MonitorDownMail($this->monitor));
        }
    }
}
