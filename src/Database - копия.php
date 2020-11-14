<?php

namespace App;

use PDO;
use PDOException;

class Database {

    private static $_instance = null;

    private $_pdo,
            $_count = 0,
            $_results,
            $_query,
            $_error = false;

    private function __construct($config){
        $this->connect($config);
    }

    protected function connect($config){

        $host     = $config['host'];
        $database = $config['database'];
        $user     = $config['user'];
        $password = $config['password'];
        $driver   = $config['driver'];
        $port     = $config['port'];

        try{

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->_pdo = new PDO("$driver:host={$host};dbname={$database}", $user, $password, $options);

        } catch(PDOException $e){
            $message = $e->getMessage();
            die('Error Connecting To Database Please Contact To Administrator: ' . $message );
        }
    }

    public static function getInstance($config){

        if(!isset(self::$_instance)){
            self::$_instance = new Database($config);
        }
        return self::$_instance;
    }

    public function query($sql, $params = array()){
        if($this->_query = $this->_pdo->prepare($sql)){
            if(count($params)){
                $x = 1;
                foreach($params as $param){
                    $this->_query->bindValue($x, $param);
                    $x++;
                }
            }
            if($this->_query->execute()){
                $this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
                $this->_count = $this->_query->rowCount();
            }else{
                $this->_error = true;
            }
        }
        return $this;
    }

    public function action($action, $table, $where=array()){
        if(count($where) === 3){
            $operators = array("=", ">", "<", ">=", "<=");
            $field    = $where[0];
            $operator = $where[1];
            $value    = $where[2];
            if(in_array($operator, $operators)){
                $sql = "{$action} FROM {$table} WHERE {$field} {$operator} ?";
                if(!$this->query($sql, array($value))->error()){
                    return $this;
                }
            }
        }
        return false;
    }

    public function get($table, $where){
        return $this->action("SELECT *", $table, $where);
    }

    public function delete($table, $where){
        return $this->action("DELETE", $table, $where);
    }

    public function insert($table, $data=array()){
        if(count($data)){
            $keys = array_keys($data);
            $values = "";
            foreach($data as $val){
                $values .= "?, ";
            }
            $values = rtrim($values, ", ");
            $sql = "INSERT INTO {$table} (`".implode('`, `', $keys)."`) VALUES ({$values})";
            $data = array_values($data);
            if(!$this->query($sql, $data)->error()){
                return true;
            }
        }
        return false;
    }

    public function update($table, $where, $fields){
        if(count($where) === 2 && count($fields)){
            $colum = $where[0];
            $value = $where[1];
            $set   = '';
            foreach($fields as $field=>$val){
                $set .= "{$field} = ?, ";
            }
            $set = rtrim($set, ', ');
            $sql = "UPDATE {$table} SET {$set} WHERE {$colum} = '{$value}'";
            if(!$this->query($sql, $fields)->error()){
                return true;
            }
        }
        return false;
    }

    public function createTable($name, $columns=array()){
        if(count($columns)){
            $itms = '';
            foreach($columns as $col=>$properties){
                $itms .= "{$col} {$properties}, ";
            }
            $itms = rtrim($itms, ", ");
            $sql = "CREATE TABLE {$name} ({$itms})";
            if($this->query($sql)){
                return true;
            }
        }
        return false;
    }

    public function error(){
        return $this->_error;
    }
    public function count(){
        return $this->_count;
    }
    public function result(){
        return $this->_results;
    }
    public function first(){
        return $this->result()[0];
    }

}
