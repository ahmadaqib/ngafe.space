# Ngafe MVP — Implementation Plan

> **Untuk agentic workers:** REQUIRED SUB-SKILL: gunakan `superpowers:subagent-driven-development` (disarankan) atau `superpowers:executing-plans` untuk mengeksekusi plan ini task-per-task. Step memakai checkbox (`- [ ]`) untuk tracking.
>
> Sumber kebenaran produk: **`Docs/Spec.md` v1.4**. Setiap task merujuk nomor section spec (mis. §10, F5). Jika plan dan spec bertentangan, spec menang — lalu perbaiki plan.

**Goal:** Membangun MVP web app review & rekomendasi cafe Makassar (ngafe.space) sesuai Spec v1.4 — jalur baca tanpa login sampai sempurna dulu, baru jalur tulis — siap soft launch klaster Tamalanrea di minggu 11.

**Architecture:** Modular monolith Laravel (`app/Domain/{Cafe,Review,Identity,Moderation}`) dengan disiplin clean architecture pragmatis (§9): controller/Livewire tipis → satu Action per use case (`handle()`) → side-effect lintas domain via event → presentasi keluar hanya lewat API Resource/Blade yang dijaga test no-PII. Model Eloquent penuh tanpa repository interface.

**Tech Stack:** Laravel 13 · Livewire 4 + Alpine.js · Filament 5 (admin) · PostgreSQL 17 (pg_trgm, Haversine SQL) · Tailwind CSS 4 (design tokens §12 sebagai CSS variables) · Pest 4 (test) · Cloudflare R2 (foto) · MapLibre GL + OpenFreeMap · Resend (email) · Sentry + UptimeRobot + Healthchecks.io · Umami (analytics).

> **Catatan versi:** Spec menulis "Laravel 11 + Filament (3) + PostgreSQL 16". Keputusan 2026-07-18: pakai versi stabil terbaru — hasil install aktual **Laravel 13.20 + Livewire 4.3 + Filament 5.7 + Pest 4**, dan Postgres lokal yang sudah terpasang adalah **17.10** (≥16, pg_trgm tersedia). Seluruh isi spec tetap berlaku; hanya nomor versi berubah.

> **Audit 2026-07-19:** status Phase 0–4 diverifikasi ulang terhadap kode dan test. Detail bukti, deviasi, serta pekerjaan tersisa ada di [`Docs/audit-phase-0-4.md`](audit-phase-0-4.md). Checkbox yang sebelumnya terlalu optimistis dikoreksi di bawah.

## Global Constraints (berlaku implisit di SEMUA task)

- **Login TIDAK PERNAH jadi gerbang membaca** (§4). Login hanya terpicu aksi tulis; jalur landing→baca review maks 2 tap, tanpa interstitial.
- **No-PII di semua output publik** (§10): API/JSON/HTML publik tidak boleh memuat `user_id`, `google_sub`, email, nama Google — hanya `display_alias`. Test CI menjaga ini; regresi = build gagal. **Log adalah output juga**: dilarang log email, `google_sub`, token, body review, koordinat user, IP utuh (§10 Logging).
- **Performa:** LCP < 2,5s di 4G, CLS < 0,1; gambar WebP + `srcset` (400/1600px) + lazy; skeleton, bukan spinner halaman (§10, §14).
- **Locale Indonesia (§12.5):** rating "4,6" (koma) · jarak "850 m"/"1,2 km" · harga "Rp 15–30rb" · jam "22.00" · timestamp kasar ("2 hari lalu"). JSON-LD tetap pakai titik (machine-readable).
- **Anti AI-slop (§15):** tanpa gradient dekoratif, tanpa tema kosmik, tanpa gambar AI-generated di mana pun, tanpa dark pattern, tanpa maskot/"min-kak". Radius hanya 8/12/full. Ikon satu set (Lucide).
- **Warna (§12.2):** hanya token semantik — hex hardcode di komponen dilarang. Primary (terracotta) hanya CTA; petrol ≤10% dan TIDAK PERNAH untuk CTA; kontras ≥4,5:1 adalah AC.
- **Arsitektur (§9):** logika bisnis di Action/Support, bukan controller; exception domain ber-`userMessage()` + handler global satu pintu; job idempotent + retry 3× backoff.
- **Vendor $0 pada skala MVP** (§9): hanya VPS + domain yang berbayar. Semua standar terbuka.
- **Keamanan (§10):** FormRequest whitelist semua input; Blade `{{ }}` wajib; Policy semua aksi; `$fillable` eksplisit; ID publik ULID; rate limit semua endpoint tulis.
- **Copy:** "kamu" bukan "Anda", santai, varian dirotasi; partikel lokal di `lang/copy/makassar.php` (§16).
- **Commit sering** — tiap task selesai = minimal satu commit, pesan `feat:`/`fix:`/`chore:` + trailer `Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>`.

---

## Phase 0 — Project Init di Root _(hari 1)_

### Task 0.1: Scaffold Laravel langsung di root repo ✅ (selesai 2026-07-18)

**Files:** seluruh scaffold Laravel di `/` (root repo, BUKAN subfolder) — `app/`, `config/`, `routes/`, `composer.json`, dst. `Docs/`, `.claude/`, `.git` existing dipertahankan.

- [x] `composer create-project laravel/laravel <temp-dir>` (root tidak kosong, jadi lewat temp) → dapat **Laravel 13.20**.
- [x] Pindahkan seluruh isi temp (termasuk dotfiles: `.env`, `.env.example`, `.gitignore`, `.editorconfig`, `.gitattributes`, `.npmrc`) ke root; hapus temp. `.git`/`Docs`/`.claude` utuh.
- [x] `php artisan about` → Laravel 13.20.0, APP_KEY terisi.
- [x] Commit: `chore: scaffold laravel 13 di root + Docs`.

### Task 0.2: Dependency inti + PostgreSQL ✅ (selesai 2026-07-18)

**Files:** `composer.json`, `.env`, `.env.example`, `app/Providers/Filament/AdminPanelProvider.php`, `tests/Pest.php`.

- [x] Postgres **17.10** (Homebrew) sudah jalan lokal; `createdb ngafe`.
- [x] `.env` + `.env.example`: `DB_CONNECTION=pgsql`, `DB_DATABASE=ngafe`, `APP_LOCALE=id`, faker `id_ID`; `SESSION_DRIVER`/`QUEUE_CONNECTION`/`CACHE_STORE=database` (Redis menyusul di VPS).
- [x] `composer require filament/filament laravel/socialite intervention/image league/flysystem-aws-s3-v3` → **Filament 5.7** (Livewire ikut sebagai dependency), Socialite 5.28, Intervention 4.2.
- [x] `composer require pestphp/pest pestphp/pest-plugin-laravel --dev -W` (**Pest 4.7**) → `php artisan pest:install -n`.
- [x] `php artisan filament:install --panels -n` → `AdminPanelProvider` dibuat; path default diganti `->path('ruang-admin')` (§10 hardening).
- [x] `php artisan migrate` (tabel bawaan) → sukses di pgsql.
- [x] `npm install && npm run build` → sukses, 0 vulnerabilities.
- [x] Commit.

### Task 0.3: Verifikasi toolchain ✅ (selesai 2026-07-18)

- [x] `php artisan test` → hijau (2 passed).
- [x] `php artisan serve` + curl → `/` 200, `/ruang-admin/login` 200, `/admin` (path lama) 404.
- [x] `git status` bersih setelah commit; `Docs/` & `.claude/` utuh.

---

## Phase 1 (Minggu 1–2) — Fondasi: Data Model, Auth, Admin, Error Handling, CI

### Task 1.1: Struktur modul domain + konvensi

**Files (create, folder + `.gitkeep`/class awal):**

```
app/Domain/Cafe/{Models,Actions,Queries,Support}/
app/Domain/Review/{Models,Actions,Support}/
app/Domain/Identity/{Models,Actions,Policies}/
app/Domain/Moderation/{Models,Actions,Support}/
app/Support/            ← helper lintas domain (LogContext, Format)
```

**Interfaces — Produces:** namespace `App\Domain\...` dipakai semua task berikutnya. Autoload PSR-4 `App\` sudah mencakup `app/` — tidak perlu ubah composer.json.

- [x] Buat struktur folder; pindahkan `User` bawaan ke `app/Domain/Identity/Models/User.php` (update namespace + `config/auth.php` providers + factory).
- [x] `php artisan test` tetap hijau. Commit.

### Task 1.2: Migrations + Models sesuai data model §11

**Files:**
- Create: `database/migrations/*_create_cafes_table.php`, `*_create_categories_table.php`, `*_create_reviews_table.php`, `*_create_photos_table.php`, `*_create_reports_table.php`, `*_create_cafe_category_table.php`, `*_create_review_tags_table.php`, `*_add_ngafe_fields_to_users_table.php`
- Create: `app/Domain/Cafe/Models/{Cafe,Category,Area}.php`, `app/Domain/Review/Models/{Review,Photo}.php`, `app/Domain/Moderation/Models/Report.php`
- Test: `tests/Feature/Database/SchemaTest.php`

**Interfaces — Produces:** semua model memakai **ULID sebagai primary key publik** (`HasUlids`); enum PHP backed: `CafeStatus {Pending,Active,Rejected,ClosedPerm}`, `ReviewStatus {Published,Pending,Removed}`, `ReportReason {Spam,Kasar,BukanTentangCafe,InfoSalah,MembukaIdentitas}`, `ReportStatus {Open,Resolved}`.

Skema inti (kolom persis §11):

```php
// users (alter): google_sub (string, unique, nullable utk admin seed), email,
//   display_alias_seed (string, random 32 byte), role enum('user','admin') default 'user',
//   status enum('active','banned') default 'active'. Nama & foto profil Google TIDAK disimpan.

Schema::create('cafes', function (Blueprint $t) {
    $t->ulid('id')->primary();
    $t->string('name');
    $t->string('slug');
    $t->string('city')->default('makassar');      // ★ multi-kota sejak hari 1
    $t->string('area');                            // enum-like, chip area §4.2c
    $t->text('address')->nullable();
    $t->decimal('lat', 10, 7); $t->decimal('lng', 10, 7);
    $t->jsonb('opening_hours')->nullable();        // per hari
    $t->jsonb('opening_hours_override')->nullable(); // [{label,date_start,date_end,hours}]
    $t->string('price_range')->nullable();         // '15-30' → render "Rp 15–30rb"
    $t->decimal('rating_avg', 3, 2)->nullable();   // denormalized, hanya published
    $t->unsignedInteger('rating_count')->default(0);
    $t->decimal('quality_score', 6, 4)->nullable(); // Bayesian F7b
    $t->decimal('trending_score', 8, 2)->default(0);
    $t->string('status')->default('pending');      // pending/active/rejected/closed_perm
    $t->foreignUlid('created_by')->nullable()->constrained('users');
    $t->timestamp('last_verified_at')->nullable();
    $t->timestamps();
    $t->unique(['city', 'slug']);
    $t->index(['city', 'status']);
});

Schema::create('reviews', function (Blueprint $t) {
    $t->ulid('id')->primary();
    $t->foreignUlid('user_id')->constrained();
    $t->foreignUlid('cafe_id')->constrained();
    $t->unsignedTinyInteger('rating');             // 1–5
    $t->text('body');                              // plain text, min 30 char (validasi form)
    $t->string('display_alias');                   // hasil AliasGenerator, disimpan
    $t->string('status')->default('published');    // published/pending/removed
    $t->boolean('is_edited')->default(false);
    $t->timestamps();
    $t->unique(['user_id', 'cafe_id']);            // 1 review/user/cafe — constraint DB, §10
});
// photos: id ulid, review_id FK, cafe_id FK, url_card, url_full, width, height,
//   status (published/pending/removed), content_hash (dedup/idempotensi), created_at
// categories: id, name, slug unique, icon (nama ikon Lucide), sort_order
// cafe_category: cafe_id, category_id, source enum-like('admin','crowd','auto'), confidence decimal
// review_tags: review_id, category_id (quick-tag §F5)
// reports: id ulid, reporter_id FK users, review_id nullable FK, photo_id nullable FK,
//   reason enum §11, note nullable, status open/resolved, resolved_by nullable, timestamps
```

- [x] Tulis `SchemaTest` dulu: assert tabel & kolom kunci ada, unique `(user_id,cafe_id)` ditegakkan (insert kedua → `QueryException`), unique `(city,slug)`.
- [x] Jalankan → FAIL. Tulis migrations + models (relasi §11: User 1—N Review; Cafe 1—N Review; Review 1—N Photo; Cafe N—M Category; User 1—N Report; casts enum + jsonb; `$fillable` eksplisit).
- [x] `php artisan migrate:fresh && php artisan test` → PASS. Commit.

### Task 1.3: Ekstensi pg_trgm + index GIN

**Files:** `database/migrations/*_add_trgm_index_to_cafes.php` · Test: `tests/Feature/Database/TrgmSearchTest.php`

```php
DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
DB::statement('CREATE INDEX cafes_name_trgm ON cafes USING gin (name gin_trgm_ops)');
```

- [x] Test: seed cafe "Kopi Anjis Perintis" → `SELECT similarity(name, 'kopi anjs')` > 0.3 via query builder → PASS setelah migration. **Index ini dipakai live-search F3 DAN dedup F8 — satu index dua fungsi (§9).** Commit.

### Task 1.4: Error handling satu pintu + request_id (§10 Logging/Error v1.4)

**Files:**
- Create: `app/Exceptions/DomainException.php` (base), `app/Domain/Review/Exceptions/{ReviewLimitExceeded,DuplicateReview}.php`, `app/Domain/Cafe/Exceptions/ProposalThrottled.php`, `app/Domain/Review/Exceptions/PhotoValidationFailed.php`
- Create: `app/Http/Middleware/AssignRequestId.php`, `app/Support/LogContext.php`
- Modify: `bootstrap/app.php` (withExceptions + middleware global), `config/logging.php` (daily JSON, 14 hari)
- Test: `tests/Feature/ErrorHandlingTest.php`, `tests/Unit/LogContextTest.php`

```php
abstract class DomainException extends \Exception
{
    abstract public function userMessage(): string; // pesan santai §16
}

// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (DomainException $e, Request $request) {
        if ($request->expectsJson()) {
            return response()->json(['message' => $e->userMessage()], 422);
        }
        return back()->with('toast_error', $e->userMessage());
    });
})

// AssignRequestId: $id = (string) Str::ulid();
// Log::withContext(['request_id' => $id]); response header X-Request-Id; Sentry tag.
```

- [x] TDD: (a) endpoint dummy melempar `ReviewLimitExceeded` → response 422 berisi `userMessage()`, TANPA stack trace; (b) exception non-domain → pesan generik "Ada yang error di kami, bukan di kamu…" (§10); (c) semua response punya header `X-Request-Id` ULID.
- [x] `LogContext::safe(array $ctx)`: helper terpusat yang **menolak key terlarang** (`email`, `google_sub`, `token`, `body`, `lat`, `lng`, `ip`) — test unit assert throw saat key terlarang masuk. Logging channel: Monolog JSON daily, `days => 14`, level prod `info`.
- [x] Commit.

### Task 1.5: Google OAuth via Socialite (§4.3, §10)

**Files:**
- Create: `app/Domain/Identity/Actions/HandleGoogleCallback.php`, `app/Http/Controllers/Auth/GoogleAuthController.php`, `routes/web.php` (rute `/auth/google/redirect`, `/auth/google/callback`)
- Modify: `config/services.php` (google), `.env.example`
- Test: `tests/Feature/Auth/GoogleLoginTest.php` (mock Socialite)

**Interfaces — Produces:** `HandleGoogleCallback::handle(SocialiteUser $g): User` — match **by `google_sub`** (bukan email), create bila belum ada, simpan hanya `google_sub` + `email` + generate `display_alias_seed`.

- [x] TDD (mock `Socialite::driver('google')->user()`): (a) sub baru → user dibuat, HANYA sub+email tersimpan (assert kolom name/avatar tidak ada); (b) sub sama email berubah → user lama ter-match, email ter-update; (c) callback tanpa state valid → redirect aman tanpa crash; (d) session di-regenerate saat login.
- [x] Scope hanya `openid email`. Cookie session `HttpOnly; Secure; SameSite=Lax` (config/session.php).
- [x] Return-to-context: sebelum redirect OAuth simpan `intended_url` + konteks aksi di session; callback redirect balik PERSIS (§4.3.4). (Bottom-sheet UI-nya menyusul Phase 4 — di sini cukup mekanika redirect.)
- [x] Commit.

### Task 1.6: Filament 5 admin panel — hardening sejak hari 1 (§10)

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Create: `app/Filament/Resources/{CafeResource,CategoryResource}.php` (CRUD dasar; Review/Report resource menyusul Phase 4)
- Test: `tests/Feature/Admin/PanelAccessTest.php`

- [x] Panel: `->path('ruang-admin')` · gate akses `role === 'admin'` (`FilamentUser` contract di model User) · **2FA TOTP wajib** (fitur MFA bawaan Filament di-enable + `->requiresMfa()`) · session pendek (`config/session.php` lifetime 60 utk panel via middleware).
- [x] TDD: user role `user` → 403 ke `/ruang-admin`; guest → redirect login; `/admin` (path default) → 404.
- [x] CRUD Cafe di Filament: form field sesuai §11 termasuk `opening_hours_override` (repeater `{label,date_start,date_end,hours}`) — dipakai seeding lapangan mulai minggu 1. CRUD Category.
- [x] Commit.

### Task 1.7: No-PII test harness + CI (§10 threat #1)

**Files:**
- Create: `tests/Support/AssertsNoPii.php` (trait), `tests/Feature/Api/NoPiiTest.php`, `.github/workflows/ci.yml`

```php
trait AssertsNoPii
{
    /** Assert response publik bebas PII — dipakai SEMUA test halaman/JSON publik. */
    public function assertNoPii(TestResponse $response, User ...$users): void
    {
        $body = $response->getContent();
        foreach ($users as $u) {
            expect($body)->not->toContain($u->email)
                ->not->toContain($u->google_sub)
                ->not->toContain((string) $u->id);
        }
    }
}
```

- [x] Test awal: halaman detail cafe (placeholder route dulu bila Phase 2 belum ada — minimal JSON resource Review) → `assertNoPii`. Setiap task Phase 2+ yang menambah output publik WAJIB menambah pemanggilan trait ini.
- [x] `.github/workflows/ci.yml`: jobs `composer audit`, `php artisan test` (service container Postgres 17 + `CREATE EXTENSION pg_trgm`), `npm run build`. Lockfile committed.
- [ ] Commit; push; CI hijau. (Commit lokal selesai pada penutupan Phase 1; push/hasil CI membutuhkan remote run.)

### Task 1.8: Seeder kategori + factories

**Files:** `database/seeders/CategorySeeder.php`, `database/factories/{CafeFactory,ReviewFactory,PhotoFactory}.php`

- [x] 12 kategori persis §F4: `Cocok nugas & WFC` · `Wifi kencang` · `Banyak colokan` · `Buka 24 jam` · `Ramah kantong` · `Aesthetic` · `Tenang` · `Rame/nongkrong` · `Hidden gem / baru buka` · `Outdoor/smoking area` · `Ramah keluarga` · `Musala & parkir gampang` — masing-masing dengan ikon Lucide & `sort_order`.
- [x] Factories realistis Makassar (§15 anti lorem-ipsum): nama cafe & review contoh dari daftar kurasi manual di factory (bukan faker lorem), koordinat dalam bbox Makassar, area dari daftar §4.2c.
- [x] Commit.

---

## Phase 2 (Minggu 3–4) — F1 Direktori & Detail + F3 Search & Lokasi

### Task 2.0: Design tokens & layout dasar (§12 — fondasi semua UI)

**Files:**
- Create: `resources/css/tokens.css` (primitif §12.1 + semantik §12.2 light/dark via `@media (prefers-color-scheme)` + `data-theme`), `resources/views/components/layout/app.blade.php` (shell: bottom nav 3 item Jelajah·Cari·Kamu, safe-area), `resources/views/components/ui/{card,chip,badge,button,skeleton,sheet}.blade.php`
- Modify: `resources/css/app.css`, `vite.config.js`, `tailwind` config (map token → utility)

- [ ] Salin SEMUA nilai hex §12.1–12.3 verbatim ke `tokens.css`; komponen hanya boleh `var(--…)`. Spacing/radius/type/motion/z-index §12.4 sebagai custom properties. **Audit: baru subset token; masih ada hex/radius inline.**
- [ ] Font: Plus Jakarta Sans variable **self-host** (`@font-face`, woff2 di `public/fonts/`); keputusan §12.4: mulai 1 family untuk semua (bundle ringan), Inter menyusul hanya jika perlu. **Audit: font belum tersedia/self-host.**
- [ ] Komponen `sheet` (bottom drawer §14): drag handle, snap peek 45%/full 92%, Alpine + CSS transform, ≤250ms, hormati `prefers-reduced-motion`. **Audit: open/close dasar ada; drag dan snap belum ada.**
- [ ] Verifikasi visual: halaman styleguide dev-only `/dev/tokens` (light+dark) — cek kontras pasangan token dengan tooling (axe/manual). Commit. **Audit: halaman dasar ada; validasi kontras belum tercatat.**

### Task 2.1: Route publik + halaman detail cafe (F1 — unit aha moment)

**Files:**
- Create: `app/Http/Controllers/CafeController.php` (tipis), `resources/views/cafe/show.blade.php`, `app/Domain/Cafe/Support/OpeningHours.php`
- Modify: `routes/web.php` — `Route::get('/{city}/{slug}', …)->name('cafe.show')` (constraint city whitelist)
- Test: `tests/Feature/Cafe/ShowPageTest.php`, `tests/Unit/OpeningHoursTest.php`

**Interfaces — Produces:** `OpeningHours::statusNow(Cafe $cafe, CarbonImmutable $now): OpeningStatus` — value object `{isOpen: bool, label: string, activeOverride: ?string}`; **override musiman menang atas jadwal normal** (§F1 AC).

- [ ] TDD `OpeningHours`: (a) jam normal buka/tutup lintas tengah malam (24 jam); (b) `opening_hours_override` aktif hari ini → dipakai + `activeOverride` = label ("Jam khusus Ramadan…" → banner); (c) tanpa data jam → label "Jam belum tersedia". **Audit: implementasi ada; matriks test belum lengkap.**
- [ ] TDD halaman: (a) cafe `active` terbaca penuh **tanpa login** + `assertNoPii`; (b) cafe `pending`/`rejected` → 404; (c) urutan konten: galeri foto (4:3 tetap, §16) → nama+rating+status buka → tag → **blok review ≤1 swipe** (review pertama server-rendered, bukan lazy JS — SEO §10); (d) review `pending` orang lain tidak tampil, tampil bagi penulisnya dengan badge "sedang ditinjau"; (e) deep-link "Arah" (`geo:`/Google Maps URL); (f) timestamp kasar "2 hari lalu". **Audit: alur inti ada; galeri/urutan dan cakupan AC masih parsial.**
- [ ] CTA akhir daftar review: "Pernah ke sini? Ceritakan versimu" (pintu login §4.1.6 — behavior penuh Phase 4, sekarang placeholder link login). **Audit: form dan sticky CTA sudah ada, copy persis belum sesuai.**
- [x] Full-page cache anonim 5 menit untuk halaman detail (§10 Performa) — middleware cache respons untuk guest.
- [x] Commit.

### Task 2.2: Homepage jalur aha (F1) — tanpa gate, tanpa splash

**Files:**
- Create: `app/Livewire/Home.php`, `resources/views/livewire/home.blade.php`, `app/Domain/Cafe/Queries/HomeSections.php`
- Test: `tests/Feature/HomePageTest.php`

- [x] Above the fold: search bar + maks **6 chip** kategori (+"Lainnya" → sheet 12 lengkap, §13 Hick) + grid kartu "Lagi rame dibahas" (sementara sort `rating_count` desc; `trending_score` asli menggantikan di Phase 6 — kolom & query SUDAH pakai `trending_score` agar tinggal diisi cron).
- [ ] Kartu cafe 5 chunk (§13 Miller): foto user 4:3, nama, rating "4,6" + jumlah, 2 tag, jarak (bila ada)/potongan review ≤90 char potong di batas kata (§12.5). **Audit: jarak/potongan review belum dirender.**
- [ ] TDD: (a) **cafe tanpa review published tidak muncul di seksi homepage** (§4.1 aturan keras); (b) tanpa login semua terbaca, tidak ada redirect; (c) `assertNoPii`; (d) rating format koma. **Audit: (a) dan no-PII ada; AC lain belum diuji eksplisit.**
- [ ] Urutan chip kontekstual (§F4): weekday 09–16 WITA → `Cocok nugas & WFC` duluan; wiken → `Hidden gem / baru buka` + `Aesthetic` (uji dengan `Carbon::setTestNow`). **Audit: logika ada; test waktu belum ada.**
- [x] Commit.

### Task 2.3: Live-search + filter kategori (F3, F4-filter)

**Files:**
- Create: `app/Livewire/Search.php`, `app/Domain/Cafe/Queries/SearchCafes.php`, `resources/views/livewire/search.blade.php`
- Test: `tests/Feature/SearchTest.php`, `tests/Unit/Queries/SearchCafesTest.php`

**Interfaces — Produces:** `SearchCafes::run(?string $q, array $categorySlugs, ?float $lat, ?float $lng, ?string $area, string $city='makassar'): Collection` — dipakai juga homepage & F8 dedup.

```php
// Inti query (§F3): pg_trgm + urutan jarak
$query->when($q, fn ($qq) => $qq
    ->whereRaw('name % ?', [$q])                     // operator similarity pg_trgm
    ->orderByRaw('similarity(name, ?) DESC', [$q]));
$query->when($lat && $lng, fn ($qq) => $qq
    ->selectRaw('*, (6371 * acos(least(1.0,
        cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?))
        + sin(radians(?)) * sin(radians(lat))))) AS distance_km', [$lat, $lng, $lat])
    ->orderBy('distance_km'));                        // Haversine SQL, tanpa API eksternal
// Filter kategori = AND (§F4): whereHas per slug (irisan)
```

- [x] TDD unit: typo "kopi anjs" tetap menemukan "Kopi Anjis"; multi kategori = irisan; jarak terurut benar (fixture 3 koordinat); hanya `active`.
- [ ] Livewire: debounce 250ms, hasil <500ms, jumlah hasil tampil ("12 cafe cocok"), update tanpa reload. **Audit: debounce/jumlah ada; anggaran <500ms belum diukur.**
- [ ] **Empty state cerdas (§4.2 edge):** hitung filter paling membatasi (lepas satu-satu, ambil yang menambah hasil terbanyak) → "Coba lepas 'Buka 24 jam' — ada 8 cafe lain" + CTA usulkan cafe. **Audit: saat ini memilih filter pertama yang membantu, bukan yang paling membatasi.**
- [ ] Riwayat pencarian maks 3 chip, localStorage (§16). Commit. **Audit: belum diimplementasikan.**

### Task 2.4: Lokasi & fallback area (F3)

**Files:**
- Modify: `app/Livewire/Search.php`, view search
- Create: `resources/js/geo.js`
- Test: `tests/Feature/SearchAreaFallbackTest.php`

- [x] Alur izin §4.2: pre-prompt penjelasan ("Boleh tau posisimu? Biar yang paling dekat muncul duluan.") → baru `navigator.geolocation`. Koordinat HANYA dikirim sebagai parameter query — TIDAK dilog, TIDAK disimpan (§10).
- [x] Ditolak → chip area §4.2c: Tamalanrea, Panakkukang, Losari/Pantai, Antang, Hertasning, Daya, Sekitar Unhas, Sekitar UNM/UIN. Tidak pernah dead-end.
- [x] Label jarak format §12.5 (`Format::distance()` helper di `app/Support/Format.php` + test unit: 850 → "850 m", 1234 → "1,2 km").
- [x] Commit.

---

## Phase 3 (Minggu 5) — F2 Rating & Agregasi + F4 Kategori penuh

### Task 3.1: Agregasi rating denormalized (F2 AC v1.2)

**Files:**
- Create: `app/Domain/Review/Events/ReviewStatusChanged.php`, `app/Domain/Cafe/Listeners/RecomputeCafeAggregates.php`, `app/Jobs/RecomputeCafeAggregates.php`
- Test: `tests/Feature/RatingAggregateTest.php`

**Interfaces:** event `ReviewStatusChanged(Review $review, ?ReviewStatus $from)` — **di-dispatch oleh semua Action yang mengubah status review** (SubmitReview, ModerateReview, dst. di Phase 4). Listener queue job idempotent: hitung ulang `rating_avg`+`rating_count` dari sumber (hanya `published`), `quality_score` Bayesian ikut di-update di sini (rumus Task 6.1).

- [x] TDD: (a) review published masuk → avg/count berubah; (b) status → `pending`/`removed` → dikeluarkan dari agregat; (c) job dijalankan 2× → hasil sama (idempotent); (d) tidak dihitung on-the-fly di request (assert query halaman detail tidak agregasi).
- [x] Tampilan: cafe tanpa rating → "Belum ada review — jadi yang pertama?" BUKAN "0.0" (§F2). Commit.

### Task 3.2: Kategori crowdsourced + Hidden gem auto (F4)

**Files:**
- Create: `app/Domain/Cafe/Actions/SyncCrowdCategories.php`, `app/Console/Commands/AssignHiddenGem.php` (scheduled daily)
- Test: `tests/Unit/Actions/SyncCrowdCategoriesTest.php`

- [x] Quick-tag ≥30% reviewer sebuah cafe → kategori tampil (`cafe_category.source='crowd'`, `confidence`=proporsi) — dipanggil dari listener `ReviewStatusChanged`.
- [x] `Hidden gem / baru buka` auto: umur <90 hari di platform ATAU review <10 (scheduled command + saat cafe approve). TDD kedua aturan + lepasnya label saat lewat ambang. Commit.

---

## Phase 4 (Minggu 6–7) — F5 Review Anonim + F6 Foto + Moderasi

### Task 4.1: AliasGenerator HMAC (§10 threat #3)

**Files:** `app/Domain/Review/Support/AliasGenerator.php` · Test: `tests/Unit/AliasGeneratorTest.php`

```php
final class AliasGenerator
{
    /** @var list<string> pool lintas segmen §F5 */
    private const ADJECTIVES = ['Penikmat', 'Pemburu', 'Penghuni', 'Pengelana', 'Penjaga'];
    private const NOUNS = ['Senja', 'Kopi Susu', 'Sudut', 'Wifi', 'Deadline', 'Wiken'];

    public function for(User $user, Cafe $cafe): string
    {
        $hash = hash_hmac('sha256', $user->id.'|'.$cafe->id, config('app.key'));
        $n1 = hexdec(substr($hash, 0, 8)); $n2 = hexdec(substr($hash, 8, 8));
        return self::ADJECTIVES[$n1 % count(self::ADJECTIVES)].' '
             .self::NOUNS[$n2 % count(self::NOUNS)].' '.$cafe->area;
        // contoh: "Penikmat Senja Panakkukang" — konsisten per (user,cafe), beda antar cafe
    }
}
```

- [x] TDD: deterministik (2× panggil = sama); user sama cafe beda → alias beda; user beda cafe sama → beda; tidak reversible (tidak memuat id). Pool kata final boleh diperluas — aturan tetap. Commit.

### Task 4.2: SubmitReview/EditReview Actions + guards (F5)

**Files:**
- Create: `app/Domain/Review/Actions/{SubmitReview,EditReview,DeleteOwnReview}.php`, `app/Domain/Review/Support/ReviewGuards.php`, `app/Http/Requests/StoreReviewRequest.php`
- Test: `tests/Unit/Actions/SubmitReviewTest.php`, `tests/Feature/ReviewRateLimitTest.php`

**Interfaces — Produces:** `SubmitReview::handle(User $u, Cafe $c, int $rating, string $body, array $tagIds): Review` — melempar `DuplicateReview` (arahkan ke edit), `ReviewLimitExceeded`; dispatch `ReviewStatusChanged`.

- [x] TDD: (a) valid → review published + alias terisi dari `AliasGenerator`; (b) review kedua cafe sama → `DuplicateReview`; `EditReview` → update + `is_edited`, BUKAN duplikat (§4.4 edge); (c) rate limit 3/jam & 10/hari → `ReviewLimitExceeded` dengan `userMessage()` santai §16; (d) auto-flag heuristik §10: akun <24 jam + rating 1 bertubi ke 1 cafe → status `pending`; kata terlarang (config `moderation.banned_words`, ID + lokal) → `pending`; (e) honeypot & hash duplikat konten ditolak; (f) hapus akun → pilihan anonimkan permanen / hapus review (UU PDP §10).
- [ ] Commit per sub-behavior (guards dulu, actions kemudian).

### Task 4.3: Form review 3 langkah + login bottom sheet (F5, §4.3–4.4)

**Files:**
- Create: `app/Livewire/ReviewForm.php`, `resources/views/livewire/review-form.blade.php`, `resources/js/review-draft.js`
- Modify: `resources/views/cafe/show.blade.php` (sticky CTA)
- Test: `tests/Feature/ReviewFormFlowTest.php` + browser test minimal (Dusk/Pest browser) jalur aha & form

- [ ] 3 langkah ber-progress (Zeigarnik §13): Rating (bintang 48px) + quick-tag → Cerita (≥30 char, placeholder "Wifinya gimana? Betah berapa jam? Habis berapa?") → Foto opsional. Draft otomatis localStorage; kembali → "Reviewmu tinggal selangkah lagi".
- [x] Belum login → **bottom sheet** copy §16 → OAuth → kembali PERSIS ke form dengan state utuh (intent sessionStorage, §4.3). Cancel → tanpa error menakutkan.
- [x] Peak-End (§4.4.6): layar sukses personality (varian dirotasi) + review tampil optimistic + tawaran "review cafe lain".
- [x] Sticky CTA "Tulis review" muncul SETELAH scroll melewati blok review (§16). Commit.

### Task 4.4: Pipeline foto (F6, §9, §10 upload)

**Files:**
- Create: `app/Jobs/ProcessReviewPhoto.php`, `app/Domain/Review/Actions/AttachPhotos.php`, `resources/js/photo-upload.js` (browser-image-compression), `config/filesystems.php` disk `r2` + `r2_backup`
- Test: `tests/Unit/Jobs/ProcessReviewPhotoTest.php` (Storage::fake), `tests/Feature/PhotoUploadTest.php`

- [x] Client: kompres maks sisi 1600px target ≤300KB WebP sebelum kirim; tolak non-gambar/>10MB DI CLIENT dengan pesan jelas (§F6).
- [ ] Server (job, queue): validasi MIME **dari magic bytes** → re-encode paksa WebP (Intervention Image) varian **400px (kartu) & 1600px (detail)** → **strip EXIF/GPS total** → nama UUID → R2 (bukan web-root), serve dari domain terpisah cookie-less. Retry 3× backoff; idempotent via `content_hash`; gagal final → `failed_jobs` + Sentry (§10 v1.4).
- [x] TDD: EXIF GPS hilang di output; upload gagal 1 foto → teks review tetap tersimpan + retry per-foto (§F6 AC); rate limit 20 foto/hari.
- [ ] Commit.

### Task 4.5: Moderasi + report + email transaksional (§10 Moderasi, §4.4.7)

**Files:**
- Create: `app/Domain/Moderation/Actions/{SubmitReport,ModerateReview,ResolveReport}.php`, `app/Filament/Resources/{ReviewResource,ReportResource}.php`, `app/Mail/{ReviewModeratedMail,AdminDigestMail}.php`, `app/Console/Commands/SendAdminDigest.php`, halaman `Kontribusimu` (`app/Livewire/MyContributions.php`)
- Test: `tests/Feature/ModerationFlowTest.php`

- [x] Report reasons §10 (termasuk `membuka_identitas`, `info_salah`); ≥3 report unik → auto `pending`; rate limit report 10/hari.
- [x] Filament: antrian moderasi (approve/takedown/ban), **alias tampil default — identitas penuh hanya via aksi "reveal" ber-audit-log** (§10 threat #7); semua aksi admin ter-audit-log; kill switch unpublish seketika (§10 Ops); report `info_salah` diprioritaskan saat override musiman aktif (§F1).
- [x] Email (Resend, `MAIL_MAILER=resend`): hasil moderasi ke penulis; digest harian admin bila ada antrian. TDD `Mail::fake`.
- [x] Prosedur keberatan konten (§10 compliance): halaman "Keberatan atas Konten" + form tanpa akun → review ter-suspend `pending` → keputusan tertulis + banding 1×, semua tercatat. Commit.

---

## Phase 5 (Minggu 8) — SEO, Share Card, PWA, Hardening

### Task 5.1: SEO on-page + sitemap (§10 SEO)

**Files:** Modify `resources/views/cafe/show.blade.php` (meta unik + JSON-LD `LocalBusiness`+`AggregateRating` — angka pakai titik §12.5), Create `app/Http/Controllers/SitemapController.php` + route `/sitemap.xml`, halaman kategori+kota `app/Livewire/CategoryCity.php` route `/{city}/cafe-{category-slug}` · Test: `tests/Feature/SeoTest.php`

- [ ] TDD: JSON-LD valid di detail; sitemap HANYA cafe `active` (§10 governance); canonical; halaman kategori+kota indexable & berisi. Commit.

### Task 5.2: OG share card + Web Share API (§10 SEO, §16)

**Files:** `app/Jobs/GenerateShareCard.php` (Intervention: foto + nama + rating + tag + wordmark "ngafe.space" lowercase §12.0), tombol share (`navigator.share` + fallback copy-link toast "Link kesalin!"), event `share_tap` · Test: `tests/Unit/Jobs/GenerateShareCardTest.php`

- [ ] Job deterministik overwrite (idempotent); regenerate saat rating/foto utama berubah; meta `og:image` menunjuk hasil. Commit.

### Task 5.3: PWA + offline ringan (§10 Ops, §4.6)

**Files:** `public/manifest.webmanifest`, `public/sw.js`, register di layout

- [ ] Cache aset statis + halaman terakhir; offline → "Sinyalnya lagi ngambek" + retry, cache terakhir tetap tampil (§4.6). Installable (Lighthouse PWA pass). Commit.

### Task 5.4: Security headers + CSP + rate limiter global (§10 hardening lapis 3)

**Files:** `app/Http/Middleware/SecurityHeaders.php`, `bootstrap/app.php`, `config/moderation.php` · Test: `tests/Feature/SecurityHeadersTest.php`

- [ ] CSP: `default-src 'self'`; img self + domain CDN R2; script self + nonce (Livewire/Alpine kompatibel). `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` minimal (geolocation self).
- [ ] Named rate limiters terpusat: review 3/jam·10/hari, foto 20/hari, report 10/hari, usul 3/hari·10/bln, login per IP. TDD tiap limiter. Commit.

---

## Phase 6 (Minggu 9–10) — F7/F7b Rekomendasi & Ranking + F8 Usul Cafe

### Task 6.1: Skor kualitas Bayesian (F7b)

**Files:** `app/Domain/Cafe/Support/QualityScore.php` · Test: `tests/Unit/QualityScoreTest.php`

```php
final class QualityScore
{
    public const PRIOR_MEAN = 3.8;   // m
    public const PRIOR_WEIGHT = 5;   // C

    public static function compute(int $count, float $sum): float
    {
        return (self::PRIOR_WEIGHT * self::PRIOR_MEAN + $sum)
             / (self::PRIOR_WEIGHT + $count);
    }
}
```

- [ ] TDD: 1 review bintang 5 → skor ~4,0 (tertarik ke prior, tidak nangkring puncak); 1 bintang 1 → ~3,3 (tidak terkubur); n besar → mendekati rata-rata asli. Integrasi ke `RecomputeCafeAggregates` (Task 3.1). Sorting "terbaik" & tie-break pencarian pakai kolom ini (§F7b). Commit.

### Task 6.2: Trending score nightly + slot eksplorasi (F7b)

**Files:** `app/Console/Commands/ComputeTrendingScores.php` (schedule nightly + ping Healthchecks), modify `HomeSections` query · Test: `tests/Unit/TrendingScoreTest.php`, `tests/Feature/ExplorationSlotTest.php`

- [ ] Rumus §F7b: `(review published 14 hari × 3) + (review_read unik 7 hari × 1)`, decay linear by umur konten (`review_read` unik diambil dari tabel event ringan `review_reads` — diisi endpoint event Task 7.3).
- [ ] Slot eksplorasi: **≥2 dari 10 kartu** tiap seksi = cafe ber-review <10 relevan konteks, rotasi **seeded by tanggal** (stabil sepanjang hari). TDD AC §F7b persis. Commit.

### Task 6.3: Homepage kontekstual penuh (F7)

**Files:** Modify `HomeSections`, `Home` Livewire · Test: `tests/Feature/ContextualHomeTest.php`

- [ ] Aturan §4.5/§F7 (uji dengan `Carbon::setTestNow`, zona WITA): >21.00 → "Masih buka sekarang" paling atas (jam + override diperhitungkan); Sen–Jum 09–16 → "Enak buat kerja hari ini"; wiken → "Buat hopping wiken ini"; kategori di-tap ≥3× → seksi kategori itu (lokal utk anon via cookie, server utk login); seksi "Baru direview minggu ini". Maks 3 seksi per render (§13 Hick). Commit.

### Task 6.4: F8 usul cafe (§4.7, §10 governance)

**Files:**
- Create: `app/Domain/Cafe/Actions/{CreateCafeProposal,ApproveCafe,RejectCafe}.php`, `app/Livewire/ProposeCafe.php`, `resources/js/map-pin.js` (MapLibre + OpenFreeMap), Filament antrian usulan, halaman "Usulanmu"
- Test: `tests/Feature/CafeProposalTest.php`, `tests/Unit/Actions/CreateCafeProposalTest.php`

- [ ] Form 3 langkah §4.7: nama + **pin peta** (koordinat dari pin) → area auto dari koordinat + ≥1 kategori → **wajib ≥1 foto** (tanpa foto = submit mati).
- [ ] Dedup inline: `SearchCafes` radius 150m + pg_trgm → "Mungkin maksudmu ini?" → tap = batal usul, ke halaman cafe. Validasi ulang server.
- [ ] TDD: `pending` tidak publik/tidak di sitemap; rate limit 3/hari·10/bln → `ProposalThrottled`; ≥3 reject beruntun → throttle; approve → publik + notifikasi; reject → alasan tampil di "Usulanmu"; sanitasi nama (trim, kapitalisasi, larang emoji/URL/nomor WA §10). Commit.

---

## Phase 7 (Minggu 11) — Observability, Backup, Analytics, Legal, Pre-launch

### Task 7.1: Observability penuh (§10 v1.4)

**Files:** `composer require sentry/sentry-laravel`, `config/sentry.php` (PII-scrubbing on), `routes/web.php` `/up` (health: DB check), `app/Console/Commands/QueueHeartbeat.php` (tiap 5 mnt → Healthchecks.io), semua scheduled command ping Healthchecks saat sukses; `deploy/supervisor/ngafe-worker.conf`

- [ ] AC operasional §10: alert email → link Sentry ber-`request_id` → akar masalah TANPA SSH. Job gagal final → Sentry. Inbox diam saat sehat. Commit.

### Task 7.2: Backup teruji (§10 Ops)

**Files:** `app/Console/Commands/BackupDatabase.php` (pg_dump → enkripsi `age` → R2 bucket private, retensi 14 hari, ping heartbeat), `docs/runbook/restore.md` (prosedur uji restore bulanan), R2 object versioning ON untuk bucket foto (keputusan §20.12 default) + credential app/backup terpisah

- [ ] Uji restore nyata ke DB kosong → data utuh → catat di runbook. Commit.

### Task 7.3: Analytics Umami + event G0–G5 (§2)

**Files:** `resources/js/analytics.js`, endpoint event first-party `POST /e` (anon-id via **cookie first-party server-set** §2 G4/D7), tabel `review_reads` ringan

- [ ] Event: `review_read` (viewport ≥3 dtk), `form_start`, `review_submit`, `share_tap`, sesi pencarian. IP dianonimkan (potong oktet akhir), retensi 14 hari (§10 threat #6). Verifikasi semua event tercatat di staging (checklist §8.4). Commit.

### Task 7.4: Halaman legal + compliance (§10)

**Files:** `resources/views/legal/{privasi,aturan-review,keberatan-konten,tentang}.blade.php` + routes footer

- [ ] Isi sesuai §10: UU PDP (data minimization, hak akses/hapus, umur 13+), Aturan Review (dasar takedown, larangan foto AI §15), Keberatan Konten (syarat + SLA 3×24 jam + banding 1×). Link `--link` petrol hanya di halaman ini (§12.2). Commit.

### Task 7.5: Pre-launch checklist §8.4 (go/no-go — runbook, bukan kode)

- [ ] Konten: coverage §8.1 (≥80% cafe ber-review; Tamalanrea ≥2/cafe) — kejar via founding reviewer.
- [ ] Keamanan: ZAP baseline; `composer audit` bersih; no-PII hijau; securityheaders.com A; uji akses origin by IP ditolak (setelah Cloudflare aktif); restore teruji.
- [ ] OAuth consent screen terverifikasi (logo+domain, scope `openid email`); PSE Kominfo diajukan via OSS; domain ngafe.space diamankan (cek harga renewal!); SPF/DKIM hijau; UptimeRobot+Sentry+Healthchecks aktif; event analytics terverifikasi.
- [ ] Deploy VPS (runbook `docs/runbook/deploy.md`): SSH key-only, ufw 80/443, Postgres/Redis bind 127.0.0.1, PHP-FPM non-root, Cloudflare proxy Full Strict, origin firewall IP Cloudflare only (§10 lapis 1–2), deploy via GitHub Actions SSH script.

---

## Verification (global, tiap akhir phase + pre-launch)

1. `php artisan test` — seluruh suite hijau, **termasuk no-PII** (`tests/Feature/Api/NoPiiTest.php` + trait di semua test halaman publik).
2. `composer audit` bersih; CI GitHub Actions hijau.
3. Lighthouse (mobile, 4G throttling) di homepage & detail: **LCP < 2,5s, CLS < 0,1**, PWA installable.
4. Uji manual jalur aha: landing → detail → blok review dalam **2 tap**, tanpa login, tanpa interstitial; form review 3 langkah end-to-end dengan login Google sungguhan di staging.
5. Cek visual light+dark terhadap token §12 (tanpa hex liar: `grep -rn '#[0-9A-Fa-f]\{6\}' resources/views app/Livewire` hanya boleh kena file tokens).
6. Checklist §8.4 semua hijau sebelum soft launch.

## Task Dependency Notes

- 1.2→1.3 (index butuh tabel) · 1.4 dipakai semua Action · 2.0 sebelum semua UI · 3.1 sebelum 4.2 (event) · 4.1→4.2→4.3 · 6.1 masuk listener 3.1 · 7.3 sebelum trending penuh 6.2 (butuh `review_reads`; 6.2 boleh jalan duluan dengan komponen review-count saja).
- Kalau timeline meleset >2 minggu: potong dari ekor (slot eksplorasi 6.2b, F8 6.4) — **jangan** dari hardening/legal (§8.3).
