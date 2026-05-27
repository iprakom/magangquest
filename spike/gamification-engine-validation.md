# Gamification Engine Validation Document

**Project:** Magang Quest  
**Date:** 2026-05-27  
**Purpose:** Research and validate best approach for building the gamification system

---

## 1. Executive Summary

**Recommendation: Option B - Build Custom Gamification Engine**

The Magang Quest PRD requires a highly specialized gamification system with unique mechanics that existing packages cannot adequately support. A custom engine built on Laravel provides the necessary flexibility for:
- Double-entry point ledger system
- Weighted WIP slot management  
- Consecutive-day streak tracking with specific milestone bonuses
- Granular penalty system
- Endgame phase transitions

---

## 2. Existing Laravel Gamification Packages Analysis

### 2.1 Package Research Results

After searching Packagist and GitHub, the following packages were identified:

| Package | Status | Fit Score | Notes |
|---------|--------|----------|-------|
| `laravel-gamify` (0x00-dev) | Abandoned | 2/10 | Basic badge/achievement only, no points ledger |
| `laravel-rewards` | Inactive | 2/10 | Simple point system, no WIP slots |
| `laravel-achievements` | Inactive | 3/10 | Badge-focused, no streak/penalty support |
| `laravel-loyalty` | Inactive | 3/10 | E-commerce focused, no quest system |

### 2.2 Why Existing Packages Don't Fit

| PRD Requirement | Package Support | Gap |
|-----------------|-----------------|-----|
| Point ledger (debit/credit) | None | All packages use simple UPDATE queries |
| WIP slots (4x multiplier, weighted) | None | No slot allocation with quest weights |
| Streak system (7/14/21/30 day bonuses) | None | No consecutive tracking |
| Penalty system (-10 revise, -50 hoarding) | None | No granular penalty engine |
| Endgame phases | None | No lifecycle management |

### 2.3 Technical Debt Assessment

**Using existing packages would require:**
- Forking and heavily modifying packages
- Building custom extensions that negate package benefits
- Maintaining bespoke patches across updates
- Fighting against package opinions

**Conclusion:** ~60-70% of implementation would be custom anyway.

---

## 3. Custom Implementation Complexity Assessment

### 3.1 Component Breakdown

| Component | Complexity | Estimated LOC | Risk |
|-----------|-------------|---------------|------|
| Point Ledger (double-entry) | Medium | 300-400 | Low |
| WIP Slot Manager | Medium-High | 400-500 | Medium |
| Streak Tracker | Medium | 200-300 | Low |
| Penalty Engine | Medium | 150-250 | Low |
| Endgame State Machine | Medium | 200-300 | Medium |
| Quest Weight Calculator | Low | 100-150 | Low |
| Admin Configuration | Low | 100-200 | Low |

**Total Estimated:** ~1,450-2,100 lines of code

### 3.2 Database Schema Requirements

```sql
-- Core Tables Required
point_transactions (id, user_id, quest_id, type, amount, balance_after, created_at)
wip_slots (id, user_id, quest_id, weight, status, expires_at)
streaks (id, user_id, current_streak, longest_streak, last_activity_date)
penalties (id, user_id, reason, amount, created_at)
user_gamification_state (id, user_id, phase, total_points, slots_used)
quest_definitions (id, name, weight, point_value, max_duration)
```

### 3.3 Key Service Classes

```
app/Services/Gamification/
├── PointLedgerService      # Debit/credit transaction logging
├── WipSlotManagerService   # Slot allocation with weights
├── StreakService           # Consecutive day tracking
├── PenaltyService          # Penalty calculation & application
├── QuestCompletionService  # Quest state transitions
├── EndgameService          # Phase management
└── GamificationFacade      # Unified API
```

---

## 4. Vue 3 State Management: Pinia vs Vuex

### 4.1 Recommendation: Pinia

| Criteria | Pinia | Vuex |
|----------|-------|------|
| Vue 3 Integration | First-class | Legacy |
| Official Status | Recommended | Maintenance mode |
| TypeScript Support | Excellent | Partial |
| DevTools Integration | Native | Good |
| Boilerplate | Minimal | Extensive |
| Learning Curve | Low | Medium |
| Testing | Easy | Moderate |

### 4.2 Pinia Advantages for Magang Quest

```javascript
// Example: Gamification Store
export const useGamificationStore = defineStore('gamification', {
  state: () => ({
    points: 0,
    currentStreak: 0,
    activeQuests: [],
    slotsUsed: 0
  }),
  
  getters: {
    availableSlots: (state) => 12 - state.slotsUsed, // 3 * 4 = 12 max
    streakBonus: (state) => {
      if (state.currentStreak >= 30) return 1.5
      if (state.currentStreak >= 21) return 1.3
      if (state.currentStreak >= 14) return 1.2
      if (state.currentStreak >= 7) return 1.1
      return 1.0
    }
  },
  
  actions: {
    async fetchGamificationState() { /* ... */ },
    async acceptQuest(questId) { /* ... */ }
  }
})
```

### 4.3 Migration Path

Vuex 4 → Pinia is straightforward if starting fresh: **Use Pinia**

---

## 5. Laravel SPA Architecture: Inertia vs Pure API + Vue

### 5.1 Recommendation: Inertia.js

| Criteria | Inertia | Pure API + Vue |
|----------|---------|----------------|
| Server Setup | Simple | Complex |
| Code Reuse | High (Laravel models) | Low |
| SSR Support | Native | Manual |
| Authentication | Built-in | Custom |
| Complexity | Low-Medium | Medium-High |
| Real-time Updates | Easy (Laravel Echo) | Requires setup |
| Initial Load | Fast (server-rendered) | Slower (client fetch) |

### 5.2 Why Inertia Fits Magang Quest

**Gamification = Real-time, Interactive UI**
- Streak updates need immediate feedback
- WIP slot changes affect available actions
- Penalty notifications need toast system
- Progress bars and animations

**Inertia provides:**
- Server-side rendering for SEO (quest descriptions)
- Client-side reactivity for real-time updates
- Seamless Laravel model binding
- Built-in CSRF and authentication

### 5.3 Architecture Diagram

```
┌─────────────────────────────────────────────────────┐
│                    Browser (Vue 3)                  │
│  ┌─────────────┐  ┌──────────────┐  ┌───────────┐  │
│  │   Pinia     │  │  Inertia.js  │  │  Router   │  │
│  │   Stores    │  │   Client     │  │           │  │
│  └─────────────┘  └──────┬───────┘  └───────────┘  │
└──────────────────────────┼──────────────────────────┘
                           │ Visits/Links
┌──────────────────────────┼──────────────────────────┐
│                  Laravel Server                     │
│  ┌──────────────┐  ┌─────┴─────┐  ┌─────────────┐  │
│  │ Controllers  │  │ Inertia   │  │   Models    │  │
│  │              │  │ Response  │  │  (Eloquent)│  │
│  └──────────────┘  └───────────┘  └─────────────┘  │
│                                                      │
│  ┌─────────────────────────────────────────────────┐│
│  │           Gamification Services                 ││
│  │  PointLedger | WipSlots | Streak | Penalty      ││
│  └─────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────┘
```

### 5.4 When Pure API Would Be Better

- Mobile app requirement (iOS/Android)
- Third-party API consumers
- Microservices architecture

**Verdict:** Start with Inertia, can extract API later if needed.

---

## 6. Implementation Roadmap

### Phase 1: Foundation (Week 1-2)
- [ ] Database migrations for all gamification tables
- [ ] PointLedgerService (debit/credit transactions)
- [ ] Basic quest acceptance flow
- [ ] Pinia store setup

### Phase 2: Core Systems (Week 2-3)
- [ ] WipSlotManagerService with weight calculations
- [ ] StreakService with milestone tracking
- [ ] Quest lifecycle state machine
- [ ] Inertia pages and Vue components

### Phase 3: Penalty & Endgame (Week 3-4)
- [ ] PenaltyService implementation
- [ ] Late penalty scheduler
- [ ] EndgameService with phase transitions
- [ ] Admin configuration endpoints

### Phase 4: Polish (Week 4-5)
- [ ] Real-time updates via Laravel Echo/Pusher
- [ ] UI/UX refinement
- [ ] Testing (Unit + Feature)
- [ ] Documentation

---

## 7. Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Streak calculation edge cases | Medium | Medium | Comprehensive test cases |
| Slot weight edge cases | Low | High | Clear business rules |
| Performance at scale | Medium | Medium | Indexing + caching |
| Penalty calculation bugs | Low | High | Audit logging |
| Endgame state corruption | Low | High | Transaction wrapping |

---

## 8. Maintenance Considerations

### 8.1 Custom Engine Benefits
- **Full control** over business logic
- **Easy debugging** - no black boxes
- **Flexible changes** - PRD evolution support
- **Learning** - team understands internals

### 8.2 Technical Debt Prevention
- Service classes with single responsibility
- Repository pattern for data access
- Event-driven architecture for extensibility
- Comprehensive unit tests

### 8.3 Future Extensibility
The custom engine can easily add:
- Leaderboards (ranked queries)
- Social features (point gifting)
- Seasonal events (multiplier campaigns)
- Achievement badges (leveraging existing infrastructure)

---

## 9. Conclusion

**Primary Recommendation: Option B - Custom Engine with Inertia + Pinia**

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Gamification | Custom Build | No package supports PRD |
| Vue State | Pinia | Official Vue 3 recommendation |
| SPA Architecture | Inertia.js | Best Laravel integration |
| Database | MySQL | Project standard |

### Key Takeaways

1. **Don't fight the package** - existing gamification packages are too generic
2. **Use the platform** - Inertia makes Laravel+Vue seamless  
3. **Official tooling** - Pinia is Vue 3's officially recommended state manager
4. **Domain-driven design** - Services mirror PRD business rules

---

## 10. Appendix: Key PRD Rules Reference

### Point Ledger Rules
- All point changes are transactions (never UPDATE balance directly)
- Types: `earn`, `spend`, `penalty`, `refund`, `bonus`
- Running balance calculated or stored for performance

### WIP Slot Rules
- Global limit configurable (default: 3)
- Max slots = global_limit × 4
- Quest weights: High=4, Mid=2, Low=1
- Cannot accept quest if weight > available slots

### Streak Rules
- Consecutive days of progress
- Bonuses: 7d=1.1x, 14d=1.2x, 21d=1.3x, 30d=1.5x
- Missed day resets streak to 0

### Penalty Rules
- Revise task: -10 points
- Cancel quest: -10 points
- Hoarding/expired: -50 points
- Late submission: -10 points/day

### Endgame Rules
- Graduation perfect: +200 bonus
- Onboarding complete: +50 bonus
- Phases: onboarding → active → graduation → alumni
