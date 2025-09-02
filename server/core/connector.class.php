<?php 
class Connector {
    var $db, $error, $query, $affected_rows=0;
    var $server, $user, $password, $dbase;
    public function __construct() {
        $jsonString = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/settings.json');
        $settings = json_decode($jsonString, true);
        $this->server = $settings['server'];
        $this->user = $settings['user'];
        $this->password = $settings['password'];
        $this->dbase = $settings['dbase'];
    }

	//подключаем
    public function sqlConnect() {
        $this->db = new mysqli();
        $this->db->connect($this->server, $this->user, $this->password, $this->dbase);

        if ($this->db->connect_errno > 0) {
            $this->error = $this->db->connect_error;
            return false;
        }
        $this->db->set_charset("utf8");
        return true;
    }
    //отключаем
    public function sqlClose($db = null) {
        if ($db) {
            $this->db = $db;
        }
        mysqli_close($this->db);
        $this->db = null;
    }
    //возвращаем объект mysqli
    /**
     * Возвращает объект mysqli
     * @param string $query Можно сразу передать текст запроса и он будет выполнен
     * @return object mysqli
     */
    public function sqlQuery($queryText = null) {
        if (!$this->db) {
            if (!$this->sqlConnect()) {
                $this->error= mysqli_connect_error();
                return FALSE;
            }
        }
        if ($queryText) {
            $this->query=$this->db->query($queryText);
            if ($this->db->errno > 0) {
                $this->error = $this->db->error;
                return FALSE;
            }
            $this->affected_rows=$this->db->affected_rows;
            return $this->query;
        }
        return $this->db;
    }

}
?>