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
            $_error = false,
            $_sql = '',
            $_params = [];

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
            print_r(['DB_CONNECT_ERROR' => $message]);
            die();
        }
    }

    public static function getInstance($config){

        if(!isset(self::$_instance)){
            self::$_instance = new Database($config);
        }
        return self::$_instance;
    }

//    public function get($type = false){
//        return $this->_results;
//    }

    public function query($sql, $params = []) {

        $this->_sql = $sql;
        $this->_params = $params;

        try {
            if($this->_query = $this->_pdo->prepare($sql)){
                if(count($params)){
                    $next = 1;
                    foreach($params as $param){
                        $this->_query->bindValue($next, $param);
                        $next++;
                    }
                }

                if($this->_query->execute()){
                    $this->_results = $this->_query->fetchAll(PDO::FETCH_ASSOC);
                    $this->_count   = $this->_query->rowCount();
                } else {
                    $this->_error = true;
                }
            }
        } catch(PDOException $e){
            $message = $e->getMessage();
            print_r([
                'DB_QUERY_ERROR' => $message,
                'SQL' => $this->_sql,
                'DATA' => $this->_params,
            ]);
            die();
        }

        return $this;
    }

    protected function action($action, $table, $where = [], $addOnCondition = ''){

        $values = [];
        $whereStr = '';

        if(count($where) === 2){
            $condition = $where[0];
            $values    = $where[1];
            $whereStr = " WHERE {$condition}";
        }

        $sql = "{$action} FROM {$table} {$whereStr} {$addOnCondition}";
        if(!$this->query($sql, $values)->error())
            return $this;
        return false;
    }

    public function select($table, $where = [], $fields = '*', $addOnCondition = ''){
        return $this->action("SELECT {$fields}", $table, $where, $addOnCondition);
    }

    public function delete($table, $where){
        return $this->action("DELETE", $table, $where);
    }

    public function insert($table, $data=array()){
        if(count($data)){
            $keys = $values = [];
            foreach($data as $key => $val){
                $values[] = "?";
                $keys[]   = "{$key}";
            }

            $sql = "INSERT INTO {$table} 
                           (" . implode(',', $keys) . ") 
                           VALUES(" . implode(',', $values) . ")";

            $data = array_values($data);
            if(!$this->query($sql, $data)->error())
                return true;
        }
        return false;
    }

    public function update($table, $fields, $where){
        if(count($where) === 2 && count($fields)){
            $colum = $where[0];
            $value = $where[1];
            $set   = '';
            foreach($fields as $field => $val){
                $set .= "{$field} = ?, ";
            }
            $set = rtrim($set, ', ');
            $sql = "UPDATE {$table} SET {$set} WHERE {$colum} = '{$value}'";
            if(!$this->query($sql, $fields)->error())
                return true;
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

    public function one(){
        return $this->first();
    }

}
