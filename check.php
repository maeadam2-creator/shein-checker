<?php
// check.php - Final Stable Version
error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$baseNumber = $input['number'] ?? '';

// Advanced Identities for Rotation
$identities = [
    ['model' => 'Pixel 8 Pro', 'version' => '14'],
    ['model' => 'Pixel 7', 'version' => '13'],
    ['model' => 'Samsung S23', 'version' => '14']
];
$id = $identities[array_rand($identities)];

function httpCall($url, $data = null, $headers = []) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 20
    ]);
    if ($data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// Telegram Notification Function
function sendTelegram($msg) {
    $token = "8494661072:AAF7CFJGJ6IFy1HefiiC5WScfVZH65qJHGg";
    $chatId = "1814354392";
    $url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chatId&text=" . urlencode($msg);
    // Silent call - won't crash if it fails
    file_get_contents($url);
}

// 1. Get SHEIN Token
$headers = [
    "Client_type: Android/{$id['version']}",
    "X-Tenant-Id: SHEIN",
    "Content-Type: application/x-www-form-urlencoded"
];
$tokenRaw = httpCall("https://api.services.sheinindia.in/uaas/jwt/token/client", "grantType=client_credentials&clientName=trusted_client&clientSecret=secret", $headers);
$token = json_decode($tokenRaw, true)['access_token'] ?? null;

if (!$token) {
    echo json_encode(['error' => 'SHEIN API Blocked. Identity: ' . $id['model']]);
    exit;
}

// 2. Check Number
$headers[] = "Authorization: Bearer $token";
$checkRaw = httpCall("https://api.services.sheinindia.in/uaas/accountCheck", "mobileNumber=$baseNumber", $headers);
$result = json_decode($checkRaw, true);

$status = 'not_registered';
if (isset($result['encryptedId'])) {
    $status = 'registered';
    sendTelegram("✅ SHEIN Found!\n📞 Number: $baseNumber\n📱 Model: {$id['model']}");
}

echo json_encode([
    'number' => $baseNumber,
    'status' => $status,
    'identity' => $id['model'],
    'timestamp' => date('H:i:s')
]);
