<?php
/**
 * Debug TCP listener - captures raw data from GPS tracker
 */
$PORT = 5023;

$server = stream_socket_server("tcp://0.0.0.0:$PORT", $errno, $errstr);
if (!$server) die("Could not create server: $errstr ($errno)\n");

echo "Debug listener on port $PORT - waiting for connections...\n";

while (true) {
    $client = @stream_socket_accept($server, 30);
    if (!$client) continue;

    $addr = stream_socket_get_name($client, true);
    echo "\n=== CONNECTION from $addr at " . date('Y-m-d H:i:s') . " ===\n";

    stream_set_timeout($client, 10);

    // Read everything the client sends
    $allData = '';
    for ($i = 0; $i < 10; $i++) {
        $data = @fread($client, 4096);
        if ($data === false || $data === '') {
            if (feof($client)) {
                echo "Client closed connection\n";
                break;
            }
            echo "No data (attempt $i)...\n";
            usleep(500000);
            continue;
        }
        $allData .= $data;
        $hex = bin2hex($data);
        $len = strlen($data);
        echo "Received $len bytes: $hex\n";
        echo "ASCII: " . preg_replace('/[^\x20-\x7E]/', '.', $data) . "\n";

        // Try to detect protocol and respond
        if (strlen($data) >= 2) {
            // GT06 protocol
            if (ord($data[0]) === 0x78 && ord($data[1]) === 0x78) {
                echo ">> Detected GT06 protocol\n";
                $protocol = ord($data[3]);
                echo ">> Protocol number: 0x" . dechex($protocol) . "\n";

                // Send login response
                $pLen = strlen($data);
                $serialH = ord($data[$pLen - 6]);
                $serialL = ord($data[$pLen - 5]);

                $resp = chr(0x78).chr(0x78).chr(0x05).chr($protocol).chr($serialH).chr($serialL);
                // Simple CRC
                $crcData = chr(0x05).chr($protocol).chr($serialH).chr($serialL);
                $crc = 0xFFFF;
                for ($c = 0; $c < strlen($crcData); $c++) {
                    $crc ^= ord($crcData[$c]);
                    for ($j = 0; $j < 8; $j++) {
                        $crc = ($crc & 1) ? (($crc >> 1) ^ 0xA001) : ($crc >> 1);
                    }
                }
                $crc ^= 0xFFFF;
                $resp .= chr(($crc >> 8) & 0xFF).chr($crc & 0xFF);
                $resp .= chr(0x0D).chr(0x0A);

                fwrite($client, $resp);
                echo ">> Sent GT06 response: " . bin2hex($resp) . "\n";
            }
            // H02 protocol (text-based, starts with *)
            elseif ($data[0] === '*') {
                echo ">> Detected H02 protocol (text)\n";
            }
            // TKSTAR specific
            elseif (strpos($data, 'imei:') !== false || strpos($data, 'IMEI:') !== false) {
                echo ">> Detected TKSTAR/Xexun protocol\n";
                fwrite($client, "ON");
                echo ">> Sent: ON\n";
            }
        }
    }

    echo "Total received: " . strlen($allData) . " bytes\n";
    echo "Full hex: " . bin2hex($allData) . "\n";
    echo "=== END ===\n\n";

    fclose($client);
}
