# Audit Implementasi Phase 0–4

Tanggal audit: 2026-07-19 (verifikasi awal, dalam sandbox tanpa akses Postgres/GitHub) — lihat **Addendum 2026-07-19** di bagian bawah untuk verifikasi ulang dengan akses penuh (Postgres 17 asli, remote CI, browser nyata) dan perbaikan yang dieksekusi setelahnya.
Sumber pembanding: `Docs/Spec.md` v1.4 dan `Docs/plan.md`

## Ringkasan

| Phase | Status | Kesimpulan |
|---|---|---|
| 0 | Selesai secara kode | Scaffold, dependency, build, test, dan konfigurasi dasar tersedia. Verifikasi Postgres lokal/live server adalah bukti historis dan tidak diulang dalam audit sandbox ini. |
| 1 | Selesai lokal; rerun CI remote tersisa | Bootstrap CI kini memiliki `APP_KEY` deterministik dan test tidak bergantung manifest Vite. Suite dalam kondisi tanpa environment `APP_KEY` lulus lokal; hasil rerun remote tetap perlu dikonfirmasi. |
| 2 | Selesai | Design system, detail cafe, homepage, live-search, smart empty state, riwayat lokal, serta lokasi/fallback area sesuai acceptance criteria dan diuji. |
| 3 | Selesai secara kode | Agregasi rating, Bayesian score, kategori crowd, dan Hidden Gem auto tersedia dan diuji. |
| 4 | Fitur inti selesai; dua bukti/integrasi tersisa | Review anonim, form, foto, report, moderasi, email, kontribusi, dan keberatan konten tersedia. Browser E2E nyata dan integrasi SDK Sentry belum selesai. |

## Yang sudah diverifikasi

### Phase 0

- Laravel berada di root, dependency terkunci, dan aset dapat dibangun.
- Versi aktual adalah Laravel 13.20, Livewire 4.3, Filament 5.7, Pest 4, dan Intervention Image 4.2.
- Test suite dan production asset build dijalankan ulang pada penutupan Phase 4.

### Phase 1

- Public content utama memakai ULID; identitas internal `users` tetap bigint. Ini deviasi dari kalimat "semua model ULID", tetapi tidak mengekspos ID user sebagai ID publik.
- Kolom legacy `name` dan `password` nullable; callback Google membuat user tanpa menyimpan nama/avatar Google.
- OAuth return-to-context, penolakan open redirect, callback gagal, dan login user baru sekarang memiliki test controller.
- Reautentikasi Google tidak lagi mereset `status=banned` atau menurunkan `role=admin`; role/status hanya diinisialisasi untuk akun baru.
- Filament memakai `/ruang-admin`, role gate, dan TOTP wajib.
- `SESSION_LIFETIME=60` saat ini bersifat global, belum middleware khusus panel seperti bunyi plan.
- CI lokal tersedia di `.github/workflows/ci.yml`; status run remote belum diverifikasi.

### Phase 2

- Token primitif/semantik light-dark lengkap, radius hanya 8/12/full, Plus Jakarta Sans variable dibundel lokal, dan sheet mendukung drag/snap 45%/92% serta reduced motion.
- Route cafe aktif, galeri 4:3, jam normal/overnight/override, no-PII, review visibility, arah, CTA persis, cache guest, dan private no-store untuk user terautentikasi tersedia.
- Homepage menjaga aturan cafe wajib memiliki review published, kartu lima chunk, excerpt batas kata, rating locale, dan urutan chip kontekstual WITA.
- Search memakai AND-category, trigram/Haversine, debounce 250ms, smart empty state berdasarkan pertambahan hasil terbesar, riwayat tiga chip lokal, serta state sukses/ditolak/error lokasi dengan fallback area.

### Phase 3

- Agregat menghitung hanya review `published` dan job idempotent.
- Penghapusan akun dengan mode delete sekarang menghitung ulang agregat dan kategori cafe; mode anonymize mempertahankan rating.
- `cafe_category.source='auto'` didokumentasikan untuk Hidden Gem agar tidak menimpa keputusan `admin`/`crowd`.

### Phase 4

- Alias HMAC deterministik per pasangan user/cafe dan tidak memuat ID mentah.
- Submit/edit/delete, duplicate guard, content hash, honeypot, banned-word flag, burst flag, serta limit 3/jam dan 10/hari tersedia.
- Edit review sekarang memakai status-account guard dan rate limit yang sama, sehingga session lama user banned tidak dapat memulihkan review removed.
- Form 3 langkah, draft lokal, login bottom sheet, OAuth exact return, optimistic review card, dan sticky CTA tersedia.
- Foto dikompres di client, diverifikasi magic bytes di server, di-reencode ke WebP 400/1600, metadata dibuang, dan diproses per-file dengan retry/idempotensi.
- Report unik, threshold suspend, prioritas musiman, queue moderasi Filament, reveal identity ber-audit, kill switch foto, Resend mail, digest, kontribusi, serta keberatan/banding satu kali tersedia.
- Endpoint report telah dipindah ke `StoreReportRequest` agar whitelist input konsisten.
- Pengajuan keberatan publik sekarang idempotent, dibatasi 3/hari per hash email, dan verifikasi email banding dibatasi 5/jam per ULID untuk mencegah brute force.

## Belum sesuai atau terlewat

### Prioritas tinggi

1. **Belum ada browser E2E nyata.** `ReviewFormFlowTest` menguji komponen Livewire, tetapi belum menjalankan jalur browser dengan JavaScript, localStorage/sessionStorage, OAuth return, kompresi foto, dan IntersectionObserver.
2. **Sentry belum terpasang.** Job sudah memanggil `report()` pada kegagalan final, tetapi SDK dan PII scrubbing baru direncanakan di Phase 7.

### Prioritas sedang

3. Session admin 60 menit masih memakai konfigurasi global. Jika session user publik perlu durasi berbeda, diperlukan middleware/panel guard khusus.
4. Policy belum menjadi satu-satunya pintu otorisasi: beberapa Action masih melakukan ownership/role check manual. Moderation action sudah ber-audit, tetapi CRUD Cafe/Category Filament belum memiliki audit log sehingga frasa "semua aksi admin" belum terpenuhi secara global.
5. Rumus Bayesian sudah berjalan dan diuji di job agregasi Phase 3, tetapi class `QualityScore` dan unit test terpisah yang diminta Task 6.1 belum ada. Plan perlu mempertahankan Task 6.1 sebagai refactor/kontrak terpisah, bukan menghitung ulang fitur yang sama.

### Bukti eksternal/prosedural

6. Push dan hasil GitHub Actions remote setelah perbaikan bootstrap belum diverifikasi; checklist Phase 1 tetap terbuka.
7. Checklist "commit per sub-behavior" Phase 4 tidak dipenuhi secara literal karena implementasi ditutup dalam satu commit Phase 4.

## Keputusan audit

- Phase 0 dan 3 dapat dianggap selesai secara implementasi.
- Phase 1 selesai secara lokal, dengan CI remote sebagai bukti eksternal yang masih terbuka.
- Phase 2 selesai secara implementasi dan seluruh checkbox acceptance criteria telah ditutup berdasarkan kode serta test lokal.
- Phase 4 selesai untuk fitur aplikasi inti. Dua item tetap parsial: browser E2E nyata dan Sentry, sehingga tidak ditandai selesai penuh secara operasional.

## Verifikasi akhir

- `vendor/bin/pint --test`: lulus setelah formatting.
- `php artisan migrate:fresh` dengan SQLite in-memory: seluruh migration lulus.
- `php artisan test` dengan `APP_KEY` tidak tersedia di environment: 77 lulus, 1 skip khusus PostgreSQL, 249 assertions.
- `npm run build`: lulus dan menghasilkan aset Plus Jakarta Sans variable lokal; tidak ada request font eksternal.
- `php artisan schedule:list`: Hidden Gem 02.00 WITA dan moderation digest 08.00 WITA terdaftar.
- PostgreSQL lokal dan `composer audit` online tidak dapat diverifikasi dari sandbox ini: koneksi `127.0.0.1:5432` ditolak dan DNS Packagist tidak tersedia. CI PostgreSQL 17 tetap terkonfigurasi, tetapi hasil remote belum diklaim hijau.

## Addendum 2026-07-19 — verifikasi ulang dengan akses penuh + perbaikan

Audit di atas ditulis dari sandbox tanpa jaringan/Postgres. Sesi lanjutan hari yang sama punya akses penuh (Postgres 17 lokal asli, remote GitHub via `gh`, Playwright/Chromium) dan dipakai untuk memverifikasi ulang tiap klaim serta menutup gap yang masih terbuka.

**Diverifikasi ulang, terkonfirmasi:**
- `git fetch` + `gh run list`: commit terakhir (`fcbc506`) sudah di remote dan CI run-nya **success** — item "push/CI remote belum diverifikasi" (poin 6 di atas) selesai.
- `php artisan migrate:fresh --force` dan `php artisan test` dijalankan terhadap **Postgres 17 asli** (bukan SQLite): seluruh migration lulus, suite default 77/78 lulus (1 skip by design karena `TrgmSearchTest` sengaja skip di luar driver pgsql — dikonfirmasi eksplisit lulus dengan `DB_CONNECTION=pgsql`).
- `composer audit`: bersih.

**Gap yang ditutup (bukan cuma dicatat):**
1. **Browser E2E nyata (prioritas tinggi, poin 1).** Terpasang `pestphp/pest-plugin-browser` + Playwright Chromium. Tiga test nyata di `tests/Browser/AhaAndReviewFormTest.php`: (a) tamu mencapai review published dalam 2 tap tanpa gate login, (b) sheet login Alpine benar-benar terbuka di browser saat tamu menekan "Tulis review", (c) user login menyelesaikan progres 3 langkah form review (radio rating → textarea cerita → step foto) lewat Livewire sungguhan. Dijalankan terpisah dari suite default (`composer test:browser` / `vendor/bin/pest tests/Browser`, tidak didaftarkan sebagai PHPUnit testsuite karena butuh binary Chromium) dan sebagai step CI tersendiri (`.github/workflows/ci.yml`, setelah `npm run build`). Login di browser test memakai rute testing-only `routes/testing.php` (`/_testing/login/{user}`, hanya terdaftar saat `app()->environment('testing')`) karena OAuth Google sungguhan tidak realistis dijalankan headless. Item localStorage-draft-lintas-reload tetap belum ada test browser eksplisit (di luar API publik plugin saat ini) — didokumentasikan di `Docs/plan.md` Task 4.3, bukan diklaim selesai.
2. **Sesi admin masih global (prioritas sedang, poin 3).** `SESSION_LIFETIME=60` yang sebelumnya berlaku ke seluruh app kini dikembalikan ke `120` (default publik), dan sesi pendek 30 menit di-scope betul-betul ke panel lewat `App\Http\Middleware\ShortenAdminSessionLifetime` (dipasang sebelum `StartSession` di `AdminPanelProvider`). Diuji `PanelAccessTest::test_admin_panel_requests_shorten_the_session_lifetime_without_affecting_the_public_app`.
3. **Audit log CRUD Cafe/Category (prioritas sedang, poin 4 — separuh).** `App\Filament\Concerns\LogsAdminAudit` (dipakai `CreateCafe`/`EditCafe`/`CreateCategory`/`EditCategory`) + `DeleteAction::after()` menulis ke `moderation_audit_logs` yang sama dipakai flow moderasi, sehingga "audit log semua aksi admin" (§10) sekarang juga mencakup CRUD biasa, bukan cuma aksi moderasi. Diuji `tests/Feature/Admin/CrudAuditLogTest.php` (create/update/delete Cafe dan Category, masing-masing diverifikasi baris audit log-nya). Catatan: policy masih belum jadi *satu-satunya* pintu otorisasi di semua Action (sisa dari poin 4) — di luar scope perbaikan hari ini.

**Sengaja tidak dikerjakan (di luar scope Phase 0–4):**
- **Sentry SDK (prioritas tinggi, poin 2).** Instalasi SDK + PII scrubbing memang dijadwalkan Task 7.1 (`Docs/plan.md` Phase 7), bukan Phase 0–4 — job sudah memanggil `report()` sehingga siap dihubungkan begitu SDK terpasang.
- **Class `QualityScore` terpisah (poin 5).** Rumus Bayesian sudah berjalan dan diuji inline di job agregasi Phase 3; ekstraksi ke `app/Domain/Cafe/Support/QualityScore.php` + unit test terpisah tetap Task 6.1 (Phase 6) sesuai `Docs/plan.md`, bukan gap Phase 0–4.
- **"Commit per sub-behavior" Phase 4 (poin 7).** Riwayat git tidak diubah retroaktif (menulis ulang history yang sudah di-push berisiko tinggi dan tidak diminta) — dicatat sebagai penyimpangan proses yang diterima, bukan diperbaiki.

**Verifikasi akhir addendum:** `php artisan migrate:fresh --force` (Postgres 17) lulus · `php artisan test` 80/81 lulus (1 skip by design) di SQLite + dikonfirmasi ulang di Postgres asli · `vendor/bin/pest tests/Browser` 3/3 lulus (Chromium nyata) · `vendor/bin/pint --test` lulus · `composer audit` bersih · `npm run build` bersih · `gh run list` menunjukkan CI remote terbaru **success**.
