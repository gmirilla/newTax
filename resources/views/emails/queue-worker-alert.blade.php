<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family: sans-serif; background: #f9fafb; margin: 0; padding: 32px 0;">
<div style="max-width: 560px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">

    <div style="background: {{ $isDown ? '#dc2626' : '#16a34a' }}; padding: 24px 32px;">
        <p style="color: #fff; font-size: 20px; font-weight: 700; margin: 0;">AccountTaxNG — Ops Alert</p>
    </div>

    <div style="padding: 32px;">
        @if($isDown)
        <h1 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 16px;">
            Queue worker appears down
        </h1>
        <p style="color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px;">
            No queued job has been processed in over {{ \App\Support\QueueHealth::STALE_AFTER_MINUTES }} minutes.
            Invoice emails, notifications, and other background work will pile up until the worker is restarted.
        </p>
        <p style="color: #6b7280; font-size: 14px; margin: 0 0 24px;">
            Last confirmed heartbeat:
            <strong>{{ $lastHeartbeat?->diffForHumans() ?? 'never recorded' }}</strong>
        </p>
        <p style="color: #9ca3af; font-size: 13px; line-height: 1.6; margin: 0 0 24px;">
            Note: this can only detect the queue worker process specifically. If the whole server or its
            scheduled task runner (cron) is down, this alert itself won't fire either — that needs separate
            external uptime monitoring.
        </p>
        @else
        <h1 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 16px;">
            Queue worker has recovered
        </h1>
        <p style="color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 24px;">
            A queued job was processed again as of
            <strong>{{ $lastHeartbeat?->diffForHumans() ?? 'moments ago' }}</strong>. No action needed.
        </p>
        @endif

        <a href="{{ route('superadmin.queue.index') }}"
           style="display: inline-block; background: #111827; color: #fff; font-weight: 600; font-size: 14px; padding: 12px 24px; border-radius: 6px; text-decoration: none;">
            View Queue Dashboard →
        </a>
    </div>

    <div style="background: #f9fafb; padding: 16px 32px; border-top: 1px solid #e5e7eb;">
        <p style="color: #9ca3af; font-size: 12px; margin: 0; text-align: center;">
            Automated ops alert from AccountTaxNG
        </p>
    </div>
</div>
</body>
</html>
