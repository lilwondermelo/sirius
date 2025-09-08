<?php
// =================================================================
// Универсальный обработчик ошибок
// =================================================================
set_exception_handler(function ($exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'SERVER_EXCEPTION',
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ]);
    exit;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'error' => 'FATAL_ERROR',
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
        ]);
    }
});

// =================================================================
// Основная логика API
// =================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/app.class.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Only POST is supported.']);
    exit;
}

try {
    $json_data = file_get_contents('php://input');
    if (empty($json_data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No data received.']);
        exit;
    }

    $app = new App();
    $result = $app->handleExternalApi($json_data);

    if ($result === false) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => $app->error]);
    } else {
        echo json_encode(['success' => true, 'data' => $result]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'UNCAUGHT_EXCEPTION',
        'message' => $e->getMessage()
    ]);
}

?>