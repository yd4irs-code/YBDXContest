<?php
// includes/ScoringHelper.php

function getDxccFromCallsign($callsign) {
    static $dxcc_data = null;
    if ($dxcc_data === null) {
        $json_file = __DIR__ . '/../ham-stuff/dxcc.json';
        if (file_exists($json_file)) {
            $dxcc_data = json_decode(file_get_contents($json_file), true);
        } else {
            $dxcc_data = ['dxcc' => []];
        }
    }
    
    $callsign = strtoupper(trim($callsign));
    
    if (isset($dxcc_data['dxcc'])) {
        foreach ($dxcc_data['dxcc'] as $entity) {
            if (!empty($entity['deleted']) && $entity['deleted']) continue;
            if (empty($entity['prefixRegex'])) continue;
            
            $regex = '#' . $entity['prefixRegex'] . '#i';
            if (preg_match($regex, $callsign)) {
                return ['country' => strtoupper($entity['name']), 'continent' => $entity['continent'][0]];
            }
        }
    }
    
    return ['country' => 'UNKNOWN', 'continent' => 'UNKNOWN'];
}

function calculateScore(&$qsos, $my_country, $my_continent) {
    $total_points = 0;
    $worked_dxcc = [];
    $worked_continents = [];
    $yb_prefixes = [];
    
    foreach ($qsos as &$q) {
        $q['qso_points'] = 0;
        $q['is_mult'] = false;
        
        if (isset($q['status']) && $q['status'] === 'xqso') continue;
        if (isset($q['is_xqso']) && $q['is_xqso']) continue;
        
        $rcvd = isset($q['rcvd_call']) ? $q['rcvd_call'] : '';
        $dxcc = getDxccFromCallsign($rcvd);
        
        $is_mult = false;
        
        // Count DXCC and Continent
        if ($dxcc['country'] !== 'UNKNOWN' && strpos($dxcc['country'], 'UNKNOWN-') === false) {
            if (!isset($worked_dxcc[$dxcc['country']])) {
                $worked_dxcc[$dxcc['country']] = true;
                $is_mult = true;
            }
        }
        if ($dxcc['continent'] !== 'UNKNOWN') {
            $worked_continents[$dxcc['continent']] = true;
        }
        
        // Points Calculation
        $pts = 1;
        if ($dxcc['country'] === 'INDONESIA') {
            $pts = 10;
            // Track YB prefix (e.g. YB1, YC2, YD3) - extract prefix
            preg_match('/^([A-Z0-9]+[0-9])/', $rcvd, $matches);
            if (isset($matches[1])) {
                if (!isset($yb_prefixes[$matches[1]])) {
                    $yb_prefixes[$matches[1]] = true;
                    $is_mult = true; // YB Prefix counts as a separate mult
                }
            }
        } elseif ($dxcc['continent'] !== $my_continent) {
            $pts = 3;
        } elseif ($dxcc['country'] !== $my_country) {
            $pts = 2;
        } else {
            $pts = 1; // Same country
        }
        
        $total_points += $pts;
        
        $q['qso_points'] = $pts;
        $q['is_mult'] = $is_mult;
    }
    
    $total_yb_prefixes = count($yb_prefixes);
    $total_dxcc = count($worked_dxcc);
    $total_multiplier = $total_yb_prefixes + $total_dxcc;
    
    if ($total_multiplier == 0) $total_multiplier = 1; // Prevent zero multiplier
    
    $raw_score = $total_points * $total_multiplier;
    
    return [
        'points' => $total_points,
        'multiplier' => $total_multiplier,
        'yb_prefixes' => $total_yb_prefixes,
        'dxcc_count' => $total_dxcc,
        'continent_count' => count($worked_continents),
        'raw_score' => $raw_score
    ];
}
?>
