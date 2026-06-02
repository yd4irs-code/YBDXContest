<?php
// views/home.php
// Calculate time remaining to contest
$now = new DateTime('now', new DateTimeZone('UTC'));
$start = new DateTime($contest_schedule['start'], new DateTimeZone('UTC'));
$end = new DateTime($contest_schedule['end'], new DateTimeZone('UTC'));

$status = 'upcoming';
if ($now >= $start && $now <= $end) {
    $status = 'active';
} elseif ($now > $end) {
    $status = 'ended';
}

$start_timestamp = $start->getTimestamp();
$end_timestamp = $end->getTimestamp();
?>

<div class="glass-container text-center animate-fade-in">
    <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem; text-shadow: 0 0 20px rgba(59, 130, 246, 0.5);">YB DX CONTEST</h1>
    <p style="color: var(--text-secondary); font-size: 1.2rem;">Single Side Band (SSB) Mode</p>
    
    <?php if ($status == 'active'): ?>
        <h2 style="color: var(--success); margin-top: 2rem;">CONTEST IS ACTIVE!</h2>
        <div id="countdown">
            <div class="countdown-item"><span id="cd-h">00</span><label>Hours</label></div>
            <div class="countdown-item"><span id="cd-m">00</span><label>Minutes</label></div>
            <div class="countdown-item"><span id="cd-s">00</span><label>Seconds</label></div>
        </div>
        <p>Until contest ends</p>
    <?php elseif ($status == 'upcoming'): ?>
        <h2 style="margin-top: 2rem;">Contest Starts In</h2>
        <div id="countdown">
            <div class="countdown-item"><span id="cd-d">00</span><label>Days</label></div>
            <div class="countdown-item"><span id="cd-h">00</span><label>Hours</label></div>
            <div class="countdown-item"><span id="cd-m">00</span><label>Minutes</label></div>
            <div class="countdown-item"><span id="cd-s">00</span><label>Seconds</label></div>
        </div>
        <p>Schedule: <?php echo $start->format('Y-m-d H:i'); ?> UTC - <?php echo $end->format('Y-m-d H:i'); ?> UTC</p>
<?php else: ?>
        <h2 style="color: var(--danger); margin-top: 2rem;">CONTEST HAS ENDED</h2>
        <p style="margin-top: 1rem;">Thank you to all participants. Please submit your Cabrillo logs!</p>
        <div style="margin-top: 2rem;">
            <a href="index.php?page=submit_log" class="btn" style="font-size: 1.2rem; padding: 1rem 2rem;">Submit Log Now</a>
        </div>
        
        <h3 style="margin-top: 3rem; color: var(--accent-hover);">Next Contest Starts In</h3>
        <?php 
            $next_year_schedule = getContestDate($current_contest_year + 1);
            $next_start = new DateTime($next_year_schedule['start'], new DateTimeZone('UTC'));
            $next_end = new DateTime($next_year_schedule['end'], new DateTimeZone('UTC'));
            $next_start_timestamp = $next_start->getTimestamp();
        ?>
        <div id="countdown">
            <div class="countdown-item"><span id="cd-d">00</span><label>Days</label></div>
            <div class="countdown-item"><span id="cd-h">00</span><label>Hours</label></div>
            <div class="countdown-item"><span id="cd-m">00</span><label>Minutes</label></div>
            <div class="countdown-item"><span id="cd-s">00</span><label>Seconds</label></div>
        </div>
        <p>Schedule: <?php echo $next_start->format('Y-m-d H:i'); ?> UTC - <?php echo $next_end->format('Y-m-d H:i'); ?> UTC</p>
    <?php endif; ?>
</div>

<div class="glass-container animate-fade-in" style="animation-delay: 0.2s;">
    <h2><?php echo $lang == 'id' ? 'Peraturan & Informasi Kontes' : 'Contest Rules & Information'; ?></h2>
    
    <div style="margin-top: 1.5rem;">
        <?php if ($lang == 'id'): ?>
            <!-- Indonesian Rules -->
            <details class="score-details level-1" open>
                <summary>
                    <span class="summary-title"><i class="fas fa-calendar-alt" style="margin-right:0.5rem; color:var(--accent-hover);"></i> Jadwal & Tujuan</span>
                    <i class="fas fa-chevron-down summary-icon"></i>
                </summary>
                <div class="details-content" style="color: var(--text-secondary); line-height: 1.6;">
                    <ul>
                        <li><strong>Jadwal:</strong> Setiap hari Sabtu minggu kedua bulan Januari. Berlangsung selama 24 Jam (00:00 UTC hingga 23:59 UTC).</li>
                        <li><strong>Tujuan:</strong> Amatir Radio Indonesia diharapkan melakukan kontak sebanyak-banyaknya dengan stasiun dari luar negeri.</li>
                    </ul>
                </div>
            </details>
            
            <details class="score-details level-1">
                <summary>
                    <span class="summary-title"><i class="fas fa-broadcast-tower" style="margin-right:0.5rem; color:var(--accent-hover);"></i> Kategori, Band & Pertukaran</span>
                    <i class="fas fa-chevron-down summary-icon"></i>
                </summary>
                <div class="details-content" style="color: var(--text-secondary); line-height: 1.6;">
                    <ul>
                        <li><strong>Mode & Pertukaran:</strong> SSB (PH) saja. Mengirimkan sinyal lapor (RS) <code>59</code> diikuti Nomor Urut mulai dari <code>001</code> (Contoh: 59 001).</li>
                        <li><strong>Band:</strong> 80m, 40m, 20m, 15m, 10m. (Untuk kelas Siaga hanya diperbolehkan pada 80m, 40m, dan 10m).</li>
                        <li><strong>Kategori:</strong> Single Operator All Band, dan Multi Operator Single Transmitter (MOST).</li>
                        <li><strong>Ketentuan MOST:</strong> Dilarang menggunakan callsign perorangan. Wajib menggunakan prefix 7A-7I atau 8A-8I. Tidak diperkenankan menggunakan YH0-YH9.</li>
                        <li><strong>Batas Daya (Power):</strong> Penegak (1000W), Penggalang (500W), Siaga (100W).</li>
                    </ul>
                </div>
            </details>
            
            <details class="score-details level-1">
                <summary>
                    <span class="summary-title"><i class="fas fa-calculator" style="margin-right:0.5rem; color:var(--accent-hover);"></i> Perhitungan Poin & Skor</span>
                    <i class="fas fa-chevron-down summary-icon"></i>
                </summary>
                <div class="details-content" style="color: var(--text-secondary); line-height: 1.6;">
                    <ul>
                        <li><strong>Rumus Skor Akhir:</strong> <code>Total Poin QSO x (Total Prefix Dunia + Total Negara)</code>.</li>
                        <li><strong>Poin Kontak:</strong>
                            <ul>
                                <li>Kontak sesama stasiun Indonesia: <strong>0 Poin</strong> (Hanya sah untuk mengumpulkan multiplier Prefix).</li>
                                <li>Kontak dengan benua yang sama (Oceania): <strong>5 Poin</strong>.</li>
                                <li>Kontak dengan benua yang berbeda: <strong>10 Poin</strong>.</li>
                            </ul>
                        </li>
                        <li><strong>Pengali (Multiplier):</strong> Setiap <em>World Prefix</em> pertama kali di setiap band bernilai 1 Mult. Setiap Negara (DXCC) pertama kali di setiap band bernilai 1 Mult.</li>
                    </ul>
                </div>
            </details>
            
            <details class="score-details level-1">
                <summary>
                    <span class="summary-title"><i class="fas fa-search" style="margin-right:0.5rem; color:var(--accent-hover);"></i> Sistem Ajudikasi (Unique, Busted, NIL)</span>
                    <i class="fas fa-chevron-down summary-icon"></i>
                </summary>
                <div class="details-content" style="color: var(--text-secondary); line-height: 1.6;">
                    <p>Setelah kontes ditutup, sistem juri otomatis (Robot) akan melakukan pengecekan silang <em>(cross-check)</em> atas seluruh log yang masuk. Berikut arti dari status UBN:</p>
                    <ul>
                        <li><strong style="color: var(--success);">VALID:</strong> Kontak sah. Data pertukaran (Callsign & Nomor Urut) cocok di antara kedua log stasiun.</li>
                        <li><strong style="color: #8b5cf6;">UNIQUE:</strong> Callsign lawan komunikasi Anda hanya muncul satu kali di seluruh log yang masuk ke panitia. Ini bisa jadi karena stasiun tersebut tidak mengirimkan log, atau Anda salah mengetik Callsign mereka.</li>
                        <li><strong style="color: var(--danger);">BUSTED:</strong> Kesalahan fatal. Anda salah mencatat Callsign lawan atau nomor urut yang dikirimkan oleh lawan tidak sesuai dengan yang ada di log mereka.</li>
                        <li><strong style="color: var(--warning);">NIL (Not In Log):</strong> Anda mencatat telah berkomunikasi dengan sebuah stasiun, tetapi stasiun tersebut tidak memiliki catatan komunikasi dengan Anda di log mereka.</li>
                    </ul>
                </div>
            </details>
            
            <details class="score-details level-1">
                <summary>
                    <span class="summary-title"><i class="fas fa-gavel" style="margin-right:0.5rem; color:var(--accent-hover);"></i> Ketentuan Log & Aturan Lainnya</span>
                    <i class="fas fa-chevron-down summary-icon"></i>
                </summary>
                <div class="details-content" style="color: var(--text-secondary); line-height: 1.6;">
                    <ul>
                        <li><strong>Format Log:</strong> Wajib format Cabrillo. Tidak menerima tulisan tangan atau via Pos.</li>
                        <li><strong>Batas Waktu:</strong> Maksimal 7 hari setelah kontes berakhir. Log yang telat otomatis berstatus <em>Checklog</em> (tidak mendapat sertifikat/plakat).</li>
                        <li><em>Self-spotting</em> diperbolehkan.</li>
                        <li>Toleransi perbedaan pencatatan waktu antar logsheet adalah maksimal <strong>15 menit</strong>.</li>
                        <li>Hanya satu sinyal pancaran yang diperbolehkan mengudara di satu band secara bersamaan.</li>
                        <li>Keputusan panitia YB DX Contest bersifat mutlak dan tidak dapat diganggu gugat.</li>
                    </ul>
                </div>
            </details>
            
        <?php else: ?>
            <!-- English Rules -->
            <details class="score-details level-1" open>
                <summary>
                    <span class="summary-title"><i class="fas fa-calendar-alt" style="margin-right:0.5rem; color:var(--accent-hover);"></i> Schedule & Objective</span>
                    <i class="fas fa-chevron-down summary-icon"></i>
                </summary>
                <div class="details-content" style="color: var(--text-secondary); line-height: 1.6;">
                    <ul>
                        <li><strong>Schedule:</strong> Every second Saturday of January. Runs for 24 Hours (00:00 UTC to 23:59 UTC).</li>
                        <li><strong>Objective:</strong> For amateurs worldwide to contact as many other amateurs in as many DXCC countries and YB Land (Indonesia) as possible. "Everyone Worked Everyone".</li>
                    </ul>
                </div>
            </details>
            
            <details class="score-details level-1">
                <summary>
                    <span class="summary-title"><i class="fas fa-broadcast-tower" style="margin-right:0.5rem; color:var(--accent-hover);"></i> Categories, Bands & Exchange</span>
                    <i class="fas fa-chevron-down summary-icon"></i>
                </summary>
                <div class="details-content" style="color: var(--text-secondary); line-height: 1.6;">
                    <ul>
                        <li><strong>Mode & Exchange:</strong> SSB (PH) only. Send RS Report <code>59</code> + Serial Number starting from <code>001</code> (e.g., 59 001).</li>
                        <li><strong>Bands:</strong> 3.5, 7, 14, 21, and 28 MHz. No WARC bands allowed.</li>
                        <li><strong>Categories:</strong> Single Operator All Band, and Multi Operator Single Transmitter.</li>
                        <li><strong>Power Limit:</strong> Any Power (subject to your country's legal regulations).</li>
                    </ul>
                </div>
            </details>
            
            <details class="score-details level-1">
                <summary>
                    <span class="summary-title"><i class="fas fa-calculator" style="margin-right:0.5rem; color:var(--accent-hover);"></i> Points & Score Calculation</span>
                    <i class="fas fa-chevron-down summary-icon"></i>
                </summary>
                <div class="details-content" style="color: var(--text-secondary); line-height: 1.6;">
                    <ul>
                        <li><strong>Final Score Formula:</strong> <code>Total QSO Points x (Total YB Prefixes + Total DXCC Countries)</code>.</li>
                        <li><strong>QSO Points:</strong>
                            <ul>
                                <li>Contact with your own country: <strong>1 Point</strong>.</li>
                                <li>Contact with different country, same continent: <strong>2 Points</strong>.</li>
                                <li>Contact with different continent: <strong>3 Points</strong>.</li>
                                <li>Contact with any YB station (Indonesia): <strong>10 Points</strong>.</li>
                            </ul>
                        </li>
                        <li><strong>Multiplier:</strong> Each different YB Prefix per band = 1 Mult. Each different DXCC country per band = 1 Mult.</li>
                    </ul>
                </div>
            </details>
            
            <details class="score-details level-1">
                <summary>
                    <span class="summary-title"><i class="fas fa-search" style="margin-right:0.5rem; color:var(--accent-hover);"></i> Adjudication System (UBN)</span>
                    <i class="fas fa-chevron-down summary-icon"></i>
                </summary>
                <div class="details-content" style="color: var(--text-secondary); line-height: 1.6;">
                    <p>After the log submission deadline, our autonomous Robot performs strict cross-checking. Here is what the UBN statuses mean:</p>
                    <ul>
                        <li><strong style="color: var(--success);">VALID:</strong> Legitimate contact. Logged Callsign and Serial Number match on both ends.</li>
                        <li><strong style="color: #8b5cf6;">UNIQUE:</strong> The contacted callsign appears only once across all submitted logs in the database. This is usually indicative of a typo in the callsign.</li>
                        <li><strong style="color: var(--danger);">BUSTED:</strong> Fatal error. You incorrectly logged the other station's callsign, or the serial number you logged does not match what they sent.</li>
                        <li><strong style="color: var(--warning);">NIL (Not In Log):</strong> You claimed a contact with a station, but that station does not have your callsign in their log.</li>
                    </ul>
                </div>
            </details>
            
            <details class="score-details level-1">
                <summary>
                    <span class="summary-title"><i class="fas fa-gavel" style="margin-right:0.5rem; color:var(--accent-hover);"></i> Log Submission & General Rules</span>
                    <i class="fas fa-chevron-down summary-icon"></i>
                </summary>
                <div class="details-content" style="color: var(--text-secondary); line-height: 1.6;">
                    <ul>
                        <li><strong>Log Format:</strong> Cabrillo format only. No paper logs.</li>
                        <li><strong>Deadline:</strong> All logs must be uploaded within 7 days after the contest ends. Late logs will be treated as <em>Checklog</em>.</li>
                        <li><em>Self-spotting</em> is allowed.</li>
                        <li>Time difference tolerance between logs is <strong>15 minutes</strong>.</li>
                        <li>Only one transmitted signal is allowed per band at any time.</li>
                        <li>The YB DX Contest Committee decisions are absolute and final.</li>
                    </ul>
                </div>
            </details>
        <?php endif; ?>
    </div>
</div>

<script>
// Simple JS Countdown
const status = '<?php echo $status; ?>';
let targetTime = 0;

if (status === 'active') {
    targetTime = <?php echo $end_timestamp; ?> * 1000;
} else if (status === 'upcoming') {
    targetTime = <?php echo $start_timestamp; ?> * 1000;
} else if (status === 'ended') {
    targetTime = <?php echo isset($next_start_timestamp) ? $next_start_timestamp : 0; ?> * 1000;
}

if (targetTime > 0) {
    const timer = setInterval(function() {
        const now = new Date().getTime();
        const distance = targetTime - now;
        
        if (distance < 0) {
            clearInterval(timer);
            location.reload();
            return;
        }
        
        const d = Math.floor(distance / (1000 * 60 * 60 * 24));
        const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((distance % (1000 * 60)) / 1000);
        
        if (document.getElementById('cd-d')) document.getElementById('cd-d').innerText = d.toString().padStart(2, '0');
        if (document.getElementById('cd-h')) document.getElementById('cd-h').innerText = h.toString().padStart(2, '0');
        if (document.getElementById('cd-m')) document.getElementById('cd-m').innerText = m.toString().padStart(2, '0');
        if (document.getElementById('cd-s')) document.getElementById('cd-s').innerText = s.toString().padStart(2, '0');
        
    }, 1000);
}
</script>
