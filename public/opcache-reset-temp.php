<?php
/**
 * Temporäres Script zum Leeren des PHP OPcache
 * NACH VERWENDUNG SOFORT LÖSCHEN!
 */

// Einfacher Schutz - nur mit Parameter aufrufbar
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes-clear-opcache') {
    http_response_code(403);
    die('Zugriff verweigert. Verwende: ?confirm=yes-clear-opcache');
}

$result = [];

// OPcache Status vor Reset
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    $result['before'] = [
        'enabled' => $status['opcache_enabled'] ?? false,
        'cached_scripts' => $status['opcache_statistics']['num_cached_scripts'] ?? 0,
        'memory_used' => round(($status['memory_usage']['used_memory'] ?? 0) / 1024 / 1024, 2) . ' MB',
    ];
}

// OPcache leeren
if (function_exists('opcache_reset')) {
    $resetResult = opcache_reset();
    $result['reset_success'] = $resetResult;
} else {
    $result['error'] = 'OPcache ist nicht verfügbar';
}

// OPcache Status nach Reset
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    $result['after'] = [
        'enabled' => $status['opcache_enabled'] ?? false,
        'cached_scripts' => $status['opcache_statistics']['num_cached_scripts'] ?? 0,
        'memory_used' => round(($status['memory_usage']['used_memory'] ?? 0) / 1024 / 1024, 2) . ' MB',
    ];
}

$result['message'] = 'OPcache wurde geleert. LÖSCHE DIESE DATEI JETZT!';
$result['timestamp'] = date('Y-m-d H:i:s');

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);
