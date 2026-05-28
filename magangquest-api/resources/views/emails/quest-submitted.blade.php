<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quest Submit untuk Review</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #ffc107;">Quest Submit untuk Review ⚠️</h2>
    <p>Hai Mentor,</p>
    <p>Intern <strong>{{ $internName }}</strong> telah submit quest untuk direview:</p>
    <div style="background: #fff3cd; border: 1px solid #ffeeba; border-radius: 8px; padding: 15px; margin: 20px 0;">
        <strong style="font-size: 18px;">{{ $questTitle }}</strong>
    </div>
    @if($submittedAt)
    <p><strong>Submitted:</strong> {{ $submittedAt }}</p>
    @endif
    <p>Segera review quest ini sebelum SLA deadline berakhir.</p>
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #666;">Email ini dikirim otomatis oleh sistem Magang Quest.</p>
</body>
</html>
