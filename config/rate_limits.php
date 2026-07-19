<?php

/*
| Docs/Spec.md §10: "Rate limiting: review 3/jam & 10/hari; upload 20
| foto/hari; report 10/hari; usul cafe 3/hari & 10/bln; login attempt per
| IP." Single source of truth so the numbers live in one place instead of
| scattered across Actions (Docs/plan.md Task 5.4). Usul cafe (F8) isn't
| built yet (Phase 6) — its limits get added here when that Action exists,
| not invented ahead of time.
*/
return [
    'review' => [
        'per_hour' => 3,
        'per_day' => 10,
    ],
    'photo' => [
        'per_day' => 20,
    ],
    'report' => [
        'per_day' => 10,
    ],
    'content_appeal' => [
        'per_day' => 3,
        'verify_per_hour' => 5,
    ],
    'login' => [
        'per_minute' => 5,
    ],
];
