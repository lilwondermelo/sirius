<?php 
class App {
    private $root;
	public $error = '';
    public function __construct() {
        $this->root = $_SERVER['DOCUMENT_ROOT'];
    }


    public function getUnits() {
        require_once $this->root . '/server/core/_dataSource.class.php';
        $query = 'SELECT id, name FROM list_units ORDER BY id';
        $dataSource = new DataSource($query);
        if (!$responseData = $dataSource->getData()) {
            $this->error = $dataSource->error;
            return false;
        }
        return $responseData;
    }

    public function getActiveTypes() {
        require_once $this->root . '/server/core/_dataSource.class.php';
        $query = 'SELECT DISTINCT lt.id, lt.name, lt.parent_id 
                  FROM list_types lt
                  INNER JOIN list_items li ON lt.id = li.type_id
                  ORDER BY lt.id';
        $dataSource = new DataSource($query);
        if (!$responseData = $dataSource->getData()) {
            $this->error = $dataSource->error;
            return false;
        }
        return $responseData;
    }

    public function getTypes() {
        require_once $this->root . '/server/core/_dataSource.class.php';
        $query = 'SELECT id, name, parent_id FROM list_types ORDER BY id';
        $dataSource = new DataSource($query);
        if (!$responseData = $dataSource->getData()) {
            $this->error = $dataSource->error;
            return false;
        }
        return $responseData;
    }

    public function typeImageUpload($file, $typeId) {
        $dir = $this->root . '/media/images/menu/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    
        // Удаляем старые версии
        $mask = rtrim($dir, '/') . '/' . $typeId . '.*';
        $oldFiles = glob($mask);
        foreach ($oldFiles as $oldFile) {
            @unlink($oldFile);
        }
    
        $tmpPath = $file['tmp_name'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $typeId . '.' . $ext;
        $targetPath = rtrim($dir, '/') . '/' . $filename;
        
        if (move_uploaded_file($tmpPath, $targetPath)) {
            return true;
        }
        else {
            $this->error = "Не удалось сохранить файл";
            return false;
        }
    }

    public function editType($data) {
        $data = json_decode($data, true);
        $typeId = $data['id'];

        require_once $this->root . '/server/core/_dataRowUpdater.class.php';
        $updater = new DataRowUpdater('list_types');

        if ($typeId == 0) {
            // Create new type
            unset($data['id']);
            $newTypeId = $updater->insert($data);
            if (!$newTypeId) {
                $this->error = $updater->error;
                return false;
            }
            $typeId = $newTypeId;
        } else {
            // Update existing type
            $updater->setKey('id', $typeId);
            unset($data['id']);
            $updater->setDataFields($data);
            if (!$updater->update()) {
                $this->error = $updater->error;
                return false;
            }
        }

        // Handle file upload
        if (isset($_FILES['image'])) {
            $this->typeImageUpload($_FILES['image'], $typeId);
        }

        return $typeId;
    }

    private function _getAllChildCategories($categoryId, $db_query) {
        $children = [];
        $query = "SELECT id FROM list_types WHERE parent_id = " . $categoryId;
        $result = $db_query->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $childId = $row['id'];
                $children[] = $childId;
                $children = array_merge($children, $this->_getAllChildCategories($childId, $db_query));
            }
        }
        return $children;
    }

    public function getData($jsonData) {
        $data = json_decode($jsonData, true);
        $type_id = (int)($data['category'] ?? 0);

        require_once $this->root . '/server/core/_dataSource.class.php';
        require_once $this->root . '/server/core/connector.class.php';

        $query = 'SELECT 
            li.id AS id, 
            li.timestamp as timestamp,
            li.name AS name, 
            li.vendor_code AS vendor_code,
            li.type_id AS type, 
            li.unit_id AS unit_id, 
            li.supplier_id AS supplier_id, 
            li.supplier_code AS supplier_code, 
            li.supplier_product_name AS supplier_product_name, 
            eip.id AS price_id, 
            eip.price AS price,
            lu.name as unit
        FROM list_items li
        LEFT JOIN (
            SELECT item_id, MAX(id) AS max_price_id
            FROM events_items_prices
            GROUP BY item_id
        ) AS latest_price ON li.id = latest_price.item_id
        LEFT JOIN events_items_prices eip ON eip.id = latest_price.max_price_id 
        LEFT JOIN list_units lu ON lu.id = li.unit_id';

        if ($type_id != 0) {
            $db = new Connector();
            if (!$db->sqlConnect()) {
                $this->error = $db->error;
                return false;
            }
            $db_query = $db->sqlQuery();
            
            $categoryIds = [$type_id];
            $childCategories = $this->_getAllChildCategories($type_id, $db_query);
            $categoryIds = array_merge($categoryIds, $childCategories);
            $db->sqlClose();
            
            $query .= ' WHERE li.type_id IN (' . implode(',', $categoryIds) . ')';
        }
        
        $dataSource = new DataSource($query);
        $responseData = $dataSource->getData();

        if ($responseData === false) {
            $this->error = $dataSource->error;
            return false;
        }

        // Bug fix: if response is null, return an empty array
        if ($responseData === null) {
            return [];
        }

        return $responseData;
    }


    public function fileUpload($file, $itemId) {
        $fname = $itemId . '.jpg';
        $dir = $this->root . '/media/images/items/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    
        // Удаляем старые версии
        $mask = rtrim($dir, '/') . '/' . $itemId . '_*.jpg';
        $oldFiles = glob($mask);
        foreach ($oldFiles as $oldFile) {
            @unlink($oldFile);
        }
    
        $tmpPath = $file['tmp_name'];
        $timestamp = time();
        $filename = $itemId . '_' . $timestamp . '.jpg';
        $targetPath = rtrim($dir, '/') . '/' . $filename;
        
        $mime = mime_content_type($tmpPath);
        
        if ($mime === 'image/webp') {
            // Конвертируем WebP в JPG
            $image = imagecreatefromwebp($tmpPath);
            if (!$image) {
                $this->error = "Ошибка чтения WebP";
                return false;
            }
        
            imagejpeg($image, $targetPath, 90); // Сохраняем JPG
            imagedestroy($image);
        
            return $timestamp;
        
        } else {
            // Просто перемещаем оригинал
            if (move_uploaded_file($tmpPath, $targetPath)) {
                return $timestamp;
            }
            else {
                $this->error = "Не удалось сохранить файл";
                return false;
            }
        }
        
    }
    

    public function editProducts($data) {
        $data = json_decode($data, true);
        $itemId = $data['id'];
        $price = $data['price'];
        unset($data['price']);

        require_once $this->root . '/server/core/_dataRowUpdater.class.php';
        $updater = new DataRowUpdater('list_items');

        if ($itemId == 0) {
            // Create new item
            unset($data['id']);
            $newItemId = $updater->insert($data);
            if (!$newItemId) {
                $this->error = $updater->error;
                return false;
            }
            $itemId = $newItemId;
        } else {
            // Update existing item
            $updater->setKey('id', $itemId);
            unset($data['id']);
            $updater->setDataFields($data);
            if (!$updater->update()) {
                $this->error = $updater->error;
                return false;
            }
        }

        // Handle file upload
        if (isset($_FILES['image'])) {
            $timestamp = $this->fileUpload($_FILES['image'], $itemId);
            if ($timestamp) {
                $timestampUpdater = new DataRowUpdater('list_items');
                $timestampUpdater->setKey('id', $itemId);
                $timestampUpdater->setDataFields(['timestamp' => $timestamp]);
                if (!$timestampUpdater->update()) {
                    $this->error = $timestampUpdater->error;
                    return false;
                }
            }
        }

        // Update price in events_items_prices
        $priceUpdater = new DataRowUpdater('events_items_prices');
        $priceData = [
            'item_id' => $itemId,
            'price' => $price
        ];
        if (!$priceUpdater->insert($priceData)) {
            $this->error = $priceUpdater->error;
            return false;
        }

        return $itemId;
    }

    public function createSupplier($jsonData) {
        $data = json_decode($jsonData, true);

        // Basic validation
        if (!isset($data['name']) || !isset($data['inn'])) {
            $this->error = 'Отсутствуют обязательные поля: имя и ИНН.';
            return false;
        }

        require_once $this->root . '/server/core/_dataRowUpdater.class.php';
        $updater = new DataRowUpdater('suppliers');

        $newSupplierId = $updater->insert($data);

        if (!$newSupplierId) {
            $this->error = $updater->error;
            return false;
        }

        return $newSupplierId;
    }

    public function getSuppliers() {
        require_once $this->root . '/server/core/_dataSource.class.php';
        $query = 'SELECT id, name, inn, email FROM suppliers ORDER BY name';
        $dataSource = new DataSource($query);
        if (!$responseData = $dataSource->getData()) {
            $this->error = $dataSource->error;
            return false;
        }
        return $responseData;
    }

    public function getProductsList() {
        require_once $this->root . '/server/core/_dataSource.class.php';
        $query = 'SELECT id, name FROM list_items ORDER BY name';
        $dataSource = new DataSource($query);
        if (!$responseData = $dataSource->getData()) {
            $this->error = $dataSource->error;
            return false;
        }
        return $responseData;
    }

    public function createProductArrival($jsonData) {
        $data = json_decode($jsonData, true);

        if (empty($data['supplier_id']) || empty($data['items'])) {
            $this->error = 'Отсутствуют обязательные поля: поставщик или товары.';
            return false;
        }

        require_once $this->root . '/server/core/connector.class.php';
        $db = new Connector();

        if (!$db->sqlConnect()) {
            $this->error = $db->error;
            return false;
        }

        $mysqli = $db->sqlQuery();

        try {
            $mysqli->begin_transaction();

            // 1. Insert into product_arrivals
            $arrivalData = [
                'supplier_id' => $data['supplier_id'],
                'comment' => $data['comment'] ?? null
            ];
            
            $stmt = $mysqli->prepare("INSERT INTO product_arrivals (supplier_id, comment) VALUES (?, ?)");
            $stmt->bind_param("is", $arrivalData['supplier_id'], $arrivalData['comment']);
            $stmt->execute();
            $arrivalId = $mysqli->insert_id;

            // 2. Insert into product_arrival_items
            $itemStmt = $mysqli->prepare(
                "INSERT INTO product_arrival_items (arrival_id, product_id, quantity, purchase_price) 
                 VALUES (?, ?, ?, ?)"
            );

            foreach ($data['items'] as $item) {
                $itemStmt->bind_param("iidd", $arrivalId, $item['product_id'], $item['quantity'], $item['purchase_price']);
                $itemStmt->execute();
            }

            $mysqli->commit();
            return $arrivalId;

        } catch (Exception $e) {
            $mysqli->rollback();
            $this->error = 'Ошибка транзакции: ' . $e->getMessage();
            return false;
        } finally {
            $db->sqlClose();
        }
    }

    public function getRecentArrivals() {
        require_once $this->root . '/server/core/_dataSource.class.php';
        $query = 'SELECT 
                    pa.id, 
                    pa.arrival_date, 
                    pa.comment, 
                    s.name AS supplier_name,
                    COUNT(pai.id) AS item_count,
                    SUM(pai.quantity * pai.purchase_price) AS total_sum
                  FROM product_arrivals pa
                  LEFT JOIN suppliers s ON pa.supplier_id = s.id
                  LEFT JOIN product_arrival_items pai ON pa.id = pai.arrival_id
                  GROUP BY pa.id
                  ORDER BY pa.arrival_date DESC
                  LIMIT 20';
        $dataSource = new DataSource($query);
        if (!$responseData = $dataSource->getData()) {
            $this->error = $dataSource->error;
            return false;
        }
        return $responseData;
    }

    public function getArrivalDetails($jsonData) {
        $data = json_decode($jsonData, true);
        $arrivalId = $data['arrivalId'];

        require_once $this->root . '/server/core/_dataSource.class.php';

        // Get main arrival data
        $arrivalQuery = new DataSource(sprintf("SELECT * FROM product_arrivals WHERE id = %d", $arrivalId));
        $arrivalData = $arrivalQuery->getData();
        if (!$arrivalData) {
            $this->error = 'Поступление не найдено.';
            return false;
        }

        // Get arrival items
        $itemsQuery = new DataSource(sprintf("SELECT * FROM product_arrival_items WHERE arrival_id = %d", $arrivalId));
        $itemsData = $itemsQuery->getData();

        $result = $arrivalData[0];
        $result['items'] = $itemsData ? $itemsData : [];

        return $result;
    }

    public function updateProductArrival($jsonData) {
        $data = json_decode($jsonData, true);

        $arrivalId = $data['arrival_id'] ?? 0;

        if (empty($arrivalId) || empty($data['supplier_id']) || empty($data['items'])) {
            $this->error = 'Отсутствуют обязательные поля: ID поступления, поставщик или товары.';
            return false;
        }

        require_once $this->root . '/server/core/connector.class.php';
        $db = new Connector();

        if (!$db->sqlConnect()) {
            $this->error = $db->error;
            return false;
        }

        $mysqli = $db->sqlQuery();

        try {
            $mysqli->begin_transaction();

            // 1. Update product_arrivals table
            $stmt = $mysqli->prepare("UPDATE product_arrivals SET supplier_id = ?, comment = ? WHERE id = ?");
            $stmt->bind_param("isi", $data['supplier_id'], $data['comment'], $arrivalId);
            $stmt->execute();

            // 2. Delete old items
            $deleteStmt = $mysqli->prepare("DELETE FROM product_arrival_items WHERE arrival_id = ?");
            $deleteStmt->bind_param("i", $arrivalId);
            $deleteStmt->execute();

            // 3. Insert new items
            $itemStmt = $mysqli->prepare(
                "INSERT INTO product_arrival_items (arrival_id, product_id, quantity, purchase_price) 
                 VALUES (?, ?, ?, ?)"
            );
            foreach ($data['items'] as $item) {
                $itemStmt->bind_param("iidd", $arrivalId, $item['product_id'], $item['quantity'], $item['purchase_price']);
                $itemStmt->execute();
            }

            $mysqli->commit();
            return true;

        } catch (Exception $e) {
            $mysqli->rollback();
            $this->error = 'Ошибка транзакции при обновлении: ' . $e->getMessage();
            return false;
        } finally {
            $db->sqlClose();
        }
    }

    public function registerUser($login, $password) {
        if (empty($login) || empty($password)) {
            $this->error = 'Логин и пароль не могут быть пустыми.';
            return false;
        }

        if (strlen($password) < 4) {
            $this->error = 'Пароль должен быть не менее 4 символов.';
            return false;
        }

        require_once $this->root . '/server/core/connector.class.php';
        $db = new Connector();

        if (!$db->sqlConnect()) {
            $this->error = 'Ошибка подключения к БД: ' . $db->error;
            return false;
        }

        $mysqli = $db->sqlQuery();

        try {
            // Check if user exists
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE login = ?");
            $stmt->bind_param("s", $login);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $this->error = 'Пользователь с таким логином уже существует.';
                return false;
            }

            // Insert new user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO users (login, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $login, $password_hash);
            
            if ($stmt->execute()) {
                return true;
            } else {
                $this->error = 'Не удалось создать пользователя.';
                return false;
            }

        } catch (Exception $e) {
            $this->error = 'Ошибка запроса: ' . $e->getMessage();
            return false;
        } finally {
            $db->sqlClose();
        }
    }

    public function login($login, $password) {
        require_once $this->root . '/server/core/connector.class.php';
        $db = new Connector();

        if (!$db->sqlConnect()) {
            $this->error = 'Ошибка подключения к БД: ' . $db->error;
            return false;
        }

        $mysqli = $db->sqlQuery();
        $user = null;

        try {
            $stmt = $mysqli->prepare("SELECT * FROM users WHERE login = ?");
            $stmt->bind_param("s", $login);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $this->error = 'Пользователь не найден';
                return false;
            }
            
            $user = $result->fetch_assoc();

        } catch (Exception $e) {
            $this->error = 'Ошибка запроса: ' . $e->getMessage();
            return false;
        } finally {
            $db->sqlClose();
        }

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_login'] = $user['login'];
            $_SESSION['user_role'] = $user['role'];
            return true;
        } else {
            $this->error = 'Неверный пароль';
            return false;
        }
    }

    public function logout() {
        session_destroy();
        return true;
    }

    public function checkAuth() {
        if (isset($_SESSION['user_id'])) {
            return [
                'loggedIn' => true,
                'login' => $_SESSION['user_login'],
                'role' => $_SESSION['user_role']
            ];
        } else {
            return ['loggedIn' => false];
        }
    }

    public function handleExternalApi($jsonData) {
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error = 'Invalid JSON format: ' . json_last_error_msg();
            return false;
        }

        $supplier_tin = $data['supplierTin'] ?? null;
        $skus = $data['skus'] ?? [];
        $names = $data['names'] ?? [];

        if (empty($supplier_tin) || !is_string($supplier_tin)) {
            $this->error = 'Missing or invalid supplierTin.';
            return false;
        }

        if (!is_array($skus)) {
            $skus = [];
        }
        if (!is_array($names)) {
            $names = [];
        }

        if (empty($skus) && empty($names)) {
            $this->error = 'Both skus and names arrays are empty.';
            return false;
        }

        require_once $this->root . '/server/core/connector.class.php';
        $db = new Connector();
        $mysqli = $db->sqlQuery();
        if (!$mysqli) {
            $this->error = 'Database connection failed: ' . $db->error;
            return false;
        }

        $supplier_id = null;
        $stmt_get_supplier = $mysqli->prepare("SELECT id FROM suppliers WHERE inn = ?");
        if (!$stmt_get_supplier) {
            $this->error = 'Failed to prepare supplier lookup query: ' . $mysqli->error;
            $db->sqlClose();
            return false;
        }
        $stmt_get_supplier->bind_param('s', $supplier_tin);
        $stmt_get_supplier->execute();
        $result_supplier = $stmt_get_supplier->get_result();
        if ($supplier_row = $result_supplier->fetch_assoc()) {
            $supplier_id = $supplier_row['id'];
        }
        $stmt_get_supplier->close();

        if (!$supplier_id) {
            $db->sqlClose();
            return [
                'supplierTin' => $supplier_tin,
                'matchedSkus' => [],
                'unmatchedSkus' => array_filter(array_merge($skus, $names))
            ];
        }

        $matched_skus = [];
        $unmatched_skus = [];
        $items_count = max(count($skus), count($names));

        $stmt_select_sku = $mysqli->prepare("SELECT vendor_code FROM list_items WHERE supplier_code = ? AND supplier_id = ?");
        if (!$stmt_select_sku) {
            $this->error = 'SKU query preparation failed: ' . $mysqli->error;
            $db->sqlClose();
            return false;
        }

        $stmt_select_name = $mysqli->prepare("SELECT vendor_code FROM list_items WHERE supplier_product_name = ? AND supplier_id = ?");
        if (!$stmt_select_name) {
            $this->error = 'Name query preparation failed: ' . $mysqli->error;
            $db->sqlClose();
            return false;
        }

        for ($i = 0; $i < $items_count; $i++) {
            $sku = trim($skus[$i] ?? '');
            $name = trim($names[$i] ?? '');
            $found = false;

            // 1. Try to match by SKU
            if (!empty($sku)) {
                $stmt_select_sku->bind_param('si', $sku, $supplier_id);
                $stmt_select_sku->execute();
                $result = $stmt_select_sku->get_result();
                if ($existing_item = $result->fetch_assoc()) {
                    $matched_skus[] = [
                        'supplier_sku' => $sku,
                        'vendor_code' => $existing_item['vendor_code']
                    ];
                    $found = true;
                }
            }

            // 2. If not found by SKU, try to match by name
            if (!$found && !empty($name)) {
                $stmt_select_name->bind_param('si', $name, $supplier_id);
                $stmt_select_name->execute();
                $result = $stmt_select_name->get_result();
                if ($existing_item = $result->fetch_assoc()) {
                    $matched_skus[] = [
                        'supplier_sku' => !empty($sku) ? $sku : $name, // use name as sku if sku is empty
                        'vendor_code' => $existing_item['vendor_code']
                    ];
                    $found = true;
                }
            }

            if (!$found) {
                $unmatched_skus[] = !empty($sku) ? $sku : $name;
            }
        }

        $stmt_select_sku->close();
        $stmt_select_name->close();
        $db->sqlClose();

        return [
            'supplierTin' => $supplier_tin,
            'matchedSkus' => $matched_skus,
            'unmatchedSkus' => $unmatched_skus
        ];
    }

    public function handleExternalApiProductSync($jsonData) {
        $data = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error = 'Invalid JSON format: ' . json_last_error_msg();
            return false;
        }

        $supplier_tin = $data['supplierTin'] ?? null;
        $products = $data['products'] ?? null;

        if (empty($supplier_tin)) {
            $this->error = 'Missing supplierTin.';
            return false;
        }

        if (!is_array($products)) {
            $this->error = 'Missing or invalid products array.';
            return false;
        }

        require_once $this->root . '/server/core/connector.class.php';
        $db = new Connector();
        $mysqli = $db->sqlQuery();
        if (!$mysqli) {
            $this->error = 'Database connection failed: ' . $db->error;
            return false;
        }

        // Get supplier_id from TIN
        $supplier_id = null;
        $stmt_get_supplier = $mysqli->prepare("SELECT id FROM suppliers WHERE inn = ?");
        if (!$stmt_get_supplier) {
            $this->error = 'Failed to prepare supplier lookup query: ' . $mysqli->error;
            $db->sqlClose();
            return false;
        }
        $stmt_get_supplier->bind_param('s', $supplier_tin);
        $stmt_get_supplier->execute();
        $result_supplier = $stmt_get_supplier->get_result();
        if ($supplier_row = $result_supplier->fetch_assoc()) {
            $supplier_id = $supplier_row['id'];
        }
        $stmt_get_supplier->close();

        if (!$supplier_id) {
            $this->error = 'Supplier with TIN ' . $supplier_tin . ' not found.';
            $db->sqlClose();
            return false;
        }

        $stats = ['created' => 0, 'existed' => 0, 'skipped' => 0, 'errors' => []];

        $stmt_check = $mysqli->prepare("SELECT id FROM list_items WHERE vendor_code = ?");
        $stmt_insert = $mysqli->prepare(
            "INSERT INTO list_items (name, vendor_code, supplier_id, supplier_code, supplier_product_name, type_id, unit_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt_check || !$stmt_insert) {
            $this->error = 'Failed to prepare statements: ' . $mysqli->error;
            $db->sqlClose();
            return false;
        }

        $default_type_id = 1; // Default category
        $default_unit_id = 1; // Default unit

        foreach ($products as $index => $product) {
            $vendor_code = trim($product['Наш Артикул'] ?? '');
            $supplier_sku = trim($product['Артикул'] ?? '');
            $supplier_name = trim($product['Наименование товара'] ?? '');

            if (empty($vendor_code)) {
                $stats['skipped']++;
                continue; 
            }

            $stmt_check->bind_param('s', $vendor_code);
            $stmt_check->execute();
            $result = $stmt_check->get_result();

            if ($result->num_rows > 0) {
                $stats['existed']++;
                continue;
            }

            $stmt_insert->bind_param(
                'ssissii', 
                $supplier_name, 
                $vendor_code, 
                $supplier_id, 
                $supplier_sku, 
                $supplier_name,
                $default_type_id,
                $default_unit_id
            );
            
            if ($stmt_insert->execute()) {
                $stats['created']++;
            } else {
                $stats['errors'][] = "Failed to insert product at index {$index} ('{$vendor_code}'): " . $stmt_insert->error;
            }
        }

        $stmt_check->close();
        $stmt_insert->close();
        $db->sqlClose();

        return $stats;
    }
}
?>
