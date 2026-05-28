<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Masa Tenggang Dimulai</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #dc3545;">Kamu Memasuki Masa Tenggang ⚠️</h2>
    <p>Hai <strong>{{ $userName }}</strong>,</p>
    <p>Karena magang kamu telah berakhir dengan quest yang masih aktif, kamu memasuki <strong>Masa Tenggang (Grace Period)</strong>.</p>
    <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center;">
        <p style="margin: 0;"><strong>Penalty: -{{ $penaltyPerDay }} poin/hari</strong></p>
        <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">Penalty mulai besok</p>
    </div>
    <p>Selesaikan quest kamu secepat mungkin untuk menghindari pengurangan poin lebih lanjut.</p>
    <p>Masa Tenggang berlangsung selama 7 hari kerja setelah tanggal selesai magang.</p>
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #666;">Email ini dikirim otomatis oleh sistem Magang Quest.</p>
</body>
</html>
