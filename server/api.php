<?php
header('Content-Type: application/json');
// Подключаем коннектор к БД
require_once __DIR__ . '/core/connector.class.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Существующая логика для POST-запросов ---
    $json_data = file_get_contents('php://input');
    
    $log_file = __DIR__ . '/../logs/arrivals.log';
    $log_entry = '[' . date('Y-m-d H:i:s') . '] ' . $json_data . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);

    echo json_encode(['success' => true]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // --- Новая логика для GET-запросов (поиск по артикулу) ---
    
    $log_file = __DIR__ . '/../logs/work.log';
    $log = function($message) use ($log_file) {
        file_put_contents($log_file, $message . PHP_EOL, FILE_APPEND);
    };

    if (isset($_GET['article'])) {
        $article = $_GET['article'];
        $log("Starting search for article: {$article}");

        // "Очищаем" артикул от пробелов для более точного поиска
        $clean_article = str_replace(' ', '', $article);

        $db = new Connector();
        $mysqli = $db->sqlQuery();

        if (!$mysqli) {
            $error_message = 'DB connection error: ' . $db->error;
            $log($error_message);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $error_message]);
            exit;
        }

        // Ищем и в нашем внутреннем артикуле (vendor_code) и в артикуле поставщика (supplier_code)
        // При сравнении также убираем пробелы из значений в базе данных
        $query = "SELECT * FROM list_items WHERE REPLACE(vendor_code, ' ', '') = ? OR REPLACE(supplier_code, ' ', '') = ?";
        
        $stmt = $mysqli->prepare($query);
        if (!$stmt) {
            $error_message = 'Query preparation error: ' . $mysqli->error;
            $log($error_message);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $error_message]);
            $db->sqlClose();
            exit;
        }

        $stmt->bind_param('ss', $clean_article, $clean_article);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($product = $result->fetch_assoc()) {
            $log("Product found: " . json_encode($product, JSON_UNESCAPED_UNICODE));
            echo json_encode(['success' => true, 'data' => $product]);
        } else {
            $log("No product found with article: {$article}");
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }

        $stmt->close();
        $db->sqlClose();
        $log("Search completed.");

    } else {
        // Если параметр article не передан
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Article parameter is missing.']);
    }

} else {
    // Для других методов (PUT, DELETE и т.д.)
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}
?>
llowed.']);
}
?>