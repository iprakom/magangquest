<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reminder SLA Deadline</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #fd7e14;">Reminder: SLA Akan Berakhir ⏰</h2>
    <p>Hai Mentor,</p>
    <p>Quest berikut dalam review dan SLA deadline akan berakhir dalam <strong>{{ $hoursRemaining }}</strong>:</p>
    <div style="background: #fff3cd; border: 1px solid #ffeeba; border-radius: 8px; padding: 15px; margin: 20px 0;">
        <p><strong>Quest:</strong> {{ $questTitle }}</p>
        <p><strong>Intern:</strong> {{ $internName }}</p>
        @if($slaDeadline)
        <p><strong>Deadline:</strong> {{ $slaDeadline }}</p>
        @endif
    </div>
    <p>Segera review dan approve/revise quest ini sebelum SLA berakhir untuk menghindari auto-approve.</p>
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #666;">Email ini dikirim otomatis oleh sistem Magang Quest.</p>
</body>
</html>
