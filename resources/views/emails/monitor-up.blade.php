<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #333; padding: 2rem;">
    <h2 style="color: #16a34a;">🟢 Site is Back Up</h2>
    <p><strong>{{ $monitor->url }}</strong> is responding normally again.</p>
    <p>Recovered at: {{ $monitor->last_checked_at?->toDateTimeString() }}</p>
    <hr>
    <p style="color: #999; font-size: 0.85rem;">
        You are receiving this because you registered this URL with Uptime Monitor.
    </p>
</body>
</html>
