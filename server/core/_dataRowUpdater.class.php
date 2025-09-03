<?php

class DataRowUpdater {

    private $query, $dataTable, $keyField, $keyValue, $dataFields;
    var $error;

    public function __construct($dataTable = null) {
        if ($dataTable) {
            $this->dataTable = $dataTable;
        }
    }

    /**
     * Устанавливет редактируемую таблицу
     * @param string $tableName
     */
    public function setDataTable(string $tableName = '') {
        $this->dataTable = $tableName;
    }

    /**
     * Устанавливет ключевое поле и значение, если передано $fieldVal
     * * @param string $fieldVal
     * @param string $fieldName
     */
    public function setKeyField(string $fieldName = '', $fieldVal = NULL) {
        $this->keyField = $fieldName;
        if ($fieldVal) {
            $this->keyValue = $fieldVal;
        }
    }

    /**
     * Устанавливает значение ключевого поля
     * @param mixed $keyValue
     */
    public function setKeyValue($keyValue) {
        $this->keyValue = $keyValue;
    }

    /**
     * Eстанавливет имя и значение ключевого поля
     * @param type $fieldName
     * @param type $keyValue
     */
    public function setKey($fieldName, $keyValue) {
        $this->setKeyField($fieldName);
        $this->setKeyValue($keyValue);
    }

    /**
     * Устанавливает наименования и значения полей для обновления
     * @param mixed $dataFields - может быть передан массив или json-строка
     */
    public function setDataFields(array $dataFields = []) {
        if (is_array($dataFields)) {
            $this->dataFields = $dataFields;
        } else if (is_string($dataFields)) {
            $this->dataFields = json_decode($dataFields);
        } else {
            $this->error = 'Wrong $dataFields value';
            return FALSE;
        }
    }

 /**
     * Выполняет обновление или вставку нового значения
     * @param mixed $dataFields  - может быть передан массив или json-строка или пусто, тогда преварительно должен быть вызван метод setDataFields
     * @return mixed возвращает true или значение вставленного/обновленного keyValue  в зависмости от удачности действия
     */

    public function insert($dataFields = NULL) {
        if ($dataFields) {
            $this->setDataFields($dataFields);
        }
        if (count($this->dataFields) == 0) {
            $this->error = 'Wrong update field list';
            return FALSE;
        }

        require_once 'connector.class.php';
        $mysql = new Connector();
        if (!$mysql->sqlConnect()) {
            $this->error = $mysql->error;
            return FALSE;
        }
        
        $query = $mysql->sqlQuery();
        $fields = [];
        $values = [];

        error_log("DataFields in insert: " . print_r($this->dataFields, true) . "\n", 3, dirname(__DIR__, 2) . '/logs/debug.log');
    
        foreach ($this->dataFields as $key => $value) {
            $fields[] = $key;
            if (is_string($value)) {
                $values[] = '"' . $query->real_escape_string($value) . '"';
            } else {
                $values[] = $value;
            }
        }
        $sql = 'INSERT INTO ' . $this->dataTable . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ')';


        //return $sqltext;
        $query->query($sql);
        if ($query->errno > 0) {
            $this->error = $query->error;
            $mysql->sqlClose();
            return FALSE;
        }
        $retval = $query->insert_id;
        $mysql->sqlClose();
        return $retval;
        /* if ($mysql->db->affected_rows > 0) {
          $mysql->sqlClose();
          return $retval;
          } else {
          $mysql->sqlClose();
          return FALSE;
          } */
    }



    /**
     * Выполняет обновление или вставку нового значения
     * @param mixed $dataFields  - может быть передан массив или json-строка или пусто, тогда преварительно должен быть вызван метод setDataFields
     * @return mixed возвращает true или значение вставленного/обновленного keyValue  в зависмости от удачности действия
     */
    public function update($dataFields = NULL) {
        if ($dataFields) {
            $this->setDataFields($dataFields);
        }
        if (count($this->dataFields) == 0) {
            $this->error = 'Wrong update field list';
            return FALSE;
        }

        require_once 'connector.class.php';
        $mysql = new Connector();
        if (!$mysql->sqlConnect()) {
            $this->error = $mysql->error;
            return FALSE;
        }
        $query = $mysql->sqlQuery();
        $ins_fields = '';
        $val_fields = '';
        $upd_fields = '';
        $vtypes = '';
        foreach ($this->dataFields as $key => $value) {
            switch (TRUE) {
                case is_string($value):
                    $value = '"' . $query->real_escape_string($value) . '"';
                    break;
                default:
                    break;
            }

            $ins_fields .= $key . ',';
            $upd_fields .= $key . '=' . $value . ',';

            $id_present = FALSE;
            if (strtolower($key) === strtolower($this->keyField)) {
                if ($this->keyValue) {
                    $val_fields .= (is_string($this->keyValue) ? '"' . $this->keyValue . '"' : $this->keyValue) . ',';
                } else {
                    $val_fields .= $value . ',';
                    $this->keyValue = $value;
                }
                $id_present = TRUE;
            } else {
                //$upd_fields .= $key . '=' . $value . ',';
                $val_fields .= $value . ',';
            }
        }
        if (!$id_present and ( $this->keyValue and $this->keyField)) {
            $ins_fields .= $this->keyField . ',';
            $val_fields .= (is_string($this->keyValue) ? '"' . $this->keyValue . '"' : $this->keyValue) . ',';
        }
        $ins_fields = substr($ins_fields, 0, strlen($ins_fields) - 1);
        $val_fields = substr($val_fields, 0, strlen($val_fields) - 1);
        $upd_fields = substr($upd_fields, 0, strlen($upd_fields) - 1);
        $sqltext = 'insert ignore into ' . $this->dataTable . ' (' . $ins_fields . ') values(' . $val_fields . ') on duplicate key update ' . $upd_fields;

        //return $sqltext;
        $query->query($sqltext);
        if ($query->errno > 0) {
            $this->error = $query->error;
            $mysql->sqlClose();
            return FALSE;
        }
        //возвращаем ID
        if ($query->insert_id > 0) {
            $retval = $query->insert_id;
        } else {
            $retval = true;
        }
        $mysql->sqlClose();
        return $retval;
        /* if ($mysql->db->affected_rows > 0) {
          $mysql->sqlClose();
          return $retval;
          } else {
          $mysql->sqlClose();
          return FALSE;
          } */
    }

    /**
     * Удаляет запись из таблицы
     * @param type $id
     * @return boolean
     */
    public function delete($id = NULL) {
        if ($id) {
            $this->keyValue = $id;
        }
        return $this->deleteRow();
    }

    /**
     * Метод позволяет удалить строку с указанным id
     * @param string $id
     * @return bool
     */
    public function deleteRow($dataTable = NULL, $keyField = NULL, $keyValue = NULL) {
        if ($dataTable) {
            $this->dataTable = $dataTable;
        }
        if ($keyField) {
            $this->keyField = $keyField;
        }
        if ($keyValue) {
            $this->keyValue = $keyValue;
        }

        require_once 'connector.class.php';
        $mysql = new Connector();
        if (!$mysql->sqlConnect()) {
            $this->error = $mysql->error;
            return FALSE;
        }


        $sqltext = "delete from " . $this->dataTable . " where " . $this->keyField . "="
                . (is_string($this->keyValue) ? "'" . $this->keyValue . "'" : $this->keyValue);
        $query = $mysql->sqlQuery();
        $query->query($sqltext);
        if ($query->errno > 0) {
            $this->error = $query->error;
            $mysql->sqlClose();
            return FALSE;
        }
        return TRUE;
    }



}
