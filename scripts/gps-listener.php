<?php
/**
 * GPS Tracker TCP Listener
 *
 * Supports three protocols (auto-detected):
 *  - JT/T 808 (0x7E): Used by TK909/EG-05 — Chinese standard vehicle tracking
 *  - TKSTAR/H02 text (*HQ,...#): Legacy TKSTAR text protocol
 *  - GT06 binary (0x78/0x79): Concox/GT06 binary protocol
 *
 * The tracker sends data directly to this server after configuring
 * its IP/port via SMS: adminip123456 <IP> <PORT>
 *
 * Run as a systemd service (see gps-listener.service)
 */

$DB_HOST = '127.0.0.1';
$DB_NAME = 'kabas';
$DB_USER = 'kabas';
$DB_PASS = 'fPz978B8DaLs';
$PORT = 5023;
$LOG_FILE = __DIR__ . '/gps-listener.log';

// --- Logging ---

function gps_log($msg) {
    global $LOG_FILE;
    $line = date('Y-m-d H:i:s') . " $msg\n";
    echo $line;
    file_put_contents($LOG_FILE, $line, FILE_APPEND);
}

// --- DB connection with auto-reconnect ---

function getDb() {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
    static $pdo = null;
    try {
        if ($pdo) {
            $pdo->query('SELECT 1');
            return $pdo;
        }
    } catch (\Exception $e) {
        $pdo = null;
    }
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

// --- Main server loop ---

$server = stream_socket_server("tcp://0.0.0.0:$PORT", $errno, $errstr);
if (!$server) {
    die("Could not create server on port $PORT: $errstr ($errno)\n");
}

gps_log("GPS Listener started on port $PORT (JT808 + TKSTAR text + GT06 binary)");
stream_set_blocking($server, false);

$clients = [];

while (true) {
    // Accept new connections
    $newClient = @stream_socket_accept($server, 0);
    if ($newClient) {
        $clientId = (int)$newClient;
        $clients[$clientId] = [
            'socket'        => $newClient,
            'device_id'     => null,
            'protocol'      => null, // 'jt808', 'tkstar', or 'gt06'
            'buffer'        => '',
            'last_activity' => time(),
        ];
        stream_set_blocking($newClient, false);
        $addr = stream_socket_get_name($newClient, true);
        gps_log("[CONN] New connection from $addr");
    }

    // Process each client
    foreach ($clients as $clientId => &$client) {
        $data = @fread($client['socket'], 4096);

        if ($data === false || ($data === '' && feof($client['socket']))) {
            gps_log("[DISC] Device " . ($client['device_id'] ?? 'unknown') . " disconnected");
            fclose($client['socket']);
            unset($clients[$clientId]);
            continue;
        }

        if ($data === '') {
            // Timeout after 10 minutes of inactivity
            if (time() - $client['last_activity'] > 600) {
                gps_log("[TIMEOUT] Closing idle connection (device: " . ($client['device_id'] ?? 'unknown') . ")");
                fclose($client['socket']);
                unset($clients[$clientId]);
            }
            continue;
        }

        $client['last_activity'] = time();
        $client['buffer'] .= $data;

        // Auto-detect protocol on first data
        if ($client['protocol'] === null) {
            $firstByte = ord($client['buffer'][0]);
            if ($firstByte === 0x7E) {
                $client['protocol'] = 'jt808';
                gps_log("[PROTO] JT/T 808 detected");
            } elseif ($firstByte === 0x78 || $firstByte === 0x79) {
                $client['protocol'] = 'gt06';
                gps_log("[PROTO] GT06 binary detected");
            } elseif ($client['buffer'][0] === '*') {
                $client['protocol'] = 'tkstar';
                gps_log("[PROTO] TKSTAR text detected");
            } else {
                // Unknown — log and skip byte
                gps_log("[PROTO] Unknown first byte: 0x" . dechex($firstByte) . " — raw: " . bin2hex(substr($client['buffer'], 0, 20)));
                $client['buffer'] = substr($client['buffer'], 1);
                continue;
            }
        }

        // Dispatch to protocol handler
        switch ($client['protocol']) {
            case 'jt808':
                processJt808Buffer($client);
                break;
            case 'tkstar':
                processTkstarBuffer($client);
                break;
            case 'gt06':
                processGt06Buffer($client);
                break;
        }
    }
    unset($client);

    usleep(50000); // 50ms
}

// ============================================================
//  JT/T 808 PROTOCOL
//  Frame: 7E MSG_ID(2) BODY_ATTR(2) TERMINAL_ID(6) SEQ(2) BODY(...) CRC(1) 7E
// ============================================================

function processJt808Buffer(&$client) {
    while (true) {
        // Find frame start
        $start = strpos($client['buffer'], chr(0x7E));
        if ($start === false) {
            $client['buffer'] = '';
            break;
        }

        // Find frame end (next 0x7E after start)
        $end = strpos($client['buffer'], chr(0x7E), $start + 1);
        if ($end === false) break; // Wait for more data

        $frame = substr($client['buffer'], $start, $end - $start + 1);
        $client['buffer'] = substr($client['buffer'], $end + 1);

        if (strlen($frame) < 15) {
            gps_log("[JT808] Frame too short: " . strlen($frame) . " bytes");
            continue;
        }

        // Unescape: 7D 01 -> 7D, 7D 02 -> 7E (inside frame, excluding start/end 7E)
        $inner = substr($frame, 1, -1);
        $inner = str_replace(chr(0x7D) . chr(0x02), chr(0x7E), $inner);
        $inner = str_replace(chr(0x7D) . chr(0x01), chr(0x7D), $inner);

        parseJt808Packet($inner, $client);
    }
}

function parseJt808Packet($data, &$client) {
    if (strlen($data) < 12) return;

    $msgId      = (ord($data[0]) << 8) | ord($data[1]);
    $bodyAttr   = (ord($data[2]) << 8) | ord($data[3]);
    $bodyLen    = $bodyAttr & 0x03FF;

    // Terminal ID: 6 bytes BCD
    $terminalId = '';
    for ($i = 4; $i < 10; $i++) {
        $terminalId .= sprintf('%02x', ord($data[$i]));
    }
    $terminalId = ltrim($terminalId, '0') ?: '0';

    $seq = (ord($data[10]) << 8) | ord($data[11]);
    $body = substr($data, 12, -1); // Exclude CRC byte
    $crc = ord($data[strlen($data) - 1]);

    // Verify XOR checksum
    $calcCrc = 0;
    for ($i = 0; $i < strlen($data) - 1; $i++) {
        $calcCrc ^= ord($data[$i]);
    }
    if ($calcCrc !== $crc) {
        gps_log("[JT808] CRC mismatch: calc=" . dechex($calcCrc) . " got=" . dechex($crc));
    }

    if ($client['device_id'] === null) {
        $client['device_id'] = $terminalId;
        ensureDevice($terminalId);
    }

    gps_log("[JT808] MSG 0x" . sprintf('%04x', $msgId) . " from $terminalId seq=$seq bodyLen=" . strlen($body));

    switch ($msgId) {
        case 0x0001: // Terminal general response
            gps_log("[JT808] Terminal ACK for seq=$seq");
            break;

        case 0x0002: // Heartbeat
            gps_log("[JT808] Heartbeat from $terminalId");
            sendJt808Response($client, $terminalId, $seq, $msgId, 0); // 0 = success
            break;

        case 0x0100: // Terminal registration
            gps_log("[JT808] Registration from $terminalId — body: " . bin2hex($body));
            sendJt808RegistrationResponse($client, $terminalId, $seq);
            break;

        case 0x0102: // Terminal authentication
            $authCode = $body;
            gps_log("[JT808] Authentication from $terminalId — code: " . bin2hex($authCode));
            sendJt808Response($client, $terminalId, $seq, $msgId, 0); // Accept
            break;

        case 0x0200: // Location report
            handleJt808Location($body, $client, $terminalId, $seq);
            break;

        case 0x0201: // Location query response
            // First 2 bytes are the response seq, then location data
            if (strlen($body) > 2) {
                handleJt808Location(substr($body, 2), $client, $terminalId, $seq);
            }
            break;

        case 0x0704: // Bulk location upload
            handleJt808BulkLocation($body, $client, $terminalId, $seq);
            break;

        default:
            gps_log("[JT808] Unhandled message 0x" . sprintf('%04x', $msgId) . " — body: " . bin2hex($body));
            // Send generic ACK
            sendJt808Response($client, $terminalId, $seq, $msgId, 0);
            break;
    }
}

/**
 * JT808 Location Report (0x0200)
 *
 * Body: alarm(4) status(4) lat(4) lon(4) altitude(2) speed(2) heading(2) time(6 BCD)
 * Latitude/Longitude in units of 1e-6 degrees
 * Speed in units of 1/10 km/h
 * Time: BCD YY MM DD HH MM SS
 */
function handleJt808Location($body, &$client, $terminalId, $seq) {
    if (strlen($body) < 28) {
        gps_log("[JT808] Location body too short: " . strlen($body));
        return;
    }

    $alarm   = (ord($body[0]) << 24) | (ord($body[1]) << 16) | (ord($body[2]) << 8) | ord($body[3]);
    $status  = (ord($body[4]) << 24) | (ord($body[5]) << 16) | (ord($body[6]) << 8) | ord($body[7]);
    $latRaw  = (ord($body[8]) << 24) | (ord($body[9]) << 16) | (ord($body[10]) << 8) | ord($body[11]);
    $lonRaw  = (ord($body[12]) << 24) | (ord($body[13]) << 16) | (ord($body[14]) << 8) | ord($body[15]);
    $alt     = (ord($body[16]) << 8) | ord($body[17]);
    $speedRaw = (ord($body[18]) << 8) | ord($body[19]);
    $heading = (ord($body[20]) << 8) | ord($body[21]);

    // Time: BCD YY MM DD HH MM SS
    $timeStr = '';
    for ($i = 22; $i < 28; $i++) {
        $timeStr .= sprintf('%02x', ord($body[$i]));
    }
    // timeStr = YYMMDDHHMMSS — JT808 spec: UTC time. Convert to Asia/Phnom_Penh.
    $utcStr = sprintf('20%s-%s-%s %s:%s:%s',
        substr($timeStr, 0, 2), substr($timeStr, 2, 2), substr($timeStr, 4, 2),
        substr($timeStr, 6, 2), substr($timeStr, 8, 2), substr($timeStr, 10, 2));
    try {
        $dt = new DateTime($utcStr, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('Asia/Phnom_Penh'));
        $deviceTime = $dt->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
        $deviceTime = $utcStr;
    }

    // Convert coordinates
    $latitude  = $latRaw / 1000000.0;
    $longitude = $lonRaw / 1000000.0;

    // Status bit 2: 0=North, 1=South
    if ($status & 0x04) $latitude = -$latitude;
    // Status bit 3: 0=East, 1=West
    if ($status & 0x08) $longitude = -$longitude;

    // Speed: 1/10 km/h
    $speed = $speedRaw / 10.0;

    // GPS fixed: status bit 1
    $gpsFix = ($status & 0x02) ? 1 : 0;

    // ACC on: status bit 0
    $accOn = ($status & 0x01) ? 1 : 0;

    gps_log("[JT808] Position: lat=$latitude, lon=$longitude, speed=$speed km/h, heading=$heading, alt=$alt, fix=$gpsFix, acc=$accOn, time=$deviceTime");

    // Parse additional info items (TLV) — extract mileage and satellites
    if (strlen($body) > 28) {
        $extra = substr($body, 28);
        $p = 0; $L = strlen($extra);
        while ($p + 2 <= $L) {
            $id = ord($extra[$p]); $len = ord($extra[$p + 1]);
            if ($p + 2 + $len > $L) break;
            $val = substr($extra, $p + 2, $len);
            if ($id === 0x01 && $len === 4) {
                // Mileage in 1/10 km
                $mileage = ((ord($val[0]) << 24) | (ord($val[1]) << 16) | (ord($val[2]) << 8) | ord($val[3])) / 10.0;
                gps_log("[JT808] Mileage: {$mileage} km");
            }
            if ($id === 0x31 && $len === 1) {
                $satellites = ord($val[0]);
            }
            $p += 2 + $len;
        }
    }

    if ($latitude != 0 || $longitude != 0) {
        storePosition($terminalId, round($latitude, 7), round($longitude, 7), $speed, $heading, null, $gpsFix, null, $deviceTime, $alt, $accOn);
    }

    sendJt808Response($client, $terminalId, $seq, 0x0200, 0);
}

/**
 * JT808 Bulk Location Upload (0x0704)
 * Body: count(2) type(1) [len(2) + location_data]...
 */
function handleJt808BulkLocation($body, &$client, $terminalId, $seq) {
    if (strlen($body) < 3) return;

    $count = (ord($body[0]) << 8) | ord($body[1]);
    $type = ord($body[2]); // 0=normal, 1=blind-spot supplement
    gps_log("[JT808] Bulk upload: $count locations (type=$type)");

    $pos = 3;
    for ($i = 0; $i < $count && $pos < strlen($body); $i++) {
        if ($pos + 2 > strlen($body)) break;
        $itemLen = (ord($body[$pos]) << 8) | ord($body[$pos + 1]);
        $pos += 2;
        if ($pos + $itemLen > strlen($body)) break;

        $locationData = substr($body, $pos, $itemLen);
        handleJt808Location($locationData, $client, $terminalId, $seq);
        $pos += $itemLen;
    }

    sendJt808Response($client, $terminalId, $seq, 0x0704, 0);
}

/**
 * Send JT808 Platform General Response (0x8001)
 */
function sendJt808Response(&$client, $terminalId, $ackSeq, $ackMsgId, $result) {
    // Body: ack_seq(2) ack_msg_id(2) result(1)
    $body = chr(($ackSeq >> 8) & 0xFF) . chr($ackSeq & 0xFF);
    $body .= chr(($ackMsgId >> 8) & 0xFF) . chr($ackMsgId & 0xFF);
    $body .= chr($result);

    sendJt808Packet($client, 0x8001, $terminalId, $body);
}

/**
 * Send JT808 Registration Response (0x8100)
 * Body: ack_seq(2) result(1) auth_code(string)
 */
function sendJt808RegistrationResponse(&$client, $terminalId, $ackSeq) {
    $authCode = 'OK'; // Simple auth code — tracker will use this for 0x0102
    $body = chr(($ackSeq >> 8) & 0xFF) . chr($ackSeq & 0xFF);
    $body .= chr(0x00); // 0 = success
    $body .= $authCode;

    sendJt808Packet($client, 0x8100, $terminalId, $body);
    gps_log("[JT808] Registration response sent (auth=$authCode)");
}

/**
 * Build and send a JT808 frame
 */
function sendJt808Packet(&$client, $msgId, $terminalId, $body) {
    static $serverSeq = 0;
    $serverSeq = ($serverSeq + 1) & 0xFFFF;

    // Header: msg_id(2) body_attr(2) terminal_id(6) seq(2)
    $header = chr(($msgId >> 8) & 0xFF) . chr($msgId & 0xFF);

    $bodyLen = strlen($body);
    $header .= chr(($bodyLen >> 8) & 0xFF) . chr($bodyLen & 0xFF);

    // Terminal ID: pad to 6 bytes BCD
    $tidHex = str_pad($terminalId, 12, '0', STR_PAD_LEFT);
    for ($i = 0; $i < 12; $i += 2) {
        $header .= chr(hexdec(substr($tidHex, $i, 2)));
    }

    $header .= chr(($serverSeq >> 8) & 0xFF) . chr($serverSeq & 0xFF);

    $packet = $header . $body;

    // XOR checksum
    $crc = 0;
    for ($i = 0; $i < strlen($packet); $i++) {
        $crc ^= ord($packet[$i]);
    }
    $packet .= chr($crc);

    // Escape: 7E -> 7D 02, 7D -> 7D 01
    $escaped = '';
    for ($i = 0; $i < strlen($packet); $i++) {
        $b = ord($packet[$i]);
        if ($b === 0x7E) {
            $escaped .= chr(0x7D) . chr(0x02);
        } elseif ($b === 0x7D) {
            $escaped .= chr(0x7D) . chr(0x01);
        } else {
            $escaped .= chr($b);
        }
    }

    // Wrap with 7E delimiters
    $frame = chr(0x7E) . $escaped . chr(0x7E);

    @fwrite($client['socket'], $frame);
    gps_log("[JT808] Sent 0x" . sprintf('%04x', $msgId) . ": " . bin2hex($frame));
}

// ============================================================
//  TKSTAR / H02 TEXT PROTOCOL
//  Format: *HQ,DEVICE_ID,CMD,data,...#
// ============================================================

function processTkstarBuffer(&$client) {
    while (($end = strpos($client['buffer'], '#')) !== false) {
        $start = strrpos(substr($client['buffer'], 0, $end), '*');
        if ($start === false) {
            $client['buffer'] = substr($client['buffer'], $end + 1);
            continue;
        }

        $packet = substr($client['buffer'], $start, $end - $start + 1);
        $client['buffer'] = substr($client['buffer'], $end + 1);

        gps_log("[TKSTAR] Packet: $packet");
        parseTkstarPacket($packet, $client);
    }

    if (strlen($client['buffer']) > 4096) {
        $client['buffer'] = '';
    }
}

function parseTkstarPacket($packet, &$client) {
    $inner = substr($packet, 1, -1);
    $parts = explode(',', $inner);

    if (count($parts) < 3) return;

    $deviceId = $parts[1];
    $command  = $parts[2];

    if ($client['device_id'] === null) {
        $client['device_id'] = $deviceId;
        ensureDevice($deviceId);
        gps_log("[TKSTAR] Device registered: $deviceId");
    }

    switch ($command) {
        case 'V1':
        case 'V19':
        case 'V4':
        case 'VI1':
        case 'V2':
            parseTkstarGps($parts, $command, $client);
            break;
        case 'NBR':
            gps_log("[TKSTAR] LBS data from $deviceId (no GPS fix)");
            break;
        case 'HTBT':
            gps_log("[TKSTAR] Heartbeat from $deviceId");
            break;
        default:
            gps_log("[TKSTAR] Unknown command: $command from $deviceId");
            break;
    }
}

function parseTkstarGps($parts, $command, &$client) {
    if (count($parts) < 12) return;

    $deviceId = $parts[1];
    $time     = $parts[3];
    $valid    = $parts[4];
    $latRaw   = $parts[5];
    $latDir   = $parts[6];
    $lonRaw   = $parts[7];
    $lonDir   = $parts[8];
    $speed    = floatval($parts[9]);
    $heading  = intval($parts[10]);
    $date     = $parts[11];

    $battery = null;
    $lastPart = end($parts);
    if (is_numeric($lastPart) && intval($lastPart) >= 1 && intval($lastPart) <= 6) {
        $batteryMap = [1 => 5, 2 => 10, 3 => 20, 4 => 60, 5 => 80, 6 => 100];
        $battery = $batteryMap[intval($lastPart)] ?? null;
    }

    if ($valid !== 'A') return;

    $latitude  = convertDMToDecimal($latRaw, $latDir);
    $longitude = convertDMToDecimal($lonRaw, $lonDir);
    if ($latitude === null || $longitude === null) return;

    $speedKmh = round($speed * 1.852, 1);
    $deviceTime = parseGpsDateTime($date, $time);

    gps_log("[TKSTAR] Position: lat=$latitude, lon=$longitude, speed=$speedKmh km/h, heading=$heading, battery=$battery%, time=$deviceTime");
    storePosition($deviceId, $latitude, $longitude, $speedKmh, $heading, null, 1, $battery, $deviceTime);
}

function convertDMToDecimal($raw, $dir) {
    if (!is_numeric($raw) || $raw == 0) return null;
    $raw = floatval($raw);
    $degrees = intval($raw / 100);
    $minutes = $raw - ($degrees * 100);
    $decimal = $degrees + ($minutes / 60.0);
    if ($dir === 'S' || $dir === 'W') $decimal = -$decimal;
    return round($decimal, 7);
}

function parseGpsDateTime($date, $time) {
    if (strlen($date) < 6 || strlen($time) < 6) return null;
    return sprintf('20%s-%s-%s %s:%s:%s',
        substr($date, 4, 2), substr($date, 2, 2), substr($date, 0, 2),
        substr($time, 0, 2), substr($time, 2, 2), substr($time, 4, 2));
}

// ============================================================
//  GT06 BINARY PROTOCOL (fallback)
// ============================================================

function processGt06Buffer(&$client) {
    while (strlen($client['buffer']) >= 4) {
        $bytes = $client['buffer'];

        if (ord($bytes[0]) === 0x78 && ord($bytes[1]) === 0x78) {
            $packetLen = ord($bytes[2]);
            $totalLen = $packetLen + 5;
            if (strlen($bytes) < $totalLen) break;
            $packet = substr($bytes, 0, $totalLen);
            $client['buffer'] = substr($bytes, $totalLen);
            processGT06Packet($packet, $client, 3);

        } elseif (ord($bytes[0]) === 0x79 && ord($bytes[1]) === 0x79) {
            $packetLen = (ord($bytes[2]) << 8) | ord($bytes[3]);
            $totalLen = $packetLen + 6;
            if (strlen($bytes) < $totalLen) break;
            $packet = substr($bytes, 0, $totalLen);
            $client['buffer'] = substr($bytes, $totalLen);
            processGT06Packet($packet, $client, 4);

        } else {
            $client['buffer'] = substr($bytes, 1);
        }
    }
}

function processGT06Packet($packet, &$client, $offset) {
    $protocol = ord($packet[$offset]);

    switch ($protocol) {
        case 0x01:
            $deviceIdBytes = substr($packet, $offset + 1, 8);
            $deviceId = ltrim(bin2hex($deviceIdBytes), '0') ?: '0';
            $client['device_id'] = $deviceId;
            ensureDevice($deviceId);
            gps_log("[GT06] Login from device: $deviceId");
            sendGT06Response($client['socket'], $packet, $offset, 0x01);
            break;
        case 0x12: case 0x22: case 0x10: case 0x11: case 0x14:
            handleGt06Location($packet, $client, $offset, $protocol);
            break;
        case 0x13: case 0x23: case 0x08:
            gps_log("[GT06] Heartbeat from " . ($client['device_id'] ?? 'unknown'));
            sendGT06Response($client['socket'], $packet, $offset, $protocol);
            break;
        default:
            gps_log("[GT06] Unknown protocol: 0x" . dechex($protocol));
            sendGT06Response($client['socket'], $packet, $offset, $protocol);
            break;
    }
}

function handleGt06Location($packet, &$client, $offset, $protocol) {
    if (!$client['device_id']) return;
    $data = $packet;
    $pos = $offset + 1;

    $deviceTime = sprintf('%04d-%02d-%02d %02d:%02d:%02d',
        2000 + ord($data[$pos]), ord($data[$pos+1]), ord($data[$pos+2]),
        ord($data[$pos+3]), ord($data[$pos+4]), ord($data[$pos+5]));
    $pos += 6;

    $satellites = ord($data[$pos]) & 0x0F;
    $pos += 1;

    $latRaw = (ord($data[$pos])<<24)|(ord($data[$pos+1])<<16)|(ord($data[$pos+2])<<8)|ord($data[$pos+3]);
    $latitude = $latRaw / 30000.0 / 60.0;
    $pos += 4;

    $lngRaw = (ord($data[$pos])<<24)|(ord($data[$pos+1])<<16)|(ord($data[$pos+2])<<8)|ord($data[$pos+3]);
    $longitude = $lngRaw / 30000.0 / 60.0;
    $pos += 4;

    $speed = ord($data[$pos]);
    $pos += 1;

    $courseStatus = (ord($data[$pos])<<8)|ord($data[$pos+1]);
    $heading = $courseStatus & 0x03FF;
    $gpsFix = ($courseStatus >> 12) & 0x01;
    if (($courseStatus >> 10) & 0x01) $latitude = -$latitude;
    if (($courseStatus >> 11) & 0x01) $longitude = -$longitude;

    if ($latitude != 0 || $longitude != 0) {
        storePosition($client['device_id'], round($latitude,7), round($longitude,7), $speed, $heading, $satellites, $gpsFix, null, $deviceTime);
    }
    sendGT06Response($client['socket'], $packet, $offset, $protocol);
}

function sendGT06Response($socket, $packet, $offset, $protocol) {
    $packetLen = strlen($packet);
    $serialHigh = ord($packet[$packetLen - 6]);
    $serialLow  = ord($packet[$packetLen - 5]);

    $response = chr(0x78).chr(0x78).chr(0x05).chr($protocol).chr($serialHigh).chr($serialLow);
    $crcData = chr(0x05).chr($protocol).chr($serialHigh).chr($serialLow);
    $crc = crc16_itu($crcData);
    $response .= chr(($crc>>8)&0xFF).chr($crc&0xFF).chr(0x0D).chr(0x0A);
    @fwrite($socket, $response);
}

function crc16_itu($data) {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $crc ^= ord($data[$i]);
        for ($j = 0; $j < 8; $j++) {
            $crc = ($crc & 1) ? (($crc >> 1) ^ 0xA001) : ($crc >> 1);
        }
    }
    return $crc ^ 0xFFFF;
}

// ============================================================
//  SHARED: DB storage
// ============================================================

function haversineM($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2)**2;
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function ensureDevice($deviceId) {
    $pdo = getDb();
    $stmt = $pdo->prepare("INSERT IGNORE INTO gps_devices (device_id, name, model, created_at, updated_at) VALUES (?, ?, 'TK909', NOW(), NOW())");
    $stmt->execute([$deviceId, 'Tracker ' . $deviceId]);
}

function storePosition($deviceId, $lat, $lng, $speed, $heading, $satellites, $gpsFix, $battery, $deviceTime, $altitude = null, $accOn = null) {
    $pdo = getDb();

    if ($deviceTime) {
        $stmt = $pdo->prepare("SELECT id FROM gps_positions WHERE device_id = ? AND device_time = ? LIMIT 1");
        $stmt->execute([$deviceId, $deviceTime]);
        if ($stmt->fetch()) {
            gps_log("[DB] Duplicate skipped: $deviceId @ $deviceTime");
            return;
        }
    }

    // Anti-drift: if speed≈0 AND new position is within 50m of last known, reuse last position
    // This prevents GPS drift at standstill without blocking real position updates
    if ($speed < 1) {
        $stmt = $pdo->prepare("SELECT latitude, longitude FROM gps_positions WHERE device_id = ? ORDER BY device_time DESC LIMIT 1");
        $stmt->execute([$deviceId]);
        $last = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($last) {
            $dist = haversineM((float)$last['latitude'], (float)$last['longitude'], $lat, $lng);
            if ($dist < 50) {
                $lat = (float)$last['latitude'];
                $lng = (float)$last['longitude'];
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO gps_positions (device_id, latitude, longitude, speed, heading, altitude, satellites, gps_fixed, acc_on, battery_level, device_time, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$deviceId, $lat, $lng, $speed, $heading, $altitude, $satellites, $gpsFix ? 1 : 0, $accOn !== null ? ($accOn ? 1 : 0) : null, $battery, $deviceTime]);

    gps_log("[DB] Stored position for $deviceId");
}
