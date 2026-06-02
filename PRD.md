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
- **Validasi Keamanan Data (Anti-Cheat):** *Callsign* peserta yang tercantum pada bagian *header* Cabrillo (`CALLSIGN:`) **wajib sama persis** dengan *callsign* pengirim (*sent call*) yang tercatat di dalam setiap baris data QSO. Jika ditemukan perbedaan, sistem menganggapnya sebagai indikasi manipulasi (potensi kecurangan) dan file otomatis ditolak.
- **Validasi Waktu QSO:** Robot akan memeriksa catatan waktu dari setiap QSO. QSO dianggap valid hanya jika terjadi selama rentang jadwal kontes berlangsung. Jika terdapat baris QSO dengan waktu di luar jadwal kontes, aplikasi otomatis menandainya sebagai `X-QSO:` sehingga tidak akan dihitung poinnya.
- **Otomatisasi Konversi Cabrillo v2 ke v3:** Jika sistem mendeteksi peserta mengunggah file **Cabrillo versi 2 (v2)**, aplikasi akan mengonversinya secara otomatis ke format standar **Cabrillo versi 3 (v3)**. Apabila dalam proses konversi tersebut terdapat data *header* wajib v3 yang kurang (karena perbedaan versi), aplikasi akan berinteraksi langsung (meminta *input*) dari peserta di layar *web* saat itu juga. **Ketentuan Mutlak:** Konversi interaktif ini *hanya* berlaku untuk baris informasi *Header*. Baris data **QSO tidak boleh diubah** atau dimanipulasi sama sekali oleh sistem selama proses konversi.
- **Penanganan Error Teknis & Perbaikan Real-Time:** Apabila ditemukan kesalahan saat proses validasi, aplikasi akan menampilkan rincian kesalahannya secara spesifik dan memfasilitasi perbaikan interaktif:
  - **Kesalahan Header:** Jika *error* terdapat pada *Header* (contoh: kategori MULTI OP tapi kolom OPERATORS hanya diisi 1 callsign), aplikasi menyediakan *textbox* atau *dropdown* agar peserta dapat langsung mengoreksinya di halaman *web*. Berdasarkan *input* tersebut, aplikasi otomatis menyusun ulang (*re-generate*) file Cabrillo yang benar dan menyimpannya sebagai file valid.
  - **Kesalahan Data QSO:** Jika kesalahan terletak pada baris data QSO (seperti format salah atau *callsign* tidak cocok dengan header), perbaikan tidak dapat dilakukan secara *real-time*. Sistem hanya akan memberitahu rekapitulasi: berapa jumlah QSO yang Valid dan berapa yang *Error*.
  - **Force Submit (Abaikan Error QSO):** Jika peserta memilih untuk tetap mengirimkan (menyetujui) file tersebut meski masih ada baris QSO yang *error*, maka aplikasi akan secara otomatis mengubah tag pada baris yang bermasalah tersebut dari `QSO:` menjadi `X-QSO:`. Baris `X-QSO:` ini dipastikan tidak akan dihitung dalam proses ajudikasi selanjutnya.
  - **Pembatalan (Cancel):** Peserta memiliki opsi (tombol Batal) untuk membatalkan seluruh proses validasi jika merasa perlu memeriksa dan memperbaiki file Cabrillo secara manual secara *offline*.
- **Syarat Kode Akses:** Peserta tidak akan mendapatkan Kode Akses (PIN 6 digit) jika belum berhasil menyelesaikan alur pengiriman log Cabrillo hingga tuntas (termasuk memperbaiki atau menyetujui *error* tersebut).

### 2.3 Perhitungan Awal, Konfirmasi, & Keamanan (RAW Score)
Apabila file Cabrillo yang dikirim peserta dinyatakan **valid secara teknis**, maka aplikasi akan menjalankan alur berikut:
1. **Perhitungan Sementara:** Aplikasi menghitung data QSO dan langsung menampilkannya kepada peserta, meliputi: Total QSO (tidak termasuk duplikat), Total QSO per *band*, Total QSO duplikat, Total negara/DXCC, Total poin, Total multiplier, dan Total skor sementara (*RAW Score*).
2. **Konfirmasi Persetujuan:** Aplikasi akan meminta konfirmasi kepada peserta apakah setuju dengan hasil perhitungan sementara tersebut.
3. **Penyimpanan File:** Jika peserta menyatakan **setuju**, aplikasi akan menyimpan file Cabrillo tersebut secara fisik ke dalam folder server dengan struktur `ValidQSO/$(tahun_kontes_berjalan)` untuk nantinya diproses pada tahap pemeriksaan silang (Ajudikasi).
4. **Kode Akses Keamanan:** Setelah file tersimpan, aplikasi akan diatur untuk memberikan informasi berupa **Kode Akses (6 digit angka)** di layar. Kode akses ini beserta laporan penerimaan file Cabrillo juga akan **dikirim otomatis melalui email** yang didaftarkan peserta.
5. **Re-Submission (Pengiriman Ulang):** Jika peserta dengan *callsign* yang sama ingin mengunggah file Cabrillo revisi, sistem akan meminta **Kode Akses** yang telah diberikan sebelumnya.
   - Apabila peserta melampirkan kode akses yang benar, maka file terbaru akan diterima dan diproses.
   - Apabila kode tidak disertakan atau salah, aplikasi akan menolak pengunggahan file atas alasan keamanan. Pengecualian hanya bisa dilakukan melalui intervensi/approval dari Manajer Kontes yang dapat melihat kode akses peserta.
6. **Publikasi RAW Score:** Skor sementara dari file yang telah mendapat persetujuan ini akan dipublikasikan ke halaman "RAW Score".

### 2.4 Sistem Cross-Check & Ajudikasi (Skor Akhir)
Setelah batas waktu pengumpulan log ditutup, robot akan menjalankan proses *cross-checking* (pemeriksaan silang) antar log semua peserta untuk mendapatkan **Skor Akhir**. Aturan *cross-checking* adalah sebagai berikut:

- **Valid QSO:** Jika YB0HHH mencatat QSO dengan JA1UUU, robot akan mencari log milik JA1UUU. Jika pada log JA1UUU data QSO tersebut benar-benar ada dan cocok, maka QSO dianggap valid dan poin diberikan sesuai aturan kontes.
- **Busted QSO:** Jika data QSO ditemukan namun terdapat perbedaan catatan data (meliputi: Band, Waktu/Time, Mode, RST, Callsign, atau Nomor Urut/NR), atau sama sekali tidak ada di log JA1UUU (karena salah ketik callsign, dsb), maka QSO tersebut dianggap *Busted*. **QSO ini tidak mendapatkan poin.**
- **Duplicate QSO:** Jika seorang peserta secara tidak sengaja mencatat data QSO yang sama sebanyak 2 kali atau lebih dalam file Cabrillo-nya, maka robot hanya menghitung data tersebut sebagai 1 QSO. Catatan yang berlebih tidak mendapat poin.
- **Unique QSO:** Jika seorang peserta mencatat QSO dengan stasiun yang *tidak mengirimkan file log Cabrillo*. 
  - **Syarat Peserta Aktif:** Sebuah callsign stasiun yang tidak mengirim log *hanya* dapat diakui keberadaannya sebagai peserta kontes jika callsign tersebut tercatat pada setidaknya **3 (tiga) file Cabrillo peserta yang berbeda**.
  - Jika stasiun tersebut tercatat kurang dari 3 kali (stasiun tersebut dianggap bukan peserta kontes sesungguhnya), maka QSO tersebut dinamakan *Unique* dan **tidak mendapatkan poin**.
- **Not In Log (NIL):** Jika seorang peserta mencatat QSO dengan peserta lain (yang mengirim log), namun di dalam log milik peserta lain tersebut TIDAK ADA data QSO yang dimaksud. QSO ini dianggap *Not In Log* dan **tidak mendapatkan poin**.

*Penghitungan Skor Akhir:* Robot menghitung ulang Poin dan Multiplier dari sisa QSO yang Valid. Total poin dikalikan multiplier menghasilkan Skor Akhir.

### 2.5 Laporan Hasil Ajudikasi (File UBN)
Bersamaan dengan pengumuman Skor Akhir, robot akan membuatkan sebuah file laporan dengan istilah **UBN (Unverified, Busted, Not In Log)** untuk setiap peserta.
Isi dari file UBN meliputi:
- **Informasi Dasar:** Data peserta yang diambil dari *header* file Cabrillo.
- **Statistik Hasil:** Total QSO, jumlah QSO per band, total poin per band, total multiplier per band, dan **Skor Akhir**.
- **Detail Analisis:** Informasi berapa jumlah QSO yang *Duplicate*, jumlah *Unique*, *Busted*, dan *Not In Log*, dilengkapi dengan lampiran **baris data QSO** spesifik yang termasuk ke dalam kategori-kategori tersebut agar peserta mengetahui letak kesalahannya.

### 2.6 Papan Peringkat (Leaderboard) Publik & Diskualifikasi
Pengumuman Skor Akhir (begitu pula Skor Sementara / RAW Score) ditampilkan dalam bentuk tabel pada halaman *website* dengan struktur hierarki pengelompokan (*grouping*) yang ketat secara berurutan:
1. Berdasarkan **Kategori Operator** (Single-Op, Multi-Op)
2. Berdasarkan **Kategori Band** (All, 80M, 40M, 20M, 15M, 10M)
3. Berdasarkan **Kategori Power** (High, Low, QRP)
4. Berdasarkan **Benua (*Continent*)**
5. Berdasarkan **Negara / DXCC**
6. **Pengurutan (*Sorting*):** Di dalam kelompok terspesifik tersebut, peserta diurutkan secara menurun berdasarkan:
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
- Data disajikan dalam format tabel yang dikelompokkan secara hierarkis berdasarkan prioritas: **Kategori Operator -> Kategori Band -> Kategori Power -> Benua -> Negara**.
- Detail informasi pada tabel meliputi: *Callsign* peserta, Nama Negara/DXCC, Jumlah QSO, Jumlah *Band* yang digunakan, serta Waktu (*timestamp*) penerimaan file Cabrillo terakhir.

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
