<?php

class DataSource {

    private $query, $keyField, $result = [], $findResult = [], $rowsCount = NULL, $fieldsCount = 0, $fieldsNames = [], $selPageNum = 1, $selRowCount = 0, $query_all, $query_src;
    var $error;

    public function __construct($queryText = null, $pageNum = null, $rowCount = null) {
        if ($pageNum) {
            $this->setPageNum($pageNum);
        }
        if ($rowCount) {
            $this->setRowCount($rowCount);
        }
        if ($queryText) {
            $this->setQuery($queryText);
        }
    }

    /**
     * Устанавливает ключевое поле
     * @param String $keyFieldName - имя ключевого поля
     */
    public function setKeyField($keyFieldName) {
        $this->keyField = $keyFieldName;
    }

    public function setPageNum($pageNum) {
        $this->selPageNum = $pageNum;
        $this->setQuery($this->query_src);
    }

    public function setRowCount($rowCount) {
        $this->selRowCount = $rowCount;
        $this->setQuery($this->query_src);
    }

    public function getPageNum() {
        return $this->selPageNum;
    }

    /**
     * Устанавливает текст запроса
     * @param String $queryText - текст запроса
     */
    public function setQuery($queryText) {
        $this->query_src = $queryText;
        $this->query_all = $queryText;
        $this->query_all = substr_replace($this->query_all, ', count(1) rowscount from ', strpos($this->query_all, 'from'), 4);
        if (!strpos(strtolower($queryText), 'limit') & ($this->selRowCount > 0)) {
            $limtext = ' limit ';
            if ($this->selPageNum > 1) {
                $offset = $this->selRowCount * $this->selPageNum - $this->selRowCount;
                $limtext .= ' ' . $offset . ', ';
            }
            $limtext .= $this->selRowCount;
            $queryText .= $limtext;
        }
//echo $queryText;
        $this->query = $queryText;
    }

    /**
     * Получает массив данных или null
     * @return array - массив данных, если все успешно 
     * @return NULL - если была ошибка, текст ошибки сохраняется в свойстве error
     */
    public function getData() {
        require_once 'connector.class.php';
        $db = new Connector();

        if (!$db->sqlConnect()) {
            $this->error = $db->error;
            return NULL;
        }

        $db_query = $db->sqlQuery();
        if (!$db_query) {
            $this->error = $db->error;
            $db->sqlClose();
            return NULL;
        }


        $this->queryRez = $db_query->query($this->query);
        if ($db_query->errno > 0 || !$this->queryRez) {
            $this->error = $db_query->error;
            $db->sqlClose();
            return NULL;
        }

        $this->result = $this->queryRez->fetch_all(MYSQLI_ASSOC); // Тут спотыкается
        if (!$this->result) {
            return NULL;
        }
        $this->rowsCount = $this->queryRez->num_rows;
        $this->totalRowsCount = $this->rowsCount;


        if ($this->rowsCount > 0) {
            $this->fieldsCount = count($this->result[0]);
            $step = 0;
            foreach ($this->result[0] as $key => $value) {
                $this->fieldsNames[$step] = $key;
                $step++;
            }
        }
        $db->sqlClose();

//узнаем общее число строк в запросе без пейджинга
        if ($this->selRowCount || $this->selRowCount > 0) {
            $this->getAllRowsCount();
        }
        return $this->result;
    }

    /**
     * Поиск по имеющемуся массиву данных по заданному полю заданное значение
     * @param string $fieldName
     * @param any $fieldValue
     * @return array - массив - поиск данных по значению поля (заполняет массив findResult из массива result или null)
     * @return null - если была ошибка или не найдено, текст ошибки сохраняется в свойстве error
     */
    public function find(string $fieldName, $fieldValue) {
        $key = array_search($fieldValue, array_column($this->result, $fieldName));
        if ($key === FALSE) {
            return NULL;
        }
        $this->findResult = $this->result[$key];
        return $this->findResult;
    }

    /**
     * возвращает массив findResult из массива result или null
     */
    public function getFindResult() {
        return $this->findResult;
    }

    /**
     * метод возвращает число строк массива $result 
     * @return int число строк выборки
     */
    public function getRowsCount() {
        return $this->rowsCount;
    }

    /**
     *  возвращает массив значений полей строки с указанным номером из массива данных result
     * @param int $rowNumber
     * @return NULL если не найдено 
     * @return array если найдено
     * 
     */
    public function getRowByNumber($rowNumber) {
//--$rowNumber; //хз надо это или нет
        if ($this->rowsCount < $rowNumber || !isset($this->result[$rowNumber])) {
            return NULL;
        }
        return $this->result[$rowNumber];
    }

    /**
     * озвращает массив значений полей строки с указанным значением ключевого поля из массива данных result с указанным номером или null

     * @param mixed $keyValue - значение ключевого поля
     * @return NULL если не найдено
     * @return array если  найдено
     */
    public function getRowByKey($keyValue) {
        for ($index = 0; $index < $this->rowsCount; $index++) {
            if ($this->result[$index][$this->keyField] == $keyValue) {
                return $this->result[$index];
            }
        }
        return NULL;
    }

    /**
     * метод возвращает название поля по номеру или null
     * @param integer $fieldNumber - передается номер поля
     * @return NULL - если не найдено
     * @return string - название поля
     */
    public function getFieldName($fieldNumber) {
        if ($fieldNumber > $this->fieldsCount) {
            return NULL;
        }
        $row = $this->result[0];
        $step = 0;
        foreach ($row as $key => $value) {
            if ($step === $fieldNumber) {
                return $key;
            }
            $step++;
        }
        return NULL;
    }

    /**
     * метод возвращает номер поля по bvtyb или null
     * @param integer $fieldNumber - передается номер поля
     * @return NULL - если не найдено
     * @return int - номер поля
     */
    public function getFieldNumber($fieldName) {
        $row = $this->result[0];
        $step = 0;
        foreach ($row as $key => $value) {
            if ($key === $fieldName) {
                return $step;
            }
            $step++;
        }
        return NULL;
    }

    /**
     * метод возвращает названия полей результата запроса
     * @return array
     */
    public function getFieldsNames() {
        return $this->fieldsNames;
    }

    /**
     * метод возвращает число полей массива $result 
     * @return int число полей выборки
     */
    public function getFieldsCount() {
        return $this->fieldsCount;
    }

    /**
     * Метод возвращает тип поля
     * @param string $fieldName
     * @return string
     */
    public function getFieldType($fieldName) {
        $field_data = $this->queryRez->fetch_field_direct($this->getFieldNumber($fieldName));
        $mysql_data_type_hash = array(
            1 => 'tinyint',
            2 => 'smallint',
            3 => 'int',
            4 => 'float',
            5 => 'double',
            7 => 'timestamp',
            8 => 'bigint',
            9 => 'mediumint',
            10 => 'date',
            11 => 'time',
            12 => 'datetime',
            13 => 'year',
            16 => 'bit',
            //252 is currently mapped to all text and blob types (MySQL 5.0.51a)
            253 => 'varchar',
            254 => 'char',
            246 => 'decimal',
            252 => 'text'
        );
//return $field_data->type;
        if (array_key_exists($field_data->type, $mysql_data_type_hash)) {
            return $mysql_data_type_hash[$field_data->type];
        } else {
            return NULL;
        }
    }

    /**
     * Метод возвращает длину поля из базы
     * @param string $fieldName
     * @return int 
     */
    public function getFieldLength($fieldName) {
        $field_data = $this->queryRez->fetch_field_direct($this->getFieldNumber($fieldName));
        if (in_array($this->getFieldType($fieldName), ['varchar', 'char'])) {
            return $field_data->length / 3;
        } else {
            return $field_data->length;
        }
    }

    /**
     * Метод возвращает значение поля по имени поля и номеру строки
     * @param string $fieldName
     * @param int $rowNumber
     * @return mixed
     */
    public function getValue($fieldName, $rowNumber) {
        /* if (array_key_exists($rowNumber, $this->result)) {
          if (array_key_exists($fieldName, $this->result[$rowNumber])) {
          return $this->result[$rowNumber][$fieldName];
          }
          } */
        if (isset($this->result[$rowNumber][$fieldName])) {
            return $this->result[$rowNumber][$fieldName];
        }
        $this->error = 'Not found field ' . $fieldName . ' in rownumber ' . $rowNumber;
        return NULL;
    }

    /**
     * Вычисляет полное количество строк запроса, которе бы вернулось без ограничения limit
     * @return type
     */
    private function getAllRowsCount() {
        require_once 'connector.class';
        $db = new Connector();
        if (!$db->sqlConnect()) {
            $this->error = $db->error;
            return false;
        }
        $db_query = $db->sqlQuery();
        if (!$db_query) {
            $this->error = $db->error;
            $db->sqlClose();
            return false;
        }

        $this->queryRez = $db_query->query($this->query_all);
        if ($db_query->errno > 0) {
            $this->error = $db_query->error;
            $db->sqlClose();
            return false;
        }
// echo ($this->error);
        $rez = $this->queryRez->fetch_array(MYSQLI_ASSOC);
        $this->rowsCount = (int) $rez['rowscount'];
        $db->sqlClose();
    }

}
