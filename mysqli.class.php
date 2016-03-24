<?php

class mysqliWrapper extends mysqli
{
//    private function _fetchQuery($queryText) {
//        $result = $this->_query($queryText);
//        return $result->fetch_assoc();
//    }

    protected $_tablePrefix = '';

    public function __construct($tablePrefix = '') {
        global $CFG;

        $this->_tablePrefix = $tablePrefix;

//        mysqli_report(MYSQLI_REPORT_ALL); // MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT

        parent::__construct($CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->dbname);

        if ($this->connect_error) {
            $this->_error("connect: ".$this->connect_errno .' ('.$this->connect_error.')');
        }

        if ($this->set_charset("utf8") === false) {
            $this->_error("Ошибка при загрузке набора символов utf8: %s\n".$this->error);
        }
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

            $result = $this->query($queryText);
        } catch (Exception $e) {
            echo "Неудалось выполнить запрос к базе данных: ".$e->getMessage().".";
            return false;
        }
        return $result;
    }

    //private
    public function _getImplode($separator, $array) {
        $arr = array();
        foreach ($array as $key => $value) {
            if (is_array($value) === false) {
                $arr[] = "`".$key."` = '".$value."'";
            } else {
                $arr[] = "`".$key."` ".$value[1]." '".$value[0]."'";
            }
        }
        return implode($separator, $arr);
    }

    public function selectRecords($tableName, $condition, $values = "*") {
        if (is_array($values) === true) {
            $values = implode(', ', $values);
        }

        $queryText = "SELECT ".$values." FROM ".$this->_tablePrefix.$tableName." WHERE ".$this->_getImplode(' AND ', $condition).";";

        return $this->_query($queryText);
    }

    public function getRecord($tableName, $condition, $values = "*") {
        $query = $this->selectRecords($tableName, $condition, $values);

        return $query->fetch_assoc();
    }

    public function getRecordId($tableName, $condition, $rowName = "id") {
        $result = $this->getRecord($tableName, $condition, $rowName);

        if (is_array($result) === false) {
            return false;
        }

        return $result[$rowName];
    }

    public function getRecords($tableName, $condition, $values = "*") {
        $query = $this->selectRecords($tableName, $condition, $values);

        if ($query === false) {
            return $false;
        }

        return $query->fetch_all(MYSQLI_ASSOC);
    }
//    public function countRows()
//    


    public function multiQueryGetResult($query) {
        if ($this->multi_query($query)) {
            do {
                if ($result = $this->store_result()) {
                    while ($row = $result->fetch_row()) {
                        $res = $row[0];
                    }
                    $result->free();
                }

                if ($this->more_results()) {
                //            printf("-----------------\n");
                }
            } while ($this->next_result());
        } else {
            echo $query."<br>";
            return false;
        }

        return $res;
    }

    public function insertRecord($tableName, $values, $updateDuplicate = true) {
        $queryText = "INSERT INTO ".$this->_tablePrefix.$tableName." SET ".$this->_getImplode(', ', $values);

        if ($updateDuplicate === true) {
            $queryText .= " ON DUPLICATE KEY UPDATE ".$this->_getImplode(', ', $values);
        }

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
}