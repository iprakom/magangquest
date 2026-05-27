# MVP Sprint 1 Implementation Plan

## Project Overview
**Project:** Magang Quest - Gamified Internship Management System  
**Tech Stack:** Laravel 11 + Vue 3 (Composition API) + MySQL + Google SSO (Laravel Socialite)  
**Sprint Duration:** 2 weeks (10 working days)  
**Objective:** Ship core gamification loop with task lifecycle, WIP slot management, and point ledger

---

## 1. MVP Scope Definition

### 1.1 Core Gamification Loop (Must Have)
| Feature | Description | Priority |
|---------|-------------|----------|
| Google SSO Authentication | Login via Laravel Socialite with Google | P0 |
| User Roles (3) | Player, Mentor, Super Admin | P0 |
| Quest CRUD | Mentors create quests; Players view/browse | P0 |
| Quest Lifecycle | Open → Assigned → Active → In Review → Approved/Revise/Cancelled | P0 |
| WIP Slot System | Global limit × 4 = capacity; weighted by difficulty | P0 |
| Point Ledger | Track points per user with transaction history | P0 |
| Mentor Dashboard | Idle overview of all players' progress | P0 |
| Player Dashboard | Quest browser + my active quests + points | P0 |

### 1.2 Automation (Should Have)
| Feature | Description | Priority |
|---------|-------------|----------|
| SLA 3×24hr Auto-Approve | Auto-approve tasks stuck in review > 72hrs | P1 |
| Holiday Exclusion | Skip SLA countdown on holidays | P1 |
| Quest Expiry | Auto-expire quests past deadline | P1 |

### 1.3 Out of Scope (Not This Sprint)
- Graduation flow (H-10 Critical Zone → H+8 Force Close)
- Streak bonus calculations
- Super Admin panel (full)
- Player leaderboard/rankings
- Quest revision workflow (Revise state handling)
- Notification system

### 1.4 User Stories

```
AS A Player (Anak Magang)
I WANT TO log in with Google SSO
SO THAT I can access the platform quickly

AS A Player
I WANT TO browse available quests
SO THAT I can pick ones that interest me

AS A Player
I WANT TO claim a quest (if WIP slots available)
SO THAT I can start working on it

AS A Player
I WANT TO submit my quest for review
SO THAT my mentor can evaluate it

AS A Player
I WANT TO see my points balance
SO THAT I can track my progress

AS A Mentor
I WANT TO create quests with difficulty levels
SO THAT I can assign work to players

AS A Mentor
I WANT TO review submitted quests
SO THAT I can approve or reject them

AS A Mentor
I WANT TO see an idle dashboard
SO THAT I can monitor all players' status

AS A Super Admin
I WANT TO manage global WIP limits
SO THAT I can control system capacity
```

---

## 2. Database Schema

### 2.1 Entity-Relationship Diagram (Text)

```
users (id, name, email, google_id, role, points_balance, created_at, updated_at)
  │
  ├──── quests (id, mentor_id FK→users, title, description, difficulty, weight, status, deadline, created_at, updated_at)
  │       │
  │       └──── quest_assignments (id, quest_id FK→quests, player_id FK→users, status, submitted_at, reviewed_at, review_notes, points_awarded, created_at, updated_at)
  │
  ├──── point_transactions (id, user_id FK→users, amount, type, reference_type, reference_id, description, created_at)
  │
  ├──── holidays (id, date, name, created_at)
  │
  └──── system_settings (id, key, value, created_at, updated_at)
```

### 2.2 Table Definitions

#### users
| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| name | VARCHAR(255) | NOT NULL | |
| email | VARCHAR(255) | UNIQUE, NOT NULL | |
| google_id | VARCHAR(255) | UNIQUE, NULL | For SSO |
| avatar | VARCHAR(500) | NULL | |
| role | ENUM('player','mentor','super_admin') | DEFAULT 'player' | |
| points_balance | INT | DEFAULT 0 | Running balance |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

#### quests
| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| mentor_id | BIGINT UNSIGNED | FK→users.id, NOT NULL | Creator |
| title | VARCHAR(255) | NOT NULL | |
| description | TEXT | | |
| difficulty | ENUM('high','mid','low') | NOT NULL | |
| weight | TINYINT UNSIGNED | NOT NULL | High=4, Mid=2, Low=1 |
| status | ENUM('open','bounty','assigned','active','in_review','approved','revise','cancelled','expired') | DEFAULT 'open' | |
| deadline | DATE | NULL | |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

#### quest_assignments
| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| quest_id | BIGINT UNSIGNED | FK→quests.id, NOT NULL | |
| player_id | BIGINT UNSIGNED | FK→users.id, NOT NULL | |
| status | ENUM('assigned','active','in_review','approved','revision','cancelled','failed','expired') | DEFAULT 'assigned' | |
| assigned_at | TIMESTAMP | | |
| started_at | TIMESTAMP | NULL | When moved to active |
| submitted_at | TIMESTAMP | NULL | When player submits |
| reviewed_at | TIMESTAMP | NULL | When mentor reviews |
| review_notes | TEXT | NULL | |
| points_awarded | INT | NULL | Points granted on approve |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Unique Constraint:** (quest_id, player_id) - one assignment per player per quest

#### point_transactions
| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | BIGINT UNSIGNED | FK→users.id, NOT NULL | |
| amount | INT | NOT NULL | +100, -50, +200 etc |
| type | ENUM('approve','hoarding','graduation','streak','adjustment') | NOT NULL | |
| reference_type | VARCHAR(50) | NULL | 'quest_assignment', 'quest', etc |
| reference_id | BIGINT UNSIGNED | NULL | FK to reference_type |
| description | VARCHAR(500) | | Human-readable |
| created_at | TIMESTAMP | | |

#### holidays
| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| date | DATE | UNIQUE, NOT NULL | |
| name | VARCHAR(255) | NOT NULL | Holiday name |
| created_at | TIMESTAMP | | |

#### system_settings
| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| key | VARCHAR(100) | UNIQUE, NOT NULL | e.g., 'global_wip_limit' |
| value | TEXT | NOT NULL | JSON-stored values |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Default Settings:**
- `global_wip_limit`: 10 (global slot limit)
- `max_capacity_slots`: 40 (10 × 4)
- `sla_hours`: 72 (3 × 24)
- `auto_approve_enabled`: true

### 2.3 Indexes
```sql
-- users
INDEX idx_users_role (role)
INDEX idx_users_google_id (google_id)

-- quests
INDEX idx_quests_mentor_id (mentor_id)
INDEX idx_quests_status (status)
INDEX idx_quests_deadline (deadline)

-- quest_assignments
INDEX idx_assignments_quest_id (quest_id)
INDEX idx_assignments_player_id (player_id)
INDEX idx_assignments_status (status)
UNIQUE INDEX idx_assignments_quest_player (quest_id, player_id)

-- point_transactions
INDEX idx_pt_user_id (user_id)
INDEX idx_pt_type (type)
INDEX idx_pt_created_at (created_at)

-- holidays
INDEX idx_holidays_date (date)
```

---

## 3. API Endpoints

### 3.1 Authentication
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /api/auth/google | Redirect to Google OAuth | Guest |
| GET | /api/auth/google/callback | Handle OAuth callback | Guest |
| POST | /api/auth/logout | Logout user | Any |
| GET | /api/auth/me | Get current user | Any |

### 3.2 Quests
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /api/quests | List quests (filterable) | Any |
| GET | /api/quests/{id} | Get quest details | Any |
| POST | /api/quests | Create quest | Mentor |
| PUT | /api/quests/{id} | Update quest | Mentor (owner) |
| DELETE | /api/quests/{id} | Delete quest | Mentor (owner) |

**Query Filters:** `?status=open&difficulty=high&mentor_id=1`

### 3.3 Quest Assignments (Player Actions)
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /api/my/assignments | My assigned quests | Player |
| POST | /api/quests/{id}/claim | Claim/bounty a quest | Player |
| PUT | /api/assignments/{id}/start | Start working (Assigned → Active) | Player |
| PUT | /api/assignments/{id}/submit | Submit for review (Active → In Review) | Player |
| PUT | /api/assignments/{id}/cancel | Cancel assignment | Player |

### 3.4 Quest Assignments (Mentor Actions)
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /api/mentor/assignments | All assignments to review | Mentor |
| PUT | /api/assignments/{id}/approve | Approve quest (+100 pts) | Mentor |
| PUT | /api/assignments/{id}/reject | Reject quest | Mentor |
| PUT | /api/assignments/{id}/request-revision | Request revision | Mentor |

### 3.5 Points
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /api/points/balance | My current balance | Any |
| GET | /api/points/transactions | My point history | Any |
| GET | /api/mentor/points | All players' points (leaderboard) | Mentor |

### 3.6 Dashboard
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /api/dashboard/player | Player stats + my quests | Player |
| GET | /api/dashboard/mentor | Idle overview of all players | Mentor |

### 3.7 Admin (Super Admin)
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /api/admin/settings | Get system settings | Super Admin |
| PUT | /api/admin/settings | Update system settings | Super Admin |
| GET | /api/admin/holidays | List holidays | Super Admin |
| POST | /api/admin/holidays | Add holiday | Super Admin |
| DELETE | /api/admin/holidays/{id} | Remove holiday | Super Admin |

### 3.8 Response Formats

**Success:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Quest created successfully"
}
```

**Error:**
```json
{
  "success": false,
  "message": "WIP limit exceeded",
  "errors": { "wip_slots": ["No slots available"] }
}
```

**Paginated:**
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

---

## 4. Task Decomposition

### Day 1: Project Setup & Auth
| Task | Hours | Description |
|------|-------|-------------|
| T1.1 | 2h | Create Laravel 11 project with Vue 3 scaffolding |
| T1.2 | 2h | Configure MySQL database connection |
| T1.3 | 3h | Install/configure Laravel Socialite with Google OAuth |
| T1.4 | 2h | Create auth controller, routes, middleware |
| T1.5 | 1h | Test Google SSO flow end-to-end |

**Deliverable:** Users can log in via Google SSO

### Day 2: Database & Models
| Task | Hours | Description |
|------|-------|-------------|
| T2.1 | 2h | Create migrations for all 6 tables |
| T2.2 | 2h | Define Eloquent models with relationships |
| T2.3 | 2h | Add factories and seeders for test data |
| T2.4 | 2h | Create base API resource classes |
| T2.5 | 2h | Write unit tests for models |

**Deliverable:** Database schema with working models

### Day 3: Quest CRUD (Mentor)
| Task | Hours | Description |
|------|-------|-------------|
| T3.1 | 2h | QuestController: index, show |
| T3.2 | 2h | QuestController: store (with difficulty→weight mapping) |
| T3.3 | 2h | QuestController: update, delete |
| T3.4 | 2h | Vue component: QuestList + QuestCard |
| T3.5 | 2h | Vue component: CreateQuestForm |

**Deliverable:** Mentors can create and manage quests

### Day 4: Quest Browsing & Claiming
| Task | Hours | Description |
|------|-------|-------------|
| T4.1 | 2h | Quest listing API with filters |
| T4.2 | 2h | WIP slot calculation logic |
| T4.3 | 3h | Claim endpoint with slot validation |
| T4.4 | 3h | Vue: QuestBrowser page |

**Deliverable:** Players can browse and claim quests

### Day 5: Quest Lifecycle (Player)
| Task | Hours | Description |
|------|-------|-------------|
| T5.1 | 2h | Assignment state machine logic |
| T5.2 | 2h | Start quest endpoint (Assigned→Active) |
| T5.3 | 2h | Submit quest endpoint (Active→In Review) |
| T5.4 | 2h | Cancel quest endpoint |
| T5.5 | 2h | Vue: MyAssignments page + status badges |

**Deliverable:** Players can manage quest lifecycle

### Day 6: Quest Review (Mentor)
| Task | Hours | Description |
|------|-------|-------------|
| T6.1 | 2h | Mentor review endpoints |
| T6.2 | 3h | Approve logic: +100 points to player |
| T6.3 | 2h | Reject logic |
| T6.4 | 3h | Vue: MentorReviewQueue page |

**Deliverable:** Mentors can review and approve quests

### Day 7: Point Ledger System
| Task | Hours | Description |
|------|-------|-------------|
| T7.1 | 2h | PointTransaction model + service |
| T7.2 | 3h | Auto-point posting on quest approve |
| T7.3 | 2h | Balance calculation (cached on user) |
| T7.4 | 3h | Vue: PointsBalance + TransactionHistory |

**Deliverable:** Points awarded on quest approval

### Day 8: Dashboards
| Task | Hours | Description |
|------|-------|-------------|
| T8.1 | 3h | Player dashboard API |
| T8.2 | 3h | Mentor idle dashboard API |
| T8.3 | 2h | Vue: PlayerDashboard |
| T8.4 | 2h | Vue: MentorDashboard |

**Deliverable:** Role-specific dashboards

### Day 9: Automation & Cron
| Task | Hours | Description |
|------|-------|-------------|
| T9.1 | 2h | Holiday model + seeder |
| T9.2 | 3h | SLA 3×24hr auto-approve command |
| T9.3 | 2h | Holiday exclusion in SLA calculation |
| T9.4 | 2h | Quest expiry command |
| T9.5 | 1h | Schedule cron jobs in Laravel |

**Deliverable:** Automated background tasks

### Day 10: Polish & Testing
| Task | Hours | Description |
|------|-------|-------------|
| T10.1 | 3h | Integration tests for critical flows |
| T10.2 | 2h | Fix any bugs from testing |
| T10.3 | 2h | Role permission middleware |
| T10.4 | 2h | API error handling + validation |
| T10.5 | 1h | README + documentation |

**Deliverable:** Shippable MVP

---

## 5. Acceptance Criteria

### 5.1 Authentication
- [ ] User can log in via Google OAuth
- [ ] User is redirected to correct dashboard based on role
- [ ] Unauthenticated requests to protected routes return 401
- [ ] Logout invalidates session

### 5.2 Quest Management
- [ ] Mentor can create quest with title, description, difficulty, deadline
- [ ] Quest weight auto-assigned: High=4, Mid=2, Low=1
- [ ] Players see only open/bounty quests in browser
- [ ] Quest list supports filtering by status and difficulty
- [ ] Mentor can update/delete their own quests only

### 5.3 WIP Slot System
- [ ] Global WIP limit configurable (default: 10)
- [ ] Max capacity = global_limit × 4 = 40 slots
- [ ] Claim fails if player has 4 active assignments (weight sum ≥ 4)
- [ ] Claim fails if global WIP is at capacity
- [ ] Weight calculation: sum of active assignment weights < 4

### 5.4 Quest Lifecycle
- [ ] Player can claim open quest → status becomes "assigned"
- [ ] Player can start assigned quest → status becomes "active"
- [ ] Player can submit active quest → status becomes "in_review"
- [ ] Player can cancel assigned/active quest → returns to open
- [ ] Quest deadline expired → status becomes "expired"

### 5.5 Mentor Review
- [ ] Mentor sees queue of in_review assignments
- [ ] Mentor can approve → +100 points to player, status "approved"
- [ ] Mentor can reject → status "failed"
- [ ] Approved quest triggers point transaction record

### 5.6 Point Ledger
- [ ] Point balance shown on user record (denormalized)
- [ ] Each point change creates transaction record
- [ ] Transaction shows type, amount, reference, description
- [ ] Balance cannot go negative

### 5.7 Dashboards
- [ ] Player dashboard: my active quests, points balance, quick stats
- [ ] Mentor dashboard: player count, quest stats, idle overview

### 5.8 Automation
- [ ] Cron job runs every hour
- [ ] In-review quests > 72hrs (excluding holidays) auto-approved
- [ ] Expired quests (past deadline) marked as expired
- [ ] Manual trigger commands available: `php artisan quests:auto-approve` and `php artisan quests:expire`

---

## 6. Technical Notes

### 6.1 WIP Slot Validation Logic

```php
// In QuestAssignmentController::claim()

$player = auth()->user();
$quest = Quest::findOrFail($questId);

// 1. Check quest is available
if ($quest->status !== 'open' && $quest->status !== 'bounty') {
    return response()->json(['message' => 'Quest not available'], 400);
}

// 2. Get player's active assignments
$activeAssignments = $player->assignments()
    ->whereIn('status', ['assigned', 'active'])
    ->with('quest')
    ->get();

// 3. Calculate current weight used
$currentWeight = $activeAssignments->sum('quest.weight');

// 4. Check individual player limit (4)
if ($currentWeight + $quest->weight > 4) {
    return response()->json(['message' => 'WIP limit exceeded (max 4)'], 400);
}

// 5. Check global WIP limit
$globalWipUsed = QuestAssignment::whereIn('status', ['assigned', 'active'])->count();
$maxCapacity = (int) setting('max_capacity_slots', 40);

if ($globalWipUsed >= $maxCapacity) {
    return response()->json(['message' => 'System at capacity'], 400);
}

// 6. Create assignment
$assignment = $player->assignments()->create([
    'quest_id' => $questId,
    'status' => 'assigned',
    'assigned_at' => now(),
]);

$quest->update(['status' => 'assigned']);

return $assignment;
```

### 6.2 Quest Weight Mapping

```php
// In Quest Model
protected static function booted()
{
    static::saving(function ($quest) {
        $quest->weight = match($quest->difficulty) {
            'high' => 4,
            'mid' => 2,
            'low' => 1,
        };
    });
}
```

### 6.3 Point Ledger Pattern

```php
// In PointService
class PointService
{
    public function awardPoints(User $user, int $amount, string $type, $reference = null, string $description = '')
    {
        // Create transaction record
        $transaction = PointTransaction::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'type' => $type,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'description' => $description,
        ]);

        // Update denormalized balance
        $user->increment('points_balance', $amount);

        return $transaction;
    }

    public function deductPoints(User $user, int $amount, string $type, $reference = null, string $description = '')
    {
        return $this->awardPoints($user, -$amount, $type, $reference, $description);
    }
}

// Usage on quest approval
$pointService->awardPoints(
    $assignment->player,
    100,
    'approve',
    $assignment,
    "Quest approved: {$assignment->quest->title}"
);
```

### 6.4 SLA Calculation with Holiday Exclusion

```php
// In QuestAssignment model
public function getSLABusinessHours(): int
{
    $start = $this->submitted_at;
    $end = now();
    $slaHours = (int) setting('sla_hours', 72);

    $businessHours = 0;
    $current = $start->copy();

    while ($businessHours < $slaHours && $current < $end) {
        // Skip weekends (Sat=6, Sun=0)
        if ($current->isWeekend()) {
            $current->addDay();
            continue;
        }

        // Skip holidays
        if (Holiday::where('date', $current->toDateString())->exists()) {
            $current->addDay();
            continue;
        }

        // Count 8 business hours per day (9am-6pm)
        $businessHours += 8;
        $current->addDay();
    }

    return $businessHours;
}
```

### 6.5 Cron Job Schedule

```php
// In routes/console.php or AppServiceProvider

// Option A: Laravel Scheduler (app/Console/Kernel.php)
protected function schedule(Schedule $schedule)
{
    // Run auto-approve every hour
    $schedule->command('quests:auto-approve')
        ->hourly()
        ->withoutOverlapping();

    // Run expiry check every hour
    $schedule->command('quests:expire')
        ->hourly()
        ->withoutOverlapping();
}

// Option B: Direct cron entry
// * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### 6.6 Artisan Commands

```php
// app/Console/Commands/QuestAutoApprove.php
class QuestAutoApprove extends Command
{
    protected $signature = 'quests:auto-approve';
    protected $description = 'Auto-approve quests in review beyond SLA';

    public function handle()
    {
        $assignments = QuestAssignment::where('status', 'in_review')
            ->whereNotNull('submitted_at')
            ->get();

        foreach ($assignments as $assignment) {
            if ($assignment->getSLABusinessHours() > 72) {
                $assignment->update(['status' => 'approved']);
                // Award points
                app(PointService::class)->awardPoints(
                    $assignment->player,
                    100,
                    'approve',
                    $assignment,
                    "Auto-approved after SLA breach"
                );
            }
        }

        $this->info("Processed {$assignments->count()} assignments");
    }
}

// app/Console/Commands/QuestExpire.php
class QuestExpire extends Command
{
    protected $signature = 'quests:expire';
    protected $description = 'Mark expired quests';

    public function handle()
    {
        $expired = Quest::whereIn('status', ['open', 'bounty', 'assigned', 'active'])
            ->where('deadline', '<', now()->toDateString())
            ->update(['status' => 'expired']);

        $assignments = QuestAssignment::whereIn('status', ['assigned', 'active'])
            ->whereHas('quest', fn($q) => $q->where('deadline', '<', now()))
            ->update(['status' => 'expired']);

        $this->info("Expired {$expired} quests and {$assignments} assignments");
    }
}
```

### 6.7 Vue Directory Structure

```
resources/js/
├── components/
│   ├── common/
│   │   ├── AppButton.vue
│   │   ├── AppCard.vue
│   │   ├── AppModal.vue
│   │   └── StatusBadge.vue
│   ├── quests/
│   │   ├── QuestCard.vue
│   │   ├── QuestForm.vue
│   │   └── QuestFilters.vue
│   └── dashboard/
│       ├── PlayerStats.vue
│       └── MentorOverview.vue
├── views/
│   ├── auth/
│   │   └── Login.vue
│   ├── quests/
│   │   ├── QuestBrowser.vue
│   │   └── MyAssignments.vue
│   ├── mentor/
│   │   └── ReviewQueue.vue
│   └── dashboard/
│       ├── PlayerDashboard.vue
│       └── MentorDashboard.vue
├── stores/
│   ├── auth.js
│   ├── quests.js
│   └── points.js
├── router/
│   └── index.js
└── App.vue
```

### 6.8 Key Dependencies

```json
// composer.json
{
    "require": {
        "laravel/framework": "^11.0",
        "laravel/socialite": "^5.0",
        "laravel/sanctum": "^4.0",
        "socialiteproviders/google": "^4.1"
    }
}

// package.json
{
    "dependencies": {
        "vue": "^3.4",
        "vue-router": "^4.3",
        "pinia": "^2.1",
        "@headlessui/vue": "^1.7",
        "axios": "^1.6"
    }
}
```

---

## Appendix: Quest State Machine

```
                    ┌──────────────────────────────────────┐
                    │                                      │
                    ▼                                      │
┌─────────┐    ┌──────────┐    ┌────────┐    ┌───────────┐ │
│  OPEN   │───▶│ ASSIGNED │───▶│ ACTIVE │───▶│ IN_REVIEW │ │
└─────────┘    └──────────┘    └────────┘    └───────────┘ │
    ▲              │                │              │       │
    │              │                │              ▼       │
    │              │                │         ┌──────────┐  │
    │              │                │         │ APPROVED │◀──┤
    │              │                │         └──────────┘    │
    │              │                │              │        │
    │              │                │              ▼        │
    │              │                │         ┌──────────┐   │
    │              │                │         │ REVISION │───┤
    │              │                │         └──────────┘   │
    │              │                │              │        │
    │              ▼                ▼              │        │
    │         ┌───────────┐   ┌───────────┐       │        │
    └─────────│ CANCELLED │   │  EXPIRED  │◀──────┘        │
              └───────────┘   └───────────┘                 │
                    │                                      │
                    │              ┌───────────┐           │
                    └─────────────▶│  FAILED   │───────────┘
                                   └───────────┘
```

---

*Document Version: 1.0*  
*Created: 2026-05-27*  
*Status: Ready for Sprint Planning*
