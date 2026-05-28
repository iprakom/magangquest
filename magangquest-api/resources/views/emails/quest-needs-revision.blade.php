<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quest Perlu Revisi</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #ffc107;">Quest Dikembalikan untuk Revisi 🔄</h2>
    <p>Hai <strong>{{ $internName }}</strong>,</p>
    <p>Quest kamu perlu direvisi:</p>
    <div style="background: #fff3cd; border: 1px solid #ffeeba; border-radius: 8px; padding: 15px; margin: 20px 0;">
        <p style="margin: 0;"><strong>{{ $questTitle }}</strong></p>
    </div>
    <p><strong>Catatan dari Mentor:</strong></p>
    <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; margin: 20px 0;">
        {{ $notes }}
    </div>
    <p>Segera revisi quest kamu sesuai catatan dan submit ulang untuk direview.</p>
    <p style="color: #dc3545;"><strong>Note:</strong> Penalty -10 poin berlaku untuk setiap revisi.</p>
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #666;">Email ini dikirim otomatis oleh sistem Magang Quest.</p>
</body>
</html>
