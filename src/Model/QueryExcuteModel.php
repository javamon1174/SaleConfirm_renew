<?php
namespace SaleConfirm\Model;

use SaleConfirm;
use \PDO;

class QueryExcuteModel
{
    use SaleConfirm\Config;
    private $db_resouce;
    /**
    *** @param string DML
    *** @param string database query
    *** @return string data
    **/
    public function queryExcuteModel($func, $query = array(), $data = array())
    {
        $this->ConfigInit();
        $this->dbConnect();
        return $this->$func($query, $data);
    }
    public function __construct() { }
    private function dbConnect()
    {
        try {
            $dsn = "mysql:host=localhost;dbname=fwill";
            $options = array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$this->charset,
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                //Please add here mysql setting
            );
            $this->db_resouce = new PDO($dsn, 'fwill', '1111', $options);
            return true;
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }
    }
    private function selectQuery($query, $data)
    {
        try {
            $prepared_query = $this->db_resouce->prepare($query);
            if (!empty($data))
                $result = $prepared_query->execute($data);
            else
                $result = $prepared_query->execute();
            $this->pdoResouceRemove();
            return $prepared_query->fetchAll();
        } catch (PDOException $e) {
            $this->pdoResouceRemove();
            return $e;
        }
    }
    private function insertQuery($query, $data)
    {
        // var_dump($query);
        // echo "<hr />";
        // var_dump($data);
        // exit;
        try {
            $prepared_query = $this->db_resouce->prepare($query);
            $result = $prepared_query->execute($data) or die(print_r($db->errorInfo(), true));
            $this->pdoResouceRemove();
            return $result;
        } catch (PDOException $e) {
            $this->pdoResouceRemove();
            return false;
        }
    }
    private function pdoResouceRemove()
    {
        unset($this->db_resouce);
        return true;
    }
}
