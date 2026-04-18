<?php
/**
 * TKSTAR GPS Poller - runs every minute via cron
 */

$pdo = new PDO('mysql:host=127.0.0.1;dbname=kabas;charset=utf8mb4', 'kabas', 'fPz978B8DaLs');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$configFile = __DIR__ . '/gps-config.json';

$config = json_decode(file_get_contents($configFile), true);
if (!$config || empty($config['cookies'])) {
    echo date('Y-m-d H:i:s') . " No config\n";
    exit(1);
}

// Poll TKSTAR
$ch = curl_init('https://www.mytkstar.net/Ajax/DevicesAjax.asmx/GetDevicesByUserID');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'UserID' => $config['user_id'],
        'isFirst' => false,
        'TimeZones' => '7:00',
        'DeviceID' => $config['device_id'],
        'IsKM' => 1,
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Requested-With: XMLHttpRequest',
        'Cookie: ' . $config['cookies'],
        'Referer: https://www.mytkstar.net/map.aspx?id=' . $config['user_id'] . '&deviceID=' . $config['device_id'],
    ],
    CURLOPT_TIMEOUT => 15,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo date('Y-m-d H:i:s') . " HTTP $httpCode\n";
    exit(1);
}

$json = json_decode($response, true);
if (!isset($json['d']) || empty($json['d'])) {
    // Session expired - mark it
    $config['session_expired'] = true;
    $config['expired_at'] = date('Y-m-d H:i:s');
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    echo date('Y-m-d H:i:s') . " SESSION EXPIRED\n";
    exit(1);
}

// Session works - clear expired flag if set
if (!empty($config['session_expired'])) {
    unset($config['session_expired']);
    unset($config['expired_at']);
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
}

// Parse response
$inner = $json['d'];
$inner = preg_replace('/([{,])\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $inner);
$data = json_decode($inner, true);
$devices = $data['devices'] ?? [];

foreach ($devices as $device) {
    $lat = floatval($device['latitude'] ?? 0);
    $lng = floatval($device['longitude'] ?? 0);
    $speed = floatval($device['speed'] ?? 0);
    $course = intval($device['course'] ?? 0);
    $deviceTime = $device['deviceUtcDate'] ?? null;
    $deviceId = strval($device['id'] ?? '');

    if ($lat == 0 && $lng == 0) continue;

    // Skip duplicates
    $stmt = $pdo->prepare("SELECT id FROM gps_positions WHERE device_id = ? AND device_time = ? LIMIT 1");
    $stmt->execute([$deviceId, $deviceTime]);
    if ($stmt->fetch()) continue;

    $stmt = $pdo->prepare("INSERT INTO gps_positions (device_id, latitude, longitude, speed, heading, gps_fixed, device_time, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())");
    $stmt->execute([$deviceId, $lat, $lng, $speed, $course, $deviceTime]);

    $stmt = $pdo->prepare("INSERT IGNORE INTO gps_devices (device_id, name, model, created_at, updated_at) VALUES (?, ?, 'TK905-4G', NOW(), NOW())");
    $stmt->execute([$deviceId, 'TK905 #' . $deviceId]);

    echo date('Y-m-d H:i:s') . " Stored: lat=$lat lng=$lng speed=$speed\n";
}
