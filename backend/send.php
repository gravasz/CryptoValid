<?php
/**
 * CryptoValid – Email küldő endpoint
 *
 * A frontend (index.html) ide POST-olja a form adatokat JSON-ben.
 * Válasz: JSON { ok: true } vagy { error: "..." } + megfelelő HTTP status.
 *
 * Telepítés: lásd README.md
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
//  Config + CORS
// ---------------------------------------------------------------------------
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'A config.php nincs beállítva. Lásd README.md.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$config = require $configPath;

// CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = $config['allowed_origins'] ?? ['*'];
if (in_array('*', $allowed, true)) {
    header('Access-Control-Allow-Origin: *');
} elseif (in_array($origin, $allowed, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Preflight
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Csak POST kérés engedélyezett.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------------------------------------------------------------------------
//  Input – JSON vagy form-data
// ---------------------------------------------------------------------------
$raw = file_get_contents('php://input');
$input = json_decode($raw ?: '[]', true);
if (!is_array($input) || empty($input)) {
    $input = $_POST;
}

function fld(array $a, string $k, int $max = 500): string {
    $v = $a[$k] ?? '';
    if (!is_string($v)) $v = '';
    $v = trim($v);
    if (mb_strlen($v) > $max) $v = mb_substr($v, 0, $max);
    return $v;
}

$name        = fld($input, 'name', 120);
$email       = fld($input, 'email', 200);
$flow        = fld($input, 'flow', 80);
$source      = fld($input, 'source', 200);
$dest        = fld($input, 'dest', 200);
$sourceLabel = fld($input, 'sourceLabel', 80);
$destLabel   = fld($input, 'destLabel', 80);
$sourceTrust = fld($input, 'sourceTrust', 200);
$destTrust   = fld($input, 'destTrust', 200);

// Honeypot (a frontend nem küld 'website' mezőt – ha mégis jön, bot)
if (!empty($input['website'])) {
    echo json_encode(['ok' => true]); // csendben elnyeljük
    exit;
}

// ---------------------------------------------------------------------------
//  Szerveroldali validáció
// ---------------------------------------------------------------------------
$errors = [];
if (mb_strlen($name) < 2) {
    $errors[] = 'Érvénytelen név.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Érvénytelen e-mail cím.';
}
if ($source === '' || $dest === '') {
    $errors[] = 'Hiányzó pénztárca / IBAN adat.';
}
$allowedFlows = ['Fiat to Crypto', 'Crypto to Fiat', 'Crypto to Crypto'];
if (!in_array($flow, $allowedFlows, true)) {
    $errors[] = 'Ismeretlen tranzakció típus.';
}
if ($errors) {
    http_response_code(400);
    echo json_encode(['error' => implode(' ', $errors)], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------------------------------------------------------------------------
//  Rate limit (file-alapú, IP-nként)
// ---------------------------------------------------------------------------
$rateLimit = (int)($config['rate_limit_seconds'] ?? 0);
if ($rateLimit > 0) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ip = explode(',', $ip)[0];
    $rlKey = preg_replace('/[^a-zA-Z0-9._-]/', '_', $ip);
    $rlFile = sys_get_temp_dir() . '/cryptovalid_rl_' . $rlKey;
    if (file_exists($rlFile) && (time() - (int)filemtime($rlFile)) < $rateLimit) {
        http_response_code(429);
        echo json_encode(['error' => 'Túl gyakori küldés. Próbáld kicsit később.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    @touch($rlFile);
}

// ---------------------------------------------------------------------------
//  PHPMailer betöltés
// ---------------------------------------------------------------------------
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
} else {
    // Manuális PHPMailer betöltés (ha composer nélkül telepíted)
    $base = __DIR__ . '/PHPMailer/src/';
    foreach (['Exception.php', 'PHPMailer.php', 'SMTP.php'] as $f) {
        if (!file_exists($base . $f)) {
            http_response_code(500);
            echo json_encode(['error' => 'PHPMailer nincs telepítve. Lásd README.md.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        require $base . $f;
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// ---------------------------------------------------------------------------
//  Email összeállítás + küldés
// ---------------------------------------------------------------------------
$mail = new PHPMailer(true);

try {
    // SMTP beállítások
    $mail->isSMTP();
    $mail->Host       = $config['smtp']['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp']['username'];
    $mail->Password   = $config['smtp']['password'];
    $mail->SMTPSecure = $config['smtp']['encryption'] === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int)$config['smtp']['port'];
    $mail->CharSet    = 'UTF-8';
    $mail->Encoding   = 'base64';
    $mail->setLanguage('hu', __DIR__ . '/PHPMailer/language/');

    if (!empty($config['debug'])) {
        $mail->SMTPDebug   = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = 'error_log';
    }

    // Feladó / címzett
    $mail->setFrom($config['smtp']['from_email'], $config['smtp']['from_name']);
    $mail->addAddress($config['recipient']['email'], $config['recipient']['name'] ?? '');
    $mail->addReplyTo($email, $name);

    // Tárgy + tartalom
    $mail->Subject = "CryptoValid érdeklődés — {$flow}";

    $textBody  = "Új érdeklődés érkezett a CryptoValid landing oldalról.\n\n";
    $textBody .= "Tranzakció típusa: {$flow}\n\n";
    $textBody .= "Név:    {$name}\n";
    $textBody .= "E-mail: {$email}\n\n";
    $textBody .= ($sourceLabel ?: 'Forrás') . ": {$source}\n";
    $textBody .= ($destLabel   ?: 'Cél')    . ": {$dest}\n";

    if ($sourceTrust || $destTrust) {
        $textBody .= "\nElőzetes megbízhatóság-ellenőrzés:\n";
        $textBody .= "  Forrás: " . ($sourceTrust ?: '—') . "\n";
        $textBody .= "  Cél:    " . ($destTrust   ?: '—') . "\n";
    }

    $textBody .= "\n— CryptoValid landing form\n";
    $textBody .= "IP: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '?') . "\n";
    $textBody .= "Időpont: " . date('Y-m-d H:i:s') . "\n";

    $mail->isHTML(false);
    $mail->Body = $textBody;

    $mail->send();

    // Sikeres küldés naplózás
    if (!empty($config['log_file'])) {
        @file_put_contents(
            $config['log_file'],
            sprintf("[%s] OK %s <%s> %s\n", date('c'), $name, $email, $flow),
            FILE_APPEND
        );
    }

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);

    if (!empty($config['log_file'])) {
        @file_put_contents(
            $config['log_file'],
            sprintf("[%s] ERR %s: %s\n", date('c'), $email, $mail->ErrorInfo),
            FILE_APPEND
        );
    }

    $msg = !empty($config['debug'])
        ? ('SMTP hiba: ' . $mail->ErrorInfo)
        : 'Az email küldés most nem sikerült. Kérlek próbáld újra később.';

    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
}
