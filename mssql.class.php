<?php

class mssqlWrapper
{
//    private function _fetchQuery($queryText) {
//        $result = $this->_query($queryText);
//        return $result->fetch_assoc();
//    }

    protected $_mssql;
    protected $_tablePrefix = '';

    public $num_rows;

    public function __construct() {
        try {
            // Подключение к MSSQL  
            $this->_mssql = new PDO("dblib:host=perebros;dbname=perebros_sql", "perebros", "126wrkg");
        } catch(PDOException $e) {  
            echo $e->getMessage(); 
            exit;
        }

        return true;
    }

    public function __destruct() {
        unset($this->_mssql);
    }

    public function countRows($tableName, $condition) {
        $queryText = "SELECT COUNT(*) FROM ".$this->_tablePrefix.$tableName." WHERE ".$this->_getImplode(' AND ', $condition).";";

        $query = $this->_query($queryText);

        $result = $query->fetch();
        $this->num_rows = $result[0];

        return true;
    }

    /*
    * Get query on server
    */
    //private
    public function _query($queryText) {
        try {
            if (defined('DEBUG')) {
                echo $queryText."<br>";
            }

            $stmt = $this->_mssql->prepare($queryText);
            $stmt->execute();
        } catch (Exception $e) {
            echo "Ошибка выполнения запроса к базе данных: ".$e->getMessage().".";
            return false;
        }
        return $stmt;
    }

    private function _getImplode($separator, $array) {
        $arr = array();
        foreach ($array as $key => $value) {
            if (is_array($value) === false) {
                $arr[] = "".$key." = '".$value."'";
            } else {
                $arr[] = "".$key." ".$value[1]." '".$value[0]."'";
            }
        }
        return implode($separator, $arr);
    }

    public function getRecord($tableName, $condition, $values = "*") {
        $query = $this->selectQuery($tableName, $condition, $values = "*");

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getRecordId($tableName, $condition, $value = "id") {
        $result = $this->getRecord($tableName, $condition, $value);

        return $result[$value];
    }

    public function selectQuery($tableName, $condition, $values = "*") {
        if (is_array($values) === true) {
            $values = implode(', ', $values);
        }

        $this->countRows($tableName, $condition);

        $queryText = "SELECT ".$values." FROM ".$this->_tablePrefix.$tableName." WHERE ".$this->_getImplode(' AND ', $condition).";";

        $query = $this->_query($queryText);

        return $query;
    }

/*
    public function insertRecord($tableName, $values) {
        $queryText = "INSERT INTO ".$this->_tablePrefix.$tableName." SET ".$this->_getImplode(', ', $values).";";

        if ($this->_query($queryText) === true) {
            return $this->insert_id;
        }
        return false;
    }

    public function updateRecord($tableName, $values, $condition = null) {
        $queryText = "UPDATE ".$this->_tablePrefix.$tableName." SET ".$this->_getImplode(', ', $values);

        if (is_array($condition) === true) {
            $queryText .= " WHERE ".$this->_getImplode(' AND ', $condition);
        }

        $queryText .= ";";

        if ($this->_query($queryText) === true) {
            return $this->insert_id;
        }
        return false;
    }

    public function deleteRecord($tableName, $condition) {
        $queryText = "DELETE FROM ".$this->_tablePrefix.$tableName." WHERE ".$this->_getImplode(' AND ', $condition).";";
        return $this->_query($queryText);
    }
*/
}