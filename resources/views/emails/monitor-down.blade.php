<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #333; padding: 2rem;">
    <h2 style="color: #dc2626;">🔴 Site is Down</h2>
    <p>We were unable to reach <strong>{{ $monitor->url }}</strong>.</p>
    <p>It has failed {{ $monitor->consecutive_failures }} consecutive check(s).</p>
    <p>Last checked: {{ $monitor->last_checked_at?->toDateTimeString() }}</p>
    <hr>
    <p style="color: #999; font-size: 0.85rem;">
        You are receiving this because you registered this URL with Uptime Monitor.
    </p>
</body>
</html>
