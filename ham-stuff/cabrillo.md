# Panduan Teknis Cabrillo (V3)

Dokumen ini berisi panduan teknis mengenai format log Cabrillo berdasarkan spesifikasi dari WWROF (World Wide Radio Operators Foundation). Cabrillo adalah format standar industri yang digunakan untuk pengiriman log (catatan kontak) pada kontes radio amatir di seluruh dunia. Aplikasi ajudikasi YB DX Contest harus mampu melakukan *parsing* terhadap format ini.

**Referensi Utama:**
- [Cabrillo Overview](https://wwrof.org/cabrillo/)
- [Header Spec](https://wwrof.org/cabrillo/cabrillo-v3-header/)
- [QSO Data Spec](https://wwrof.org/cabrillo/cabrillo-qso-data/)
- [Notes](https://wwrof.org/cabrillo/cabrillo-specification-notes/)

---

## 1. Struktur Umum

Log Cabrillo pada dasarnya adalah file teks polos (.txt atau .log) dengan struktur:
1. Dimulai dengan baris `START-OF-LOG: 3.0`
2. Diikuti oleh baris-baris Header (Metadata Kontes & Data Peserta)
3. Diikuti oleh daftar Kontak / Baris QSO (QSO Data)
4. Diakhiri dengan baris `END-OF-LOG:`

Setiap *tag* (parameter) diawali dengan nama tag, diikuti oleh titik dua `:`, dan satu spasi.
*Contoh:* `CALLSIGN: YB0DX`

*Delimiter:* Data pada umumnya dipisahkan oleh spasi (Space) atau tab (Tab), meskipun ada panduan kolom (*fixed columns*) pada spesifikasi lama, aplikasi saat ini harus bisa mem-parsing data berdasarkan pemisah spasi (space-delimited).

---

## 2. Spesifikasi Header (Metadata)

Tag-tag berikut dapat muncul dalam urutan apapun di bagian atas file log (sebelum baris QSO).

### Tag Wajib (Required)
- `START-OF-LOG: version-number` (Untuk saat ini `3.0`)
- `END-OF-LOG:` (Baris paling terakhir)

### Tag Umum (Common Tags)
- `CALLSIGN:` Callsign peserta kontes.
- `CONTEST:` Identitas kontes, maksimum 32 karakter (Contoh: `YB-DX-CONTEST`).
- `CATEGORY-ASSISTED:` `ASSISTED` atau `NON-ASSISTED`.
- `CATEGORY-BAND:` Pita frekuensi. `ALL`, `160M`, `80M`, `40M`, `20M`, `15M`, `10M`, dll.
- `CATEGORY-MODE:` Mode. `CW`, `DIGI`, `FM`, `RTTY`, `SSB`, `MIXED`.
- `CATEGORY-OPERATOR:` `SINGLE-OP`, `MULTI-OP`, atau `CHECKLOG`.
- `CATEGORY-POWER:` `HIGH`, `LOW`, atau `QRP`.
- `CATEGORY-STATION:` `DISTRIBUTED`, `FIXED`, `MOBILE`, `PORTABLE`, `ROVER`, `EXPEDITION`, dll.
- `CATEGORY-TIME:` Waktu partisipasi: `6-HOURS`, `8-HOURS`, `12-HOURS`, `24-HOURS`.
- `CATEGORY-TRANSMITTER:` Dibutuhkan untuk Multi-Op (`ONE`, `TWO`, `LIMITED`, `UNLIMITED`, `SWL`).
- `CATEGORY-OVERLAY:` `CLASSIC`, `ROOKIE`, `TB-WIRES`, `YOUTH`, `NOVICE-TECH`, `YL`.
- `CERTIFICATE:` `YES` atau `NO`.
- `CLAIMED-SCORE:` Skor sementara dalam format integer (Contoh: `120000`). Jangan gunakan koma atau titik.
- `CLUB:` Nama klub yang didukung oleh skor peserta ini.
- `CREATED-BY:` Nama dan versi software logging yang digunakan.
- `EMAIL:` Alamat email peserta.
- `GRID-LOCATOR:` Maidenhead Grid Square (Contoh: `OI33`).
- `LOCATION:` Lokasi (Untuk peserta internasional biasanya diisi `DX`).
- `NAME:`, `ADDRESS:`, `ADDRESS-CITY:`, `ADDRESS-STATE-PROVINCE:`, `ADDRESS-POSTALCODE:`, `ADDRESS-COUNTRY:` Data identitas diri & alamat pengiriman.
- `OPERATORS:` Daftar operator yang berpartisipasi (untuk `MULTI-OP`), dipisahkan koma atau spasi. Tuan rumah bisa ditandai dengan `@` (Contoh: `YB1AA, YB2BB, @YB0ZZ`).
- `OFFTIME:` Penanda waktu istirahat (Format: `yyyy-mm-dd nnnn yyyy-mm-dd nnnn`).
- `SOAPBOX:` Komentar / Feedback. Bisa ditulis beberapa baris (Maks. 75 karakter per baris).
- `X-<anything>:` Tag custom yang biasanya akan diabaikan oleh robot *checker*. Pengecualian: `X-QSO`.

---

## 3. Spesifikasi Data QSO (Kontak)

Setiap kontak berada di baris baru dan dimulai dengan tag `QSO: ` atau `X-QSO: ` (untuk diabaikan). Baris-baris QSO harus terurut secara kronologis.

**Format Standar Baris QSO:**
```text
                              --------info sent------- -------info rcvd--------
QSO:  freq mo date       time call          rst exch   call          rst exch   t
```

**Penjelasan Kolom / Parameter Data QSO:**
1. **`freq` (Frekuensi/Band):** Frekuensi aktual dalam kHz (contoh: `7000`, `14000`, `21000`) atau sekedar band.
2. **`mo` (Mode):** `CW`, `PH` (Phone/SSB), `FM`, `RY` (RTTY), `DG` (Digital). Untuk *cross-mode*, ini adalah mode saat *transmit*.
3. **`date` (Tanggal):** Format UTC `yyyy-mm-dd`.
4. **`time` (Waktu):** Format UTC `nnnn` (0000 s/d 2359).
5. **`call` (Sent - Pemanggil):** Callsign pengirim log.
6. **`rst exch` (Sent - RST & Pertukaran):** RST yang dikirim dan data *exchange* kontes yang dikirim (misal serial number / zona).
7. **`call` (Rcvd - Lawan):** Callsign lawan bicara.
8. **`rst exch` (Rcvd - RST & Pertukaran):** RST yang diterima dan data *exchange* kontes yang diterima dari lawan.
9. **`t` (Transmitter ID):** ID Pemancar (`0` atau `1`). Hanya digunakan pada kategori Multi-Transmitter/Multi-Op. Diabaikan untuk Single-Op.

**Contoh 2 Baris QSO:**
```text
QSO: 21000 PH 2026-01-10 1430 YB0DX          59 001    W1AW           59 123    0
QSO: 14000 CW 2026-01-10 1435 YB0DX          599 002   JA1YCG         599 045   0
```

---

## 4. Catatan Penting untuk *Parser* (Aplikasi Ajudikasi YB DX Contest)
- Format harus toleran terhadap pemisah (delimiters). Walaupun spesifikasi menunjukkan kolom yang sejajar (fixed column), sebagian besar log Cabrillo valid jika dipisahkan oleh karakter **spasi** tunggal atau **tab**.
- Sistem *parser* harus mampu mengabaikan data di luar tag resmi (seperti `X-<nama>`).
- Komputasi skor (`CLAIMED-SCORE`) hanya bersifat informasi. Skor final (*Line score*, QSO Points, Multipliers) harus dihitung ulang oleh *parser* YBDXContest berdasarkan aturan kontes yang didefinisikan (lihat `PRD.md`).
- Beberapa aplikasi logger lawas mungkin mencetak format Cabrillo V2. Pastikan *parser* bersifat backward-compatible dan dapat beradaptasi jika menemukan V2.
