# Product Requirements Document (PRD)
**Nama Aplikasi:** YB DX Contest Robot

## 1. Pendahuluan
Aplikasi **YB DX Contest Robot** adalah platform ajudikasi (penilaian otomatis) dan portal informasi terpadu untuk acara amatir radio YB DX Contest. Aplikasi ini dirancang untuk menerima laporan hasil kontak (QSO) dari para peserta dalam format Cabrillo, memvalidasi dan menghitung skor secara otomatis, melakukan pemeriksaan silang (cross-check), dan mempublikasikan peringkat peserta ke publik. Aplikasi ini dibangun dengan antarmuka pengguna yang futuristik, modern, dan elegan.

## 2. Fitur Utama

### 2.1 Portal Informasi Kontes
Aplikasi menyediakan laman informasi publik yang merangkum:
- **Jadwal Kontes:** Pelaksanaan kontes dijadwalkan secara rutin pada **setiap hari Sabtu di minggu kedua bulan Januari**, mulai pukul **00:00 UTC hingga 23:59 UTC**. Jadwal ini akan ditampilkan lengkap dengan perhitungan mundur (*countdown*) menuju tanggal penyelenggaraan terdekat, serta menampilkan rilis daftar proyeksi jadwal pelaksanaan kontes untuk 5 tahun ke depan secara terkomputasi otomatis.
- **Band & Mode:** Pita frekuensi yang diizinkan (3.5, 7, 14, 21, dan 28 MHz) dan Mode (SSB).
- **Peraturan (Rules):** Informasi detail terkait aturan kontes untuk Peserta Dalam Negeri (Indonesia) maupun Luar Negeri (Internasional).
- **Kategori Peserta:** Sistem mengelompokkan peserta secara spesifik berdasarkan klasifikasi *header* Cabrillo berikut:
  - **CATEGORY-OPERATOR:**
    - `SINGLE-OP`: Hanya diizinkan memiliki 1 (satu) *callsign* operator.
    - `MULTI-OP`: Membutuhkan minimal 2 (dua) *callsign* operator.
    - `CHECKLOG`: Peserta yang hanya mengirimkan data QSO semata-mata untuk keperluan pemeriksaan silang (*cross-checking*) dengan file Cabrillo peserta lain. Entri *Checklog* **tidak** diikutkan dalam proses ajudikasi/penilaian, skornya tidak akan dihitung maupun ditampilkan di papan peringkat, dan secara otomatis tidak berhak atas hadiah apa pun (termasuk sertifikat/piagam).
  - **CATEGORY-POWER:** Pengelompokan daya pancar meliputi `HIGH`, `LOW`, dan `QRP`.
  - **CATEGORY-BAND:** Menentukan baris data QSO mana yang diakui dalam penjurian:
    - `ALL`: Seluruh valid QSO dari *band* 80M, 40M, 20M, 15M, dan 10M akan dihitung skornya.
    - `80M` / `40M` / `20M` / `15M` / `10M`: Hanya valid QSO yang dilakukan secara spesifik pada *band* tunggal tersebut yang akan dihitung poin dan *multiplier*-nya (QSO di *band* lain diabaikan dalam penghitungan akhir).
- **Plakat Hadiah:** Menampilkan daftar plakat yang tersedia pada sebuah laman khusus. Informasi disajikan dalam bentuk tabel yang memuat kolom: Nama Plakat, Kategori, dan Sponsor. Seluruh isi dari daftar hadiah ini bersifat dinamis dan dapat ditambah, diubah, maupun dihapus oleh pengguna level Manajer melalui antarmuka *backend*.
- **Daftar Pengurus Kontes (Committee):** Menampilkan susunan nama, *callsign*, dan jabatan para pengurus kontes dalam bentuk tabel publik. Data kepengurusan ini sepenuhnya bersifat dinamis dan dikelola (ditambah, diubah, dihapus) secara eksklusif oleh Manajer melalui *backend*.
- **Komentar Peserta (Soapbox):** Aplikasi menyediakan laman publik khusus untuk menampilkan data komentar peserta yang diekstrak secara otomatis dari tag `SOAPBOX:` pada *header* Cabrillo. Tampilan komentar disajikan dalam struktur tabel namun didesain menyerupai antarmuka obrolan (*chat interface*). Setiap entri memuat ikon profil (orang), *callsign* pemilik komentar, serta isi komentar yang dibungkus dalam *text balloon* (*chat bubble*). Data diurutkan secara *descending*, di mana peserta dengan waktu pengiriman log terakhir (*latest timestamp*) akan menempati posisi teratas.

### 2.2 Sistem Penerimaan & Validasi File Cabrillo
Aplikasi menyediakan formulir (*submission form*) interaktif pada *website* publik yang dapat digunakan oleh masing-masing peserta untuk mengirimkan (mengunggah) file laporan QSO mereka dalam format standar **Cabrillo**.
- **Validasi Teknis:** Robot akan memeriksa validitas teknis file Cabrillo yang diunggah (seperti tag `START-OF-LOG`, `END-OF-LOG`, kelengkapan *header`, dan format `QSO:`).
  - **Aturan Mode:** Kontes ini secara eksklusif menggunakan mode **SSB**. Oleh karena itu, tag kategori pada *header* (contoh `CATEGORY-MODE:`) diwajibkan bernilai `SSB`. Lebih lanjut, seluruh baris data kontak (`QSO:`) harus mencatatkan kolom mode secara spesifik sebagai `PH`. Jika ditemukan baris QSO yang modenya bukan `PH`, maka aplikasi akan otomatis menandainya sebagai `X-QSO:` (baris tersebut dianulir dari penjurian/tidak mendapat poin).
  - **Aturan Kategori Operator:** Jika peserta berada pada kelompok **SINGLE OP**, tag `OPERATORS:` pada *header* hanya boleh berisi maksimal 1 (satu) *callsign*. Jika peserta pada kelompok **MULTI OP**, maka tag `OPERATORS:` wajib memuat lebih dari satu *callsign* operator yang terlibat.
- **Validasi Keamanan Data (Anti-Cheat):** 
  - *Callsign* peserta yang tercantum pada bagian *header* Cabrillo (`CALLSIGN:`) **wajib sama persis** dengan *callsign* pengirim (*sent call*) yang tercatat di dalam setiap baris data QSO.
  - **Self-QSO Detection:** Peserta tidak diizinkan mencatatkan komunikasi (QSO) dengan dirinya sendiri. Jika *Received Call* sama dengan *Sent Call* atau *Header Callsign*, baris tersebut otomatis dicoret (X-QSO).
  - Jika ditemukan pelanggaran fatal, file otomatis ditolak atau baris dibatalkan.
- **Validasi Waktu QSO:** Robot akan memeriksa catatan waktu dari setiap QSO. QSO dianggap valid hanya jika terjadi selama rentang jadwal kontes berlangsung. Jika terdapat baris QSO dengan waktu di luar jadwal kontes, aplikasi otomatis menandainya sebagai `X-QSO:` sehingga tidak akan dihitung poinnya.
- **Otomatisasi Konversi Cabrillo v2 ke v3:** Jika sistem mendeteksi peserta mengunggah file **Cabrillo versi 2 (v2)**, aplikasi akan mengonversinya secara otomatis ke format standar **Cabrillo versi 3 (v3)**. Apabila dalam proses konversi tersebut terdapat data *header* wajib v3 yang kurang (karena perbedaan versi), aplikasi akan berinteraksi langsung (meminta *input*) dari peserta di layar *web* saat itu juga. **Ketentuan Mutlak:** Konversi interaktif ini *hanya* berlaku untuk baris informasi *Header*. Baris data **QSO tidak boleh diubah** atau dimanipulasi sama sekali oleh sistem selama proses konversi.
- **Penanganan Error Teknis & Perbaikan Real-Time:** Apabila ditemukan kesalahan saat proses validasi, aplikasi akan menampilkan rincian kesalahannya secara spesifik dan memfasilitasi perbaikan interaktif:
  - **Kesalahan & Abnormal Header:** Jika terdapat *header* yang kosong atau berisi nilai tidak standar (contoh: `CATEGORY-POWER: LOW-YD/YG`), sistem tidak akan membenahinya secara diam-diam (*no autofix*). Sebaliknya, sistem menandainya sebagai tidak valid dan memunculkan *form* perbaikan (*dropdown*) yang memaksa peserta memilih nilai standar yang sah (contoh: HIGH, LOW, QRP). Berdasarkan *input* tersebut, aplikasi otomatis menyusun ulang (*re-generate*) file Cabrillo yang benar.
  - **Kesalahan Data QSO:** Jika kesalahan terletak pada baris data QSO (seperti format salah atau *callsign* tidak cocok dengan header), perbaikan tidak dapat dilakukan secara *real-time*. Sistem hanya akan memberitahu rekapitulasi: berapa jumlah QSO yang Valid dan berapa yang *Error*.
  - **Force Submit (Abaikan Error QSO):** Jika peserta memilih untuk tetap mengirimkan (menyetujui) file tersebut meski masih ada baris QSO yang *error*, maka aplikasi akan secara otomatis mengubah tag pada baris yang bermasalah tersebut dari `QSO:` menjadi `X-QSO:`. Baris `X-QSO:` ini dipastikan tidak akan dihitung dalam proses ajudikasi selanjutnya.
  - **Pembatalan (Cancel):** Peserta memiliki opsi (tombol Batal) untuk membatalkan seluruh proses validasi jika merasa perlu memeriksa dan memperbaiki file Cabrillo secara manual secara *offline*.
- **Syarat Kode Akses:** Peserta tidak akan mendapatkan Kode Akses (PIN 6 digit) jika belum berhasil menyelesaikan alur pengiriman log Cabrillo hingga tuntas (termasuk memperbaiki atau menyetujui *error* tersebut).

### 2.3 Perhitungan Awal, Konfirmasi, & Keamanan (RAW Score)
Apabila file Cabrillo yang dikirim peserta dinyatakan **valid secara teknis**, maka aplikasi akan menjalankan alur berikut:
1. **Perhitungan Sementara:** Aplikasi menghitung data QSO dan langsung menampilkannya kepada peserta, meliputi: Total QSO (tidak termasuk duplikat), Total QSO per *band*, Total QSO duplikat, Total negara/DXCC, Total poin, Total multiplier, dan Total skor sementara (*RAW Score*).
   
   **Metode Perhitungan Poin:**
   - **Peserta Indonesia:** QSO dengan stasiun Indonesia mendapat **0 poin**. QSO dengan stasiun di benua yang sama (OC) mendapat **5 poin**. QSO dengan stasiun di benua berbeda mendapat **10 poin**.
   - **Peserta Internasional (DX):** QSO dengan stasiun di negara yang sama mendapat **1 poin**. QSO dengan stasiun di benua yang sama mendapat **2 poin**. QSO dengan stasiun di benua berbeda mendapat **3 poin**. Khusus QSO dengan stasiun Indonesia mendapat **10 poin**.
   
   **Metode Perhitungan Multiplier:**
   - Setiap QSO yang memecahkan rekor pertama kali berkomunikasi dengan **Prefix** baru di suatu *band* akan mendapatkan **1 multiplier**.
   - Setiap QSO yang memecahkan rekor pertama kali berkomunikasi dengan **Negara (DXCC)** baru di suatu *band* akan mendapatkan **1 multiplier**.
   - Apabila sebuah QSO memecahkan rekor Prefix dan DXCC sekaligus pada *band* tersebut, maka QSO itu berhak mendapatkan **2 multiplier**.
   - (Peserta DX hanya mendapatkan multiplier dari Prefix dan DXCC khusus milik stasiun Indonesia).
   - **Stasiun Portable:** *Callsign* dengan *modifier* seperti `YB/JH1HUT` atau `JH1HUT/YB` akan dibaca sebagai Prefix `YB1` dan DXCC `INDONESIA`. `YB1AR/5` akan dibaca sebagai Prefix `YB5`. *Modifier* operasional seperti `/P`, `/M`, `/QRP` diabaikan dalam penentuan *prefix*.
   - **Aturan Out-of-Band (Kategori Single Band):** Jika peserta memilih kategori band tunggal (misal `20M`), namun mencatatkan QSO di frekuensi lain (misal `40M`), QSO tersebut akan mendapat **0 Poin dan 0 Multiplier** bagi dirinya. Namun, QSO tersebut **tidak dicoret** (tidak di-X-QSO), sehingga stasiun lawan tetap berhak mendapatkan poin dan multiplier dari kontak tersebut saat ajudikasi silang.

   **Total Skor Sementara = Total Poin × Total Multiplier**.

2. **Konfirmasi Persetujuan:** Aplikasi akan meminta konfirmasi kepada peserta apakah setuju dengan hasil perhitungan sementara tersebut.
3. **Penyimpanan File:** Jika peserta menyatakan **setuju**, aplikasi akan menyimpan file Cabrillo tersebut secara fisik ke dalam folder server dengan struktur `ValidQSO/$(tahun_kontes_berjalan)` untuk nantinya diproses pada tahap pemeriksaan silang (Ajudikasi).
4. **Kode Akses Keamanan:** Setelah file tersimpan, aplikasi akan diatur untuk memberikan informasi berupa **Kode Akses (6 digit angka)** di layar. Kode akses ini beserta laporan penerimaan file Cabrillo juga akan **dikirim otomatis melalui email** yang didaftarkan peserta.
5. **Re-Submission (Pengiriman Ulang):** Jika peserta dengan *callsign* yang sama ingin mengunggah file Cabrillo revisi, sistem akan meminta **Kode Akses** yang telah diberikan sebelumnya.
   - Apabila peserta melampirkan kode akses yang benar, maka file terbaru akan diterima dan diproses.
   - Apabila kode tidak disertakan atau salah, aplikasi akan menolak pengunggahan file atas alasan keamanan. Pengecualian hanya bisa dilakukan melalui intervensi/approval dari Manajer Kontes yang dapat melihat kode akses peserta.
6. **Publikasi RAW Score:** Skor sementara dari file yang telah mendapat persetujuan ini akan dipublikasikan ke halaman "RAW Score".

### 2.4 Sistem Cross-Check & Ajudikasi (Skor Akhir)
Setelah batas waktu pengumpulan log ditutup, robot akan menjalankan proses *cross-checking* (pemeriksaan silang) antar log semua peserta untuk mendapatkan **Skor Akhir**. Aturan *cross-checking* adalah sebagai berikut:

- **Valid QSO:** Jika YB0HHH mencatat QSO dengan JA1UUU, dan log milik JA1UUU mencatat hal yang persis sama, maka QSO dianggap valid dan poin beserta multiplier diberikan secara normal.
- **Busted QSO:** Jika data QSO ditemukan secara waktu, band, dan mode, namun terdapat kesalahan pencatatan data/kode pertukaran (*exchange* / NR), maka QSO tersebut dianggap *Busted*. **QSO ini tidak mendapatkan poin dan tidak mendapat multiplier**.
- **Duplicate QSO:** Jika seorang peserta mencatat data QSO yang sama sebanyak 2 kali atau lebih dalam *band* yang sama, maka aplikasi hanya menghitung data pertama. Catatan yang berlebih ditandai duplikat dan tidak mendapat poin.
- **Unique QSO:** Peserta/callsign *Unique* adalah *callsign* yang tercatat di dalam basis data (seluruh log masuk) pada **kurang dari 3 peserta yang berbeda**. Jika sebuah QSO berstatus *Unique*, QSO tersebut **tidak mendapatkan poin dan tidak mendapat multiplier**.
- **Not In Log (NIL):** Sebuah QSO dianggap *NIL* (dan **tidak mendapatkan poin serta tidak mendapat multiplier**) apabila memenuhi salah satu kondisi berikut:
  - QSO yang dicatat tidak ditemukan di dalam log lawan komunikasi.
  - Terdapat perbedaan catatan waktu lebih dari **30 menit** antar kedua log.
  - Terdapat ketidakcocokan *Band* atau *Mode* antar kedua log.
  - *Callsign* tercatat dengan salah (contoh YB1UUU tercatat sebagai YB2UUU).

*Penghitungan Skor Akhir:* Setelah proses penyaringan di atas selesai, robot hanya mengumpulkan sisa baris QSO yang berstatus **Valid**. Robot menghitung ulang Total Poin dan Multiplier berdasarkan sisa QSO tersebut secara dinamis per *band*. **Total Skor Akhir = Total Poin Valid × Total Multiplier Valid**.

### 2.5 Laporan Hasil Ajudikasi (File UBN)
Bersamaan dengan pengumuman Skor Akhir, robot akan membuatkan sebuah file laporan dengan istilah **UBN (Unverified, Busted, Not In Log)** untuk setiap peserta.
Isi dari file UBN meliputi:
- **Informasi Dasar:** Data peserta yang diambil dari *header* file Cabrillo.
- **Statistik Hasil:** Total QSO, jumlah QSO per band, total poin per band, total multiplier per band, dan **Skor Akhir**.
- **Detail Analisis:** Informasi berapa jumlah QSO yang *Duplicate*, jumlah *Unique*, *Busted*, dan *Not In Log*, dilengkapi dengan lampiran **baris data QSO** spesifik yang termasuk ke dalam kategori-kategori tersebut agar peserta mengetahui letak kesalahannya.

### 2.6 Papan Peringkat (Leaderboard) Publik & Diskualifikasi
Pengumuman Skor Akhir (begitu pula Skor Sementara / RAW Score) disajikan dalam wujud **Tabel Datar (*Flat Table*)** yang bersih, padat, dan seragam ala *Received Logs*. Atribut seperti Band, Power, dan Benua ditampilkan secara sejajar di bagian judul kelompok.

Tabel Papan Peringkat disusun dengan prioritas pengurutan/pemisahan (meski visualnya datar) berdasarkan:
1. Kategori Operator -> Band -> Power -> Benua -> Negara.
2. Di dalam kelompok tersebut, tabel disajikan memanjang memuat kolom Rank, Callsign, QSO, Poin, Mult, dan Raw Score.

3. **Pengurutan (*Sorting*):** Di dalam kelompok terspesifik tersebut, peserta diurutkan secara menurun berdasarkan:
   - *Total Skor* Tertinggi
   - *Jumlah Poin* Tertinggi (jika skor sama)
   - *Jumlah QSO* Tertinggi (jika poin sama)
   - *Multiplier* Tertinggi (jika QSO sama)

**Klasemen Klub (Club Competition):**
Aplikasi wajib memiliki antarmuka khusus yang mempublikasikan **Peringkat Klub**.
- Sistem akan mendata nama klub peserta yang diekstrak langsung dari tag `CLUB:` pada *header* file Cabrillo mereka.
- Papan peringkat klub disusun berdasarkan agregasi (akumulasi) Total Skor dari seluruh peserta yang bernaung di bawah nama klub yang persis sama.
- Informasi yang disajikan meliputi: Peringkat, Nama Klub, Total Skor Gabungan, dan Jumlah Peserta penyumbang skor untuk klub tersebut.

**Penanganan Diskualifikasi:**
- Pada laman pengumuman skor akhir ini, sistem juga secara transparan akan menampilkan daftar peserta yang terkena status **Diskualifikasi**.
- Melalui antarmuka *backend*, **Manajer** memiliki kewenangan untuk menandai peserta yang terbukti melakukan pelanggaran sebagai peserta yang didiskualifikasi pada kontes tahun berjalan.
- **Sanksi Konsekuensi:** Peserta yang berstatus diskualifikasi secara otomatis digugurkan haknya dan **tidak berhak mendapatkan apresiasi atau hadiah dalam bentuk apa pun** pada tahun tersebut (termasuk tidak akan bisa mengunduh piagam/e-Certificate maupun memenangkan plakat).

### 2.7 Statistik Kontes & Rekor Sepanjang Masa (All-Time High / ATH)
Aplikasi akan secara otomatis mencatat, merangkum, dan menampilkan data historis kontes kepada publik:
- **Statistik Kontes:** Menampilkan ringkasan pencapaian secara keseluruhan:
  - Jumlah peserta setiap tahun.
  - Total negara atau DXCC yang berpartisipasi.
  - Total QSO secara keseluruhan dan rincian total QSO per *band*.
  - Rincian jumlah peserta berdasarkan *Continent* (Benua) dan berdasarkan Negara / DXCC.
- **All-Time High (ATH) Records:** Aplikasi akan merekam dan mempublikasikan skor-skor tertinggi yang pernah diraih sepanjang masa penyelenggaraan kontes. Papan rekor ATH wajib dikelompokkan secara spesifik mengikuti hierarki prioritas: **Kategori Operator -> Kategori Band -> Kategori Power -> Benua -> Negara**.
  - **Data Rekor:** Setiap catatan rekor ATH wajib mencantumkan informasi detail: *Callsign* peserta peraih rekor, Jumlah QSO, Total Skor, dan Tahun berapa rekor tersebut diraih.

### 2.8 Manajemen Akses & Level Pengguna (User Roles)
Aplikasi menerapkan pembatasan hak akses untuk menjaga keamanan data:
- **Level Peserta (Participant):** Hak akses ini berbasis *Callsign* dan otentikasi **Kode Akses (6 digit)** yang diberikan saat *submit* pertama kali. Hak yang dimiliki peserta meliputi:
  1. Melihat semua informasi kontes, statistik, dan papan peringkat yang bersifat publik.
  2. Melihat hasil perhitungan detail dari *score* sementara milik pribadi dengan cara memasukkan Kode Akses yang telah dikirimkan ke email mereka.
  3. Mengganti atau mengirimkan ulang (*re-submit*) file Cabrillo terbaru dengan melampirkan Kode Akses sebagai syarat wajib.
- **Level Manajer (Manager):** Level pengelola operasional kontes, dengan hak akses meliputi:
  1. Mengubah informasi konten publik seperti Peraturan Kontes, Daftar Plakat Hadiah, dan Daftar Pengurus (*Committee*).
  2. Mengatur konfigurasi akun **SMTP Email** yang digunakan aplikasi untuk pengiriman notifikasi otomatis.
  3. Melihat daftar Kode Akses milik masing-masing peserta.
  4. Menghapus data peserta, yang mencakup penghapusan file Cabrillo dari server beserta seluruh basis data terkait milik peserta tersebut.
- **Level Administrator:** Level tertinggi (Super Admin) di dalam sistem. Administrator secara absolut mewarisi seluruh hak akses yang dimiliki oleh level Peserta maupun Manajer (termasuk kewenangan mengatur SMTP), serta berhak mengendalikan aspek sistem secara menyeluruh.

### 2.9 Halaman "Received Logs" (Daftar Log Diterima)
Aplikasi menyediakan laman publik **Received Logs** yang memuat daftar seluruh peserta yang telah berhasil mengirimkan file Cabrillo dan lolos validasi teknis.
- **Fitur Pencarian:** Tersedia kotak penelusuran (pencarian) interaktif di bagian atas agar peserta dapat dengan cepat menemukan data *log* miliknya di tengah ribuan *log* lain berdasarkan *Callsign*.
- Data disajikan dalam format tabel datar (*flat*) namun tetap dikelompokkan secara visual berdasarkan prioritas: **Kategori Operator -> Kategori Band -> Kategori Power -> Benua -> Negara**.
- Detail informasi pada tabel meliputi: Rank, *Callsign* peserta, Nama Negara/DXCC, Jumlah QSO, Jumlah *Band* yang digunakan, serta Waktu (*timestamp*) penerimaan file Cabrillo terakhir.

### 2.10 Sertifikat Elektronik (e-Certificate) & Laman Unduhan
Aplikasi menyediakan fasilitas piagam penghargaan yang di- *generate* dalam format **PDF** (menggunakan *library* **mPDF**) secara cuma-cuma (gratis) kepada peserta kontes.
- **Laman Unduh Piagam:** Peserta dapat memasukkan *Callsign* mereka pada sebuah laman khusus pencarian piagam.
- **Riwayat Partisipasi:** Aplikasi akan memunculkan tabel riwayat keikutsertaan (kapan saja *callsign* tersebut mengikuti kontes YB DX) beserta tombol unduh (*download*) piagam untuk setiap tahun penyelenggaraan.
- **Generate On-Demand:** Dokumen PDF piagam baru akan dibuat / di-*generate* secara dinamis (langsung pada saat itu juga) ketika peserta meng-klik tombol unduh tersebut.
- **Informasi Prestasi:** Piagam akan memuat data prestasi peserta yang bersangkutan (seperti: Total QSO, Total Skor Akhir, Peringkat di Benua, dan Peringkat di Negara/DXCC).
- **Desain Khusus:** Pengguna level Manajer berhak untuk mengunggah dan mengatur desain gambar latar belakang (*background*) piagam sesuai dengan tema penyelenggaraan setiap tahunnya melalui antarmuka *backend*.

### 2.11 Halaman Pemenang Plakat (Plaque Winners)
Aplikasi menampilkan laman khusus **Pemenang Plakat** yang mempublikasikan daftar peserta peraih plakat penghargaan.
- **Penentuan Pemenang (Backend):** Pada antarmuka *backend*, Manajer dapat meninjau data skor akhir peserta tahun berjalan dan menentukan/menandai peserta mana saja yang berhak mendapatkan hadiah plakat, menyesuaikan dengan daftar plakat yang tersedia pada tahun tersebut.
- **Tampilan Publik:** Setelah ditandai oleh Manajer, pemenang akan muncul di halaman publik ini. Tabel informasi yang disajikan meliputi: *Callsign* penerima hadiah, Nama Negara/DXCC, Benua (*Continent*), Total Skor, dan Nama Plakat yang dimenangkan.

## 3. Spesifikasi Teknis & Desain Antarmuka
- **Tech Stack:** 
  - **Backend / Logika Utama:** PHP.
  - **Dependency Manager:** Composer (untuk beberapa *package* yang dibutuhkan).
  - **Styling / Frontend:** CSS Native (Vanilla CSS) tanpa framework tambahan (kecuali diminta sebaliknya).
- **Desain UI/UX:** 
  - Seluruh informasi dan platform adjudikasi akan dibungkus dalam tampilan *website* publik. Aplikasi wajib menyematkan gambar *banner* (dari rujukan file `assets/banner-YBDXContest.jpg`) sebagai elemen *header* statis yang selalu tampil secara konsisten di bagian paling atas pada seluruh laman *website*.
  - **Dukungan Dua Bahasa (Bilingual):** Mengingat peserta berasal dari seluruh dunia, seluruh informasi, pengumuman, dan antarmuka pengguna pada aplikasi ini akan disajikan dalam dua bahasa, yaitu **Bahasa Indonesia** dan **Bahasa Inggris** (dapat diubah melalui tombol pilihan bahasa).
  - **Tema:** Futuristik dan modern dengan perpaduan warna yang elegan. Aplikasi wajib memberikan impresi yang premium (contoh: pemanfaatan palet warna yang dinamis, *dark mode*, efek transisi yang halus, tipografi *modern*, dan *glassmorphism*).
  - **Interaktif:** Layout tabel papan peringkat yang *responsive* dan komponen-komponen UI yang hidup untuk meningkatkan *User Experience*.
- **Standar Keamanan (Security):**
  - **Pencegahan SQL Injection:** Seluruh interaksi aplikasi dengan *database* (seperti pengolahan pencarian *callsign*, validasi PIN, *login* Manajer/Admin, dan perekaman data QSO) diwajibkan menggunakan struktur **Prepared Statements** melalui PDO (PHP Data Objects). Dilarang keras menggunakan *raw query* yang rentan terhadap peretasan berbasis *SQL Injection*.
  - **Sanitasi Data & Proteksi XSS:** Setiap *input* teks yang diberikan pengguna wajib disanitasi secara ketat dan disandikan (*escape*) ketika dirender ke HTML (contoh: menggunakan `htmlspecialchars`) guna menutup celah *Cross-Site Scripting* (XSS).
  - **Keamanan *File Upload*:** Fitur unggah file Cabrillo akan memberlakukan proteksi ketat (membaca isi sebagai teks murni tanpa mengeksekusi berkasnya) untuk menggagalkan upaya *Remote Code Execution* (RCE) melalui file yang disamarkan.

### 2.12 Manajemen Data & Perbaikan Database (Hotfix)
- Aplikasi dilengkapi skrip perbaikan database (seperti `fix_9w.php` / `fix_db_dxcc.php`) yang memungkinkan penyisiran dan pemutakhiran ulang data benua (Continent) dan negara (DXCC) peserta secara masif jika terjadi koreksi aturan pada pangkalan data DXCC (`dxcc.json`).
