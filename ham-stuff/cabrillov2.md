# Cabrillo File Version 2 Header

Berikut adalah informasi lengkap mengenai format Cabrillo versi 2 berdasarkan standar CQ WW Contest. Penggunaan Cabrillo v2 sudah di- *deprecated*, namun masih sering digunakan dan diterima oleh berbagai robot pengiriman *log* kontes.

## Contoh File Cabrillo v2
```text
START-OF-LOG: 2.0
CONTEST: CQ-WW-SSB 
ARRL-SECTION: WMA
CALLSIGN: AA1ZZZ
CATEGORY: SINGLE-OP ALL LOW
CATEGORY-OVERLAY: ROOKIE
CLAIMED-SCORE: 12345
CLUB: Yankee Clipper Contest Club
CREATED-BY: WriteLog V10.72C
NAME: John Smith
ADDRESS: 100 Main St
ADDRESS-CITY: Uxbridge
ADDRESS-STATE-PROVINCE: MA
ADDRESS-POSTALCODE: 01569
ADDRESS-COUNTRY: USA
OPERATORS: [required for multi-op stations]
SOAPBOX: Put your comments here.
SOAPBOX: Use multiple lines if needed.
QSO:  3799 PH 2000-10-26 0711 AA1ZZZ          59  05     K9QZO         59  04     0
QSO: 14256 PH 2000-10-26 0711 AA1ZZZ          59  05     P29AS         59  28     0
QSO: 21250 PH 2000-10-26 0711 AA1ZZZ          59  05     4S7TWG        59  22     0
QSO: 28530 PH 2000-10-26 0711 AA1ZZZ          59  05     JT1FAX        59  23     0
QSO:  7250 PH 2000-10-26 0711 AA1ZZZ          59  05     WA6MIC        59  03     0
END-OF-LOG:
```

## Format Baris (Tag)
Format untuk setiap baris adalah `<KEY>:` diikuti oleh spasi, lalu data.

- **`START-OF-LOG: version-number X.X`**
  Harus menjadi baris pertama dari file. Untuk versi ini nilainya `2.0`.
- **`CALLSIGN: call sign`**
  Callsign yang digunakan selama kontes.
- **`CONTEST: contest-name`**
  Nama kontes.
- **`CATEGORY: operator-category band-category power-category [mode-category]`**
  Dalam Cabrillo v2, tag kategori digabung dalam satu baris, berbeda dengan v3 yang dipecah-pecah.
  - *operator-category* meliputi: `SINGLE-OP`, `SINGLE-OP-ASSISTED`, `MULTI-ONE`, `MULTI-TWO`, `MULTI-MULTI`, `CHECKLOG`.
  - *band-category* meliputi: `ALL`, `160M`, `80M`, `40M`, `20M`, `15M`, `10M`.
  - *power-category* meliputi: `HIGH`, `LOW`, `QRP`.
  - *mode-category* meliputi: `SSB`, `CW`.
  **Contoh:**
  - `CATEGORY: SINGLE-OP ALL HIGH CW` (Single operator, all band, high power, CW).
  - `CATEGORY: SINGLE-OP-ASSISTED 80M LOW SSB` (Single operator assisted, 80 meter, low power, SSB).
- **`CATEGORY-OVERLAY: text`**
  Jika masuk kategori overlay (seperti `CLASSIC`, `ROOKIE`, `YOUTH`).
- **`CERTIFICATE: text`**
  `YES` atau `NO`.
- **`GRID-LOCATOR: aann or aannbb`**
  Locator stasiun (Opsional).
- **`CLAIMED-SCORE: integer`**
  Skor sementara tanpa koma.
- **`CLUB: text`**
  Nama klub, ditulis penuh.
- **`CREATED-BY: text`**
  Software *logger* yang digunakan (Opsional).
- **`ARRL-SECTION: arrl-section`**
  Untuk stasiun USA/Canada (Opsional/Sesuai aturan kontes).
- **`EMAIL: text`**
  Alamat email peserta.
- **`NAME: text`**
  Nama operator (Maks. 75 karakter).
- **`ADDRESS: text`**
  Alamat surat menyurat (Maks. 6 baris, per baris 45 karakter).
- **`OPERATORS: callsign1 [callsign2 callsign3...]`**
  Daftar *callsign* operator untuk stasiun *multi-operator*.
- **`SOAPBOX: text`**
  Komentar/catatan (Maks. 75 karakter per baris).
- **`X-<anything>: text`**
  Komentar kustom, diabaikan oleh robot pemeriksa log (kecuali `X-QSO`).

## Baris Data QSO
- **`QSO: qso-data`**
  ```text
                                --------info sent------- -------info rcvd--------
  QSO: freq  mo date       time call          rst exch   call          rst exch   t
  QSO: ***** ** yyyy-mm-dd nnnn ************* nnn ****** ************* nnn ****** n
  QSO:  3799 PH 2000-11-26 0711 N6TW          59  03     JT1Z          59  23     0
  ```
  *(Catatan Kolom 81: Untuk kategori MULTI-ONE dan MULTI-TWO, kolom terakhir menunjukkan transmitter ID (0 atau 1). Tidak diperlukan untuk kategori lain.)*
- **`X-QSO: qso-data`**
  QSO yang diabaikan dalam perhitungan.
- **`END-OF-LOG:`**
  Menandai akhir dari file *log*. Wajib ditulis pada baris terakhir.
