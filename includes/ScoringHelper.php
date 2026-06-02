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

function parsePortableCallsign($callsign) {
    $callsign = strtoupper(trim($callsign));
    
    if (strpos($callsign, '/') === false) {
        return ['base' => $callsign, 'effective_modifier' => null];
    }
    
    $parts = explode('/', $callsign);
    $base = '';
    $modifier = '';
    
    $op_modifiers = ['P', 'M', 'MM', 'AM', 'QRP'];
    
    $filtered_parts = [];
    foreach ($parts as $p) {
        if (!in_array($p, $op_modifiers)) {
            $filtered_parts[] = $p;
        }
    }
    
    if (count($filtered_parts) == 1) {
        return ['base' => $filtered_parts[0], 'effective_modifier' => null];
    }
    
    $part1 = $filtered_parts[0];
    $part2 = $filtered_parts[1];
    
    if (strlen($part1) < strlen($part2)) {
        $modifier = $part1;
        $base = $part2;
    } elseif (strlen($part2) < strlen($part1)) {
        $modifier = $part2;
        $base = $part1;
    } else {
        $modifier = $part1;
        $base = $part2;
    }
    
    return ['base' => $base, 'effective_modifier' => $modifier];
}

function getPrefixAndDxccLookup($callsign) {
    $parsed = parsePortableCallsign($callsign);
    $base = $parsed['base'];
    $mod = $parsed['effective_modifier'];
    
    if (!$mod) {
        preg_match('/^([A-Z0-9]+[0-9])/', $base, $matches);
        $prefix = isset($matches[1]) ? $matches[1] : substr($base, 0, 3);
        return ['prefix' => $prefix, 'dxcc_lookup' => $base];
    }
    
    if (is_numeric($mod)) {
        preg_match('/^([A-Z0-9]+)[0-9]/', $base, $matches);
        $base_letters = isset($matches[1]) ? $matches[1] : substr($base, 0, 2);
        $prefix = $base_letters . $mod;
        return ['prefix' => $prefix, 'dxcc_lookup' => $base];
    }
    
    if (preg_match('/[0-9]/', $mod)) {
        $prefix = $mod;
    } else {
        $prefix = $mod . '1';
    }
    return ['prefix' => $prefix, 'dxcc_lookup' => $mod];
}

function getBandFromFreq($freq) {
    if (!is_numeric($freq)) {
        $f = strtoupper(trim($freq));
        if (strpos($f, 'M') !== false) return $f;
        return 'UNKNOWN';
    }
    $f = (int)$freq;
    if ($f >= 1800 && $f <= 2000) return '160M';
    if ($f >= 3500 && $f <= 4000) return '80M';
    if ($f >= 7000 && $f <= 7300) return '40M';
    if ($f >= 14000 && $f <= 14350) return '20M';
    if ($f >= 21000 && $f <= 21450) return '15M';
    if ($f >= 28000 && $f <= 29700) return '10M';
    // Fallback if freq is exactly MHz edge
    if ($f == 1800) return '160M';
    if ($f == 3500) return '80M';
    if ($f == 7000) return '40M';
    if ($f == 14000) return '20M';
    if ($f == 21000) return '15M';
    if ($f == 28000) return '10M';
    return 'UNKNOWN';
}

function calculateScore(&$qsos, $my_country, $my_continent) {
    $total_points = 0;
    $worked_dxcc = [];
    $worked_continents = [];
    $worked_prefixes = [];
    
    $is_indonesian = ($my_country === 'INDONESIA');
    
    foreach ($qsos as &$q) {
        $q['qso_points'] = 0;
        $q['is_mult'] = false;
        $q['mult_count'] = 0;
        
        if (isset($q['status']) && $q['status'] === 'xqso') continue;
        if (isset($q['is_xqso']) && $q['is_xqso']) continue;
        
        $rcvd = isset($q['rcvd_call']) ? $q['rcvd_call'] : '';
        
        // Handle portable callsigns for prefix and DXCC lookup
        $parsed_call = getPrefixAndDxccLookup($rcvd);
        $prefix = $parsed_call['prefix'];
        $dxcc = getDxccFromCallsign($parsed_call['dxcc_lookup']);
        
        $band = getBandFromFreq(isset($q['freq']) ? $q['freq'] : '0');
        
        $is_mult = false;
        $mult_count = 0;
        $pts = 0;
        
        if ($is_indonesian) {
            // Points for Indonesian stations
            if ($dxcc['country'] === 'INDONESIA') {
                $pts = 0;
            } elseif ($dxcc['continent'] === $my_continent) {
                $pts = 5;
            } else {
                $pts = 10;
            }
            
            // Multipliers (per band) for Indonesian stations
            if ($dxcc['country'] !== 'UNKNOWN' && strpos($dxcc['country'], 'UNKNOWN-') === false) {
                if (!isset($worked_dxcc[$band][$dxcc['country']])) {
                    $worked_dxcc[$band][$dxcc['country']] = true;
                    $is_mult = true;
                    $mult_count++;
                }
            }
            if (!isset($worked_prefixes[$band][$prefix])) {
                $worked_prefixes[$band][$prefix] = true;
                $is_mult = true;
                $mult_count++;
            }
            
        } else {
            // Points for DX stations
            if ($dxcc['country'] === 'INDONESIA') {
                $pts = 10;
                // Multipliers (YB Prefixes) for DX stations per band
                if (!isset($worked_prefixes[$band][$prefix])) {
                    $worked_prefixes[$band][$prefix] = true;
                    $is_mult = true;
                    $mult_count++;
                }
            } elseif ($dxcc['continent'] === $my_continent) {
                if ($dxcc['country'] === $my_country) {
                    $pts = 1; // Same country
                } else {
                    $pts = 2; // Same continent, different country
                }
            } else {
                $pts = 3; // Different continent
            }
            
            // DXCC multiplier for DX per band
            if ($dxcc['country'] !== 'UNKNOWN' && strpos($dxcc['country'], 'UNKNOWN-') === false) {
                if (!isset($worked_dxcc[$band][$dxcc['country']])) {
                    $worked_dxcc[$band][$dxcc['country']] = true;
                    $is_mult = true;
                    $mult_count++;
                }
            }
        }
        
        if ($dxcc['continent'] !== 'UNKNOWN') {
            $worked_continents[$dxcc['continent']] = true;
        }
        
        $total_points += $pts;
        
        $q['qso_points'] = $pts;
        $q['is_mult'] = $is_mult;
        $q['mult_count'] = $mult_count;
    }
    
    // Sum up multipliers
    $total_dxcc = 0;
    $total_prefixes = 0;
    
    foreach ($worked_dxcc as $band => $countries) {
        $total_dxcc += count($countries);
    }
    foreach ($worked_prefixes as $band => $pfxs) {
        $total_prefixes += count($pfxs);
    }
    
    $total_multiplier = $total_dxcc + $total_prefixes;
    if ($total_multiplier == 0) $total_multiplier = 1; // Prevent zero
    
    $raw_score = $total_points * $total_multiplier;
    
    return [
        'points' => $total_points,
        'multiplier' => $total_multiplier,
        'yb_prefixes' => $total_prefixes, 
        'dxcc_count' => $total_dxcc,
        'continent_count' => count($worked_continents),
        'raw_score' => $raw_score
    ];
}
?>
