<?php
// includes/CrossChecker.php
require_once __DIR__ . '/../config.php';

class CrossChecker {
    private $pdo;
    private $year;

    public function __construct($pdo, $year) {
        $this->pdo = $pdo;
        $this->year = $year;
    }

    public function runAdjudication() {
        // Reset all valid/busted/nil/dupe statuses first (keep xqso as is)
        $this->pdo->exec("UPDATE qsos SET status = 'valid', points = 0, is_multiplier = 0 WHERE status != 'xqso'");
        
        // Fetch all participants
        $stmt = $this->pdo->prepare("SELECT * FROM participants WHERE year = ? AND disqualified = 0");
        $stmt->execute([$this->year]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $ubn_reports = [];

        foreach ($participants as $p) {
            $p_id = $p['id'];
            $callsign = $p['callsign'];
            $ubn_reports[$callsign] = "UBN Report for $callsign - YB DX Contest {$this->year}\n";
            $ubn_reports[$callsign] .= "=================================================\n\n";

            // Fetch QSOs (including xqso so we can report them)
            $qso_stmt = $this->pdo->prepare("SELECT * FROM qsos WHERE participant_id = ? ORDER BY qso_date, qso_time");
            $qso_stmt->execute([$p_id]);
            $qsos = $qso_stmt->fetchAll(PDO::FETCH_ASSOC);

            $worked_calls = [];
            $worked_multipliers = [];
            
            $valid_count = 0;
            $dupe_count = 0;
            $busted_count = 0;
            $nil_count = 0;
            $unique_count = 0;
            $xqso_count = 0;
            $total_points = 0;

            require_once __DIR__ . '/ScoringHelper.php';

            foreach ($qsos as $q) {
                if ($q['status'] === 'xqso') {
                    $ubn_reports[$callsign] .= $this->formatQsoLine($q) . " - QSO Error\n";
                    $xqso_count++;
                    continue;
                }
                
                $rcvd_call = strtoupper($q['rcvd_call']);
                $band_mode = $q['freq'] . '_' . $q['mode'];
                
                // 1. Dupe Check
                if (isset($worked_calls[$rcvd_call][$band_mode])) {
                    $this->updateQsoStatus($q['id'], 'dupe', 0, 0);
                    $ubn_reports[$callsign] .= $this->formatQsoLine($q) . " - duplicate QSO\n";
                    $dupe_count++;
                    continue;
                }
                $worked_calls[$rcvd_call][$band_mode] = true;

                // 2. Cross Check
                if ($this->hasSubmittedLog($rcvd_call)) {
                    $cross = $this->findMatchingQso($rcvd_call, $callsign, $q);
                    if ($cross['status'] === 'NIL') {
                        $this->updateQsoStatus($q['id'], 'nil', 0, 0);
                        $ubn_reports[$callsign] .= $this->formatQsoLine($q) . " - NIL\n";
                        $nil_count++;
                    } elseif ($cross['status'] === 'BUSTED') {
                        $this->updateQsoStatus($q['id'], 'busted', 0, 0);
                        $ubn_reports[$callsign] .= $this->formatQsoLine($q) . " - Busted (" . $cross['reason'] . ")\n";
                        $busted_count++;
                    } else {
                        // Valid
                        $this->updateQsoStatus($q['id'], 'valid', 0, 0);
                        $ubn_reports[$callsign] .= $this->formatQsoLine($q) . " - valid QSO\n";
                        $valid_count++;
                    }
                } else {
                    // Did NOT submit a log. Check if Unique.
                    if ($this->isUniqueQso($rcvd_call)) {
                        $this->updateQsoStatus($q['id'], 'unique', 0, 0);
                        $ubn_reports[$callsign] .= $this->formatQsoLine($q) . " - Unique\n";
                        $unique_count++;
                    } else {
                        // Valid because it is recorded by >= 3 participants
                        $this->updateQsoStatus($q['id'], 'valid', 0, 0);
                        $ubn_reports[$callsign] .= $this->formatQsoLine($q) . " - valid QSO\n";
                        $valid_count++;
                    }
                }
            }
            
            // Now that all statuses are marked, fetch ONLY VALID QSOs
            $valid_stmt = $this->pdo->prepare("SELECT * FROM qsos WHERE participant_id = ? AND status = 'valid' ORDER BY qso_date, qso_time");
            $valid_stmt->execute([$p_id]);
            $valid_qsos = $valid_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Re-calculate points and multipliers using ScoringHelper
            $score_data = calculateScore($valid_qsos, $p['country'], $p['continent']);
            
            // Update individual QSOs in DB with their calculated points and multipliers
            $upd_qso = $this->pdo->prepare("UPDATE qsos SET points = ?, is_multiplier = ? WHERE id = ?");
            foreach ($valid_qsos as $vq) {
                $upd_qso->execute([$vq['qso_points'], $vq['mult_count'], $vq['id']]);
            }
            
            $total_points = $score_data['points'];
            $total_mults = $score_data['multiplier'];
            $final_score = $score_data['raw_score'];
            
            $raw_qso = $valid_count + $unique_count + $dupe_count + $busted_count + $nil_count + $xqso_count;

            // Update cabrillo_logs
            $upd = $this->pdo->prepare("UPDATE cabrillo_logs SET raw_score=?, total_qso=?, total_dupes=?, total_points=?, total_multiplier=?, status='adjudicated' WHERE participant_id=?");
            $upd->execute([$final_score, $raw_qso, $dupe_count, $total_points, $total_mults, $p_id]);

            // Save UBN
            $ubn_reports[$callsign] .= "\nSUMMARY\n-------\n";
            $ubn_reports[$callsign] .= "VALID QSOs: $valid_count\n";
            $ubn_reports[$callsign] .= "UNIQUE QSOs: $unique_count\n";
            $ubn_reports[$callsign] .= "DUPE QSOs: $dupe_count\n";
            $ubn_reports[$callsign] .= "BUSTED QSOs: $busted_count\n";
            $ubn_reports[$callsign] .= "NIL QSOs: $nil_count\n";
            $ubn_reports[$callsign] .= "TOTAL MULTS: $total_mults\n";
            $ubn_reports[$callsign] .= "FINAL SCORE: $final_score\n";

            $ubn_dir = __DIR__ . '/../ValidQSO/' . $this->year . '/UBN/';
            if (!is_dir($ubn_dir)) mkdir($ubn_dir, 0777, true);
            file_put_contents($ubn_dir . $callsign . '.txt', $ubn_reports[$callsign]);
        }
        
        return true;
    }

    private function findMatchingQso($target_call, $my_call, $my_qso) {
        $stmt = $this->pdo->prepare("SELECT q.* FROM qsos q JOIN participants p ON q.participant_id = p.id WHERE p.callsign = ? AND q.rcvd_call = ? AND p.year = ? AND q.status != 'xqso'");
        $stmt->execute([$target_call, $my_call, $this->year]);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($matches) == 0) {
            return ['status' => 'NIL'];
        }
        
        require_once __DIR__ . '/ScoringHelper.php';
        $my_time = strtotime($my_qso['qso_date'] . ' ' . $my_qso['qso_time']);
        $my_band = getBandFromFreq($my_qso['freq']);
        
        $best_match = null;
        $closest_diff = PHP_INT_MAX;
        
        foreach ($matches as $m) {
            $their_time = strtotime($m['qso_date'] . ' ' . $m['qso_time']);
            $diff = abs($my_time - $their_time);
            $their_band = getBandFromFreq($m['freq']);
            
            // 30 minutes = 1800 seconds tolerance
            if ($my_band === $their_band && $my_qso['mode'] === $m['mode'] && $diff <= 1800) {
                if ($diff < $closest_diff) {
                    $closest_diff = $diff;
                    $best_match = $m;
                }
            }
        }
        
        if (!$best_match) {
            return ['status' => 'NIL']; // Band, Mode, or Time mismatch > 30 mins
        }
        
        if ($my_qso['rcvd_exch'] == $best_match['sent_exch']) {
            return ['status' => 'VALID'];
        } else {
            return ['status' => 'BUSTED', 'reason' => "Exchange mismatch. Rcvd: {$my_qso['rcvd_exch']} vs Their Sent: {$best_match['sent_exch']}"];
        }
    }

    private function isUniqueQso($target_call) {
        $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT participant_id) FROM qsos WHERE rcvd_call = ? AND status != 'xqso'");
        $stmt->execute([$target_call]);
        $count = $stmt->fetchColumn();
        return $count < 3;
    }

    private function hasSubmittedLog($callsign) {
        $stmt = $this->pdo->prepare("SELECT id FROM participants WHERE callsign = ? AND year = ?");
        $stmt->execute([$callsign, $this->year]);
        return $stmt->rowCount() > 0;
    }


    private function updateQsoStatus($id, $status, $pts, $mult) {
        $stmt = $this->pdo->prepare("UPDATE qsos SET status = ?, points = ?, is_multiplier = ? WHERE id = ?");
        $stmt->execute([$status, $pts, $mult, $id]);
    }

    private function formatQsoLine($q) {
        return sprintf("%s %s %s %s %s %s %s %s %s %s",
            $q['freq'], $q['mode'], $q['qso_date'], $q['qso_time'],
            $q['sent_call'], $q['sent_rst'], $q['sent_exch'],
            $q['rcvd_call'], $q['rcvd_rst'], $q['rcvd_exch']);
    }
}
?>
