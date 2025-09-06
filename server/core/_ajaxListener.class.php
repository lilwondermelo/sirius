<?php
session_start();

require_once dirname(__DIR__) . '/app.class.php';
$app = new App();

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'login':
            $login = $_POST['login'] ?? '';
            $password = $_POST['password'] ?? '';
            if ($app->login($login, $password)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $app->error]);
            }
            break;

        case 'logout':
            $app->logout();
            echo json_encode(['success' => true]);
            break;

        case 'checkAuth':
            $authData = $app->checkAuth();
            echo json_encode($authData);
            break;

        case 'register':
            $login = $_POST['login'] ?? '';
            $password = $_POST['password'] ?? '';
            if ($app->registerUser($login, $password)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $app->error]);
            }
            break;
            
        default:
            // Fallback to original code if action is not recognized
            executeOriginalLogic();
            break;
    }
} else {
    executeOriginalLogic();
}

function executeOriginalLogic() {
    // All the original code from the file is placed here.
    $class_file = '';

    if (isset($_POST['classFile'])) {
        $class_file = trim(filter_input(INPUT_POST, 'classFile'));
    }
    if (!$class_file) {
        $class_file = trim(filter_input(INPUT_GET, 'classFile'));
    }

    $class = filter_input(INPUT_POST, 'class');
    $method = filter_input(INPUT_POST, 'method');


    if (!$class) {
        $class = filter_input(INPUT_GET, 'class');
    }
    if (!$method) {
        $method = filter_input(INPUT_GET, 'method');
    }

    if (strlen($class_file) == 0) {
        $class_file = '_' . $class . '.php';
        if (!file_exists($class_file)) {
            $class_file = $class . '.php';
        }
    } else {
        $class_file = '../' . $class_file . '.php';
    }
    if (!file_exists($class_file)) {
        die(json_encode(['result' => 'Error', 'descr' => 'PHP class does not exists: ' . $class_file, 'data' => '']));
    }


    if (!$class) {
        die(json_encode(['result' => 'Error', 'descr' => 'Wrong classname ' . $class . ' of classfile ' . $class_file, 'data' => '']));
    }
    if (!$method) {
        die(json_encode(['result' => 'Error', 'descr' => 'Wrong method name ' . $method, 'data' => '']));
    }
    $class = strtolower(substr($class, 0, 1)) . substr($class, 1, strlen($class) - 1);
    $class_name = strtoupper(substr($class, 0, 1)) . substr($class, 1, strlen($class) - 1);

    require_once '_dataSource.class.php';
    require_once '_dataRowUpdater.class.php';
    require_once $class_file;
    $obj = new $class;

    if (!method_exists($obj, $method)) {
        die(json_encode(['result' => 'Error', 'descr' => 'Wrong method ' . $method . ' of class: ' . $class, 'data' => '']));
    }

    $isprop = false;

    foreach ($_GET as $key => $value) {
        if (property_exists($obj, $key)) {
            $obj->$key = $value;
            //echo $value;
            $isprop = true;
        }
    }

    foreach ($_POST as $key => $value) {
        if (property_exists($obj, $key)) {
            $obj->$key = $value;
            $isprop = true;
            //echo $value;
        }
    }

    //Если переданы файла - принудительно передаем их в свойство $files класса. Если свойства нет - создадим
    if ($_FILES) {
        if (property_exists($obj, 'files')) {
            $obj->files = $_FILES;
        } else {
            $obj->{'files'} = $_FILES;
        }
    }

    //У класса нет нужных свойств, передаем переменные как пареметры метода
    //ВАЖНО!!! Параметры будут переданы в том порядке, что пришли в POST! Имена роли не играют
    //Если метод принимает больше параметров, чем пришло, то параметры метода сразу должны быть инициализированы со значениями в классе!
    if (!$isprop) {
        $params = [];
        foreach ($_GET as $key => $value) {
            if (!in_array($key, ['classFile', 'class', 'method'])) {
                $params[] = $value;
            }
        }
        foreach ($_POST as $key => $value) {
            if (!in_array($key, ['classFile', 'class', 'method'])) {
                $params[] = $value;
            }
        }

        $result = call_user_func_array(array($obj, $method), $params);
    } else {
        $result = $obj->$method();
    }

    if (!property_exists($obj, 'error')) {
        $obj->{'error'} = '';
    }

    switch (true) {
        case $result === TRUE:
            die(json_encode(['result' => 'Ok', 'descr' => $obj->error, 'data' => '']));
            break;
            case $result === FALSE:
            // --- Логирование ошибки в файл проекта ---
            $log_dir = dirname(__DIR__, 2) . '/logs';
            if (!is_dir($log_dir)) {
                mkdir($log_dir, 0755, true);
            }
            $log_file = $log_dir . '/error.log';
            $log_message = "[" . date("Y-m-d H:i:s") . "] AJAX Error in class '{$class_name}', method '{$method}'.";
            if (property_exists($obj, 'error') && !empty($obj->error)) {
                $error_details = is_array($obj->error) ? json_encode($obj->error, JSON_UNESCAPED_UNICODE) : $obj->error;
                $log_message .= " Details: " . $error_details . "\n";
            } else {
                $log_message .= " The method returned FALSE without a specific error message.\n";
            }
            error_log($log_message, 3, $log_file);
            // --- Конец логирования ---
            die(json_encode(['result' => 'Error', 'descr' => $obj->error, 'data' => '']));
            break;
        default:
            die(json_encode(['result' => 'Ok', 'descr' => '', 'data' => $result], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
            break;
    }
}

//or die(json_encode(['result' => 'Error', 'descr' => 'Cant require file: ' . $class_file, 'data' => '']));
