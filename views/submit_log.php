<?php
// views/submit_log.php
require_once __DIR__ . '/../includes/CabrilloParser.php';
require_once __DIR__ . '/../includes/Mailer.php';

$message = '';
$message_type = '';
$step = 1;

global $pdo;
$stmt_status = $pdo->query("SELECT status FROM cabrillo_logs JOIN participants p ON cabrillo_logs.participant_id = p.id WHERE p.year = $current_contest_year LIMIT 1");
$global_status = $stmt_status->fetchColumn();
$is_adjudicated = ($global_status === 'adjudicated');

if ($is_adjudicated) {
    $message = "Log submission is now closed. The adjudication process has been completed.";
    $message_type = "danger";
    $step = 0; // lock form
}

$temp_file = isset($_SESSION['temp_cabrillo']) ? $_SESSION['temp_cabrillo'] : '';
$parser = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload_log'])) {
        // Step 1: Handle File Upload
        if (isset($_FILES['cabrillo_file']) && $_FILES['cabrillo_file']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['cabrillo_file']['name'], PATHINFO_EXTENSION);
            if (strtolower($ext) !== 'log') {
                $message = "Please upload a .log file.";
                $message_type = "danger";
            } else {
                $content = file_get_contents($_FILES['cabrillo_file']['tmp_name']);
                // Strict Security: read as text, save to a safe temp name
                $tmp_name = __DIR__ . '/../ValidQSO/temp_' . time() . '_' . rand(1000,9999) . '.log';
                file_put_contents($tmp_name, $content);
                $_SESSION['temp_cabrillo'] = $tmp_name;
                $temp_file = $tmp_name;
                
                $step = 2; // Move to validation step
            }
        } else {
            $message = "Error uploading file.";
            $message_type = "danger";
        }
    } 
    elseif (isset($_POST['correct_header'])) {
        // Step 2b: Handle header corrections
        $temp_file = $_SESSION['temp_cabrillo'];
        $parser = new CabrilloParser($temp_file, date('Y'));
        $parser->parse();
        
        // Update headers from POST
        $required_headers = ['CALLSIGN', 'CONTEST', 'CATEGORY-OPERATOR', 'CATEGORY-BAND', 'CATEGORY-POWER', 'CATEGORY-MODE', 'EMAIL', 'OPERATORS'];
        foreach ($required_headers as $req) {
            if (isset($_POST[$req]) && !empty(trim($_POST[$req]))) {
                $val = trim($_POST[$req]);
                if ($req !== 'EMAIL') $val = strtoupper($val);
                $parser->headers[$req] = $val;
            }
        }
        
        // Re-save file with new headers
        $new_content = $parser->generateV3Content();
        file_put_contents($temp_file, $new_content);
        
        $step = 2; // Re-validate
    }
    elseif (isset($_POST['force_submit'])) {
        // Step 3: Final Submit (ignoring X-QSOs)
        $temp_file = $_SESSION['temp_cabrillo'];
        $parser = new CabrilloParser($temp_file, date('Y'));
        $parser->parse();
        
        $email = isset($parser->headers['EMAIL']) ? $parser->headers['EMAIL'] : '';
        $callsign = isset($parser->headers['CALLSIGN']) ? strtoupper($parser->headers['CALLSIGN']) : '';
        $cat_op = isset($parser->headers['CATEGORY-OPERATOR']) ? strtoupper($parser->headers['CATEGORY-OPERATOR']) : '';
        $cat_band = isset($parser->headers['CATEGORY-BAND']) ? strtoupper($parser->headers['CATEGORY-BAND']) : '';
        $cat_power = isset($parser->headers['CATEGORY-POWER']) ? strtoupper($parser->headers['CATEGORY-POWER']) : '';
        $club = isset($parser->headers['CLUB']) ? $parser->headers['CLUB'] : '';
        $country_header = isset($parser->headers['ADDRESS-COUNTRY']) ? strtoupper($parser->headers['ADDRESS-COUNTRY']) : '';
        
        $country = $country_header;
        require_once __DIR__ . '/../includes/ScoringHelper.php';
        $dxcc_info = getDxccFromCallsign($callsign);
        $country = $dxcc_info['country'];
        $continent = $dxcc_info['continent'];
        
        // If the parsed DXCC is not precise enough but the user supplied a recognizable country, we could theoretically use it, but relying on DXCC DB is much better.
        // The user specifically requested to strictly use dxcc.json for this.
        
        global $pdo;
        
        try {
            $pdo->beginTransaction();
            
            // Upsert participant
            $stmt = $pdo->prepare("SELECT id, access_code FROM participants WHERE callsign = ? AND year = ?");
            $stmt->execute([$callsign, $current_contest_year]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $proceed_with_save = true;
            $pin = '';
            
            if ($participant) {
                // Must verify PIN to update
                $input_pin = isset($_POST['update_pin']) ? trim($_POST['update_pin']) : '';
                if ($input_pin !== $participant['access_code']) {
                    $proceed_with_save = false;
                    $message = "You have already submitted a log. To update it, you must enter the correct PIN.";
                    $message_type = "danger";
                    $step = 2; // back to validation view
                    $pdo->rollBack();
                } else {
                    $p_id = $participant['id'];
                    $pin = $participant['access_code']; // keep old pin
                    // Update
                    $stmt = $pdo->prepare("UPDATE participants SET email=?, category_op=?, category_band=?, category_power=?, club_name=?, continent=?, country=? WHERE id=?");
                    $stmt->execute([$email, $cat_op, $cat_band, $cat_power, $club, $continent, $country, $p_id]);
                    // Delete old log
                    $pdo->prepare("DELETE FROM cabrillo_logs WHERE participant_id=?")->execute([$p_id]);
                    $pdo->prepare("DELETE FROM qsos WHERE participant_id=?")->execute([$p_id]);
                }
            } else {
                // Insert new
                $pin = sprintf("%06d", mt_rand(1, 999999));
                $stmt = $pdo->prepare("INSERT INTO participants (callsign, email, access_code, category_op, category_band, category_power, club_name, continent, country, year) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$callsign, $email, $pin, $cat_op, $cat_band, $cat_power, $club, $continent, $country, $current_contest_year]);
                $p_id = $pdo->lastInsertId();
            }
            
            if ($proceed_with_save) {
                // Save final valid log
                $final_path = __DIR__ . '/../ValidQSO/' . $current_contest_year;
                if (!is_dir($final_path)) mkdir($final_path, 0777, true);
                $final_file = $final_path . '/' . $callsign . '.log';
                $final_content = $parser->generateV3Content();
                file_put_contents($final_file, $final_content);
                
                $soapbox = isset($parser->headers['SOAPBOX']) ? $parser->headers['SOAPBOX'] : '';
                
                require_once __DIR__ . '/../includes/ScoringHelper.php';
                $score_data = calculateScore($parser->qsos, $country, $continent);
                $raw_qso = 0;
                foreach ($parser->qsos as $q) {
                    if (!$q['is_xqso']) $raw_qso++;
                }
                
                $stmt = $pdo->prepare("INSERT INTO cabrillo_logs (participant_id, file_path, soapbox, total_qso, raw_score) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$p_id, $final_file, $soapbox, $raw_qso, $score_data['raw_score']]);
                
                // Save QSOs to DB for Adjudication
                $stmt = $pdo->prepare("INSERT INTO qsos (participant_id, freq, mode, qso_date, qso_time, sent_call, sent_rst, sent_exch, rcvd_call, rcvd_rst, rcvd_exch, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($parser->qsos as $qso) {
                    $status = $qso['is_xqso'] ? 'xqso' : 'valid';
                    $formatted_time = (strlen($qso['time']) === 4) ? substr($qso['time'], 0, 2) . ':' . substr($qso['time'], 2, 2) . ':00' : $qso['time'];
                    $stmt->execute([
                        $p_id, $qso['freq'], $qso['mode'], $qso['date'], $formatted_time,
                        $qso['sent_call'], $qso['sent_rst'], $qso['sent_exch'],
                        $qso['rcvd_call'], $qso['rcvd_rst'], $qso['rcvd_exch'], $status
                    ]);
                }
                
                $pdo->commit();
                
                // Send PIN only if it was newly generated
                if (!$participant) {
                    Mailer::sendPin($email, $callsign, $pin);
                }
                
                unset($_SESSION['temp_cabrillo']);
                unlink($temp_file);
                
                $step = 3; // Success
                if ($participant) {
                    $message = "Log successfully updated!";
                } else {
                    $message = "Log successfully submitted! Your PIN has been emailed to $email.";
                }
                $message_type = "success";
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error saving log: " . $e->getMessage();
            $message_type = "danger";
            $step = 1;
        }
    }
}

if ($step === 2 && !empty($temp_file)) {
    $parser = new CabrilloParser($temp_file, date('Y'));
    $parser->parse();
    
    // Check if there are missing headers
    $missing_headers = [];
    $required_headers = ['CALLSIGN', 'CONTEST', 'CATEGORY-OPERATOR', 'CATEGORY-BAND', 'CATEGORY-POWER', 'CATEGORY-MODE', 'EMAIL', 'OPERATORS'];
    
    // Auto-fill logic
    $auto_fixed = false;
    
    // 1. CATEGORY-MODE: Auto-fill if mode in QSOs is PH
    if (empty($parser->headers['CATEGORY-MODE'])) {
        $has_ph = false;
        foreach ($parser->qsos as $q) {
            if (isset($q['mode']) && $q['mode'] === 'PH') {
                $has_ph = true; break;
            }
        }
        if ($has_ph) {
            $parser->headers['CATEGORY-MODE'] = 'SSB';
            $auto_fixed = true;
        }
    }
    
    // 2. OPERATORS: Auto-fill if SINGLE-OP
    if (isset($parser->headers['CATEGORY-OPERATOR']) && strtoupper($parser->headers['CATEGORY-OPERATOR']) === 'SINGLE-OP') {
        if (empty($parser->headers['OPERATORS']) && !empty($parser->headers['CALLSIGN'])) {
            $parser->headers['OPERATORS'] = $parser->headers['CALLSIGN'];
            $auto_fixed = true;
        }
    }
    
    if ($auto_fixed) {
        $new_content = $parser->generateV3Content();
        file_put_contents($temp_file, $new_content);
    }
    
    foreach ($required_headers as $req) {
        if ($req === 'OPERATORS') {
            // Only require OPERATORS if MULTI-OP or if operator category is not yet known
            if (isset($parser->headers['CATEGORY-OPERATOR']) && strtoupper($parser->headers['CATEGORY-OPERATOR']) === 'SINGLE-OP') continue;
        }
        
        if (!isset($parser->headers[$req]) || empty(trim($parser->headers[$req]))) {
            $missing_headers[] = $req;
        }
    }
    
    if (count($missing_headers) > 0) {
        $step = '2_fix_headers';
    }
}
?>

<div class="wrapper animate-fade-in">
    <div class="glass-container">
        <h2><?php echo t('submit_log'); ?></h2>
        
        <?php if (!empty($message)): ?>
            <div class="badge badge-<?php echo $message_type; ?>" style="display:block; padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <p>Upload your Cabrillo .log file here. Maximum file size: 5MB.</p>
            <form method="post" enctype="multipart/form-data" style="margin-top: 1.5rem;">
                <div class="form-group">
                    <label for="cabrillo_file">Select File</label>
                    <input type="file" name="cabrillo_file" id="cabrillo_file" accept=".log,.txt" required>
                </div>
                <button type="submit" name="upload_log" class="btn">Upload and Validate</button>
            </form>

        <?php elseif ($step === '2_fix_headers'): ?>
            <h3 style="color: var(--warning);">Missing Required Headers</h3>
            <p>We detected this is a Cabrillo v2 log, or it's missing some required v3 headers. Please fill in the missing information below.</p>
            <form method="post" style="margin-top: 1.5rem; max-width: 500px;">
                <?php foreach ($missing_headers as $h): ?>
                    <div class="form-group">
                        <?php if ($h === 'CONTEST'): ?>
                            <label>CONTEST (Apakah benar ini log untuk YB DX Contest?)</label>
                            <select name="CONTEST" required>
                                <option value="">-- Pilih --</option>
                                <option value="YB DX Contest">Ya, ini untuk YB DX Contest</option>
                            </select>
                        <?php elseif ($h === 'CATEGORY-MODE'): ?>
                            <label>CATEGORY-MODE</label>
                            <input type="text" name="<?php echo $h; ?>" value="SSB" readonly style="background: rgba(255,255,255,0.1);">
                        <?php elseif ($h === 'CATEGORY-OPERATOR'): ?>
                            <label>CATEGORY-OPERATOR</label>
                            <select name="<?php echo $h; ?>" required>
                                <option value="">-- Pilih --</option>
                                <option value="SINGLE-OP">SINGLE-OP</option>
                                <option value="MULTI-OP">MULTI-OP</option>
                                <option value="CHECKLOG">CHECKLOG</option>
                            </select>
                        <?php elseif ($h === 'CATEGORY-BAND'): ?>
                            <label>CATEGORY-BAND</label>
                            <select name="<?php echo $h; ?>" required>
                                <option value="">-- Pilih --</option>
                                <option value="ALL">ALL</option>
                                <option value="80M">80M</option>
                                <option value="40M">40M</option>
                                <option value="20M">20M</option>
                                <option value="15M">15M</option>
                                <option value="10M">10M</option>
                            </select>
                        <?php elseif ($h === 'CATEGORY-POWER'): ?>
                            <label>CATEGORY-POWER</label>
                            <select name="<?php echo $h; ?>" required>
                                <option value="">-- Pilih --</option>
                                <option value="HIGH">HIGH</option>
                                <option value="LOW">LOW</option>
                                <option value="QRP">QRP</option>
                            </select>
                        <?php elseif ($h === 'CALLSIGN'): ?>
                            <label>CALLSIGN (Log ini milik siapa?)</label>
                            <input type="text" name="CALLSIGN" pattern="[a-zA-Z0-9/]+" title="Gunakan callsign radio amatir yang valid" style="text-transform: uppercase;" required>
                        <?php elseif ($h === 'OPERATORS'): ?>
                            <label>OPERATORS (Sebutkan minimal 2 callsign untuk MULTI-OP)</label>
                            <input type="text" name="OPERATORS" placeholder="Misal: YB1AR, YC2VOC" style="text-transform: uppercase;" required>
                        <?php elseif ($h === 'EMAIL'): ?>
                            <label>EMAIL (Alamat email Anda)</label>
                            <input type="email" name="EMAIL" placeholder="Misal: user@example.com" required>
                        <?php else: ?>
                            <label><?php echo $h; ?></label>
                            <input type="text" name="<?php echo $h; ?>" required>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" name="correct_header" class="btn">Update & Continue</button>
            </form>

        <?php elseif ($step === 2): ?>
            <h3 style="color: var(--success); margin-bottom: 1.5rem;">Validation Results</h3>
            
            <div style="background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <h4 style="margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem; color: var(--accent-hover);">Header Details (v<?php echo htmlspecialchars($parser->version); ?>)</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div><strong style="color: var(--text-secondary);">Callsign:</strong> <?php echo htmlspecialchars($parser->headers['CALLSIGN'] ?? '-'); ?></div>
                    <div><strong style="color: var(--text-secondary);">Operator:</strong> <?php echo htmlspecialchars($parser->headers['CATEGORY-OPERATOR'] ?? '-'); ?></div>
                    <div><strong style="color: var(--text-secondary);">Band:</strong> <?php echo htmlspecialchars($parser->headers['CATEGORY-BAND'] ?? '-'); ?></div>
                    <div><strong style="color: var(--text-secondary);">Power:</strong> <?php echo htmlspecialchars($parser->headers['CATEGORY-POWER'] ?? '-'); ?></div>
                    <div><strong style="color: var(--text-secondary);">Mode:</strong> <?php echo htmlspecialchars($parser->headers['CATEGORY-MODE'] ?? '-'); ?></div>
                    <div><strong style="color: var(--text-secondary);">Club:</strong> <?php echo htmlspecialchars($parser->headers['CLUB'] ?? '-'); ?></div>
                    <div><strong style="color: var(--text-secondary);">Email:</strong> <?php echo htmlspecialchars($parser->headers['EMAIL'] ?? '-'); ?></div>
                </div>
            </div>
            
            <?php
            require_once __DIR__ . '/../includes/ScoringHelper.php';
            
            $valid_qso = 0;
            $xqso = 0;
            $xqso_details = [];
            $band_qsos = [];
            
            foreach ($parser->qsos as $i => $qso) {
                if ($qso['is_xqso']) {
                    $xqso++;
                    $xqso_details[] = "Line " . ($i + 1) . ": " . implode(", ", $qso['error_reasons']);
                } else {
                    $valid_qso++;
                    
                    // Determine Band from Frequency
                    $freq = (int)$qso['freq'];
                    $band = 'UNKNOWN';
                    if ($freq >= 3500 && $freq <= 4000) $band = '80M';
                    elseif ($freq >= 7000 && $freq <= 7300) $band = '40M';
                    elseif ($freq >= 14000 && $freq <= 14350) $band = '20M';
                    elseif ($freq >= 21000 && $freq <= 21450) $band = '15M';
                    elseif ($freq >= 28000 && $freq <= 29700) $band = '10M';
                    else $band = 'OTHER';
                    
                    if (!isset($band_qsos[$band])) $band_qsos[$band] = 0;
                    $band_qsos[$band]++;
                }
            }
            
            // Calculate DXCC and Continent counts
            $score_data = calculateScore($parser->qsos, 'UNKNOWN', 'UNKNOWN');
            ksort($band_qsos); // Sort bands alphabetically/numerically
            ?>
            
            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                <div style="background: rgba(16, 185, 129, 0.1); padding: 1rem; border-radius: 8px; flex: 1; min-width: 150px;">
                    <h4 style="color: var(--success); margin-bottom: 0.5rem; font-size: 0.9rem; text-transform: uppercase;">Valid QSOs</h4>
                    <span style="font-size: 2rem; font-weight: bold; color: var(--success);"><?php echo $valid_qso; ?></span>
                </div>
                <div style="background: rgba(59, 130, 246, 0.1); padding: 1rem; border-radius: 8px; flex: 1; min-width: 150px;">
                    <h4 style="color: var(--accent-hover); margin-bottom: 0.5rem; font-size: 0.9rem; text-transform: uppercase;">Continents Worked</h4>
                    <span style="font-size: 2rem; font-weight: bold; color: var(--accent-hover);"><?php echo $score_data['continent_count']; ?></span>
                </div>
                <div style="background: rgba(59, 130, 246, 0.1); padding: 1rem; border-radius: 8px; flex: 1; min-width: 150px;">
                    <h4 style="color: var(--accent-hover); margin-bottom: 0.5rem; font-size: 0.9rem; text-transform: uppercase;">Countries / DXCC</h4>
                    <span style="font-size: 2rem; font-weight: bold; color: var(--accent-hover);"><?php echo $score_data['dxcc_count']; ?></span>
                </div>
                <div style="background: rgba(239, 68, 68, 0.1); padding: 1rem; border-radius: 8px; flex: 1; min-width: 150px;">
                    <h4 style="color: var(--danger); margin-bottom: 0.5rem; font-size: 0.9rem; text-transform: uppercase;">Error QSOs (X-QSO)</h4>
                    <span style="font-size: 2rem; font-weight: bold; color: var(--danger);"><?php echo $xqso; ?></span>
                </div>
            </div>
            
            <h4 style="margin-bottom: 1rem; color: var(--text-secondary);">Valid QSOs Breakdown per Band</h4>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2.5rem;">
                <?php foreach ($band_qsos as $b => $c): ?>
                    <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); padding: 0.8rem 1.5rem; border-radius: 8px; text-align: center; min-width: 80px;">
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.2rem;"><?php echo $b; ?></div>
                        <div style="font-size: 1.5rem; font-weight: bold; color: #fff;"><?php echo $c; ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($band_qsos)): ?>
                    <div style="color: var(--text-secondary); font-style: italic;">No valid QSOs found to categorize.</div>
                <?php endif; ?>
            </div>
            
            <?php if (count($parser->errors) > 0): ?>
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="color: var(--danger);">Critical Header Errors</h4>
                    <ul style="color: var(--danger); margin-left: 1.5rem;">
                        <?php foreach ($parser->errors as $err) echo "<li>" . htmlspecialchars($err) . "</li>"; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($xqso > 0): ?>
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="color: var(--warning);">QSO Error Details</h4>
                    <div style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; max-height: 200px; overflow-y: auto; font-family: monospace; font-size: 0.85rem;">
                        <?php foreach (array_slice($xqso_details, 0, 50) as $det) echo htmlspecialchars($det) . "<br>"; ?>
                        <?php if(count($xqso_details) > 50) echo "... and " . (count($xqso_details)-50) . " more."; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (count($parser->errors) > 0): ?>
                <p style="color: var(--danger);">You must fix critical header errors before you can submit.</p>
                <a href="index.php?page=submit_log" class="btn btn-danger">Cancel & Re-upload</a>
            <?php else: ?>
                <p>If you proceed, any X-QSOs will be ignored for points.</p>
                <form method="post" style="margin-top: 1.5rem; max-width: 400px;">
                    <?php 
                    $stmt_check = $pdo->prepare("SELECT id FROM participants WHERE callsign = ? AND year = ?");
                    $stmt_check->execute([$parser->headers['CALLSIGN'] ?? '', $current_contest_year]);
                    if ($stmt_check->rowCount() > 0): 
                    ?>
                        <div class="form-group" style="background: rgba(245, 158, 11, 0.2); padding: 1rem; border-radius: 8px; border: 1px solid rgba(245, 158, 11, 0.5);">
                            <label style="color: #fbbf24;">You have already submitted a log. To overwrite it, please enter your PIN:</label>
                            <input type="password" name="update_pin" required placeholder="6-digit PIN">
                        </div>
                    <?php endif; ?>
                    <button type="submit" name="force_submit" class="btn">Force Submit Log</button>
                    <a href="index.php?page=submit_log" class="btn btn-danger" style="margin-left: 1rem;">Cancel</a>
                </form>
            <?php endif; ?>

        <?php elseif ($step === 3): ?>
            <p>Thank you for your participation.</p>
            <p>You can check the received logs page to ensure your log is listed.</p>
            <div style="margin-top: 2rem;">
                <a href="index.php?page=received_logs" class="btn">View Received Logs</a>
            </div>
        <?php endif; ?>
    </div>
</div>
