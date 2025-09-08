<?php
// =================================================================
// Универсальный обработчик ошибок
// =================================================================
// Устанавливаем глобальный обработчик исключений, чтобы всегда возвращать JSON
set_exception_handler(function ($exception) {
    // Логируем ошибку
    error_log($exception->getMessage());

    // Устанавливаем HTTP-статус 500 (Internal Server Error)
    http_response_code(500);

    // Отправляем JSON с подробным описанием ошибки
    echo json_encode([
        'success' => false,
        'error' => 'SERVER_EXCEPTION',
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ]);
    exit;
});

// Перехватываем также и фатальные ошибки PHP
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        // Если заголовки еще не отправлены
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

// Устанавливаем заголовок ответа, чтобы клиент всегда ждал JSON
header('Content-Type: application/json');

// Подключаем коннектор к БД
require_once __DIR__ . '/core/connector.class.php';

// Определяем метод запроса
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            handlePostRequest();
            break;
        case 'GET':
            handleGetRequest();
            break;
        default:
            // Неподдерживаемый метод
            http_response_code(405); // Method Not Allowed
            echo json_encode(['success' => false, 'message' => 'Method not allowed. Only POST and GET are supported.']);
            break;
    }
} catch (Exception $e) {
    // Это дополнительный перехват на случай, если что-то пойдет не так до основного обработчика
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'UNCAUGHT_EXCEPTION',
        'message' => $e->getMessage()
    ]);
}

/**
 * Обрабатывает POST-запросы: принимает JSON с товарами, добавляет артикулы, сохраняет в БД.
 */
function handlePostRequest() {
    // 1. Получаем и декодируем JSON из тела запроса
    $json_data = file_get_contents('php://input');
    if (empty($json_data)) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'No data received.']);
        return;
    }

    $products = json_decode($json_data, true); // true для получения ассоциативного массива

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Invalid JSON format: ' . json_last_error_msg()]);
        return;
    }

    // 2. Подключаемся к БД
    $db = new Connector();
    $mysqli = $db->sqlQuery();
    if (!$mysqli) {
        throw new Exception('Database connection failed: ' . $db->error);
    }

    $mysqli->begin_transaction();

    $updated_products = [];
    $errors = [];

    // 3. Обрабатываем каждый товар
    foreach ($products as $product) {
        // Генерируем уникальный внутренний артикул
        $vendor_code = 'ART-' . uniqid();
        $product['vendor_code'] = $vendor_code;

        // Предполагаем, что в $product есть ключи, соответствующие полям таблицы list_items
        // Например: name, price, supplier_code и т.д.
        // Важно: нужно обеспечить безопасность данных перед вставкой!
        $name = $product['name'] ?? 'Без имени';
        $price = $product['price'] ?? 0.0;
        $supplier_code = $product['supplier_code'] ?? null;
        
        // Пример запроса на вставку. Адаптируйте под вашу структуру таблицы!
        $query = "INSERT INTO list_items (vendor_code, name, price, supplier_code) VALUES (?, ?, ?, ?)";
        
        $stmt = $mysqli->prepare($query);
        if (!$stmt) {
            $errors[] = "Query preparation failed for product: " . ($product['name'] ?? 'N/A') . ". Error: " . $mysqli->error;
            continue; // Пропускаем этот товар, но продолжаем с другими
        }

        $stmt->bind_param('ssds', $vendor_code, $name, $price, $supplier_code);
        
        if ($stmt->execute()) {
            // Товар успешно добавлен, добавляем его в массив для ответа
            $updated_products[] = $product;
        } else {
            // Ошибка при выполнении
            $errors[] = "Failed to insert product: " . ($product['name'] ?? 'N/A') . ". Error: " . $stmt->error;
        }
        $stmt->close();
    }

    // 4. Завершаем транзакцию
    if (empty($errors)) {
        $mysqli->commit();
        // Все успешно, отправляем обновленный список товаров
        echo json_encode(['success' => true, 'data' => $updated_products]);
    } else {
        $mysqli->rollback();
        http_response_code(500); // Internal Server Error
        // Отправляем отчет об ошибках
        echo json_encode(['success' => false, 'message' => 'Some products could not be processed.', 'errors' => $errors, 'processed_count' => count($updated_products)]);
    }

    $db->sqlClose();
}


/**
 * Обрабатывает GET-запросы: ищет товар по артикулу.
 */
function handleGetRequest() {
    if (!isset($_GET['article'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Article parameter is missing.']);
        return;
    }

    $article = $_GET['article'];
    $clean_article = str_replace(' ', '', $article);

    $db = new Connector();
    $mysqli = $db->sqlQuery();
    if (!$mysqli) {
        throw new Exception('Database connection failed: ' . $db->error);
    }

    $query = "SELECT * FROM list_items WHERE REPLACE(vendor_code, ' ', '') = ? OR REPLACE(supplier_code, ' ', '') = ?";
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        throw new Exception('Query preparation error: ' . $mysqli->error);
    }

    $stmt->bind_param('ss', $clean_article, $clean_article);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($product = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $product]);
    } else {
        // Не ошибка, просто ничего не найдено
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }

    $stmt->close();
    $db->sqlClose();
}

?>