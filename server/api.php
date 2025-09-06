<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json_data = file_get_contents('php://input');
    
    // Логирование в ../logs/arrivals.log
    $log_file = __DIR__ . '/../logs/arrivals.log';
    $log_entry = '[' . date('Y-m-d H:i:s') . '] ' . $json_data . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);

    // Просто подтверждаем получение
    echo json_encode(['success' => true]);

} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Only POST method is allowed.']);
}
?>