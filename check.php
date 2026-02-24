<?php
// check.php - Super Stable Version
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$baseNumber = $input['number'] ?? '98451';

// Agar SHEIN block kare toh ye error message jayega
$errorResponse = json_encode([
    'number' => $baseNumber,
    'status' => 'API_BLOCKED',
    'error' => 'SHEIN blocked this request. Try again later.'
]);

function httpCall($url, $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER => false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if ($data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// 1. Get Token
$tokenRaw = httpCall("https://api.services.sheinindia.in/uaas/jwt/token/client", "grantType=client_credentials&clientName=trusted_client&clientSecret=secret");
$tokenData = json_decode($tokenRaw, true);
$token = $tokenData['access_token'] ?? null;

if (!$token) {
    // Agar API block hai toh crash hone ke bajaye ye bhejo
    die($errorResponse);
}

// 2. Check Number
$headers = ["Authorization: Bearer $token", "Content-Type: application/x-www-form-urlencoded"];
$ch = curl_init("https://api.services.sheinindia.in/uaas/accountCheck");
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, "mobileNumber=$baseNumber");
$res = curl_exec($ch);
curl_close($ch);

$result = json_decode($res, true);

if (!$result) {
    die($errorResponse);
}

echo json_encode([
    'number' => $baseNumber,
    'status' => isset($result['encryptedId']) ? 'registered' : 'not_registered'
]);
