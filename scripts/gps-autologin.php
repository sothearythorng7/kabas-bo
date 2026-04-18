<?php
/**
 * TKSTAR Auto-Login with captcha solving
 */

$configFile = __DIR__ . '/gps-config.json';
$logFile = __DIR__ . '/gps-poller.log';

$IMEI = '9590074321';
$PASSWORD = '4321';
$USER_ID = 508291;
$DEVICE_ID = 2566655;
$MAX_ATTEMPTS = 15;

function log_msg($msg) {
    global $logFile;
    $line = date('Y-m-d H:i:s') . " [LOGIN] $msg\n";
    echo $line;
    file_put_contents($logFile, $line, FILE_APPEND);
}

function solveCaptcha($imageData) {
    $tmpGif = tempnam(sys_get_temp_dir(), 'cap_') . '.gif';
    $tmpClean = tempnam(sys_get_temp_dir(), 'cap_clean_') . '.png';
    file_put_contents($tmpGif, $imageData);

    $img = @imagecreatefromgif($tmpGif);
    if (!$img) {
        @unlink($tmpGif);
        return null;
    }

    $w = imagesx($img);
    $h = imagesy($img);

    // Convert palette to true color
    $tc = imagecreatetruecolor($w, $h);
    imagecopy($tc, $img, 0, 0, 0, 0, $w, $h);
    imagedestroy($img);

    // Scale up 5x for better OCR
    $scale = 5;
    $clean = imagecreatetruecolor($w * $scale, $h * $scale);
    $white = imagecolorallocate($clean, 255, 255, 255);
    $black = imagecolorallocate($clean, 0, 0, 0);
    imagefill($clean, 0, 0, $white);

    for ($x = 0; $x < $w; $x++) {
        for ($y = 0; $y < $h; $y++) {
            $rgb = imagecolorat($tc, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            // Check if pixel is "colorful" (not white, not light gray)
            // Background is white (255,255,255) or very light
            // Text is colored (red, blue, green, dark)
            $maxC = max($r, $g, $b);
            $minC = min($r, $g, $b);
            $saturation = ($maxC > 0) ? (($maxC - $minC) / $maxC) : 0;
            $brightness = $maxC;

            // Keep pixel if: it's dark OR it's saturated (colored)
            $isText = ($brightness < 200) || ($saturation > 0.3 && $brightness < 240);

            if ($isText) {
                imagefilledrectangle($clean,
                    $x * $scale, $y * $scale,
                    $x * $scale + $scale - 1, $y * $scale + $scale - 1,
                    $black);
            }
        }
    }

    imagedestroy($tc);
    imagepng($clean, $tmpClean);
    imagedestroy($clean);

    // Try tesseract with different PSM modes
    $tmpOut = tempnam(sys_get_temp_dir(), 'ocr_');
    $bestResult = '';

    foreach ([7, 8, 13] as $psm) {
        exec("tesseract $tmpClean $tmpOut -c tessedit_char_whitelist=0123456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ --psm $psm 2>/dev/null");
        $r = @file_get_contents($tmpOut . '.txt');
        $r = preg_replace('/\s+/', '', trim($r));
        if (strlen($r) >= 4 && strlen($r) <= 6) {
            $bestResult = $r;
            break;
        }
        if (strlen($r) > strlen($bestResult)) {
            $bestResult = $r;
        }
    }

    @unlink($tmpGif);
    @unlink($tmpClean);
    @unlink($tmpOut);
    @unlink($tmpOut . '.txt');

    return $bestResult;
}

function attemptLogin($imei, $password) {
    $cookieJar = tempnam(sys_get_temp_dir(), 'cookies_');

    // Step 1: Get login page
    $ch = curl_init('https://www.mytkstar.net/Login.aspx');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $loginPage = curl_exec($ch);
    curl_close($ch);

    if (!$loginPage) {
        log_msg("Failed to load login page");
        @unlink($cookieJar);
        return null;
    }

    preg_match('/name="__VIEWSTATE".*?value="([^"]*)"/', $loginPage, $m);
    $viewState = $m[1] ?? '';
    preg_match('/name="__VIEWSTATEGENERATOR".*?value="([^"]*)"/', $loginPage, $m);
    $viewStateGen = $m[1] ?? '';
    preg_match('/name="__EVENTVALIDATION".*?value="([^"]*)"/', $loginPage, $m);
    $eventValidation = $m[1] ?? '';

    // Step 2: Get captcha
    $ch = curl_init('https://www.mytkstar.net/VerCode.ashx?t=' . time());
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_TIMEOUT => 15,
    ]);
    $captchaImage = curl_exec($ch);
    curl_close($ch);

    if (!$captchaImage || strlen($captchaImage) < 100) {
        log_msg("Failed to get captcha");
        @unlink($cookieJar);
        return null;
    }

    // Step 3: Solve
    $captchaCode = solveCaptcha($captchaImage);
    if (!$captchaCode || strlen($captchaCode) < 4) {
        log_msg("OCR failed: '$captchaCode'");
        @unlink($cookieJar);
        return null;
    }

    $captchaCode = substr($captchaCode, 0, 4);
    log_msg("Trying code: '$captchaCode'");

    // Step 4: Login
    $postData = http_build_query([
        '__VIEWSTATE' => $viewState,
        '__VIEWSTATEGENERATOR' => $viewStateGen,
        '__EVENTVALIDATION' => $eventValidation,
        'txtImeiNo' => $imei,
        'txtImeiPassword' => $password,
        'txtVerCode' => $captchaCode,
        'btnLoginImei' => '',
        'hidGMT' => '7',
    ]);

    $ch = curl_init('https://www.mytkstar.net/Login.aspx');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    curl_exec($ch);
    curl_close($ch);

    $cookieContent = file_get_contents($cookieJar);
    @unlink($cookieJar);

    if (strpos($cookieContent, '.ASPXAUTH') === false) {
        log_msg("Login failed (wrong captcha)");
        return null;
    }

    $cookies = [];
    foreach (explode("\n", $cookieContent) as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;
        $parts = preg_split('/\s+/', $line);
        if (count($parts) >= 7) {
            $cookies[$parts[5]] = $parts[6];
        }
    }

    $cookieStr = '';
    foreach ($cookies as $name => $value) {
        $cookieStr .= "$name=$value; ";
    }
    return rtrim($cookieStr, '; ');
}

// Main
for ($attempt = 1; $attempt <= $MAX_ATTEMPTS; $attempt++) {
    log_msg("Attempt $attempt/$MAX_ATTEMPTS");

    $cookies = attemptLogin($IMEI, $PASSWORD);
    if ($cookies) {
        // Verify session
        $ch = curl_init('https://www.mytkstar.net/Ajax/DevicesAjax.asmx/GetDevicesByUserID');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'UserID' => $USER_ID,
                'isFirst' => false,
                'TimeZones' => '7:00',
                'DeviceID' => $DEVICE_ID,
                'IsKM' => 1,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Requested-With: XMLHttpRequest',
                'Cookie: ' . $cookies,
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $testResponse = curl_exec($ch);
        curl_close($ch);

        $testJson = json_decode($testResponse, true);
        if (isset($testJson['d']) && strpos($testJson['d'], 'latitude') !== false) {
            log_msg("SUCCESS!");
            $config = [
                'user_id' => $USER_ID,
                'device_id' => $DEVICE_ID,
                'cookies' => $cookies,
                'last_login' => date('Y-m-d H:i:s'),
            ];
            file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
            log_msg("Session saved");
            exit(0);
        }
    }

    if ($attempt < $MAX_ATTEMPTS) sleep(1);
}

log_msg("All attempts failed");
exit(1);
