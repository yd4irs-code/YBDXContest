<?php
// includes/ScoringHelper.php

function getDxccFromCallsign($callsign) {
    $callsign = strtoupper(trim($callsign));
    
    // YB prefixes
    if (preg_match('/^(YB|YC|YD|YE|YF|YG|YH|7[A-I]|8[A-I])/', $callsign)) {
        return ['country' => 'INDONESIA', 'continent' => 'OC'];
    }
    // USA
    if (preg_match('/^(K|W|N|A[A-K])/', $callsign)) {
        return ['country' => 'UNITED STATES', 'continent' => 'NA'];
    }
    // Canada
    if (preg_match('/^(V[A-G]|X[J-O]|C[F-K])/', $callsign)) {
        return ['country' => 'CANADA', 'continent' => 'NA'];
    }
    // Japan
    if (preg_match('/^(J[A-S]|7[J-N]|8[J-N])/', $callsign)) {
        return ['country' => 'JAPAN', 'continent' => 'AS'];
    }
    // European Russia
    if (preg_match('/^(R|U[A-I])/', $callsign)) {
        return ['country' => 'RUSSIA', 'continent' => 'EU'];
    }
    // Asiatic Russia
    if (preg_match('/^(U[A-I]9|U[A-I]0|R9|R0)/', $callsign)) {
        return ['country' => 'ASIATIC RUSSIA', 'continent' => 'AS'];
    }
    // Spain
    if (preg_match('/^(EA|EB|EC|ED|EE|EF|EG|EH|AM|AN|AO)/', $callsign)) {
        return ['country' => 'SPAIN', 'continent' => 'EU'];
    }
    // Germany
    if (preg_match('/^(D[A-R])/', $callsign)) {
        return ['country' => 'GERMANY', 'continent' => 'EU'];
    }
    // Italy
    if (preg_match('/^(I)/', $callsign)) {
        return ['country' => 'ITALY', 'continent' => 'EU'];
    }
    // UK
    if (preg_match('/^(G|M|2)/', $callsign)) {
        return ['country' => 'UNITED KINGDOM', 'continent' => 'EU'];
    }
    // France
    if (preg_match('/^(F|TM)/', $callsign)) {
        return ['country' => 'FRANCE', 'continent' => 'EU'];
    }
    
    // Default fallback based on first character
    $first_char = substr($callsign, 0, 1);
    if (in_array($first_char, ['E', 'F', 'G', 'I', 'M', 'O', 'S', 'T'])) return ['country' => 'UNKNOWN-EU', 'continent' => 'EU'];
    if (in_array($first_char, ['A', 'B', 'H', 'J', 'V', 'X'])) return ['country' => 'UNKNOWN-AS', 'continent' => 'AS'];
    if (in_array($first_char, ['C', 'L', 'P', 'Y'])) return ['country' => 'UNKNOWN-SA', 'continent' => 'SA'];
    if (in_array($first_char, ['5', '6', 'D'])) return ['country' => 'UNKNOWN-AF', 'continent' => 'AF'];
    if (in_array($first_char, ['Z'])) return ['country' => 'UNKNOWN-OC', 'continent' => 'OC'];
    
    return ['country' => 'UNKNOWN', 'continent' => 'UNKNOWN'];
}

function calculateScore($qsos, $my_country, $my_continent) {
    $total_points = 0;
    $worked_dxcc = [];
    $worked_continents = [];
    $yb_prefixes = [];
    
    foreach ($qsos as $q) {
        if (isset($q['status']) && $q['status'] === 'xqso') continue;
        if (isset($q['is_xqso']) && $q['is_xqso']) continue;
        
        $rcvd = isset($q['rcvd_call']) ? $q['rcvd_call'] : '';
        $dxcc = getDxccFromCallsign($rcvd);
        
        // Count DXCC and Continent
        if ($dxcc['country'] !== 'UNKNOWN' && strpos($dxcc['country'], 'UNKNOWN-') === false) {
            $worked_dxcc[$dxcc['country']] = true;
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
                $yb_prefixes[$matches[1]] = true;
            }
        } elseif ($dxcc['continent'] !== $my_continent) {
            $pts = 3;
        } elseif ($dxcc['country'] !== $my_country) {
            $pts = 2;
        } else {
            $pts = 1; // Same country
        }
        
        $total_points += $pts;
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
