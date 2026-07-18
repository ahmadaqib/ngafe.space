# PRD — Ngafe (working title): Platform Review & Rekomendasi Cafe — MVP Makassar, arsitektur multi-kota

> Versi: **1.4** · Kota pertama: Makassar (nama, copy, dan data model netral kota — siap scale) · Format: heading English, isi Bahasa Indonesia · Dokumen untuk dieksekusi solo developer
>
> **Changelog v1.4 (dari v1.3):** subsection baru **Logging, Error Handling & Observability** di section 10 — prinsip "diam saat sehat, berisik saat sakit": satu pintu exception handler + exception domain ber-`userMessage()`, aturan log terstruktur ber-`request_id` (dan daftar yang DILARANG masuk log), tiga kanal alert ke satu email (Sentry · UptimeRobot · heartbeat cron via Healthchecks.io), aturan job queue (retry-backoff, idempotent, failed_jobs), health endpoint `/up`, dan definisi operasional "mudah" — diagnosis dari email alert tanpa SSH.
>
> **Changelog v1.3 (dari v1.2):** domain final **ngafe.space** masuk checklist brand (8.4) · konsep brand ".space" = _ruang/third place_, eksplisit BUKAN tema kosmik (12.0) · **aksen dingin petrol** ditambahkan ke sistem token — primitif, semantik, dan aturan proporsi 60-30-10 (12.1–12.2) · konvensi formatting locale Indonesia — angka, jarak, harga, waktu, ikon (12.5) · DON'Ts diperkuat: tema luar angkasa, gambar AI-generated, dark pattern, campur set ikon, scroll-entrance animation, maskot/persona "min-kak", quip berulang (15) · DOs baru: Web Share API, sticky CTA review, riwayat pencarian, rasio foto tetap, rotasi microcopy (16) · subsection **Application Architecture** — modular monolith pragmatis dengan aturan dependensi & testing pyramid (9).
>
> **Changelog v1.2 (dari v1.1):** perbaikan definisi metrik G3 (denominator pembaca, bukan user login) · metrik lantai volume G0 · section baru **Launch Readiness & Distribution Plan** (matematika cold start, review coverage, ops kanal WA, timeline build 12 minggu) · compliance Indonesia (PSE Kominfo + prosedur keberatan konten/UU ITE) · infra email transaksional masuk stack · algoritma ranking & anti rich-get-richer didefinisikan · jam buka musiman/Ramadan · audit data proaktif (data decay) · backup foto R2 · verifikasi OAuth consent screen Google · cookie D7 server-set · pg_trgm untuk live-search · aturan agregasi rating · **section Design Tokens lengkap (tokenisasi warna primitif → semantik → komponen, light/dark)** · cek merek/kompetitor masuk pre-launch. Brand name tetap working title (belum final — lihat section 20).

---

## A. Product

### 1. Overview & Problem Statement

**Produk:** Web app mobile-first untuk mencari, membaca, dan menulis review cafe di Makassar — untuk semua yang hidupnya lewat cafe: nugas, WFC (work from cafe), nongkrong, sampai hunting spot baru tiap wiken. Kebutuhan nyata yang dijawab: wifi, colokan, harga, vibe, kenyamanan duduk lama, dan "tempat baru apa yang worth it".

**Masalah:** Anak muda Makassar — mahasiswa (Unhas, UNM, UIN Alauddin), first-jobber & freelancer yang kerja dari cafe, dan cafe hopper yang tiap wiken cari tempat baru — mencari cafe lewat jalur yang tersebar dan bias: TikTok (konten endorse/settingan), Google Maps (rating inflasi 4.8 semua, review generik "tempatnya bagus"), dan mulut ke mulut (lambat, terbatas circle). Tidak ada satu tempat yang menjawab pertanyaan spesifik: _"cafe mana yang wifinya kencang, colokannya banyak, dan nggak diusir kalau duduk 4 jam cuma pesan es kopi susu?"_ — atau versi wiken-nya: _"cafe baru yang di IG kelihatan bagus itu, aslinya gimana?"_

**Biaya tidak menyelesaikan:** User buang 15–30 menit riset per keputusan, sering salah pilih (datang → penuh / wifi mati / mahal), dan cafe bagus yang tidak main TikTok tidak pernah ditemukan.

**Solusi MVP:** Direktori cafe Makassar yang bisa dicari berdasarkan lokasi + kategori kebutuhan, dengan review anonim yang jujur (anonim di UI, terikat akun di backend untuk anti-abuse), rating, dan foto asli dari user — bisa diakses penuh tanpa login.

**Aha moment yang dioptimalkan:** user pertama kali datang → **berhasil membaca minimal satu review penuh** sebelum menutup app. Semua keputusan arsitektur halaman (section 4) melayani jalur tercepat ke momen ini. **Konsekuensi operasional (baru di v1.2):** karena aha moment butuh review yang benar-benar ada, _review coverage_ adalah launch criteria eksplisit — lihat section 8.

---

### 2. Goals & Success Metrics

Semua metrik non-vanity, terukur via analytics event sederhana (Umami self-host — gratis).

| #      | Metrik                                                      | Definisi pengukuran                                                                                                                                                                                        | Target 30 hari | Target 90 hari |
| ------ | ----------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------- | -------------- |
| **G0** | **Volume floor (gerbang validitas)**                        | Visitor unik per minggu (anon-id, cookie first-party server-set)                                                                                                                                           | ≥ 500/minggu   | ≥ 1.500/minggu |
| G1     | **Read-through rate**                                       | % visitor unik yang membuka ≥1 halaman detail cafe DAN scroll sampai blok review (event `review_read`, elemen di viewport ≥3 detik)                                                                        | ≥ 55%          | ≥ 70%          |
| G2     | **Time-to-first-review-read**                               | Median detik dari landing sampai event `review_read` pertama                                                                                                                                               | ≤ 45 detik     | ≤ 30 detik     |
| G3     | **Reader→Reviewer conversion** _(definisi diperbaiki v1.2)_ | % **pembaca** (visitor unik yang mencapai `review_read`) yang menulis ≥1 review published dalam 14 hari sejak baca pertama                                                                                 | ≥ 2%           | ≥ 4%           |
| G4     | **D7 return rate**                                          | % visitor yang kembali dalam 7 hari — diukur via **cookie first-party yang di-set server** (bukan localStorage/JS; Safari ITP memangkas storage yang di-set script jadi 7 hari, tepat di batas metrik ini) | ≥ 15%          | ≥ 25%          |
| G5     | **Search success rate**                                     | % sesi pencarian (sesi = rangkaian query/filter dengan jeda <5 menit) yang berakhir dengan tap ke detail cafe                                                                                              | ≥ 60%          | ≥ 75%          |

**Catatan definisi G3 (kenapa diubah):** di v1.1 denominatornya "user login" — padahal login _hanya_ terpicu saat user menekan aksi tulis, sehingga hampir semua user login pasti menulis dan metrik jadi tautologis (~90%+, hanya mengukur completion rate form). Denominator pembaca membuat G3 mengukur hal yang sebenarnya: apakah pembaca terkonversi jadi kontributor. Completion rate form review tetap dipantau terpisah sebagai metrik funnel sekunder (`form_start` → `review_submit`, target ≥ 60%).

**Catatan G0:** G0 bukan vanity — semua G1–G5 adalah rasio; rasio dari 30 visitor tidak membuktikan apa pun. G0 adalah syarat validitas statistik: keputusan produk berdasarkan G1–G5 hanya boleh diambil jika G0 terpenuhi pada periode yang sama.

**Bukan metrik sukses (vanity, jangan dipakai untuk keputusan):** total pageview, jumlah registrasi, follower sosmed.

**Catatan segmen:** G1–G5 diukur agregat DAN dipecah per konteks akses (weekday jam kerja vs wiken) — proxy murah untuk melihat apakah segmen WFC/hopper ikut hidup.

**Guardrail metrics:**

- Rasio review di-report/di-takedown < 5% dari total review — kalau lebih, mekanisme anonim sedang disalahgunakan (section 18).
- **Review coverage** (baru): % cafe `active` yang punya ≥1 review published — tidak boleh turun di bawah 70% pasca-launch (cafe baru masuk lebih cepat dari review = direktori terasa kosong lagi).

---

### 3. Segmentation & Personas

**Strategi segmen: wedge sempit, produk lebar.** Produk dirancang untuk seluruh spektrum "orang yang hidupnya lewat cafe" (Gen Z + Milenial, dengan Gen Alpha sebagai gelombang berikutnya) — tapi **akuisisi awal menusuk lewat mahasiswa** karena kanalnya paling murah dan padat (grup WA kampus, satu klaster geografis). Kebutuhan fungsional sama (wifi, colokan, harga, vibe, betah lama); yang beda hanya konteks pemakaian — melebarkan segmen TIDAK menambah fitur MVP, hanya melebarkan taksonomi kategori, konteks rekomendasi, dan nada copy.

| Segmen                                                         | Siapa (usia ±)                                                                     | Kebutuhan khas                                                               | Peran di produk                                                                                                                                                               |
| -------------------------------------------------------------- | ---------------------------------------------------------------------------------- | ---------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Primer — Mahasiswa** (Gen Z, 18–24)                          | Anak kampus, nugas & nongkrong                                                     | Murah, betah lama, buka malam/24 jam                                         | Motor trafik & konten awal; wedge akuisisi                                                                                                                                    |
| **Sekunder — Cafe hopper & WFC** (Gen Z akhir–Milenial, 23–35) | First-jobber, freelancer, remote worker, pasangan muda, konten kreator             | Wifi stabil jam kerja, tenang, meeting-able; wiken: hidden gem & tempat baru | Daya beli lebih tinggi, review lebih kaya & berfoto bagus, penemu cafe baru (bahan F8)                                                                                        |
| **Emerging — Gen Alpha** (13–17)                               | Nongkrong pulang sekolah se-circle, visual-first, keputusan grup via share link WA | Harga super jelas, foto dominan, minim baca                                  | Dilayani otomatis oleh desain mobile-first & share card — tanpa fitur khusus anak di MVP; batas umur akun 13+ di ToS (kebijakan akun Google + kehati-hatian data anak UU PDP) |

#### Persona 1 (primer) — **Nisa, 20, mahasiswi Ilmu Komunikasi Unhas, semester 5.**

- **Konteks perilaku:** kos di Tamalanrea, mobile-only (Android mid-range, kuota 4G). Nugas kelompok 2–3x/minggu, sore–malam. Budget nongkrong Rp 25–50rb sekali datang.
- **Cara cari cafe sekarang:** search TikTok "cafe nugas makassar", screenshot 3–4 kandidat, cek Google Maps buat jam buka & jarak, tanya grup WA. Total ±20 menit, sering tetap zonk.
- **Frustrasi utama:** (1) konten TikTok ternyata endorse, (2) review Google Maps tidak menjawab "bisa nugas lama nggak?", (3) datang jauh-jauh ternyata full/colokan cuma 2.
- **Yang dia butuhkan:** jawaban cepat "malam ini enaknya ke mana yang dekat, murah, bisa duduk lama" — dalam <1 menit, tanpa bikin akun dulu.
- **Trigger balik lagi:** app "ngerti" konteksnya — buka jam 8 malam → yang muncul duluan cafe yang masih buka & cocok nugas.
- **Jakob's Law anchor:** feed IG/TikTok (scroll kartu bergambar), bottom sheet Gojek/Shopee, "lanjut dengan Google" sekali tap.

#### Persona 2 (sekunder) — **Raka, 26, UI designer remote-first, tinggal di Hertasning.**

- **Konteks perilaku:** kerja dari cafe 3x/minggu jam 09–16 (wifi kuat video call, colokan, kursi nyaman, suasana meeting-able). Wiken jadi **cafe hopper**: hunting tempat baru, foto buat IG story.
- **Frustrasi utama:** (1) cafe "aesthetic" versi IG sering tidak nyaman untuk kerja, (2) info cafe baru cuma dari akun foodies endorse, (3) datang ke cafe hasil hunting → antre panjang karena semua orang lihat konten yang sama.
- **Yang dia butuhkan:** weekday → filter `Cocok nugas & WFC` yang jujur soal wifi & keramaian; wiken → seksi `Hidden gem / baru buka` yang reviewnya dari pengunjung asli.
- **Nilai bagi platform:** review lebih panjang & foto bagus (hero content), penemu cafe baru (kontributor F8), daya beli lebih tinggi (relevan Fase 4).

#### Persona 3 (emerging, dilayani pasif) — **Zahra, 15, kelas X SMA.**

- Nongkrong pulang sekolah se-circle 4–6 orang, budget patungan, keputusan grup via **share link grup WA** — konsumen utama share card OG (section 11). Hampir tidak membaca paragraf: foto → harga → rating → putuskan.
- **Implikasi desain (bukan fitur baru):** indikator harga terlihat di kartu tanpa masuk detail; foto & rating "berbicara" tanpa teks. Bukan target akuisisi aktif MVP.

---

### 4. User Flow (lengkap, step-by-step)

> Prinsip lintas-flow: **login TIDAK PERNAH jadi gerbang untuk membaca**. Login hanya dipicu aksi menulis (review/rating/foto).

#### 4.1 First visit → browse → baca review (jalur aha moment, tanpa login)

1. User landing di homepage (share link WA / organik Google).
2. Above the fold langsung tampil: search bar + chip kategori (maks 6 tampil, sisanya di "Lainnya") + grid kartu cafe "Lagi rame dibahas" (algoritma: section 5, F7b) — **tanpa splash screen, onboarding carousel, atau popup login**.
3. Kartu cafe menampilkan: foto user (bukan foto owner), nama, rating, 2 tag kategori, jarak (jika izin lokasi ada), potongan 1 kalimat review terbaru → potongan review = _hook_ menuju aha moment.
4. Tap kartu → halaman detail cafe. Urutan konten: foto (swipeable), nama + rating + status buka/tutup, tag kategori, **blok review terlihat maks 1x swipe**.
5. User membaca review → event `review_read` tercatat. **Aha moment tercapai.**
6. Di akhir daftar review, CTA lembut: "Pernah ke sini? Ceritakan versimu" → baru di sini pintu login (flow 4.3).

**Aturan keras:** langkah 1→5 maksimal 2 tap. Tidak ada interstitial apa pun di jalur ini. **Cafe tanpa review tidak boleh tampil di seksi homepage** (boleh muncul di hasil pencarian dengan CTA "jadi yang pertama") — jalur aha tidak boleh berujung halaman kosong.

#### 4.2 Cari cafe by lokasi & filter kategori

1. Tap search bar ATAU tombol "Dekat sini".
2. **Cabang izin lokasi:**
   - a. Belum pernah diminta → penjelasan dulu ("Biar bisa urutkan dari yang paling dekat") → baru browser permission prompt.
   - b. Izin diberikan → hasil urut by jarak, list (default) + toggle peta.
   - c. Izin ditolak → **fallback area manual**: chip area Makassar (Tamalanrea, Panakkukang, Losari/Pantai, Antang, Hertasning, Daya, Sekitar Unhas, Sekitar UNM/UIN…). Tidak boleh dead-end.
3. Filter kategori multi-select (AND): mis. "Cocok nugas" + "Buka 24 jam" + "Murah".
4. Hasil update tanpa reload penuh; jumlah hasil tampil ("12 cafe cocok").
5. Tap hasil → detail cafe (flow 4.1 langkah 4).

**Edge — hasil kosong:** (a) saran melonggarkan filter dengan menunjukkan filter paling membatasi ("Coba lepas 'Buka 24 jam' — ada 8 cafe lain"), (b) cafe terdekat di luar filter, (c) CTA "Tau cafe yang belum ada di sini? Usulkan!" (flow 4.7).

#### 4.3 Login Google SSO

**Trigger point (satu-satunya):** aksi tulis ("Tulis review", "Kasih rating", "Upload foto") saat belum login. Tidak ada tombol "Daftar" mandiri di navigasi.

**Happy path:**

1. Bottom sheet (bukan redirect halaman penuh): "Login sebentar biar reviewmu tersimpan. Nama aslimu nggak akan tampil — review tetap anonim." + "Lanjut dengan Google".
2. Tap → OAuth Google → pilih akun → callback.
3. Akun dibuat/di-match by Google `sub` (bukan email — email bisa berubah).
4. Kembali PERSIS ke konteks sebelumnya: form review Cafe X langsung terbuka. **State niat user tidak boleh hilang** (intent di sessionStorage sebelum redirect).

**Cancel path:** popup ditutup / back → kembali ke halaman cafe tanpa perubahan, tanpa error menakutkan. Bottom sheet bisa dipicu ulang.

**Error path:** OAuth gagal → toast "Yah, gagal nyambung ke Google. Coba lagi ya" + retry. Log ke Sentry. Jangan tampilkan kode error mentah.

**Catatan pre-launch (baru v1.2):** sebelum launch publik, **OAuth consent screen Google harus diverifikasi** (logo + domain terkonfirmasi di Google Cloud Console) — tanpa ini user melihat layar "unverified app" yang menakutkan tepat di momen konversi. Scope hanya `openid email` sehingga proses verifikasi ringan. Masuk checklist section 8.4.

#### 4.4 Tulis review anonim + rating + upload foto

1. (Pra-syarat: login.) Form review = halaman penuh di mobile dengan progress indicator 3 langkah: **Rating → Cerita → Foto (opsional)**.
2. **Langkah 1 — Rating:** tap bintang 1–5 (wajib) + quick-tag opsional ("wifi kencang", "colokan banyak", dst — sinyal kategori crowdsourced).
3. **Langkah 2 — Cerita:** textarea, minimal 30 karakter, placeholder memancing spesifik: "Wifinya gimana? Betah berapa jam? Habis berapa?"
4. **Langkah 3 — Foto (opsional, maks 4):** galeri/kamera → kompres di client → preview + hapus. Upload gagal per-foto → tandai gagal + retry; **teks review tidak ikut hilang** (draft di localStorage sampai submit sukses).
5. Submit → validasi server (rate limit, 1 review/user/cafe, tipe & ukuran file) → sukses.
6. **Peak-End moment:** layar sukses dengan personality ("Reviewmu tayang! 🎉 Kamu barusan bantu orang lain nggak salah pilih tempat") + review langsung tampil (optimistic) + tawaran satu tap: "Mau review cafe lain yang pernah kamu datangi?"
7. Kena auto-flag (section 10): tetap tampil ke penulisnya dengan badge "sedang ditinjau", tidak tampil publik sampai lolos moderasi. Hasil moderasi dinotifikasi via **email transaksional** (section 9 — infra email) + status di halaman "Kontribusimu".

**Edge:** sudah pernah review cafe ini → tombol jadi "Edit reviewmu"; submit kedua = update, bukan duplikat.

#### 4.5 Returning user → rekomendasi

1. User kembali (login ataupun tidak — anon-id cukup).
2. Homepage kontekstual, bukan statis:
   - **Waktu × segmen:** Senin–Jumat 09:00–16:00 WITA → "Enak buat kerja hari ini"; setelah 21:00 → "Masih buka sekarang"; Sabtu–Minggu pagi–sore → "Buat hopping wiken ini" (`Hidden gem / baru buka` + `Aesthetic`).
   - **Lokasi:** izin ada → "Dekat kamu".
   - **Riwayat ringan:** kategori paling sering di-tap (lokal untuk anon, server untuk login) memengaruhi urutan seksi.
3. Seksi "Baru direview minggu ini" memberi alasan kembali (konten segar = retention driver).
4. MVP TIDAK memakai ML — rekomendasi = aturan sort/filter kontekstual + algoritma ranking F7b.

#### 4.6 Edge cases lintas-flow (rangkuman)

| Kasus                          | Perilaku                                                                             |
| ------------------------------ | ------------------------------------------------------------------------------------ |
| Izin lokasi ditolak            | Fallback chip area manual (4.2c), tidak pernah blokir                                |
| Hasil pencarian kosong         | Saran pelonggaran filter + usul cafe                                                 |
| Upload foto gagal              | Retry per-foto, teks review aman di draft                                            |
| Review kena moderasi           | Tampil ke penulis dengan status; hasil dinotifikasi email                            |
| Offline / koneksi putus        | Skeleton → "Sinyalnya lagi ngambek" + retry; cache terakhir tetap tampil             |
| Cafe tutup permanen            | Badge "Tutup permanen" (dari report user), tetap indexable tapi turun dari pencarian |
| Jam buka musiman (Ramadan dll) | `opening_hours_override` per rentang tanggal + banner otomatis (section 5, F1)       |

#### 4.7 Usul cafe baru oleh user (kontribusi terkontrol — anti data sampah)

1. Entry point: CTA hasil kosong (4.2) atau menu "Usulkan cafe" di profil. **Wajib login** — tidak ada penambahan cafe anonim.
2. Form 3 langkah:
   - **Langkah 1 — Nama & lokasi:** ketik nama + **taruh pin di peta** (koordinat dari pin, bukan alamat ketikan bebas).
   - **Langkah 2 — Detail:** area terisi otomatis dari koordinat, pilih ≥1 kategori, jam buka & kisaran harga opsional.
   - **Langkah 3 — Bukti:** **wajib ≥1 foto asli tempat** — gate murah penyaring usulan asal-asalan dan tempat fiktif.
3. **Dedup check inline:** saat nama diketik dan pin ditaruh, cek cafe existing radius 150m + kemiripan nama (pg_trgm) → "Mungkin maksudmu ini?". Tap kandidat → batal usul, langsung ke halaman cafe itu.
4. Submit → status `pending` — **tidak tampil publik & tidak indexable** sampai admin approve. Layar sukses: "Makasih! Kami cek dulu biar datanya rapi — biasanya 1×24 jam."
5. Approve → tampil publik + status berubah di "Usulanmu". Reject → alasan singkat tampil di halaman sama.
6. Rate limit: 3 usulan/hari, 10/bulan per user; ≥3 reject beruntun → throttle dari fitur usul.

---

### 5. Fitur MVP (Scope) — dengan prioritas implementasi

**Urutan implementasi & reasoning:** urutan di bawah = urutan build. Prinsip: bangun jalur _baca_ dulu sampai sempurna (aha moment), baru jalur _tulis_.

#### F1 — Direktori & halaman detail cafe _(Prioritas 1)_

Fondasi semua fitur; halaman detail = tempat aha moment terjadi + unit SEO utama.

- **AC:**
  - Given visitor tanpa login, When membuka URL detail cafe, Then seluruh konten terbaca penuh tanpa gate apa pun.
  - Given halaman dibuka di 4G, When dimuat, Then LCP < 2.5s dan blok review terlihat dalam ≤1 swipe.
  - Given cafe punya data lokasi, When halaman dibuka, Then jam buka + status buka/tutup saat ini + link arah (deep link app peta) tampil.
  - **(Baru v1.2 — jam musiman)** Given ada `opening_hours_override` aktif untuk tanggal hari ini (mis. jam Ramadan), When status buka/tutup dihitung, Then override yang dipakai DAN banner kecil tampil: "Jam khusus [nama periode] — bisa beda dari biasanya. Salah? Kabari kami". Admin bisa set override massal per rentang tanggal via Filament (satu form untuk semua cafe / per cafe). Report `info_salah` diprioritaskan di antrian selama periode override aktif.

#### F2 — Lihat rating cafe _(Prioritas 2, bareng F1)_

- **AC:**
  - Given cafe punya ≥1 rating published, When kartu/halaman tampil, Then rata-rata rating (1 desimal) + jumlah review tampil.
  - Given cafe belum punya rating, When kartu tampil, Then "Belum ada review — jadi yang pertama?" (bukan "0.0").
  - **(Baru v1.2 — aturan agregasi)** Given review berstatus `pending`/`removed`, When agregat dihitung, Then review tsb TIDAK ikut dihitung; agregat (avg + count) disimpan denormalized di kolom `cafes.rating_avg`/`rating_count` dan di-recompute setiap ada perubahan status review (observer/event) — bukan dihitung on-the-fly per request.

#### F3 — Cari berdasarkan lokasi _(Prioritas 3)_

- **AC:**
  - Given izin lokasi diberikan, When membuka pencarian, Then hasil terurut by jarak dengan label jarak ("1,2 km"). Jarak = Haversine di SQL dari koordinat tersimpan — tanpa API call eksternal.
  - Given izin ditolak, When membuka pencarian, Then chip area Makassar tampil dan hasil bisa difilter per area.
  - Given user mengetik ≥2 karakter, Then hasil live-search muncul <500ms (debounced 250ms). **(Baru v1.2)** Implementasi: index **pg_trgm (GIN)** di `cafes.name` + Postgres FTS di kolom pencarian — index yang sama dipakai dedup F8; jangan bangun dua mekanisme berbeda.

#### F4 — Kategori cafe _(Prioritas 4)_

**Taksonomi MVP (12, lintas segmen — berbasis kebutuhan fungsional):**
`Cocok nugas & WFC` · `Wifi kencang` · `Banyak colokan` · `Buka 24 jam` · `Ramah kantong` · `Aesthetic` · `Tenang` · `Rame/nongkrong` · `Hidden gem / baru buka` · `Outdoor/smoking area` · `Ramah keluarga` · `Musala & parkir gampang`

- Maks 6 chip tampil default (Hick's Law); urutan chip kontekstual: weekday jam kerja → `Cocok nugas & WFC` duluan; wiken → `Hidden gem / baru buka` & `Aesthetic` duluan.
- `Hidden gem / baru buka` auto-assign untuk cafe berumur <90 hari di platform ATAU ber-review <10.
- Kategori di-assign saat seeding (admin) + diperkuat quick-tag review (F5). Tag dipilih ≥30% reviewer sebuah cafe → otomatis jadi kategori tampil.
- **AC:**
  - Given user memilih >1 kategori, When filter diterapkan, Then hasil = irisan (AND) dan jumlah hasil tampil.
  - Given filter menghasilkan 0 cafe, Then saran pelonggaran filter tampil (bukan layar kosong).

#### F5 — Review anonim + rating _(Prioritas 5)_

Mesin diferensiasi produk. Anonim **di UI saja** — backend selalu terikat `user_id` (reasoning section 10).

- **AC:**
  - Given user login, When submit review valid (rating + ≥30 karakter), Then review tampil dengan nama samaran ("Mahasiswa Tamalanrea", "Cafe Hopper Panakkukang", "Penikmat Senja" — pool alias lintas segmen, generated, konsisten per user per cafe) dan tanpa jejak identitas Google di API response mana pun.
  - Given sudah pernah review cafe tsb, When membuka form lagi, Then mode edit — tidak bisa membuat review kedua.
  - Given belum login, When menekan "Tulis review", Then flow 4.3 terpicu dan setelah sukses kembali ke form dengan konteks utuh.
  - Given submit >3 review dalam 1 jam, When submit ke-4, Then ditolak dengan pesan rate limit santai.

#### F6 — Upload foto oleh user _(Prioritas 6)_

Foto asli user = hero content + bukti kejujuran.

- **AC:**
  - Given file non-gambar atau >10MB, When memilih file, Then ditolak di client dengan pesan jelas sebelum upload.
  - Given foto valid, When upload, Then client mengompres (maks sisi 1600px, target ≤300KB WebP) sebelum kirim; server validasi ulang MIME dari byte + strip EXIF/GPS.
  - Given salah satu foto gagal, When submit review, Then teks review tetap tersimpan dan foto gagal bisa retry.

#### F7 — Rekomendasi kontekstual homepage _(Prioritas 7)_

Rule-based (waktu + lokasi + kategori favorit), bukan ML. Dibangun setelah jalur baca-tulis stabil.

- **AC:**
  - Given waktu akses >21:00 WITA, When homepage dimuat, Then seksi "Masih buka sekarang" paling atas, hanya berisi cafe yang jam bukanya cocok (termasuk override musiman).
  - Given user pernah tap kategori X ≥3 kali, Then satu seksi berbasis kategori X tampil.

#### F7b — Algoritma ranking & anti rich-get-richer _(baru v1.2 — bagian dari F7, tapi rumusnya dipakai sejak F1)_

Tanpa desain eksplisit, direktori kena _rich-get-richer_: cafe ber-review banyak makin naik, cafe baru tenggelam — padahal `Hidden gem` adalah selling point segmen hopper.

- **Skor kualitas (untuk sorting "terbaik"):** Bayesian average — `(C×m + Σrating) / (C + n)` dengan prior `m = 3.8`, bobot prior `C = 5`. Cafe ber-review sedikit tertarik ke rata-rata, tidak bisa nangkring di puncak dengan satu review bintang 5 (dan tidak terkubur oleh satu bintang 1).
- **Skor "Lagi rame dibahas" (homepage default):** `(review published 14 hari terakhir × 3) + (review_read unik 7 hari terakhir × 1)`, decay linear by umur konten. Dihitung nightly via cron, disimpan di kolom `cafes.trending_score`.
- **Slot eksplorasi (anti rich-get-richer):** minimal **2 dari 10 kartu** di seksi homepage mana pun dialokasikan untuk cafe ber-review <10 yang relevan konteks (rotasi acak per hari, seeded by tanggal agar stabil sepanjang hari). Cafe baru selalu punya jalan untuk ditemukan.
- **Sorting hasil pencarian:** default = jarak (kalau izin lokasi ada) → tie-break skor kualitas; tanpa lokasi = skor kualitas → tie-break jumlah review.
- **AC:** Given seksi homepage berisi 10 kartu, When dirender, Then ≥2 kartu berasal dari cafe ber-review <10 (selama stok cafe tsb ada di konteks/area yang sama).

#### F8 — Usul cafe oleh user _(Prioritas 8)_

Mesin long-tail direktori pasca-seeding, dengan gate kualitas (flow 4.7 + governance section 10).

- **AC:**
  - Given user login, When submit usulan lengkap (nama, pin peta, ≥1 kategori, ≥1 foto), Then status `pending`, tidak tampil di pencarian/sitemap sampai approve.
  - Given ada cafe existing radius 150m dengan nama mirip, When mengisi form, Then kandidat duplikat tampil sebelum submit bisa ditekan.
  - Given sudah submit 3 usulan hari ini, When submit ke-4, Then ditolak dengan pesan santai.
  - Given usulan di-reject, When membuka "Usulanmu", Then alasan reject terlihat.

---

### 6. Out of Scope MVP (Non-Goals)

| Tidak dibangun                                       | Kenapa                                                                                                                                                         |
| ---------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Booking meja / reservasi                             | Butuh buy-in cafe & operasional; bukan masalah inti user                                                                                                       |
| Chat / DM / komentar antar-user                      | Beban moderasi besar, bukan jalur aha moment                                                                                                                   |
| Monetisasi (ads, cafe premium, endorse)              | Merusak positioning "jujur" sebelum trust terbentuk                                                                                                            |
| App native iOS/Android                               | PWA/web mobile cukup; distribusi via link lebih cepat untuk kampus                                                                                             |
| Gamifikasi penuh (poin, leaderboard, badge kompleks) | Insentif ekstrinsik memancing review spam; cukup Peak-End moment                                                                                               |
| Multi-kota aktif                                     | Data model siap (`city`), tapi UI & seeding fokus 1 kota sampai G1–G5 tercapai                                                                                 |
| Menu & harga lengkap per cafe                        | Maintenance berat & cepat basi; cukup "Ramah kantong" + info harga di review                                                                                   |
| Akun/klaim halaman oleh pemilik cafe                 | Konflik kepentingan dengan review jujur; ditunda ke roadmap. **Tapi:** owner tetap punya saluran keberatan resmi tanpa akun — section 10 (notice-and-takedown) |
| Notifikasi push                                      | Butuh service worker + izin; nilai kecil sebelum konten mengalir. Email transaksional (moderasi) tetap ada                                                     |

---

### 7. Post-MVP Roadmap

- **Fase 1 — Perkuat trust & konten (bulan 2–3):** moderasi lebih pintar (deteksi pola review palsu), verifikasi kunjungan ringan (opsional foto struk → badge "terverifikasi datang"), simpan/bookmark cafe, koleksi kurasi lintas segmen ("10 cafe 24 jam buat SKS-an", "Hidden gem buat hopping wiken", "Cafe paling stabil buat video call").
- **Fase 2 — Retention & komunitas ringan (bulan 3–5):** notifikasi (cafe favorit dapat review baru), reaksi "membantu" di review (sorting by helpful), profil anonim publik minimal (jumlah kontribusi, tanpa identitas).
- **Fase 3 — Ekspansi multi-kota (bulan 5–8):** city switcher (data model siap), playbook seeding per kota (Gowa/Samata sebagai perluasan alami, lalu Kendari/Palu/Manado), SEO programatik per kota+kategori.
- **Fase 4 — Keberlanjutan (bulan 8+):** halaman resmi cafe (claim, balas review — pemisahan visual tegas dari review user), monetisasi non-destruktif (job board barista? highlight event cafe?), eksperimen app native jika data menunjukkan kebutuhan.

---

### 8. Launch Readiness & Distribution Plan _(section baru v1.2)_

Section ini menjawab tiga lubang eksekusi v1.1: matematika cold start tidak dihitung, tidak ada rencana launch operasional, dan tidak ada timeline.

#### 8.1 Matematika cold start & review coverage (launch criteria konten)

Aha moment = membaca review. Halaman cafe kosong = aha gagal. Maka target seeding bukan hanya jumlah cafe, tapi **pasangan cafe × review**:

| Item                   | Target pre-launch                                                                                                                                        | Reasoning                                                                                                                           |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| Cafe `active` ter-seed | 60–80 (4 klaster: Tamalanrea/Unhas ±20, Panakkukang/Pettarani ±20, pusat kota/Losari ±15, Samata/Hertasning ±15; komposisi tiap klaster campuran segmen) | Cukup padat agar pencarian per area tidak kosong                                                                                    |
| **Review awal**        | **≥150 review** (rata-rata ~2/cafe)                                                                                                                      | Dari 25–30 founding reviewer × 5–8 review jujur cafe yang benar-benar mereka datangi. Komitmen per orang realistis (5–8, bukan 12+) |
| **Review coverage**    | **≥80% cafe punya ≥1 review; klaster soft-launch (Tamalanrea) ≥2 review/cafe**                                                                           | Gerbang go/no-go: di bawah ini, launch ditunda atau di-scope ke klaster yang lolos                                                  |
| Foto                   | ≥60% cafe punya ≥1 foto user/founding                                                                                                                    | Kartu tanpa foto = hook lemah                                                                                                       |

**Aturan produk pendukung:** cafe tanpa review tidak dipromosikan di seksi homepage (4.1); guardrail review coverage ≥70% pasca-launch (section 2) menjaga rasio ini saat F8 mulai memasukkan cafe baru.

**Rekrutmen founding reviewer:** teman/komunitas mahasiswa + 5–8 orang dari segmen WFC/hopper (agar konten awal tidak monoton "cafe nugas semua"). Brief tertulis satu halaman: tulis jujur, tempat yang benar-benar didatangi, minimal 30 karakter, foto sendiri — bukan review pesanan. Tidak dibayar; imbalannya disebut sebagai founding contributor di halaman Tentang (opsional, seizin mereka).

#### 8.2 Rencana distribusi & kanal (ops konkret, bukan "grup WA kampus" sebagai jimat)

- **Kanal #1 — WA kampus (soft launch):** seeding link via founding reviewer ke grup angkatan/organisasi mereka sendiri (bukan broadcast dingin). Format pesan: share card OG satu cafe spesifik yang relevan grup itu ("cafe deket kampus yang wifinya beneran kenceng") — bukan link homepage generik. Target: 10–15 grup di minggu pertama soft launch.
- **Timing:** launch mengikuti kalender akademik — target minggu-minggu awal semester atau menjelang UTS/UAS (kebutuhan "cafe nugas" memuncak). Hindari libur semester.
- **Kanal #2 — SEO (compounding, bukan instan):** halaman kategori+kota indexable sejak hari pertama; ekspektasi realistis: organik baru berarti bulan 2–3.
- **Kanal #3 — share loop produk:** tombol share di detail cafe + share card OG (section 10 SEO) = mekanisme distribusi utama jangka panjang. Diukur: event `share_tap`.
- **Yang TIDAK dilakukan di MVP:** akun IG/TikTok resmi yang butuh konten rutin (beban konten solo dev), paid ads, kerja sama endorse.
- **Target volume (selaras G0):** minggu 1–2 soft launch ≥200 visitor unik/minggu; hari-30 ≥500/minggu; hari-90 ≥1.500/minggu. Jika hari-30 <250/minggu → masalahnya distribusi, bukan produk; perbaiki kanal sebelum menambah fitur.

#### 8.3 Timeline build (solo dev, asumsi full-time; kalau part-time, kalikan ~1.7×)

| Minggu | Fokus build                                                                                              | Paralel (non-coding)                                                               |
| ------ | -------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| 1–2    | Fondasi: Laravel + Postgres + auth Google + data model + Filament CRUD cafe/review + pipeline deploy VPS | Mulai seeding lapangan klaster 1 (target 8–10 cafe/minggu, berlanjut s/d minggu 8) |
| 3–4    | F1–F3: direktori, halaman detail, search + lokasi + area fallback                                        | Rekrut founding reviewer (25–30 orang)                                             |
| 5      | F2 + F4: rating, agregasi, kategori + chip kontekstual                                                   | Seeding klaster 2                                                                  |
| 6–7    | F5–F6: form review 3 langkah, alias HMAC, pipeline foto (kompres client → re-encode server → R2)         | Founding reviewer mulai menulis (staging)                                          |
| 8      | SEO (schema, sitemap, share card OG), PWA manifest, email transaksional, hardening checklist             | Seeding klaster 3–4 selesai                                                        |
| 9–10   | F7 + F7b (rekomendasi + ranking + cron trending), F8 (usul cafe), polish moderasi Filament               | Review coverage dikejar sampai gerbang 8.1 lolos                                   |
| 11     | **Pre-launch checklist (8.4)** + soft launch klaster Tamalanrea                                          | Pantau G0–G2 harian                                                                |
| 12+    | Iterasi dari data soft launch → launch kampus penuh                                                      | Distribusi 8.2 penuh                                                               |

Estimasi ini sengaja tanpa buffer tersembunyi — kalau meleset >2 minggu, potong dari ekor (F7b slot eksplorasi bisa menyusul, F8 bisa menyusul), **jangan** potong dari hardening/legal.

#### 8.4 Pre-launch checklist gabungan (go/no-go, semua harus hijau)

- **Konten:** gerbang review coverage 8.1 lolos.
- **Legal & compliance:** halaman Kebijakan Privasi + Aturan Review + prosedur keberatan konten live (section 10); **pendaftaran PSE Lingkup Privat Kominfo diajukan** (via OSS); batas umur 13+ di ToS.
- **Keamanan:** checklist security section 9–10 (ZAP baseline, `composer audit`, test no-PII hijau, uji restore backup, securityheaders.com, akses origin by IP ditolak).
- **OAuth:** consent screen Google terverifikasi (logo + domain), scope hanya `openid email`.
- **Brand:** domain final **ngafe.space** — cek RDAP (Jul 2026) tidak menemukan record terdaftar = indikasi kuat tersedia; **amankan segera** di registrar (konfirmasi final saat checkout). Perhatikan **harga renewal .space**: tahun pertama biasanya promo murah, perpanjangan bisa 5–10× — masukkan ke budget tahunan sebelum commit. Opsional: amankan juga `ngafe.id` sebagai cadangan/redirect brand. Tetap jalan: **cek merek DJKI** + survey singkat pemain lokal serupa (kalau ada yang pernah gagal, pelajari kenapa).
- **Ops:** backup DB + foto teruji restore; UptimeRobot + Sentry aktif; email transaksional terkirim dari domain sendiri (SPF/DKIM beres).
- **Analytics:** event G0–G5 terpasang & terverifikasi di staging (termasuk `review_read`, `form_start`, `share_tap`).

---

## B. Technical Specification

### 9. Tech Stack Recommendation

#### Laravel vs Next.js untuk kasus ini

| Kriteria                          | Laravel (+ Livewire, Filament)       | Next.js 14+ (App Router)             | Bobot                              |
| --------------------------------- | ------------------------------------ | ------------------------------------ | ---------------------------------- |
| SEO halaman cafe                  | ✅ Server-rendered natively          | ✅ SSR/ISR setara                    | Tinggi — imbang                    |
| Kecepatan di 4G Android mid-range | ✅ HTML ringan, JS minimal           | ⚠️ Perlu disiplin bundle             | Tinggi — Laravel unggul tipis      |
| Interaksi mobile app-like         | ⚠️ Livewire + Alpine cukup           | ✅ Ekosistem React matang            | Tinggi — Next unggul               |
| Admin panel moderasi              | ✅✅ Filament = jam, bukan minggu    | ⚠️ Bangun sendiri                    | Tinggi — Laravel unggul jauh       |
| Google OAuth                      | ✅ Socialite                         | ✅ Auth.js                           | Imbang                             |
| Upload + resize gambar            | ✅ Intervention Image + queue bawaan | ✅ sharp, serverless perlu perhatian | Sedang — Laravel unggul tipis      |
| Biaya hosting MVP                 | ✅ 1 VPS murah, semua jadi satu      | ✅ Vercel free, tapi kuota           | Tinggi — Laravel lebih terprediksi |
| Kecepatan solo dev                | ✅ Batteries-included                | ⚠️ Lebih banyak perakitan            | Tinggi                             |

**Rekomendasi: Laravel 11 + Livewire 3 (+ Alpine.js) + Filament untuk admin.** Produk ini menang lewat (1) kecepatan halaman baca di HP murah, (2) SEO, (3) moderasi jalan sejak hari pertama — kekuatan alami Laravel monolith + Filament. Arsitektur dokumen ini framework-agnostic; kalau pindah Next.js, yang berubah hanya lapisan render.

#### Komponen pendukung

| Komponen                              | Rekomendasi                                                                                                                                          | Alternatif               | Reasoning                                                                                                                                                            |
| ------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Database                              | **PostgreSQL 16** (VPS sama, atau Neon free saat awal)                                                                                               | MySQL                    | earthdistance/Haversine untuk jarak; **pg_trgm (GIN index) untuk live-search DAN dedup F8 — satu index dua fungsi**; FTS bawaan cukup tanpa Meilisearch              |
| Storage gambar                        | **Cloudflare R2** + CDN                                                                                                                              | Bunny Storage            | Egress gratis, S3-compatible, free tier 10GB. **(Baru v1.2) Object versioning/bucket kedua wajib — lihat Backup di section 10 Ops**                                  |
| Pipeline gambar                       | Kompres client (browser-image-compression) → validasi & re-encode server (Intervention Image → WebP, varian 400px/1600px) → R2. Strip EXIF di server | —                        | Hemat kuota user + jaminan server-side                                                                                                                               |
| Maps & geocoding                      | **MapLibre GL + OpenFreeMap/Protomaps; Nominatim** (rate limit 1 req/s — hanya saat seeding)                                                         | Mapbox free tier         | Jarak dihitung sendiri (Haversine SQL) — tanpa API call per-request                                                                                                  |
| **Email transaksional** _(baru v1.2)_ | **Resend free tier (3.000 email/bln)** atau Brevo (300/hari) — SPF/DKIM di domain sendiri                                                            | Amazon SES ($~0)         | Dibutuhkan flow yang sudah dijanjikan: notif hasil moderasi ke user (4.4.7), notif antrian moderasi & usulan ke admin, notif status usulan F8. Bukan untuk marketing |
| Hosting                               | VPS Indonesia/SG (2GB, IDR 75–150rb/bln)                                                                                                             | Vercel+Neon jika Next.js | Latensi Makassar; SG ≈ 20–40ms                                                                                                                                       |
| Analytics                             | Umami self-host (VPS sama)                                                                                                                           | PostHog cloud free       | Event custom G0–G5, tanpa cookie banner ribet. Anon-id via **cookie first-party server-set** (bukan localStorage — Safari ITP)                                       |
| Error tracking                        | Sentry free tier                                                                                                                                     | —                        | Wajib solo dev                                                                                                                                                       |

**Prinsip vendor eksternal: wajib $0 pada skala MVP.** Google OAuth · OpenFreeMap/Protomaps · Nominatim (seeding only) · R2 free tier · Neon (ops.) · Umami · Sentry free · UptimeRobot free · Resend free · font self-host · seluruh library MIT/Apache. **Satu-satunya biaya wajib = VPS + domain (≈ IDR 100–180rb/bulan).** Anti lock-in: semua standar terbuka (S3-compatible, OSM, Postgres, SMTP).

#### Application Architecture — modular monolith, pragmatic clean architecture _(baru v1.3)_

Jawaban atas "apakah clean architecture?": yang diadopsi adalah **disiplin arah dependensi dan pemisahan concern** dari clean architecture — BUKAN seremoni lapisannya. Full hexagonal (repository interface di atas Eloquent, DTO di semua batas, use-case ceremony) untuk solo dev + Laravel adalah overhead tanpa nilai: Eloquent SUDAH abstraction; membungkusnya dengan repository interface = double abstraction untuk skenario ganti-ORM yang tidak akan terjadi.

**Struktur modul by domain (modular monolith):**

```
app/
  Domain/
    Cafe/        Models (Cafe, Category, Area) · Actions (CreateCafeProposal,
                 ApproveCafe, SetSeasonalHours) · Queries (NearbyCafes,
                 TrendingCafes, SearchCafes)
    Review/      Models (Review, Photo) · Actions (SubmitReview, EditReview,
                 ModerateReview) · Support (AliasGenerator [HMAC], ReviewGuards
                 [rate limit, dedup])
    Identity/    User, Socialite callback handler, Policies, account deletion
    Moderation/  Report, banned-words, auto-flag rules, takedown/keberatan flow
  Http/          Controllers & Livewire components TIPIS (orkestrasi saja) ·
                 FormRequests (validasi whitelist) · API Resources (whitelist
                 no-PII — test CI menempel di lapisan ini)
  Jobs/          ProcessReviewPhoto (re-encode, strip EXIF, R2),
                 RecomputeCafeAggregates, GenerateShareCard
  Console/       Cron: ComputeTrendingScores (nightly), DataDecayCheck
                 (bulanan), BackupDatabase (harian)
```

**Aturan dependensi (ini yang membuatnya "clean"):**

1. **Controller/Livewire tidak berisi logika bisnis.** Maksimal: terima request → FormRequest validasi → panggil satu Action → render/redirect. Kalau ada `if` bisnis di controller, itu salah tempat.
2. **Action = satu use case per class**, method `handle()`, bisa dites unit tanpa HTTP. Semua aturan bisnis (1 review/user/cafe, rate limit, status transition) hidup di sini atau di Support.
3. **Side-effect lintas domain via event**, bukan panggilan langsung: `ReviewStatusChanged` → listener `RecomputeCafeAggregates` + `NotifyReviewAuthor`. Modul Review tidak perlu tahu cara Cafe menghitung agregat.
4. **Presentasi keluar hanya lewat API Resource/Blade** — satu-satunya pintu data ke publik; test no-PII menjaga pintu ini.
5. **Model boleh Eloquent penuh** (scope, relasi, cast, observer) — tanpa repository interface. Query kompleks yang dipakai berulang → class Query tersendiri, bukan method 80 baris di model.

**Testing pyramid:** unit (Actions, AliasGenerator, ranking Bayesian, guards) → feature/HTTP (flow kritis: submit review, moderasi, usul cafe, **no-PII assertion**) → browser minimal (jalur aha 2-tap, form review 3 langkah). Target bukan coverage %, tapi: semua Action punya test, semua response publik lewat no-PII test.

**Kapan naik kelas:** kalau tim >1 orang atau kota kedua butuh perilaku beda — batas modul sudah bersih, ekstraksi jadi service/paket terpisah murah. Jangan bangun untuk skenario itu sekarang.

---

### 10. Spec Requirements

#### Fungsional

- Browse, cari, filter, baca review, lihat foto: **tanpa autentikasi**.
- Menulis review/rating/foto: wajib login Google SSO. Satu review per user per cafe (unique constraint `(user_id, cafe_id)` di DB).
- Review anonim: **anonim di presentasi, teridentifikasi di backend.** Reasoning: (1) akuntabilitas — abuse ditindak per akun; (2) rate limiting & dedup butuh identitas; (3) hukum — konten bermasalah bisa ditindak tanpa membuka identitas ke publik. API publik TIDAK BOLEH memuat `user_id`/email/nama Google — hanya `display_alias`.
- Edit & hapus review milik sendiri. Hapus akun → review di-anonimkan permanen atau dihapus (pilihan user; UU PDP).

#### Non-fungsional — Performa

- **LCP < 2.5s di 4G** (Lighthouse throttling, device mid-range), CLS < 0.1.
- Gambar: WebP, `srcset` (400px kartu / 1600px detail), lazy di bawah fold, dimensi eksplisit, hero di-`preload`.
- Halaman detail di-cache (full-page cache anonim, 5 menit).
- Skeleton loading, bukan spinner halaman (section 14).

#### Non-fungsional — Keamanan & anti-abuse

- OAuth: authorization code + PKCE (Socialite); `state` divalidasi; Google `sub` sebagai identifier; cookie `HttpOnly; Secure; SameSite=Lax`.
- Rate limiting: review 3/jam & 10/hari; upload 20 foto/hari; report 10/hari; usul cafe 3/hari & 10/bln; login attempt per IP.
- Upload: MIME dari magic bytes, maks 10MB pre-kompres, re-encode paksa WebP, strip EXIF/GPS, nama UUID, serve dari domain terpisah.
- Spam/abuse: honeypot; blokir duplikat konten (hash); akun baru (<24 jam) review 1-bintang bertubi ke 1 cafe → auto-flag.

#### Moderasi (MVP minimal tapi ada)

- Tombol "Laporkan" di tiap review & foto (spam / kasar / bukan tentang cafe ini / info salah / **membuka identitas seseorang**).
- Auto-flag → `pending`: (a) ≥3 report unik, (b) daftar kata terlarang (ID + istilah lokal Makassar), (c) heuristik akun baru.
- Review `pending` disembunyikan dari publik, terlihat penulis dengan badge. Keputusan admin via Filament: approve / takedown / ban. Hasil dinotifikasi email.
- SLA internal solo dev: cek antrian 1x/hari (email digest jika ada flag).

#### Compliance Indonesia _(baru v1.2 — bukan nasihat hukum; verifikasi dengan praktisi hukum ID)_

- **Pendaftaran PSE Lingkup Privat (Kominfo, via OSS)** — platform UGC yang beroperasi publik di Indonesia wajib terdaftar. Diajukan sebelum launch publik (checklist 8.4). Konsekuensi terdaftar: kewajiban tata kelola konten & respons permintaan takedown sesuai regulasi — sudah selaras dengan desain moderasi kita.
- **Prosedur keberatan konten (notice-and-takedown)** — mitigasi risiko UU ITE (pencemaran nama baik). Pemilik cafe tidak punya akun (out of scope), tapi punya saluran resmi tanpa akun: halaman "Keberatan atas Konten" berisi form/email khusus + syarat (identitas pelapor, konten yang dimaksud, alasan) + **SLA respons 3×24 jam** + alur: review di-suspend sementara (`pending`) saat keberatan diterima → dinilai terhadap Aturan Review → keputusan tertulis (dipertahankan/di-takedown) → opsi banding satu kali. Semua keputusan dicatat di audit log.
- **UU PDP:** data minimization (hanya `google_sub` + email), hak akses & hapus di Kebijakan Privasi, batas umur 13+.

#### Security Hardening — Defense in Depth

Prinsip: tiap jalur ke application layer dipetakan dan ditutup berlapis; semua input dianggap berbahaya; least-privilege di semua akses.

**Lapis 1 — VPS/OS:** SSH key-only, `PasswordAuthentication no`, `PermitRootLogin no`, fail2ban. ufw: hanya 80/443 + SSH. **PostgreSQL & Redis bind `127.0.0.1` saja.** `unattended-upgrades`; PHP-FPM non-root; hanya `storage/` & `bootstrap/cache` writable. Tidak ada phpMyAdmin publik — DB via SSH tunnel.

**Lapis 2 — Edge (Cloudflare free):** proxy aktif (TLS Full Strict, HSTS, ≥1.2), WAF managed rules, rate limit dasar, bot fight mode. **Origin firewall hanya menerima 80/443 dari IP range Cloudflare.**

**Lapis 3 — Application (Laravel):**

| Jalur serangan        | Penutup                                                                                                                                              |
| --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| SQL injection         | Hanya Eloquent/query builder ber-binding; validasi whitelist FormRequest untuk semua input                                                           |
| XSS                   | Blade `{{ }}` wajib; `{!! !!}` dilarang untuk konten user; review plain text; CSP ketat (`default-src 'self'`; img self + CDN R2; script self+nonce) |
| CSRF                  | Token Laravel semua form/Livewire                                                                                                                    |
| IDOR                  | Laravel Policy semua aksi; ID publik ULID acak (anti enumeration)                                                                                    |
| Mass assignment       | `$fillable` eksplisit; `$guarded = []` dilarang                                                                                                      |
| **Upload (webshell)** | Magic bytes → re-encode paksa WebP → simpan R2 (bukan web-root) → serve domain terpisah cookie-less → nama UUID                                      |
| SSRF                  | Tidak ada fitur fetch-URL dari input user — ditutup by design                                                                                        |
| Session hijack        | Cookie `HttpOnly; Secure; SameSite=Lax`, regenerate saat login, absolute timeout; `X-Frame-Options: DENY`, `nosniff`, `Permissions-Policy` minimal   |
| Brute force           | Rate limit per-IP + per-akun semua endpoint tulis                                                                                                    |
| Info leak             | `APP_DEBUG=false`; detail error hanya Sentry                                                                                                         |
| Dependency            | `composer audit` + Dependabot di CI; lockfile committed                                                                                              |
| Secrets               | `.env` di luar git, permission 600; credential scoped minimal; prosedur rotasi tertulis                                                              |
| **Admin panel**       | Path Filament non-default, **2FA TOTP wajib**, sesi pendek, opsional IP allowlist, audit log semua aksi                                              |

**Lapis 4 — Data:** DB user least-privilege; backup terenkripsi (`age`/GPG) sebelum ke R2; bucket private. **Data minimization = kontrol #1: yang tidak disimpan tidak bisa bocor** — hanya `google_sub` + email; nama & foto profil Google TIDAK disimpan.

#### Reviewer Privacy Threat Model (jalur deanonimisasi & penutupnya)

| #   | Jalur bocor                        | Penutup                                                                                                            |
| --- | ---------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| 1   | `user_id`/email di JSON publik     | API Resource whitelist eksplisit; **test CI assert no-PII di semua response publik — regresi = build gagal**       |
| 2   | EXIF/GPS foto                      | Strip total saat re-encode                                                                                         |
| 3   | Alias sama lintas cafe → profiling | Alias = `HMAC(user_id, cafe_id, APP_KEY)` — konsisten per cafe, beda antar cafe, tidak reversible                  |
| 4   | Korelasi waktu                     | Timestamp publik kasar ("2 hari lalu"); opsional jeda publikasi acak 0–30 mnt (off di MVP — lihat section 20)      |
| 5   | Konten membocorkan diri sendiri    | Nudge di langkah foto: "Cek dulu: nggak ada wajah/namamu kefoto kan?"; alasan report "membuka identitas seseorang" |
| 6   | Log & analytics                    | Umami tanpa PII; IP dianonimkan (potong oktet akhir), retensi 14 hari; Sentry PII-scrubbing                        |
| 7   | Mata admin                         | Filament tampilkan alias internal; identitas penuh hanya via aksi "reveal" ber-audit-log                           |
| 8   | Backup bocor                       | Terenkripsi sebelum keluar server                                                                                  |
| 9   | Scope OAuth                        | Hanya `openid email`                                                                                               |
| 10  | Enumeration user                   | Tidak ada profil publik / endpoint `users/{id}` — by design                                                        |
| 11  | Referrer & pihak ketiga            | `Referrer-Policy: strict-origin-when-cross-origin`; nol script pihak ketiga; font self-host                        |
| 12  | Permintaan hukum                   | Kebijakan Privasi jujur; data minimization membatasi apa yang bahkan bisa diserahkan                               |

#### Cafe data governance (anti dump data)

- **Tiga pintu masuk, tidak ada keempat:** (1) seeding admin via Filament (form + import CSV admin-only), (2) usulan user ber-gate (4.7), (3) koreksi via report. **Tidak ada API publik create cafe.**
- Field wajib usulan: nama, koordinat pin peta, ≥1 kategori, ≥1 foto. Tanpa foto = submit mati.
- Dedup dua lapis: client inline (radius 150m + pg_trgm) + validasi ulang server.
- Koordinat hanya dari pin peta (user) atau geocoding terverifikasi admin (seeding).
- Approval wajib: `pending` → antrian Filament; hanya `active` publik & masuk sitemap; `rejected` disimpan untuk audit & throttle.
- Akuntabilitas: `created_by` tercatat; ≥3 reject beruntun → throttle; pola spam (lokasi acak / hash foto berulang) → ban fitur usul.
- Sanitasi: nama dinormalisasi (trim, kapitalisasi, larang emoji/URL/nomor WA), deskripsi difilter pola promosi.

#### Data freshness & decay _(baru v1.2 — proaktif, bukan hanya reaktif)_

Direktori 80 cafe dalam 12 bulan realistisnya 10–20% tutup/pindah/ganti jam. Report `info_salah` (reaktif) tidak cukup karena mengandalkan user peduli.

- **Audit klaster bergilir:** 1 klaster/bulan dicek ringan (jam buka via telp/IG cafe/kunjungan sambil lalu) — seluruh direktori ter-refresh tiap ±4 bulan.
- **Heuristik flag otomatis (cron bulanan):** cafe tanpa review baru DAN tanpa `review_read` 6 bulan → masuk antrian "cek kondisi" di Filament.
- **Jam musiman:** `opening_hours_override` per rentang tanggal (F1) — dipakai untuk Ramadan (jam berubah total sebulan, tepat saat musim bukber/trafik puncak), tahun baru, dll. Admin bisa bulk-set. Banner otomatis + prioritas report selama periode aktif.
- Report `info_salah` → antrian admin, target respons 3 hari.

#### SEO

- Halaman detail server-rendered, URL `/{city}/{cafe-slug}` (`/makassar/kopi-anjis-perintis`), meta unik, `LocalBusiness` + `AggregateRating` JSON-LD, sitemap.xml otomatis (hanya cafe `active`), canonical.
- Halaman kategori+kota indexable (`/makassar/cafe-24-jam`) — mesin organik programatik.
- Review user = konten unik → jangan render lewat JS-only.
- **Open Graph share card otomatis per cafe** (digenerate server via Intervention Image: foto + nama + rating + tag) — WA adalah kanal distribusi #1; ini fitur akuisisi. ($0)

#### Aksesibilitas dasar

- Kontras ≥4.5:1 (cek juga dark mode — token section 12 sudah dihitung), touch target ≥44×44px, alt text otomatis foto user ("Foto {nama cafe} dari pengunjung") + editable, form ber-label, fokus keyboard terlihat.

#### Operations

- **Backup DB:** `pg_dump` harian (cron) → terenkripsi → R2, retensi 14 hari; **uji restore tiap bulan**.
- **Backup foto (baru v1.2):** `pg_dump` tidak menyelamatkan foto. Aktifkan **R2 object versioning** ATAU `rclone sync` mingguan ke bucket kedua (R2 bucket terpisah / Backblaze B2 free 10GB). Credential app scoped per-bucket; credential admin/backup terpisah — credential app bocor ≠ semua hero content hilang.
- **Monitoring:** UptimeRobot (uptime + SSL, cek endpoint `/up`) + Sentry + **Healthchecks.io free untuk heartbeat cron** (detail di subsection Logging & Observability di bawah); semua alert ke satu email. ($0)
- **Email:** Resend/Brevo + SPF/DKIM di domain — dipakai notif moderasi, status usulan, digest admin.
- **Halaman legal wajib pre-launch:** Kebijakan Privasi (UU PDP), Aturan Review (dasar takedown & banding), **Keberatan atas Konten** (notice-and-takedown), Tentang/Kontak.
- **PWA:** manifest + service worker (cache aset statis + halaman terakhir) → installable. ($0)
- **Kill switch:** admin unpublish cafe/review/foto seketika via Filament.
- **CI minimal:** GitHub Actions free — `composer audit`, test suite (termasuk test no-PII), deploy via script SSH.

#### Logging, Error Handling & Observability — satu pola, ops ringan _(baru v1.4)_

**Prinsip:** solo dev tidak menatap dashboard. Sistem harus **diam saat sehat, berisik saat sakit** — dan saat berisik, diagnosis harus bisa dilakukan dari email alert → satu klik → konteks lengkap, **tanpa SSH dulu**. Setiap lapisan punya SATU pola penanganan; penanganan ad-hoc (try/catch tersebar, `Log::info` sembarang, `dd()` ketinggalan) dilarang.

**1. Error handling — satu pintu per lapisan:**

- **Exception handler global Laravel = satu-satunya tempat** mapping exception → response. Tidak ada try/catch untuk keperluan presentasi di controller/Livewire/Action.
- **Exception domain** (`ReviewLimitExceeded`, `DuplicateReview`, `ProposalThrottled`, `PhotoValidationFailed`, dst.) extend satu base `DomainException` yang membawa `userMessage()` — pesan santai sesuai section 16. Action cukup `throw`; handler global otomatis merender toast (Livewire) / halaman error (HTTP) / response 422 dengan pesan itu. **Menambah kasus error baru = menambah satu class exception, bukan menambah cabang if di banyak tempat.**
- **Error tak terduga** (bukan DomainException): user melihat pesan generik manusiawi ("Ada yang error di kami, bukan di kamu. Udah kami catat, coba lagi ya") + Sentry event tercatat; TIDAK PERNAH stack trace, kode error mentah, atau SQL di layar.
- **Validasi input** = FormRequest → error inline per field (bukan exception, bukan toast).
- **External call gagal** (R2, Resend, Nominatim, OAuth): selalu lewat wrapper dengan timeout eksplisit + retry terbatas; kegagalan permanen → exception domain dengan fallback yang sudah didesain (foto → retry per-foto; email → antrikan ulang; OAuth → toast retry flow 4.3).

**2. Logging rules:**

- **Format:** structured JSON (Monolog daily channel), rotasi otomatis **14 hari** (selaras retensi privasi section 10), level produksi minimal `info` — `debug` dilarang di prod.
- **`request_id` (ULID) di setiap request**: di-generate middleware, ikut di semua baris log request itu + response header `X-Request-Id` + Sentry tag. Satu keluhan user / satu alert = satu ID = seluruh jejaknya. Job queue membawa `request_id` asalnya.
- **Yang WAJIB dilog:** login sukses/gagal (pakai user ULID, bukan email), semua aksi moderasi & admin (audit log — sudah diwajibkan section 10), rate-limit hit, job gagal, external call gagal, keputusan auto-flag.
- **Yang DILARANG masuk log:** email, `google_sub`, token/session/cookie, body review, koordinat user, IP utuh (potong oktet akhir — sudah berlaku), header Authorization. Aturan ini setara dengan no-PII test di API: **log adalah output juga.** Satu helper logging terpusat (context builder) supaya larangan ini ditegakkan di satu tempat, bukan diingat-ingat di 50 call site.

**3. Kanal sinyal — maksimal 3, semua ke satu email:**

| Kanal                      | Menangkap                                                                                                                                     | Kapan berisik            |
| -------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------ |
| **Sentry**                 | Exception tak terduga, job gagal final (setelah retry habis)                                                                                  | Seketika, per issue baru |
| **UptimeRobot**            | Situs down, SSL expiring, `/up` gagal (cek DB)                                                                                                | Seketika                 |
| **Healthchecks.io** (free) | **Cron yang diam-diam mati** — setiap job terjadwal (backup harian, trending nightly, decay bulanan) ping saat sukses; tidak ada ping = alert | Saat jadwal terlewat     |

Backup yang mati tanpa suara adalah mode kegagalan ops klasik solo dev — heartbeat menutupnya. **Tidak ada dashboard yang harus dicek manual secara rutin**; satu-satunya ritual manual = antrian moderasi 1×/hari (sudah ada email digest-nya).

**4. Job & queue handling:**

- Semua job: **retry 3× dengan exponential backoff**, lalu masuk `failed_jobs` + Sentry. Perintah retry manual (`queue:retry`) cukup — tidak perlu tooling khusus.
- **Semua job wajib idempotent** (aman dijalankan ulang): `RecomputeCafeAggregates` menghitung ulang dari sumber; `ProcessReviewPhoto` cek hash sebelum proses; `GenerateShareCard` overwrite deterministik. Idempotensi adalah AC job, bukan nice-to-have.
- Queue worker jalan via **Supervisor/systemd dengan auto-restart**; worker mati terdeteksi lewat heartbeat job ringan tiap 5 menit → Healthchecks.io.

**5. Definisi "mudah" (acceptance criteria operasional):**

- Given kegagalan apa pun di produksi, When alert email masuk, Then akar masalah bisa ditemukan dari link di alert (Sentry issue ber-`request_id` / log ber-ID) **tanpa membuka SSH**.
- Given developer menambah fitur baru, When butuh menangani error/log, Then cukup memakai pola yang ada (exception domain + helper log) — kalau fitur "butuh" pola penanganan baru, desain fiturnya yang salah.
- Given semua sistem sehat, Then inbox alert = kosong. Alert yang rutin di-ignore harus dimatikan atau diperbaiki akarnya (alert fatigue = tidak punya alert).

---

### 11. High-level Data Model

```
User          Cafe                 Review          Photo         Category      Report
----          ----                 ------          -----         --------      ------
id            id                   id              id            id            id
google_sub ¹  name                 user_id FK      review_id FK² name          reporter_id FK
email ¹       slug (unique/city)   cafe_id FK      cafe_id FK    slug          review_id FK (nullable)
display_alias city ★               rating (1-5)    url_card      icon          photo_id FK (nullable)
  _seed       address              body            url_full      sort_order    reason (enum, termasuk
role (user/   area (enum/tbl)      display_alias   width/height                  'membuka_identitas',
  admin)      lat, lng             status:         status                        'info_salah')
status        opening_hours          published/    created_at                  status (open/resolved)
created_at    (JSON per hari)        pending/                                  created_at
              opening_hours_         removed
                override ³         is_edited
              price_range          created_at
              rating_avg ⁴         updated_at
              rating_count ⁴
              trending_score ⁵
              status: pending/
                active/rejected/
                closed_perm
              created_by
              last_verified_at ⁶
              created_at

CafeCategory (pivot): cafe_id, category_id, source (admin/crowd), confidence
ReviewTag (quick-tag): review_id, category_id   ← sinyal crowdsourcing kategori
```

**Relasi:** User 1—N Review; Cafe 1—N Review; Review 1—N Photo (foto selalu menempel ke review); Cafe N—M Category; User 1—N Report.

**Catatan kunci:**

- ★ `city` sejak hari pertama; slug unik per city; semua query publik ter-scope city → multi-kota = tambah baris, bukan refactor.
- ¹ `google_sub` = identifier utama; email hanya kontak & notif moderasi. Keduanya tidak pernah keluar di API publik. Nama & foto profil Google tidak disimpan.
- ² Foto wajib bagian dari review (tidak ada upload berdiri sendiri) — moderasi & data model sederhana.
- ³ _(baru v1.2)_ `opening_hours_override`: JSON array `{label, date_start, date_end, hours}` — jam musiman (Ramadan dll), bisa di-bulk-set admin.
- ⁴ _(baru v1.2)_ Agregat denormalized, hanya review `published`, di-recompute via observer saat status review berubah.
- ⁵ _(baru v1.2)_ Skor "Lagi rame dibahas", dihitung nightly (F7b).
- ⁶ _(baru v1.2)_ `last_verified_at` untuk audit bergilir & heuristik decay.
- Unique constraint: `reviews(user_id, cafe_id)`. Index: pg_trgm GIN di `cafes.name`.
- `display_alias` digenerate dari seed per (user, cafe) — konsisten saat edit, beda antar cafe.
- Status `pending`/`rejected` tidak pernah publik / masuk sitemap.

---

## C. Design System & UX

### 12. Design Tokens _(section baru v1.2 — tokenisasi penuh, siap dipindah ke CSS variables / Tailwind config)_

Prinsip: **tiga lapis token** — primitif (nilai mentah, tidak dipakai langsung di komponen) → semantik (makna, yang dipakai di 95% kasus) → komponen (hanya bila semantik tidak cukup). Komponen tidak boleh mereferensikan primitif langsung; ganti tema = ganti mapping semantik, bukan cari-ganti hex di seluruh codebase.

#### 12.0 Brand direction — ".space" sebagai konsep RUANG, bukan tema kosmik _(baru v1.3)_

Domain `ngafe.space` memberi wordplay yang kuat: **space = ruang** — cafe sebagai _third place_ (bukan rumah, bukan kampus/kantor: ruang ketiga tempat hidup terjadi). Ini narasi brand yang jujur dan nyambung dengan produk. Ekspresinya:

- **Copy angle:** "space" dipakai sebagai kata benda di microcopy secukupnya — "cari space-mu buat nugas", "space baru buat wiken ini". **Maksimal satu pemakaian per layar**; kalau tiap kalimat ada "space", jadi gimmick.
- **Ekspresi visual utama = negative space.** "Space" diterjemahkan jadi kelegaan: whitespace generus adalah signature desain (skala spacing 12.4 ditegakkan ketat; antar blok konten utama di halaman detail minimal `--space-6`; jangan takut area kosong). Direktori yang lega terasa premium & mudah dipindai — kebalikan dari feed penuh sesak.
- **Brand mark sederhana: titik bulat penuh** (`--radius-full`, warna `--primary`) = "satu space di peta". Dipakai konsisten sebagai: marker peta, dot separator metadata ("1,2 km · Buka · Rp 15–30rb"), bullet di daftar kurasi. Satu bentuk, banyak fungsi — tanpa perlu maskot atau logo rumit.
- **Wordmark:** "ngafe.space" ditulis lowercase utuh di footer & share card — domain adalah bagian dari nama, gratis brand recall di tiap share WA.
- **DILARANG KERAS interpretasi luar angkasa** — bintang, galaksi, roket, nebula, gradient ungu-kosmik. Bertabrakan frontal dengan brand terracotta-kopi-Makassar, dan merupakan AI slop klasik (ditegaskan ulang di section 15).

#### 12.1 Primitif warna (raw palette — bukan untuk dipakai langsung)

Terracotta (brand) — hangat khas kopi & bata Makassar:

```
--terra-50:  #FDF3EF    --terra-400: #D96C43    --terra-700: #9A3412
--terra-100: #FAE3D9    --terra-500: #C4451C    --terra-800: #7C2D12
--terra-200: #F4C7B3    --terra-600: #A93A16    --terra-900: #641F0E
--terra-300: #E99A78
```

Leaf (hijau — status positif, "Buka", rating bagus):

```
--leaf-50:  #EFF7F2    --leaf-400: #55A97D    --leaf-700: #1E5138
--leaf-100: #D8EDE1    --leaf-500: #2D6A4F    --leaf-800: #17402C
--leaf-200: #AFDCC4    --leaf-600: #265A43    --leaf-900: #113021
--leaf-300: #7FC5A1
```

Sand (netral hangat — bg/surface/teks; BUKAN abu-abu klinis):

```
--sand-0:   #FFFFFF    --sand-300: #C9C1B4    --sand-700: #4A443C
--sand-50:  #FAF8F5    --sand-400: #A39B8F    --sand-800: #2E2A25
--sand-100: #F2EEE8    --sand-500: #6B6459    --sand-850: #1E1B18
--sand-200: #E4DED3    --sand-600: #57503F    --sand-900: #141210
```

Petrol (aksen dingin — penyeimbang palet yang serba hangat; info, link, konteks WFC) _(baru v1.3)_:

```
--petrol-50:  #EDF5F5    --petrol-400: #4A9296    --petrol-700: #1C494C
--petrol-100: #D4E8E9    --petrol-500: #2C6E72    --petrol-800: #143638
--petrol-200: #A9D1D3    --petrol-600: #235B5E    --petrol-900: #0D2627
--petrol-300: #74B4B7
```

Fungsional:

```
--gold-500:  #E8A317   (bintang rating — satu-satunya kuning di sistem)
--gold-600:  #C4880F   (bintang di light bg kalau kontras kurang)
--amber-600: #B45309   (warning)     --amber-100: #FCEBD8
--red-600:   #B3261E   (danger)      --red-100:   #F9DEDC
--blue-600:  #1D4ED8   (link eksternal saja, jarang — opsional, boleh tidak dipakai)
```

#### 12.2 Token semantik (yang dipakai komponen) — light & dark

| Token                               | Light                        | Dark                     | Pemakaian                                                                                                       |
| ----------------------------------- | ---------------------------- | ------------------------ | --------------------------------------------------------------------------------------------------------------- |
| `--bg`                              | `sand-50` #FAF8F5            | `sand-900` #141210       | Latar halaman                                                                                                   |
| `--surface`                         | `sand-0` #FFFFFF             | `sand-850` #1E1B18       | Kartu, sheet, input                                                                                             |
| `--surface-raised`                  | `sand-0` + shadow            | `sand-800` #2E2A25       | Bottom sheet, FAB, popover                                                                                      |
| `--surface-sunken`                  | `sand-100` #F2EEE8           | `sand-900` #141210       | Area inset (quote review, pre-chip)                                                                             |
| `--border`                          | #00000014                    | #FFFFFF14                | Garis kartu/input default                                                                                       |
| `--border-strong`                   | `sand-300`                   | `sand-600`               | Input focus-adjacent, divider tegas                                                                             |
| `--text`                            | `sand-900` #1A1714\*         | `sand-100` #F2EEE8       | Teks utama (\*nilai v1.1 dipertahankan)                                                                         |
| `--text-muted`                      | `sand-500` #6B6459           | `sand-400` #A39B8F       | Metadata, caption, placeholder                                                                                  |
| `--text-faint`                      | `sand-400`                   | `sand-500`               | Timestamp kasar, hint                                                                                           |
| `--primary`                         | `terra-500` #C4451C          | `terra-400` #D96C43      | CTA utama, elemen interaktif utama                                                                              |
| `--primary-hover`                   | `terra-600`                  | `terra-300`              | Hover CTA                                                                                                       |
| `--primary-pressed`                 | `terra-700`                  | `terra-500`              | Pressed CTA                                                                                                     |
| `--primary-ink`                     | #FFFFFF                      | `sand-900`               | Teks di atas primary (kontras ≥4.5:1 di kedua mode)                                                             |
| `--primary-subtle`                  | `terra-50`                   | #D96C4326                | Latar chip aktif, highlight lembut                                                                              |
| `--primary-subtle-ink`              | `terra-700`                  | `terra-300`              | Teks di atas primary-subtle                                                                                     |
| `--accent` / `--success`            | `leaf-500` #2D6A4F           | `leaf-400` #55A97D       | Status "Buka", toast sukses, rating bagus                                                                       |
| `--success-subtle`                  | `leaf-50`                    | #55A97D26                | Badge "Buka" background                                                                                         |
| `--warning`                         | `amber-600` #B45309          | #E28A2B                  | "Tutup sebentar lagi", peringatan                                                                               |
| `--warning-subtle`                  | `amber-100`                  | #B4530926                | Latar banner jam musiman                                                                                        |
| `--danger`                          | `red-600` #B3261E            | #E46962                  | "Tutup", hapus, error                                                                                           |
| `--danger-subtle`                   | `red-100`                    | #B3261E26                | Latar pesan error                                                                                               |
| `--star`                            | `gold-500` #E8A317           | `gold-500` #E8A317       | Bintang rating — konsisten dua mode                                                                             |
| `--star-empty`                      | `sand-200`                   | `sand-600`               | Bintang kosong                                                                                                  |
| `--focus-ring`                      | `terra-500`                  | `terra-300`              | Ring 2px `focus-visible`                                                                                        |
| `--overlay`                         | #1A171466 (40%)              | #00000080 (50%)          | Backdrop bottom sheet                                                                                           |
| `--skeleton-base`                   | `sand-100`                   | `sand-800`               | Blok skeleton                                                                                                   |
| `--skeleton-shimmer`                | `sand-200`                   | `sand-700`               | Shimmer skeleton                                                                                                |
| `--img-scrim`                       | gradient #00000000→#00000099 | sama                     | Overlay tipis di atas foto untuk keterbacaan teks (satu-satunya gradient yang diizinkan — section 15)           |
| `--accent-cool` _(v1.3)_            | `petrol-500` #2C6E72         | `petrol-300` #74B4B7     | Aksen sekunder NON-CTA: ikon kategori WFC/tenang, highlight seksi sekunder, pattern placeholder cafe tanpa foto |
| `--accent-cool-subtle` _(v1.3)_     | `petrol-50` #EDF5F5          | #74B4B726                | Latar lembut aksen dingin (chip kategori varian dingin)                                                         |
| `--link` _(v1.3)_                   | `petrol-600` #235B5E         | `petrol-300` #74B4B7     | Tautan inline — jarang; mayoritas navigasi berbentuk kartu/tombol                                               |
| `--info` / `--info-subtle` _(v1.3)_ | `petrol-600` / `petrol-50`   | `petrol-300` / #2C6E7226 | Banner informasional netral (hint install PWA, tips) — bukan warning, bukan sukses                              |

**Aturan pemakaian warna:**

- **Primary hanya untuk CTA & elemen interaktif utama** — kalau semuanya terracotta, tidak ada yang terracotta. Maksimal 1 CTA primary per viewport.
- `--star` adalah SATU-SATUNYA kuning; `--success` untuk semua hal "positif/buka"; jangan pakai hijau untuk rating (rating = bintang gold).
- Semua pasangan teks/latar di tabel sudah dihitung ≥4.5:1 (teks normal) / ≥3:1 (teks besar & ikon). Cek ulang dengan tooling saat implementasi — **kontras adalah AC, bukan saran.**
- Dark mode: primary dinaikkan ke `terra-400` karena `terra-500` di atas `sand-900` kontrasnya kurang untuk teks/ikon kecil.
- **Proporsi 60-30-10** _(v1.3)_: netral sand dominan (bg, surface, teks — ±60%+), terracotta hanya elemen interaktif utama (±30% dari elemen berwarna), petrol ≤10% sebagai penyeimbang. Kalau ragu warna apa: netral.
- **Petrol TIDAK PERNAH untuk CTA** — CTA = terracotta, selalu, tanpa pengecualian. Dan tidak ada dua warna aksen dalam satu komponen: satu komponen, satu aksen.
- `--link` jarang dipakai by design: link inline hanya di halaman legal/tentang/footer; di jalur produk, semua navigasi = kartu & tombol.

#### 12.3 Token komponen (hanya bila semantik tidak cukup)

```
--chip-bg:            var(--surface)         --chip-active-bg:    var(--primary-subtle)
--chip-border:        var(--border)          --chip-active-ink:   var(--primary-subtle-ink)
--chip-active-border: var(--primary)

--card-bg:            var(--surface)         --nav-bg:            var(--surface)
--card-border:        var(--border)          --nav-active:        var(--primary)
                                             --nav-inactive:      var(--text-muted)

--badge-open-bg:      var(--success-subtle)  --badge-open-ink:    var(--success)
--badge-closed-bg:    var(--danger-subtle)   --badge-closed-ink:  var(--danger)
--badge-pending-bg:   var(--warning-subtle)  --badge-pending-ink: var(--warning)
```

#### 12.4 Token non-warna

**Spacing (4px base):** `--space-1: 4` / `-2: 8` / `-3: 12` / `-4: 16` / `-6: 24` / `-8: 32` / `-12: 48`. Padding kartu 16, gap antar kartu 12, section gap 32. Tidak ada nilai di luar skala.

**Radius (3 nilai saja):** `--radius-sm: 8px` (input, chip) · `--radius-md: 12px` (kartu, bottom sheet) · `--radius-full: 9999px` (avatar alias, tombol pill CTA). JANGAN semua elemen 24px.

**Tipografi (maks 2 family):**

- Display/UI: **Plus Jakarta Sans** (variable, self-host). Body/review: **Inter** — atau Jakarta Sans untuk semua (1 font file lebih ringan; putuskan saat implementasi by ukuran bundle).
- Skala: `--type-h1: 24/700` · `--type-h2: 20/700` · `--type-h3: 17/600` · `--type-body: 15/400` (line-height 1.6 untuk review) · `--type-caption: 13/500` · `--type-overline: 11/600` uppercase tracking-wide. Maks 3 level per layar.

**Elevasi:** default border 1px (`--border`); shadow hanya elemen mengambang: `--shadow-float: 0 4px 16px rgba(0,0,0,0.12)` maksimal. Tidak ada shadow di kartu list.

**Motion:** `--motion-fast: 150ms` (state change) · `--motion-base: 200ms` (umum, batas atas animasi konten) · `--motion-sheet: 250ms` (bottom sheet) · easing `cubic-bezier(0.2, 0, 0, 1)`. Hormati `prefers-reduced-motion` (matikan shimmer & transisi non-esensial).

**Z-index scale:** `--z-nav: 10` · `--z-sticky-filter: 20` · `--z-sheet-backdrop: 30` · `--z-sheet: 40` · `--z-toast: 50`. Tidak ada z-index di luar skala.

#### 12.5 Formatting conventions — locale Indonesia _(baru v1.3; inkonsistensi format angka = tell AI slop paling halus)_

- **Angka:** desimal pakai koma — rating "**4,6**" (bukan 4.6); ribuan pakai titik. Konsisten di UI, share card, dan schema (schema.org JSON-LD tetap pakai titik — itu machine-readable, beda lapisan).
- **Jarak:** <1 km → "**850 m**"; ≥1 km → "**1,2 km**" (1 desimal, koma). Tidak pernah "0.85km" atau "1200m".
- **Harga:** "**Rp 15–30rb**" (en dash, singkatan "rb") untuk kisaran; tidak pernah "Rp15.000,00" atau "IDR 15K".
- **Waktu:** format 24 jam dengan titik ala Indonesia — "Buka · tutup **22.00**" (bukan 22:00 atau 10 PM). Zona selalu WITA, tidak perlu ditulis kecuali di konteks ambigu.
- **Tanggal relatif kasar:** "2 hari lalu", "minggu ini" — selaras privacy threat #4; tidak pernah timestamp menit.
- **Ikon: SATU set saja** (rekomendasi: Lucide), stroke konsisten 1.5–2px, ukuran 20/24px dari skala. Emoji BUKAN pengganti ikon — emoji hanya di microcopy personality (section 16), ikon hanya dari set. Jangan campur.
- **Truncation:** potongan review di kartu = 1 kalimat atau maks 90 karakter + "…" — potong di batas kata, bukan tengah kata.

---

### 13. Applied UX Laws

| Hukum                          | Penerapan konkret                                                                                                                                                 |
| ------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Hick's Law**                 | Maks 6 chip kategori default (+ "Lainnya" → bottom sheet 12 lengkap). Homepage maks 3 seksi. Form review 3 langkah.                                               |
| **Fitts's Law**                | CTA utama full-width di bawah layar, zona jempol. Navigasi = bottom bar. Bintang rating 48px, berjauhan.                                                          |
| **Jakob's Law**                | Kartu cafe = pola feed IG. Filter = chip horizontal-scroll ala Tokopedia/Google Maps. Login = bottom sheet ala Gojek. Detail = swipeable gallery ala marketplace. |
| **Miller's Law**               | Kartu cafe 5 chunk: foto, nama, rating+jumlah, 2 tag, jarak/potongan review. Jam buka = status tunggal ("Buka · tutup 22:00"), tabel 7 hari di balik satu tap.    |
| **Peak-End Rule**              | Layar sukses submit ber-personality + konfirmasi dampak + review langsung tampil. Bawah detail cafe: "Cafe mirip di dekat sini" — sesi berakhir dengan penemuan.  |
| **Zeigarnik Effect**           | Progress 3 langkah form review; draft otomatis + saat kembali: "Reviewmu tinggal selangkah lagi".                                                                 |
| **Aesthetic-Usability Effect** | Kualitas visual difokuskan ke kartu & detail. Foto user terbaik tampil dulu. Konsistensi token (section 12) > dekorasi.                                           |

### 14. Concrete UI Rules

**State komponen (wajib per komponen interaktif):**

- `hover` (desktop): surface naik 1 elevation; `active/pressed`: scale 0.98 + darken 8% (`--primary-pressed`); `focus-visible`: ring 2px `--focus-ring`; `disabled`: opacity 40%; `loading`: spinner-inline 16px DI DALAM tombol (satu-satunya spinner yang boleh ada), ukuran tombol tetap.
- `empty`: ilustrasi sederhana/emoji tunggal + 1 kalimat personality + 1 CTA.
- `error`: pesan manusiawi + aksi pemulihan, tidak pernah cuma "Terjadi kesalahan".

**Thumb hot zone map (mobile satu tangan):**

- **⅓ bawah (nyaman)** — aksi frekuensi tinggi: bottom nav 3 item (Jelajah · Cari · Kamu), CTA primer full-width, chip filter aktif (sticky di atas nav), drag handle drawer, FAB "Dekat sini" / toggle peta-list (kanan bawah).
- **Tengah (ok)** — konten scroll: kartu, review, foto.
- **⅓ atas (sulit)** — hanya elemen jarang: logo, judul, ikon share. Search bar tetap di atas (Jakob's Law) TAPI selalu bisa dipicu dari "Cari" di bottom nav.
- Aksi destruktif (hapus review, logout) di dalam drawer/menu — butuh niat. Back = gesture OS + breadcrumb, bukan tombol kiri-atas semata.

**Bottom drawer sebagai pola default (mobile):**

- Semua aksi sekunder = drawer dari bawah: login Google, filter lengkap, pilih area, quick-peek cafe, jam buka 7 hari, opsi review, form report. Modal tengah layar dilarang di mobile.
- Anatomi: drag handle, snap point **peek (±45%)** dan **full (±92%)**, swipe-down/tap backdrop untuk tutup, scroll internal saat full, hormati safe-area inset iOS.
- **Quick-peek cafe:** tap marker peta / long-press kartu → drawer peek: foto, nama, rating, 2 tag, jarak + "Lihat detail" & "Arah" — menimbang kandidat tanpa kehilangan konteks (pola Gojek/Grab).
- Implementasi: Alpine.js + CSS transform, animasi ≤ `--motion-sheet` (250ms).

**Loading = skeleton, bukan spinner halaman:** kartu skeleton (blok foto + 2 baris) dengan shimmer (`--skeleton-*`); jumlah skeleton = perkiraan hasil (3–5). Spinner layar penuh dilarang.

### 15. Anti AI-Slop Design — Explicit DON'Ts

- ❌ Gradient ungu→biru (atau gradient apa pun sebagai background). Satu-satunya gradient = `--img-scrim` di atas foto.
- ❌ Emoji bertaburan di UI kerangka. Emoji hanya di microcopy personality — maks satu per layar.
- ❌ Glassmorphism/blur tanpa fungsi. Blur hanya backdrop bottom sheet.
- ❌ Ilustrasi 3D stock generic. Hero content = foto asli user, titik.
- ❌ Placeholder lorem-ipsum vibes — contoh konten pakai nama cafe & kalimat review Makassar realistis.
- ❌ Semua kartu sama rata. Kartu pertama tiap seksi boleh lebih besar; rating & jarak lebih menonjol.
- ❌ Centered-everything. Rata kiri; center hanya empty state & layar sukses.
- ❌ Border-radius maksimal di semua elemen (aturan 3 nilai, section 12.4).
- ❌ Shadow berlebihan / kartu "melayang" semua.
- ❌ Microcopy kaku-korporat ("Silakan masukkan kredensial Anda").
- ❌ Popup rating app / newsletter / login di tengah jalur baca.
- ❌ Font dekoratif/script untuk heading.
- ❌ **Interpretasi ".space" sebagai luar angkasa** — bintang, galaksi, roket, nebula, partikel berkelip. ".space" = ruang/third place (12.0), titik.
- ❌ **Gambar AI-generated di mana pun** — placeholder, ilustrasi kosong, hero, materi share/marketing. Produk ini menjual "asli & jujur"; satu gambar sintetis meruntuhkan positioning. (Konsekuensi kebijakan: foto AI/stock di review user = dasar takedown — masuk Aturan Review.)
- ❌ Dark pattern apa pun: fake urgency ("3 orang lagi lihat cafe ini"), counter palsu, badge notifikasi tanpa isi, guilt-trip di tombol batal ("Nggak jadi bantu teman-temanmu?").
- ❌ Scroll-triggered entrance animation per kartu / parallax / stagger beruntun. Konten langsung ada; animasi hanya untuk feedback interaksi & transisi sheet.
- ❌ Maskot, dan persona sapaan "min/kak/sobat/gaes" di copy. Suara brand = teman sebaya yang to the point, bukan admin olshop.
- ❌ Quip/joke yang sama muncul verbatim di banyak tempat (tell AI slop). Setiap microcopy personality punya 2–3 varian yang dirotasi (file copy per kota sudah mendukung ini).
- ❌ ALL-CAPS + letter-spacing di body text; format angka campur aduk (4.6 di satu layar, 4,6 di layar lain) — konvensi 12.5 mengikat.

### 16. Gen Z Friendly — Explicit DOs

**Microcopy santai (Indonesia sehari-hari, boleh berasa Makassar tapi dipahami umum):**

| Konteks                  | Tulis begini                                                                       | Bukan begini                                   |
| ------------------------ | ---------------------------------------------------------------------------------- | ---------------------------------------------- |
| Empty state pencarian    | "Belum ketemu yang pas 😔 Coba lepas satu filter, atau usulkan cafe favoritmu!"    | "Tidak ada hasil ditemukan."                   |
| Empty state review       | "Belum ada yang cerita soal tempat ini. Jadi yang pertama, mi!"                    | "Belum terdapat ulasan."                       |
| Error jaringan           | "Sinyalnya lagi ngambek. Coba lagi sebentar, di'?"                                 | "Terjadi kesalahan jaringan."                  |
| Sukses submit review     | "Reviewmu tayang! Kamu barusan bantu sesama pemburu cafe nggak salah tempat 🙌"    | "Ulasan berhasil dikirim."                     |
| CTA login (bottom sheet) | "Login sebentar biar reviewmu kesimpan. Tenang, namamu tetap rahasia."             | "Silakan masuk untuk melanjutkan."             |
| Izin lokasi              | "Boleh tau posisimu? Biar yang paling dekat muncul duluan."                        | "Aplikasi memerlukan akses lokasi Anda."       |
| Rate limit               | "Santai dulu, reviewmu banyak sekali hari ini 😅 Lanjut lagi besok ya."            | "Anda telah melebihi batas pengiriman."        |
| Banner jam musiman       | "Jam khusus Ramadan — bisa beda dari biasanya. Salah? Kabari kami ya."             | "Jam operasional dapat berubah sewaktu-waktu." |
| 404                      | "Halaman ini kayak cafe yang kamu cari jam 3 subuh — nggak ada. Balik ke beranda?" | "404 Not Found."                               |

> **Catatan skala:** partikel lokal ("mi", "di'") disimpan sebagai varian copy per kota (`copy/makassar.php` dst.), bukan hardcode.

**Aturan DO lainnya:**

- ✅ Foto asli user = hero; cafe tanpa foto → placeholder pattern warna brand + inisial, bukan stock photo.
- ✅ Interaksi cepat: filter = tap chip, rating = tap bintang langsung, aksi sekunder di bottom sheet swipeable.
- ✅ Optimistic UI: review & rating tampil seketika, sinkron di belakang.
- ✅ Personality di detail kecil — tapi animasi ≤200ms dan tidak pernah menunda konten. Lucu vs cepat: pilih cepat.
- ✅ Konsisten "kamu" (bukan "Anda"), kalimat pendek, kata kerja di depan CTA.
- ✅ _(v1.3)_ "space" sebagai kata brand di copy — "cari space-mu buat nugas", "space baru buat wiken ini" — maksimal satu per layar (aturan 12.0).
- ✅ _(v1.3)_ **Web Share API native** untuk tombol share (sheet share OS yang user hafal); fallback copy-link + toast "Link kesalin!". Event `share_tap` tercatat.
- ✅ _(v1.3)_ **Sticky CTA "Tulis review"** muncul di detail cafe SETELAH user scroll melewati blok review — tepat saat konteks "aku juga pernah ke sini" terbentuk; bukan sejak awal (jangan halangi jalur baca).
- ✅ _(v1.3)_ Riwayat pencarian terakhir (maks 3 chip, simpan lokal) di layar cari — mempercepat pola "cek cafe yang sama lagi".
- ✅ _(v1.3)_ **Rasio foto tetap:** kartu 4:3, galeri detail 4:3 tinggi tetap — layout tidak pernah lompat (anti-CLS), grid selalu rapi apa pun foto usernya.
- ✅ _(v1.3)_ Varian microcopy dirotasi (lihat DON'T quip verbatim) — personality terasa hidup, bukan template.

### 17. Do & Don't Summary Table (scan cepat developer)

| Area           | ✅ DO                                                          | ❌ DON'T                                                          |
| -------------- | -------------------------------------------------------------- | ----------------------------------------------------------------- |
| Akses konten   | Semua terbaca tanpa login                                      | Gate/popup login di jalur baca                                    |
| Login          | Bottom sheet Google, hanya saat aksi tulis, kembali ke konteks | Halaman register, form email/password                             |
| Warna          | Token semantik section 12, terracotta + leaf, dark mode        | Gradient ungu-biru, hex hardcode di komponen                      |
| Radius         | 8 / 12 / full                                                  | Semua elemen 24px                                                 |
| Loading        | Skeleton kartu                                                 | Spinner layar penuh                                               |
| Foto           | Foto user asli, WebP, lazy, srcset                             | Stock photo, ilustrasi 3D, full-res                               |
| Copy           | "kamu", santai, aksi jelas                                     | "Anda", korporat, pasif                                           |
| Modal          | Bottom sheet swipeable                                         | Modal tengah layar di mobile                                      |
| Filter         | Chip tap-langsung, maks 6 tampil                               | Dropdown + Apply, 15 opsi sekaligus                               |
| Rating kosong  | "Jadi yang pertama?"                                           | "0.0 (0 review)"                                                  |
| Rating agregat | Hanya `published`, denormalized + recompute                    | Hitung on-the-fly termasuk pending                                |
| Ranking        | Bayesian avg + slot eksplorasi cafe baru                       | Sort by rating mentah / review terbanyak                          |
| Review anonim  | Alias HMAC per-cafe, user_id di backend                        | Anonim total tanpa akuntabilitas                                  |
| Jarak          | Haversine dari koordinat tersimpan                             | Maps API per request                                              |
| Anim           | ≤200ms, tidak menunda konten                                   | Animasi beruntun tiap elemen                                      |
| Hot zone       | Aksi sering di ⅓ bawah                                         | CTA penting hanya di pojok atas                                   |
| Tambah cafe    | Login + pin peta + foto + approval                             | Form bebas / API terbuka                                          |
| Vendor         | Free tier + standar terbuka                                    | Berbayar / proprietary lock-in                                    |
| Privasi        | Alias HMAC, timestamp kasar, no-PII test CI                    | user_id/email di JSON, EXIF utuh                                  |
| Server         | DB/Redis localhost, origin hanya IP Cloudflare                 | Port DB publik, origin bisa diakses langsung                      |
| Backup         | DB + foto (versioning/bucket kedua), teruji restore            | pg_dump saja, restore tak pernah diuji                            |
| Launch         | Gerbang review coverage + checklist 8.4 hijau                  | Launch by feeling saat "fitur selesai"                            |
| Jam buka       | Override musiman + banner + audit bergilir                     | JSON statis dibiarkan basi setahun                                |
| Brand ".space" | Ruang/third place, negative space, dot mark                    | Bintang, galaksi, roket, gradient kosmik                          |
| Aksen          | Petrol ≤10%, non-CTA, satu aksen per komponen                  | Petrol di CTA, dua aksen numpuk                                   |
| Ikon           | Satu set (Lucide), stroke & ukuran konsisten                   | Campur set, emoji sebagai ikon                                    |
| Imagery        | Foto user asli; placeholder pattern brand                      | Gambar AI-generated, stock photo                                  |
| Format angka   | Locale ID: "4,6" · "1,2 km" · "Rp 15–30rb" · "22.00"           | "4.6", "1200m", "IDR 15K", "10 PM"                                |
| Arsitektur     | Controller tipis → Action per use case → event lintas domain   | Logika bisnis di controller, repository ceremony di atas Eloquent |
| Share          | Web Share API native + copy-link fallback                      | Deret tombol share 10 sosmed                                      |
| Error handling | Exception domain + handler global satu pintu                   | try/catch presentasi tersebar di controller                       |
| Logging        | JSON + request_id, no-PII, rotasi 14 hari                      | `Log::info` sembarang, email/token di log                         |
| Alert          | 3 kanal → satu email; heartbeat cron; diam saat sehat          | Dashboard dicek manual, alert rutin di-ignore                     |
| Jobs           | Idempotent, retry 3× backoff, failed_jobs + Sentry             | Job sekali jalan tanpa retry, gagal senyap                        |

---

## D. Closing

### 18. Risks & Assumptions

| Risiko                                              | Dampak                                                | Mitigasi MVP                                                                                                                                                                                  |
| --------------------------------------------------- | ----------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Review palsu / serangan rating**                  | Merusak diferensiasi "jujur" — nyawa produk           | 1 akun Google = 1 review/cafe, rate limit, heuristik akun-baru, report ≥3 → pending, admin takedown + ban. Bayesian average juga meredam efek segelintir rating ekstrem. Fase 1: deteksi pola |
| **Cold start — direktori kosong**                   | Aha moment mustahil                                   | **Gerbang kuantitatif section 8.1** (60–80 cafe + ≥150 review + coverage ≥80%); launch ditunda/di-scope kalau tidak lolos; cafe tanpa review tidak dipromosikan homepage                      |
| **Distribusi gagal (volume tidak datang)**          | Semua metrik rasio tidak valid, keputusan produk buta | G0 sebagai gerbang validitas; playbook kanal 8.2; jika hari-30 <250 visitor/minggu → perbaiki distribusi, freeze fitur                                                                        |
| **Data cafe sampah / spam usulan**                  | Direktori tidak dipercaya, SEO konten tipis           | Governance section 10: login + foto wajib + pin peta + dedup 2 lapis + approval + rate limit + throttle                                                                                       |
| **Data basi (jam buka, cafe tutup)**                | Trust turun ("katanya buka")                          | Override musiman + banner Ramadan, audit klaster bergilir, heuristik decay 6 bulan, report `info_salah` prioritas                                                                             |
| **Somasi / keberatan pemilik cafe (UU ITE)**        | Risiko hukum + reputasi                               | Prosedur keberatan resmi + SLA 3×24 jam + suspend sementara + audit log (section 10); identitas backend memungkinkan penindakan tanpa buka ke publik; PSE terdaftar                           |
| **Moderasi konten anonim oleh solo dev**            | Konten kasar/fitnah lolos                             | Auto-flag + antrian Filament + cek harian + email digest; Aturan Review tertulis                                                                                                              |
| **Biaya maps membengkak**                           | Bootstrap mati di tagihan                             | OSM/MapLibre + koordinat tersimpan; geocoding hanya seeding                                                                                                                                   |
| **Kehilangan foto (R2 credential bocor/terhapus)**  | Hero content hilang permanen                          | Versioning/bucket kedua + credential terpisah (section 10 Ops)                                                                                                                                |
| **Asumsi: pembaca mau menulis tanpa insentif poin** | G3 gagal                                              | Peak-End + framing "bantu sesama"; jika G3 <1% (dari pembaca) setelah 30 hari → uji insentif ringan non-gamifikasi (highlight "review terbantu minggu ini")                                   |
| **Asumsi: web (bukan native) cukup**                | Distribusi lambat                                     | Share-link WA + SEO = kanal alami kampus; PWA installable jalan tengah                                                                                                                        |
| **Asumsi: timeline 12 minggu solo dev realistis**   | Molor = momentum kalender akademik lewat              | Potong dari ekor (F7b/F8 menyusul), jangan dari hardening/legal; target launch fleksibel ke jendela akademik berikutnya                                                                       |

### 19. Resolved in v1.2 (keputusan yang sudah diambil dari daftar konfirmasi v1.1)

Diasumsikan final kecuali kamu koreksi: stack Laravel 11 + Livewire + Filament · foto wajib menempel review · taksonomi 12 kategori · alias beda per cafe (anti-profiling; reputasi menyusul Fase 2) · struktur URL `/{city}/{slug}` + kategori-kota · hapus akun = anonimkan permanen dengan opsi hapus total · governance usul cafe (3/hari, 150m, throttle 3 reject) · snap drawer 45%/92% + bottom nav 3 item · jeda publikasi acak OFF di MVP (timestamp kasar saja) · 2FA TOTP admin sejak hari pertama · wedge sempit produk lebar · batas umur 13+.

### 20. Needs Confirmation (asumsi baru v1.2 — setujui/koreksi)

1. **Brand tetap working title "Ngafe"** — sesuai arahanmu, dibiarkan dulu. Yang tetap harus jalan pre-launch: cek domain + cek merek DJKI (checklist 8.4). Konfirmasi hanya saat mau final.
2. **G3 baru = ≥2% dari pembaca (30 hari)** — angka konservatif untuk platform review; kalau menurutmu terlalu rendah/tinggi, sebut angkamu.
3. **G0 volume floor 500/minggu (hari-30)** — realistis untuk soft launch 10–15 grup WA? Kalau jaringanmu lebih kecil, turunkan ke 300 dan geser target 90 hari.
4. **Founding reviewer 25–30 orang × 5–8 review (≥150 review)** — sanggup direkrut dari circle-mu? Kalau tidak, opsi: soft launch 1 klaster saja dengan gerbang coverage yang sama.
5. **Timeline 12 minggu asumsi full-time** — kondisimu full-time atau part-time? Ini mengubah target jendela kalender akademik.
6. **PSE Kominfo diajukan pre-launch** (bukan setelah traction) — setuju menanggung effort administratifnya di minggu 11?
7. **Email provider: Resend free tier** — ok, atau ada preferensi (Brevo/SES)?
8. **Ranking: Bayesian prior m=3.8, C=5; slot eksplorasi 2/10 kartu** — angka awal, dikalibrasi setelah data nyata. Ok sebagai default?
9. **Palet token section 12** (terracotta `#C4451C` + leaf `#2D6A4F` + sand warm neutral + gold star `#E8A317`, skala 50–900, semantik light/dark) — sesuai selera? Ini fondasi hi-fi; koreksi sekarang lebih murah daripada setelah komponen jadi.
10. **Dark mode primary dinaikkan ke `terra-400`** demi kontras — ok, atau mau tetap satu hex primary dua mode (konsekuensi: kontras dark mode di bawah 4.5:1 untuk teks kecil)?
11. **Audit klaster bergilir 1 klaster/bulan** — masuk kapasitasmu (±2–3 jam/bulan)?
12. **Backup foto via R2 object versioning** (paling murah effort) vs rclone ke bucket kedua — pilih mana?
13. **Domain ngafe.space** _(v1.3)_ — RDAP tidak menemukan record (indikasi tersedia); amankan segera? Catat: renewal .space bisa 5–10× harga promo tahun-1 — cek harga perpanjangan di registrar sebelum commit. Amankan `ngafe.id` juga sebagai cadangan (±IDR 250rb/thn)?
14. **Aksen dingin petrol `#2C6E72`** _(v1.3)_ — sreg dengan seleramu? Alternatif: tanpa aksen dingin sama sekali (palet full hangat, lebih minimal) — konsekuensi: banner info & link ikut terracotta, hirarki warna lebih tipis.
15. **Level arsitektur "modular monolith + Actions"** _(v1.3)_ — tanpa repository interface & hexagonal ceremony. Setuju, atau kamu punya preferensi struktur lain yang sudah kamu hafal (konsistensi dengan kebiasaanmu > kemurnian pattern)?
16. **Larangan total gambar AI-generated** _(v1.3)_ — termasuk untuk materi marketing/share card. Ini komitmen brand, bukan cuma aturan desain. Setuju?
17. **Healthchecks.io sebagai kanal alert ketiga** _(v1.4)_ — heartbeat cron/queue, free tier, satu email tujuan yang sama. Ok, atau kamu mau semua heartbeat dilewatkan Sentry Crons saja (satu vendor lebih sedikit, tapi kuota free Sentry lebih ketat)?
