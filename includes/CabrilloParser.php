<?php
// includes/CabrilloParser.php
require_once __DIR__ . '/../config.php';

class CabrilloParser {
    private $file_lines = [];
    public $headers = [];
    public $qsos = [];
    public $errors = [];
    public $version = '';
    public $is_v2 = false;
    
    // Contest config
    private $contest_start_ts;
    private $contest_end_ts;

    public function __construct($file_path, $contest_year) {
        $this->file_lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        $schedule = getContestDate($contest_year);
        $this->contest_start_ts = strtotime($schedule['start']);
        $this->contest_end_ts = strtotime($schedule['end']);
    }

    public function parse() {
        $in_qso_section = false;

        foreach ($this->file_lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (strpos($line, 'START-OF-LOG:') === 0) {
                $this->version = trim(substr($line, 13));
                if (strpos($this->version, '2.0') !== false) {
                    $this->is_v2 = true;
                }
                continue;
            }

            if (strpos($line, 'END-OF-LOG:') === 0) {
                break;
            }

            if (strpos($line, 'QSO:') === 0 || strpos($line, 'X-QSO:') === 0) {
                $in_qso_section = true;
                $this->parseQSO($line);
            } elseif (!$in_qso_section) {
                $this->parseHeader($line);
            }
        }
        
        $this->validate();
    }

    private function parseHeader($line) {
        $parts = explode(':', $line, 2);
        if (count($parts) == 2) {
            $key = strtoupper(trim($parts[0]));
            $value = trim($parts[1]);
            
            if ($key === 'SOAPBOX') {
                if (!isset($this->headers['SOAPBOX'])) {
                    $this->headers['SOAPBOX'] = '';
                }
                $this->headers['SOAPBOX'] .= $value . "\n";
            } else {
                $this->headers[$key] = $value;
            }
        }
    }

    private function parseQSO($line) {
        $is_x_qso = (strpos($line, 'X-QSO:') === 0);
        $data_str = $is_x_qso ? trim(substr($line, 6)) : trim(substr($line, 4));
        
        // Split by multiple spaces
        $parts = preg_split('/\s+/', $data_str);
        
        if (count($parts) >= 10) {
            $freq = $parts[0];
            $mode = strtoupper($parts[1]);
            $date = $parts[2];
            $time = $parts[3];
            $sent_call = strtoupper($parts[4]);
            $sent_rst = $parts[5];
            $sent_exch = $parts[6];
            $rcvd_call = strtoupper($parts[7]);
            $rcvd_rst = $parts[8];
            $rcvd_exch = $parts[9];
            
            $qso = [
                'original_line' => $line,
                'is_xqso' => $is_x_qso,
                'freq' => $freq,
                'mode' => $mode,
                'date' => $date,
                'time' => $time,
                'sent_call' => $sent_call,
                'sent_rst' => $sent_rst,
                'sent_exch' => $sent_exch,
                'rcvd_call' => $rcvd_call,
                'rcvd_rst' => $rcvd_rst,
                'rcvd_exch' => $rcvd_exch,
                'error_reasons' => []
            ];
            
            $this->qsos[] = $qso;
        }
    }

    private function validate() {
        // 1. Check required headers
        $required_headers = ['CALLSIGN', 'CATEGORY-OPERATOR', 'CATEGORY-BAND', 'CATEGORY-POWER'];
        foreach ($required_headers as $req) {
            if (!isset($this->headers[$req]) || empty($this->headers[$req])) {
                $this->errors[] = "Missing required header: $req";
            }
        }

        // 2. Validate Operator Count
        if (isset($this->headers['CATEGORY-OPERATOR']) && isset($this->headers['OPERATORS'])) {
            $cat_op = strtoupper($this->headers['CATEGORY-OPERATOR']);
            $operators = preg_split('/[\s,]+/', trim($this->headers['OPERATORS']));
            $operators = array_filter($operators); // remove empty
            
            if ($cat_op == 'SINGLE-OP' && count($operators) > 1) {
                $this->errors[] = "SINGLE-OP category only allows 1 callsign in OPERATORS header.";
            } elseif ($cat_op == 'MULTI-OP' && count($operators) < 2) {
                $this->errors[] = "MULTI-OP category requires at least 2 callsigns in OPERATORS header.";
            }
        }

        // 3. Validate CATEGORY-MODE is SSB
        if (isset($this->headers['CATEGORY-MODE'])) {
            if (strtoupper($this->headers['CATEGORY-MODE']) !== 'SSB') {
                $this->errors[] = "CATEGORY-MODE must be SSB. Invalid mode found: " . $this->headers['CATEGORY-MODE'];
            }
        }

        $header_callsign = isset($this->headers['CALLSIGN']) ? strtoupper($this->headers['CALLSIGN']) : '';

        // Validate each QSO
        foreach ($this->qsos as &$qso) {
            if ($qso['is_xqso']) continue; // already marked

            // Anti-Cheat: Sent Call must match Header Callsign
            if ($qso['sent_call'] !== $header_callsign) {
                $qso['is_xqso'] = true;
                $qso['error_reasons'][] = "Sent callsign ({$qso['sent_call']}) does not match header CALLSIGN ($header_callsign)";
            }

            // Mode must be PH
            if ($qso['mode'] !== 'PH') {
                $qso['is_xqso'] = true;
                $qso['error_reasons'][] = "Invalid mode ({$qso['mode']}). Only PH is allowed in QSO line.";
            }

            // Timeframe validation
            $qso_datetime = DateTime::createFromFormat('Y-m-d Hi', $qso['date'] . ' ' . $qso['time'], new DateTimeZone('UTC'));
            if (!$qso_datetime) {
                // Try Y-m-d H:i (some logs might have colons)
                $qso_datetime = DateTime::createFromFormat('Y-m-d H:i', $qso['date'] . ' ' . $qso['time'], new DateTimeZone('UTC'));
            }
            
            if ($qso_datetime) {
                $qso_ts = $qso_datetime->getTimestamp();
                if ($qso_ts < $this->contest_start_ts || $qso_ts > $this->contest_end_ts) {
                    $qso['is_xqso'] = true;
                    $qso['error_reasons'][] = "QSO time is outside the contest schedule.";
                }
            } else {
                $qso['is_xqso'] = true;
                $qso['error_reasons'][] = "Invalid date/time format.";
            }
        }
    }

    public function generateV3Content() {
        // Build new content array
        $content = ["START-OF-LOG: 3.0"];
        foreach ($this->headers as $k => $v) {
            if ($k === 'SOAPBOX') {
                $lines = explode("\n", trim($v));
                foreach ($lines as $l) {
                    if(!empty(trim($l))) $content[] = "SOAPBOX: " . trim($l);
                }
            } else {
                $content[] = "$k: $v";
            }
        }
        
        foreach ($this->qsos as $qso) {
            $prefix = $qso['is_xqso'] ? 'X-QSO:' : 'QSO:';
            // Align spacing nicely
            $line = sprintf("%s %5s %2s %s %s %-10s %3s %-6s %-10s %3s %-6s",
                $prefix,
                $qso['freq'],
                $qso['mode'],
                $qso['date'],
                $qso['time'],
                $qso['sent_call'],
                $qso['sent_rst'],
                $qso['sent_exch'],
                $qso['rcvd_call'],
                $qso['rcvd_rst'],
                $qso['rcvd_exch']
            );
            $content[] = $line;
        }
        $content[] = "END-OF-LOG:";
        return implode("\n", $content);
    }
}
?>
