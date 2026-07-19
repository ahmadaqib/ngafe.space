# Audit Implementasi Phase 0–4

Tanggal audit: 2026-07-19
Sumber pembanding: `Docs/Spec.md` v1.4 dan `Docs/plan.md`

## Ringkasan

| Phase | Status | Kesimpulan |
|---|---|---|
| 0 | Selesai secara kode | Scaffold, dependency, build, test, dan konfigurasi dasar tersedia. Verifikasi Postgres lokal/live server adalah bukti historis dan tidak diulang dalam audit sandbox ini. |
| 1 | Selesai dengan verifikasi eksternal tersisa | Domain, skema, OAuth, admin, error handling, no-PII, seeder, dan CI file tersedia. Push/CI remote belum dapat ditandai hijau dari checkout lokal. |
| 2 | Parsial | Jalur baca/search dasar berfungsi, tetapi design system dan beberapa acceptance criteria UI/search sebelumnya salah ditandai selesai. |
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

- Route cafe aktif, no-PII, review visibility, cache server guest, search AND-category, Haversine, area fallback, dan format jarak tersedia.
- Scope review pending pengguna telah diperbaiki agar tidak dapat bocor lintas cafe.
- Halaman autentikasi diberi `private, no-store`; cache server hanya dipakai guest.

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

1. **Phase 2 design system belum sesuai Spec §12/§14.** `tokens.css` baru subset, masih ada hex dan inline style, radius card 16px melanggar batas 8/12/full, Plus Jakarta Sans belum self-host, dark token tidak lengkap, dan sheet belum punya drag/snap.
2. **Belum ada browser E2E nyata.** `ReviewFormFlowTest` menguji komponen Livewire, tetapi belum menjalankan jalur browser dengan JavaScript, localStorage/sessionStorage, OAuth return, kompresi foto, dan IntersectionObserver.
3. **Sentry belum terpasang.** Job sudah memanggil `report()` pada kegagalan final, tetapi SDK dan PII scrubbing baru direncanakan di Phase 7.

### Prioritas sedang

4. Homepage belum menampilkan jarak atau potongan review ≤90 karakter; test format rating dan urutan chip berbasis WITA belum lengkap.
5. Smart empty state search memilih filter pertama yang menghasilkan tambahan, bukan menghitung filter paling membatasi. Riwayat tiga pencarian di localStorage belum ada.
6. Detail cafe baru menampilkan satu foto utama, belum galeri lengkap sesuai urutan dan gesture di Spec. Test `OpeningHours` belum mencakup semua cabang tanpa data dan tutup normal.
7. Pre-prompt lokasi ada, tetapi penanganan callback denial/error belum memberi state/copy khusus; area chip selalu tampil sebagai fallback pasif.
8. Session admin 60 menit masih memakai konfigurasi global. Jika session user publik perlu durasi berbeda, diperlukan middleware/panel guard khusus.
9. Policy belum menjadi satu-satunya pintu otorisasi: beberapa Action masih melakukan ownership/role check manual. Moderation action sudah ber-audit, tetapi CRUD Cafe/Category Filament belum memiliki audit log sehingga frasa "semua aksi admin" belum terpenuhi secara global.
10. Rumus Bayesian sudah berjalan dan diuji di job agregasi Phase 3, tetapi class `QualityScore` dan unit test terpisah yang diminta Task 6.1 belum ada. Plan perlu mempertahankan Task 6.1 sebagai refactor/kontrak terpisah, bukan menghitung ulang fitur yang sama.

### Bukti eksternal/prosedural

11. Push dan hasil GitHub Actions remote belum diverifikasi; checklist Phase 1 tetap terbuka.
12. Checklist "commit per sub-behavior" Phase 4 tidak dipenuhi secara literal karena implementasi ditutup dalam satu commit Phase 4.

## Keputusan audit

- Phase 0 dan 3 dapat dianggap selesai secara implementasi.
- Phase 1 selesai secara lokal, dengan CI remote sebagai bukti eksternal yang masih terbuka.
- Phase 2 tidak boleh dianggap selesai penuh; checkbox yang tidak didukung kode/test telah dibuka kembali di `Docs/plan.md`.
- Phase 4 selesai untuk fitur aplikasi inti. Dua item tetap parsial: browser E2E nyata dan Sentry, sehingga tidak ditandai selesai penuh secara operasional.

## Verifikasi akhir

- `vendor/bin/pint --test`: lulus.
- `php artisan migrate:fresh` dengan SQLite in-memory: seluruh migration lulus.
- `php artisan test`: 65 lulus, 1 skip khusus PostgreSQL, 190 assertions.
- `npm run build`: lulus; warning non-blocking karena package opsional `fontaine` belum dipasang. Build masih mengeluarkan Instrument Sans, yang menguatkan gap Plus Jakarta Sans di Phase 2.
- `php artisan schedule:list`: Hidden Gem 02.00 WITA dan moderation digest 08.00 WITA terdaftar.
- PostgreSQL lokal dan `composer audit` online tidak dapat diverifikasi dari sandbox ini: koneksi `127.0.0.1:5432` ditolak dan DNS Packagist tidak tersedia. CI PostgreSQL 17 tetap terkonfigurasi, tetapi hasil remote belum diklaim hijau.
