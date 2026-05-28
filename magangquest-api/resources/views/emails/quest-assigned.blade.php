<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quest Baru Assigned</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #007bff;">Quest Baru Assigned! 📋</h2>
    <p>Hai <strong>{{ $internName }}</strong>,</p>
    <p>Sebuah quest baru telah di-assign ke kamu:</p>
    <div style="background: #e7f3ff; border: 1px solid #b6d7f7; border-radius: 8px; padding: 15px; margin: 20px 0;">
        <strong style="font-size: 18px;">{{ $questTitle }}</strong>
    </div>
    @if($slaDeadline)
    <p><strong>Deadline:</strong> {{ $slaDeadline }}</p>
    @endif
    <p>Segera cek dan mulai kerjakan quest kamu!</p>
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #666;">Email ini dikirim otomatis oleh sistem Magang Quest.</p>
</body>
</html>
