<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quest Disetujui</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #28a745;">Quest Kamu Disetujui! 🎉</h2>
    <p>Hai <strong>{{ $internName }}</strong>,</p>
    <p>Quest kamu telah disetujui oleh mentor:</p>
    <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 15px; margin: 20px 0;">
        <p style="margin: 0;"><strong>{{ $questTitle }}</strong></p>
    </div>
    <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 15px; text-align: center; margin: 20px 0;">
        <span style="font-size: 24px; font-weight: bold; color: #28a745;">+{{ $points }} XP</span>
    </div>
    <p>Great job! Tetap semangat menyelesaikan quest-quest berikutnya!</p>
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #666;">Email ini dikirim otomatis oleh sistem Magang Quest.</p>
</body>
</html>
