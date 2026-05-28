# Changelog - Magang Quest

All notable changes to this project will be documented in this file.

## [Unreleased] - 2026-05-28

### Added

#### Onboarding (US-01)
- Added `intern_type` field to onboarding form (options: SMA/SMK, Mahasiswa, Profesional)
- Updated `OnboardingController` to handle intern_type validation and storage
- Bonus points set to 50 upon admin approval
- Middleware `CheckOnboarding` gates all quest APIs when status != 'approve'

#### Admin Onboarding Validation (US-11)
- Created `AdminOnboardingController` for admin onboarding management
- Created `AdminOnboarding.vue` page with:
  - Tab navigation (Pending / Semua Intern)
  - User list with search functionality
  - Approve/Reject buttons with confirmation
  - Reject modal with reason input
  - Document preview links
- API endpoints: `GET /api/admin/onboarding`, `POST /api/admin/onboarding/{id}/approve`, `POST /api/admin/onboarding/{id}/reject`
- Route: `GET /admin/onboarding`

#### Dashboard Nyawa Countdown (US-02)
- Created `NyawaDisplay.vue` component with:
  - Working days countdown (excludes weekends and holidays)
  - Color-coded badges: 🟢 Green (H-10 to H-6), 🟡 Yellow (H-5 to H-3), 🔴 Red (H-2 to H-0), ⚫ Graduated
  - Pulse animation for critical zone
- Dashboard route now passes `endDate` and `holidays` as props
- User model enhanced with `getWorkingDaysRemaining()` method

#### Claim Bounty with WIP Slot (US-03)
- `QuestController`: `bountyList()` and `claimBounty()` methods
- API: `GET /api/quests/bounty`, `POST /api/quests/{id}/claim`
- Slot validation: High=4, Mid=2, Low=1 slots
- Max slots = Global_Limit × 4 (default: 4 × 4 = 16)
- QuestLogbook shows slot usage display "Slot: X/16"
- Rejects claim if capacity exceeded

#### Daily Progress with Evidence (US-04)
- Progress form in QuestLogbook: notes textarea + file upload
- Each progress entry: +10 points via PointTransaction
- Submit for review button changes status to 'in_review'
- API endpoints already existed, updated frontend integration
- Evidence file upload with size limit 10MB

#### Endgame Critical Zone H-10 (US-05)
- User model: `isInCriticalZone()` using working days calculation
- QuestAssignmentController: blocks new claims during Critical Zone
- MentorController: blocks new assigns during Critical Zone
- NyawaDisplay: 🚨 FASE KRUSIAL badge at H-10
- Dashboard: Critical Zone warning banner with remaining task count
- Graduation Bonus: +200 XP for 0 active quests at H-0
- Console command `CheckEndgamePhase`: enhanced to use working days

#### Endgame Grace Period H+1-H+7 (US-06, US-10)
- Grace period auto-entry at H-0 with pending tasks
- Daily penalty: -10 points per day during grace period
- Cannot claim new tasks during grace (blocked by Critical Zone logic)
- Force close at H+8: all active quests → 'failed' status
- Dashboard: ⚠️ MASA TENGGANG banner with countdown to Force Close
- NyawaDisplay: Grace period badge (amber/orange)
- `PointTransaction`: added `REF_GRACE_PENALTY` constant
- `CheckEndgamePhase`: enhanced with `processGracePenalty()` and `forceCloseGracePeriod()`

#### Mentor Idle Dashboard (US-07)
- Created `MentorDashboard.vue` page with:
  - Summary cards (Total, Idle, Active, Overloaded)
  - Filter by Room and Status
  - Intern table grouped by room
  - Slot utilization progress bars
  - Color coding: 🔴 Red (<=50%), 🟡 Yellow (51-99%), ⚫ Gray (100%)
  - Auto-refresh every 5 minutes
  - Endgame status column (Normal/Critical/Grace/Graduated)
- API: `GET /api/mentor/idle-dashboard`
- Route: `GET /mentor/dashboard`

#### Mentor Create Quest (US-08)
- Created `MentorCreateQuest.vue` page with:
  - Form: Title, Description, Type (High/Mid/Low), Difficulty, Due Date
  - Radio: Bounty / Assigned / Usulan
  - For Assigned: intern dropdown with slot availability display
  - Slot impact warning before confirmation
- `MentorController`: `storeQuest()` and `getInterns()` methods
- API: `POST /api/mentor/quests`, `GET /api/mentor/interns`
- Route: `GET /mentor/quests/create`
- Auto-deducts from intern's slot on assignment

#### Mentor Approve/Revise with SLA (US-09)
- Created `MentorReview.vue` page with:
  - List of 'in_review' assignments with SLA countdown
  - Badge colors: Green (<24hr), Yellow (24-72hr), Red (overdue)
  - Evidence/progress file links
  - Approve (+100 points) and Revise (-10 points) buttons
  - Revise modal with notes input
- `QuestAssignmentController`: `approve()` (+100 pts) and `revise()` (-10 pts)
- SLA calculation: 3 working days from submission
- Console command `AutoApproveQuests`: auto-approves overdue SLA submissions
- Route: `GET /mentor/review`

#### Admin Holiday Calendar (US-12)
- Created `AdminHolidayCalendar.vue` page with:
  - Holiday list with year/type filtering
  - Add/Edit holiday modal (date picker + name + type)
  - Delete with confirmation
  - Info box about holiday usage in SLA/Endgame/Hoarding calculations
- `HolidayController`: full CRUD methods
- API: `GET/POST/PUT/DELETE /api/admin/holidays`
- Route: `GET /admin/holidays`

#### Admin Global Limit (US-13)
- Created `AdminSettings.vue` page with:
  - Current Global Limit value display
  - Input to change value (1-10 range)
  - Impact preview: "Global Limit X → Max Slots = X × 4"
  - Audit trail of setting changes
- API: `GET /api/admin/settings`, `PUT /api/admin/settings/{key}`
- Route: `GET /admin/settings`

#### Admin Leaderboard and Export (US-14)
- Enhanced `Leaderboard.vue` with:
  - Status filter tabs: All / Aktif / Lulus / Frozen
  - Search by name
  - Statistics section (Total Intern, Avg Points, Completion Rate)
  - Export CSV button
- Created `AdminLeaderboard.vue` with:
  - Statistics dashboard cards
  - Top Performer card with gradient
  - Full table: Rank, Name, Email, Total Poin, Streak, Completed, Status
  - Batch/period filter
  - Pagination
  - CSV export download
- `LeaderboardController`: `adminIndex()`, `adminExportCsv()`, `adminStats()`
- API: `GET /api/admin/leaderboard`, `GET /api/admin/leaderboard/export`, `GET /api/admin/stats`
- Route: `GET /admin/leaderboard`

### Fixed
- Fixed `route()` not defined error - replaced all `route()` helper calls with direct URLs in Vue components
- Fixed Tailwind CSS not loading - added `@tailwindcss/vite` plugin to vite.config.js
- Fixed Vue blank page - corrected resolve path in app.js from `./${name}.vue` to `./pages/${name}.vue`
- Fixed 500 error in HandleInertiaRequests - reverted to original middleware (Ziggy not working due to OPcache)

### Changed
- `OnboardingController`: bonus points changed from 100 to 50
- `QuestController`: `bountyList()` now only shows open status quests
- `QuestAssignmentController`: claim validation now includes Critical Zone check
- `ProgressController`: submitForReview now sets SLA deadline (3 working days)
- `AutoApproveQuests`: approved points changed from slot_weight to 100
- `CheckEndgamePhase`: graduation bonus changed from 100 to 200, uses working days for all calculations
- `NyawaDisplay.vue`: added Critical Zone and Grace Period badges
- `Dashboard.vue`: added NyawaDisplay, Critical Zone banner, Grace Period banner
- `MentorDashboard.vue`: added endgame status column

### Dependencies Added
- `@tailwindcss/vite`: ^4.0.0 (Tailwind CSS v4 integration)
- `ziggy-js`: ^2.6.2 (route helper for Vue) - installed but not actively used (hardcoded URLs instead)

### New Artisan Commands
- `admin:set {email}` - Set a user as admin by their email
- `quests:sla-reminder` - Send SLA deadline reminder emails (scheduled hourly)

### New Mailable Classes
- `OnboardingApproved` - Email saat pendaftaran disetujui
- `OnboardingRejected` - Email saat pendaftaran ditolak
- `QuestAssigned` - Email saat quest baru di-assign
- `QuestSubmitted` - Email ke mentor saat intern submit quest
- `QuestApproved` - Email saat quest disetujui (+XP)
- `QuestNeedsRevision` - Email saat quest perlu revisi
- `GracePeriodStarted` - Email saat memasuki masa tenggang
- `SlaDeadlineReminder` - Email reminder SLA deadline

### New Vue Pages & Components
- `resources/js/layouts/AppLayout.vue` - Shared layout dengan role-based navigation
- `resources/js/pages/QuestDetail.vue` - Halaman detail quest

### Database Tables
- `notifications` - Laravel notifications table (migrations created)

### Database Fields
- `users.grace_started_at`: date, nullable (grace period tracking)
- `users.is_grace_period`: boolean, default false

### Role-based Navigation
- AppLayout.vue dengan conditional menu berdasarkan user role
- Player: Dashboard, Quest Logbook, Leaderboard, Profile
- Mentor: + Mentor Dashboard, Create Quest, Review
- Admin: + Semua menu admin (Onboarding, Holidays, Settings, Leaderboard)

### Profile Page Enhancement
- Intern Type badge (SMA/SMK, Mahasiswa, Profesional)
- Active Slots stat
- Slot Usage progress bar

### Onboarding Upload Fix
- Storage link created on VPS (documents can now be uploaded)

---

## Previous (Production Fix) - 2026-05-28

### Fixed
- Vue blank page: resolve path mismatch in app.js
- Tailwind CSS not loading: added @tailwindcss/vite plugin
- Google OAuth route: hardcoded `/auth/google` URL in Login.vue
- HandleInertiaRequests 500 error: reverted to original middleware

### Security
- SSL Let's Encrypt configured for kotakpasir.my.id
- CSRF token properly embedded in pages
