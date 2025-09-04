<?php 
class App {
    private $root;
	public $error = '';
    public function __construct() {
        $this->root = $_SERVER['DOCUMENT_ROOT'];
    }

    public function sendOrderToTelegram($cart_data, $name, $phone, $email) {
        $botToken = '7657389817:AAEjFrbfp0Z7Peh0JXEs7m3LUJPJOOvXXNE';
        $chatId = '8165809889';
        $cart =  json_decode($cart_data, true);
        $name = trim($name ?? '—');
        $phone = trim($phone ?? '—');
        $email = trim($email ?? '—');
        // Формируем текст
        $message = "📦 Новый заказ:\n";
        foreach ($cart as $item) {
            $message .= "🛒 {$item['name']} × {$item['amount']}\n";
        }
        $message .= "\n👤 Покупатель:\n";
        $message .= "Имя: $name\n";
        $message .= "Телефон: $phone\n";
        $message .= "Email: $email\n";
        // Кодируем и отправляем
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage?" .
            http_build_query([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ]);

        file_get_contents($url); // пуш ушёл!
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

    public function getData($jsonData) {
        $data = json_decode($jsonData, true);
        $type_id = (int)($data['category'] ?? 0);

        require_once $this->root . '/server/core/_dataSource.class.php';
        $query = 'SELECT 
        li.id AS id, 
        li.timestamp as timestamp,
        li.name AS name, 
        li.vendor_code AS vendor_code,
        li.type_id AS type, 
        li.unit_id AS unit_id, 
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
            $query .= ' WHERE li.type_id = ' . $type_id;
        }
        
        $dataSource = new DataSource($query);
        $responseData = $dataSource->getData();

        if ($responseData === false) {
            $this->error = $dataSource->error;
            return false;
        }

        return $responseData; // Всегда возвращаем массив, даже если он пустой
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
}
?>
