<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pendaftaran Ditolak</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #dc3545;">Maaf, {{ $userName }} 😔</h2>
    <p>Pendaftaran kamu <strong>ditolak</strong>.</p>
    <p><strong>Alasan:</strong></p>
    <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; padding: 15px; margin: 20px 0;">
        {{ $reason }}
    </div>
    <p>Jika kamu merasa ada kesalahan, silakan hubungi admin untuk informasi lebih lanjut.</p>
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #666;">Email ini dikirim otomatis oleh sistem Magang Quest.</p>
</body>
</html>
