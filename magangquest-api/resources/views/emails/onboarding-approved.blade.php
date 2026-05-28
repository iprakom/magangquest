<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pendaftaran Disetujui</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #28a745;">Selamat, {{ $userName }}! 🎉</h2>
    <p>Pendaftaran kamu telah <strong>disetujui</strong>. Selamat memulai perjalanan magang kamu!</p>
    <p>Sebagai apresiasi, kamu mendapatkan bonus:</p>
    <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 15px; text-align: center; margin: 20px 0;">
        <span style="font-size: 24px; font-weight: bold; color: #28a745;">+{{ $bonusPoints }} XP</span>
    </div>
    <p>Kamu sekarang bisa mulai klaim dan mengerjakan quest!</p>
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #666;">Email ini dikirim otomatis oleh sistem Magang Quest.</p>
</body>
</html>
