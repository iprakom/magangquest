# Magang Quest - Gamified Internship Logbook

Sistem logbook magang bergamifikasi untuk BPS Pusdiklat. Sistem ini mengubah pengalaman magang menjadi petualangan quest, di mana peserta magang menyelesaikan tugas, mendapatkan XP, naik level, dan bersaing di leaderboard.

## Tech Stack

- **Backend:** Laravel 13.8 (PHP 8.3+)
- **Frontend:** Vue 3 + Inertia.js
- **Styling:** Tailwind CSS v4
- **Database:** MySQL 8.0+
- **Authentication:** Google OAuth (Laravel Socialite)
- **Server:** Nginx

## Fitur

Sistem ini mencakup 14 user stories:

### Onboarding
1. **Pendaftaran & Login Google SSO** - Peserta magang login menggunakan akun Google BPS
2. **Input Data Pribadi** - Pengisian NIP, unit kerja, tanggal mulai/selesai magang
3. **Upload Dokumen** - Upload dokumen pendukung (surat pengantar, dll)
4. **Validasi Admin** - Admin memvalidasi data peserta baru

### Quest System
5. **Quest Assignment** - Peserta mendapatkan quest dari admin/mentor
6. **WIP (Work In Progress) Slots** - Slot quest yang sedang dikerjakan (batas maksimal)
7. **Quest Progress Submission** - Pengisian progress logbook harian
8. **Mentor Review** - Mentor review dan validasi progress quest
9. **Bounty System** - Quest tambahan yang bisa diklaim peserta

### Gamification
10. **Point & XP System** - Mendapatkan XP dari setiap quest yang diselesaikan
11. **Level System** - Naik level berdasarkan total XP
12. **Leaderboard** - Ranking peserta magang berdasarkan XP
13. **Nyawa (Lives) System** - Sistem nyawa untuk quest yang terlambat

### Endgame
14. **Endgame Phase Check** - Automatis mengaktifkan fase endgame saat masa magang hampir berakhir

## Requirements

- PHP 8.3+
- Node.js 18+
- MySQL 8.0+
- Composer 2.x
- Nginx (untuk production)

## Local Development Setup

### 1. Clone Repository

```bash
git clone <repository-url>
cd magangquest-api
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Setup Environment

```bash
cp .env.example .env
```

Edit file `.env` dan setup database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=magangquest
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Generate Key & Run Migration

```bash
php artisan key:generate
php artisan migrate
```

### 5. Run Development Server

```bash
php artisan serve
```

Buka terminal lain dan jalankan Vite dev server:

```bash
npm run dev
```

Aplikasi akan tersedia di `http://localhost:8000`

## Deployment to VPS

Server: Ubuntu, IP: 43.156.90.61

### 1. Build Frontend Assets

```bash
npm run build
```

### 2. Sync Built Assets to VPS

```bash
rsync -avz --delete public/build/ magangquest-vps:/var/www/magangquest/magangquest-api/public/build/
```

### 3. Sync Application Files

```bash
rsync -avz app/ routes/ magangquest-vps:/var/www/magangquest/magangquest-api/
```

### 4. Clear Cache on VPS

```bash
ssh magangquest-vps "cd /var/www/magangquest/magangquest-api && php artisan view:clear && php artisan config:clear && php artisan route:clear"
```

### 5. Fix Storage Permissions

```bash
ssh magangquest-vps "sudo chown -R www-data:www-data storage/ bootstrap/cache/"
```

### 6. Restart PHP-FPM

```bash
ssh magangquest-vps "sudo systemctl restart php8.3-fpm"
```

## Cron Jobs (Production)

Tambahkan cron job berikut di server VPS:

```bash
# Run Laravel scheduler every minute
* * * * * cd /var/www/magangquest/magangquest-api && php artisan schedule:run >> /dev/null 2>&1

# Check endgame phases daily at midnight
0 0 * * * php /var/www/magangquest/magangquest-api/artisan endgame:check

# Auto-approve overdue quests daily at midnight
0 0 * * * php /var/www/magangquest/magangquest-api/artisan quests:auto-approve
```

## SSL (Let's Encrypt)

```bash
sudo certbot --nginx -d magangquest.domain.com
```

## Useful Commands

### Set Admin User

```bash
php artisan admin:set user@example.com
```

### Check Endgame Phases

```bash
php artisan endgame:check
```

### Auto-Approve Overdue Quests

```bash
php artisan quests:auto-approve
```

## API Endpoints

### Onboarding (Auth + Web Session)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/onboarding/status` | Check onboarding status |
| POST | `/api/onboarding/submit` | Submit personal info |
| POST | `/api/onboarding/upload` | Upload documents |
| POST | `/api/onboarding/validate` | Submit for validation |

### Admin Onboarding

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/onboarding/pending` | Get pending users |
| GET | `/api/admin/onboarding/all` | Get all users |
| POST | `/api/admin/onboarding/{userId}/approve` | Approve user |
| POST | `/api/admin/onboarding/{userId}/reject` | Reject user |

### Quests

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/quests` | List all quests |
| GET | `/api/quests/bounty` | List bounty quests |
| GET | `/api/quests/{id}` | Get quest detail |
| POST | `/api/quests/{id}/claim` | Claim bounty quest |

### Quest Assignments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/quest-assignments/my` | Get my assignments |
| GET | `/api/quest-assignments/wip-slots` | Get WIP slots |
| GET | `/api/quest-assignments/{id}/progress` | Get progress |
| POST | `/api/quest-assignments/{id}/progress` | Store progress |
| POST | `/api/quest-assignments/{id}/submit-review` | Submit for review |

### Leaderboard

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/leaderboard` | Get leaderboard |
| GET | `/api/leaderboard/export` | Export leaderboard CSV |

### Mentor

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/mentor/idle-dashboard` | Get idle dashboard |
| POST | `/api/mentor/assign` | Assign quest to intern |
| POST | `/api/mentor/quests` | Create quest |
| GET | `/api/mentor/interns` | Get intern list |
| GET | `/api/mentor/pending-validations` | Get pending validations |
| PUT | `/api/mentor/assignments/{id}/validate` | Validate assignment |
| PUT | `/api/mentor/assignments/{id}/override-sla` | Override SLA |

### Holidays

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/holidays` | List holidays |
| GET | `/api/holidays/range` | Get holidays in range |

### System Settings (Admin)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/settings` | Get all settings |
| GET | `/api/admin/settings/{key}` | Get setting by key |
| PUT | `/api/admin/settings/{key}` | Update setting |

## Environment Variables

Berikut variabel environment yang diperlukan di file `.env`:

```env
APP_NAME=MagangQuest
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=magangquest
DB_USERNAME=root
DB_PASSWORD=your_password

SESSION_DRIVER=database
SESSION_LIFETIME=120

CACHE_STORE=database
QUEUE_CONNECTION=database

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

# Google OAuth (Socialite)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=/auth/google/callback
```

## Struktur Direktori

```
magangquest-api/
├── app/
│   ├── Console/Commands/     # Artisan commands
│   ├── Http/Controllers/     # API Controllers
│   └── Models/               # Eloquent Models
├── public/build/              # Compiled frontend assets
├── resources/js/              # Vue 3 components
├── routes/                     # Route definitions
├── storage/                    # Storage (logs, uploads)
├── bootstrap/cache/            # Laravel cache
└── .env                        # Environment file
```

## License

MIT License
