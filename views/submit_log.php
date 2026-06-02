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
        $required_headers = ['CALLSIGN', 'CATEGORY-OPERATOR', 'CATEGORY-BAND', 'CATEGORY-POWER', 'CATEGORY-MODE', 'EMAIL'];
        foreach ($required_headers as $req) {
            if (isset($_POST[$req]) && !empty(trim($_POST[$req]))) {
                $parser->headers[$req] = strtoupper(trim($_POST[$req]));
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
        $continent = '';
        
        // Simple heuristic for DXCC
        if (preg_match('/^(YB|YC|YD|YE|YF|YG|YH)/', $callsign)) {
            $country = 'INDONESIA'; $continent = 'OC';
        } elseif (preg_match('/^(K|W|N|A[A-K])/', $callsign) || strpos($country, 'UNITED STATES') !== false || strpos($country, 'USA') !== false) {
            $country = 'UNITED STATES'; $continent = 'NA';
        } elseif (preg_match('/^(V[A-G]|X[J-O])/', $callsign) || strpos($country, 'CANADA') !== false) {
            $country = 'CANADA'; $continent = 'NA';
        } elseif (preg_match('/^(J[A-S])/', $callsign) || strpos($country, 'JAPAN') !== false) {
            $country = 'JAPAN'; $continent = 'AS';
        } elseif (preg_match('/^(R|U[A-I])/', $callsign) || strpos($country, 'RUSSIA') !== false) {
            $country = 'RUSSIA'; $continent = 'EU';
        } else {
            if (in_array($country, ['UNITED STATES', 'CANADA', 'MEXICO'])) $continent = 'NA';
            elseif (in_array($country, ['INDONESIA', 'AUSTRALIA', 'NEW ZEALAND', 'PHILIPPINES'])) $continent = 'OC';
            elseif (in_array($country, ['JAPAN', 'CHINA', 'INDIA', 'MALAYSIA'])) $continent = 'AS';
            elseif (in_array($country, ['RUSSIA', 'SPAIN', 'GERMANY', 'ITALY', 'FRANCE', 'UK', 'ENGLAND', 'ROMANIA', 'BULGARIA'])) $continent = 'EU';
            elseif (in_array($country, ['BRAZIL', 'ARGENTINA', 'CHILE', 'COLOMBIA'])) $continent = 'SA';
            elseif (in_array($country, ['SOUTH AFRICA', 'EGYPT', 'MOROCCO'])) $continent = 'AF';
            
            if (empty($country)) $country = 'UNKNOWN-COUNTRY';
            if (empty($continent)) $continent = 'UNKNOWN-CONT';
        }
        
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
                
                $raw_qso = 0;
                foreach ($parser->qsos as $q) {
                    if (!$q['is_xqso']) $raw_qso++;
                }
                
                $stmt = $pdo->prepare("INSERT INTO cabrillo_logs (participant_id, file_path, soapbox, total_qso) VALUES (?, ?, ?, ?)");
                $stmt->execute([$p_id, $final_file, $soapbox, $raw_qso]);
                
                // Save QSOs to DB for Adjudication
                $stmt = $pdo->prepare("INSERT INTO qsos (participant_id, freq, mode, qso_date, qso_time, sent_call, sent_rst, sent_exch, rcvd_call, rcvd_rst, rcvd_exch, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($parser->qsos as $qso) {
                    $status = $qso['is_xqso'] ? 'xqso' : 'valid';
                    $stmt->execute([
                        $p_id, $qso['freq'], $qso['mode'], $qso['date'], $qso['time'],
                        $qso['sent_call'], $qso['sent_rst'], $qso['sent_exch'],
                        $qso['rcvd_call'], $qso['rcvd_rst'], $qso['rcvd_exch'], $status
                    ]);
                }
                
                $pdo->commit();
                
                // Send PIN only if it was newly generated or we just want to remind them
                Mailer::sendPin($email, $callsign, $pin);
                
                unset($_SESSION['temp_cabrillo']);
                unlink($temp_file);
                
                $step = 3; // Success
                $message = "Log successfully submitted! Your PIN has been emailed to $email.";
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
    
    // Check if there are missing headers (especially from v2 to v3)
    $missing_headers = [];
    $required_headers = ['CALLSIGN', 'CATEGORY-OPERATOR', 'CATEGORY-BAND', 'CATEGORY-POWER', 'CATEGORY-MODE', 'EMAIL'];
    foreach ($required_headers as $req) {
        if (!isset($parser->headers[$req]) || empty(trim($parser->headers[$req]))) {
            $missing_headers[] = $req;
        }
    }
    
    if ($parser->is_v2 && count($missing_headers) > 0) {
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
                        <label><?php echo $h; ?></label>
                        <?php if ($h === 'CATEGORY-MODE'): ?>
                            <input type="text" name="<?php echo $h; ?>" value="SSB" readonly style="background: rgba(255,255,255,0.1);">
                        <?php elseif ($h === 'CATEGORY-OPERATOR'): ?>
                            <select name="<?php echo $h; ?>" required>
                                <option value="SINGLE-OP">SINGLE-OP</option>
                                <option value="MULTI-OP">MULTI-OP</option>
                                <option value="CHECKLOG">CHECKLOG</option>
                            </select>
                        <?php elseif ($h === 'CATEGORY-BAND'): ?>
                            <select name="<?php echo $h; ?>" required>
                                <option value="ALL">ALL</option>
                                <option value="80M">80M</option>
                                <option value="40M">40M</option>
                                <option value="20M">20M</option>
                                <option value="15M">15M</option>
                                <option value="10M">10M</option>
                            </select>
                        <?php elseif ($h === 'CATEGORY-POWER'): ?>
                            <select name="<?php echo $h; ?>" required>
                                <option value="HIGH">HIGH</option>
                                <option value="LOW">LOW</option>
                                <option value="QRP">QRP</option>
                            </select>
                        <?php else: ?>
                            <input type="text" name="<?php echo $h; ?>" required>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" name="correct_header" class="btn">Update & Continue</button>
            </form>

        <?php elseif ($step === 2): ?>
            <h3 style="color: var(--success);">Validation Results</h3>
            <p>Version: <?php echo htmlspecialchars($parser->version); ?></p>
            <p>Callsign: <?php echo htmlspecialchars($parser->headers['CALLSIGN'] ?? '-'); ?></p>
            
            <?php
            $valid_qso = 0;
            $xqso = 0;
            $xqso_details = [];
            foreach ($parser->qsos as $i => $qso) {
                if ($qso['is_xqso']) {
                    $xqso++;
                    $xqso_details[] = "Line " . ($i + 1) . ": " . implode(", ", $qso['error_reasons']);
                } else {
                    $valid_qso++;
                }
            }
            ?>
            
            <div style="display: flex; gap: 2rem; margin: 1.5rem 0;">
                <div style="background: rgba(16, 185, 129, 0.1); padding: 1rem; border-radius: 8px; flex: 1;">
                    <h4 style="color: var(--success); margin-bottom: 0.5rem;">Valid QSOs</h4>
                    <span style="font-size: 2rem; font-weight: bold;"><?php echo $valid_qso; ?></span>
                </div>
                <div style="background: rgba(239, 68, 68, 0.1); padding: 1rem; border-radius: 8px; flex: 1;">
                    <h4 style="color: var(--danger); margin-bottom: 0.5rem;">Error QSOs (X-QSO)</h4>
                    <span style="font-size: 2rem; font-weight: bold;"><?php echo $xqso; ?></span>
                </div>
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
