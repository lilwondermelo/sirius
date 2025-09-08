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
 * Обрабатывает POST-запросы: принимает JSON от поставщика, сопоставляет SKU, создает новые товары.
 */
function handlePostRequest() {
    // 1. Получаем и декодируем JSON из тела запроса
    $json_data = file_get_contents('php://input');
    if (empty($json_data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No data received.']);
        return;
    }

    $data = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON format: ' . json_last_error_msg()]);
        return;
    }

    // 2. Валидация входящих данных по новой структуре
    $supplier_tin = $data['supplierTin'] ?? null;
    $skus = $data['skus'] ?? null;

    if (empty($supplier_tin) || !is_string($supplier_tin)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing or invalid supplierTin.']);
        return;
    }

    if (!is_array($skus)) { // Разрешаем пустой массив SKU
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing or invalid skus array.']);
        return;
    }

    // 3. Подключаемся к БД
    $db = new Connector();
    $mysqli = $db->sqlQuery();
    if (!$mysqli) {
        throw new Exception('Database connection failed: ' . $db->error);
    }

    $mysqli->begin_transaction();

    $processed_skus = [];
    $errors = [];

    // Готовим запросы заранее
    // !!! ВАЖНО: Адаптируйте `supplier_tin` под имя вашей колонки для ИНН поставщика
    $select_query = "SELECT vendor_code FROM list_items WHERE supplier_code = ? AND supplier_tin = ?";
    $stmt_select = $mysqli->prepare($select_query);

    // !!! ВАЖНО: Адаптируйте имена колонок под вашу структуру таблицы
    $insert_query = "INSERT INTO list_items (vendor_code, supplier_code, supplier_tin, name, supplier_product_name, type_id) VALUES (?, ?, ?, ?, ?, 0)";
    $stmt_insert = $mysqli->prepare($insert_query);

    if (!$stmt_select || !$stmt_insert) {
        throw new Exception('Query preparation failed: ' . $mysqli->error);
    }

    // 4. Обрабатываем каждый SKU
    foreach ($skus as $sku) {
        if (!is_string($sku) || empty(trim($sku))) {
            $errors[] = "Invalid SKU value found in list: not a string or empty.";
            continue;
        }
        $clean_sku = trim($sku);

        // a. Проверяем, существует ли уже такой SKU у этого поставщика
        $stmt_select->bind_param('ss', $clean_sku, $supplier_tin);
        $stmt_select->execute();
        $result = $stmt_select->get_result();

        if ($existing_item = $result->fetch_assoc()) {
            // SKU уже существует, просто добавляем его в результат
            $processed_skus[] = [
                'supplier_sku' => $clean_sku,
                'vendor_code' => $existing_item['vendor_code'],
                'status' => 'exists'
            ];
        } else {
            // b. SKU не найден, создаем новый
            $vendor_code = 'ART-' . uniqid();
            
            $stmt_insert->bind_param('sssss', $vendor_code, $clean_sku, $supplier_tin, $clean_sku, $clean_sku);
            
            if ($stmt_insert->execute()) {
                // Успешно создано
                $processed_skus[] = [
                    'supplier_sku' => $clean_sku,
                    'vendor_code' => $vendor_code,
                    'status' => 'created'
                ];
            } else {
                // Ошибка при вставке
                $errors[] = "Failed to insert SKU: {$clean_sku}. Error: " . $stmt_insert->error;
            }
        }
    }
    
    $stmt_select->close();
    $stmt_insert->close();

    // 5. Завершаем транзакцию
    if (empty($errors)) {
        $mysqli->commit();
        echo json_encode([
            'success' => true,
            'data' => [
                'supplierTin' => $supplier_tin,
                'processedSkus' => $processed_skus
            ]
        ]);
    } else {
        $mysqli->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Some SKUs could not be processed.', 
            'errors' => $errors
        ]);
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