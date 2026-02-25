<?php
// Debug version - no SHEIN call, just testing connection
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$number = $input['number'] ?? 'No Number';

echo json_encode([
    'number' => $number,
    'status' => 'registered',
    'debug' => 'Server is working perfectly!'
]);
