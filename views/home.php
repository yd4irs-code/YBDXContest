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
    <h2>Contest Rules & Information</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 1.5rem;">
        
        <div>
            <h3 style="color: var(--accent-color); border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">Schedule</h3>
            <p>Every second Saturday of January.</p>
            <p><strong>00:00 UTC to 23:59 UTC</strong> (24 hours).</p>
        </div>
        
        <div>
            <h3 style="color: var(--accent-color); border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">Bands & Mode</h3>
            <p><strong>Mode:</strong> SSB (PH) only.</p>
            <p><strong>Bands:</strong> 80M, 40M, 20M, 15M, 10M.</p>
        </div>
        
        <div>
            <h3 style="color: var(--accent-color); border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">Categories</h3>
            <ul>
                <li>Operator: SINGLE-OP, MULTI-OP</li>
                <li>Band: ALL, 80M, 40M, 20M, 15M, 10M</li>
                <li>Power: HIGH, LOW, QRP</li>
            </ul>
        </div>
        
        <div>
            <h3 style="color: var(--accent-color); border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">Log Submission</h3>
            <p>Standard Cabrillo v3 format. All files will be automatically checked and validated.</p>
            <p>Only contacts made within the contest timeframe in SSB mode will be awarded points.</p>
        </div>
        
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
