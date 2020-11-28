<?php

// header('Content-Type: text/html; charset=utf-8');
// $backtrace = debug_backtrace();
// namespace Api\Classes;

class DatabaseClass implements DatabaseInterface {

        protected  $tableName;
        protected  $action;
        protected  $primaryKey;
        protected  $query;
        protected  $driver;
        protected  $itemId;

        protected  $pdo;
        protected  $data = [];
        protected  $config = [];
        protected  $tableFields = [];

		public function __construct($config = []) {
            $this->config = $config;
            $this->connect();
		}

        //---- Выборка данных
        public function fetchData(string $query, array $param = [], bool $first = false) : array {

            $this->query = $query;
            $this->data  = $param;

            $resp = $this->make($query, $param);
            $status = $resp['status'];
            $stmt   = $resp['stmt'];
            $result = ($first) ? $stmt->fetch() : $stmt->fetchAll();
            $stmt = null;

            return $result;
        }

        // Сохранение данных
        public function save(array $data, string $tableName, string $action, array $where = []) : array {

            $this->tableName = $tableName;
            $this->action    = $action;
            $this->query     = false;
            $this->itemId    = false;
            $this->data      = [];
            $result = $response = $item = [];

            $state = $this->queryBuilder($data, $where);

            if($state) {
                $response = $this->make();
            } else {

                $errInfo = [
                    'title'  => 'Не удалось построить sql-зарос',
                    'desc'   => 'Проверьте правильность sql-запроса',
                    'line'   => __LINE__,
                    'file'   => __FILE__,
                    'method' => __METHOD__,
                ];
                $this->error($errInfo, $data);
            }

            $count = false;
            $status = $response['status'];
            $stmt   = $response['stmt'];

            switch ($action) {
                case 'insert':
                    if($this->driver == 'mysql')
                        $this->itemId = $this->pdo->lastInsertId();
                    break;

                case 'update':
                    $count = $stmt->rowCount();
                    break;

                // case 'delete': break;
            }

            if($this->itemId && $this->primaryKey && $this->tableName) {
                $sql = "SELECT * FROM {$this->tableName} WHERE {$this->primaryKey} = {$this->itemId}";
                $item = $this->fetchData($sql, [], true);
            }

            $stmt = null;

            return [
                'action' => $this->action,
                'id'     => $this->itemId,
                'status' => $status,
                'count'  => $count,
                'item'   => $item,
            ];
        }

        // Сырой запрос (без обработки)
        public function query(string $sql, string $action = 'select') : array {
		    $result = false;
            try {
                switch ($action) {
                    case 'select' :
                        $result = $this->pdo->query($sql)->fetchAll();
                        break;
                }
            } catch(\PDOException $exception){

                $errInfo = [
                    'title'  => 'Не удалось выполнить запрос',
                    'desc'   => 'Проверьте правильность sql-запроса',
                    'line'   => __LINE__,
                    'file'   => __FILE__,
                    'method' => __METHOD__,
                ];
                $this->error($errInfo, $exception);
            }

            return $result;
        }

        protected function make(string $query = '', array $data = []) {

		    if(!$query && empty($data)) {
                $query = $this->query;
                $data = $this->data;
            }

		    try {
                $stmt  = $this->pdo->prepare($query);
                $status = $stmt->execute($data);
                // dd($stmt->queryString);
            } catch(\PDOException $exception){

                $errInfo = [
                    'title'  => 'Не удалось выполнить запрос',
                    'desc'   => 'Проверьте правильность sql-запроса',
                    'line'   => __LINE__,
                    'file'   => __FILE__,
                    'method' => __METHOD__,
                ];
                $this->error($errInfo, $exception);
            }

            return [
                'status' => $status,
                'stmt'   => $stmt
            ];
        }

        protected function getConfig(string $fieldName) : string {
            $result = '';
            if($this->config[$fieldName])
                $result = $this->config[$fieldName];
            return $result;
        }

        protected function connect() {

            $host   = $this->getConfig('host');
            $user   = $this->getConfig('user');
            $dbName = $this->getConfig('dbname');
            $driver = $this->driver = $this->getConfig('driver');
            $port   = $this->getConfig('port');

            $password = $this->getConfig('password');
            if(!$password)
                $password = $this->getConfig('passwd');

            $dsn = "{$driver}:host={$host};dbname=$dbName;charset=utf8";

            $options = [
                \PDO::ATTR_EMULATE_PREPARES   => false,                  // turn off emulation mode for "real" prepared statements
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,       //make the default fetch be an associative array
            ];

            try {

                $this->pdo = new \PDO($dsn, $user, $password, $options);

            } catch(\PDOException $exception){
                $errInfo = [
                    'title'  => 'Не удалось подключиться к базе данных',
                    'desc'   => 'Проверьте параметры подключения',
                    'line'   => __LINE__,
                    'file'   => __FILE__,
                    'method' => __METHOD__,
                ];
                $this->error($errInfo, $exception);
            }

            return true;
        }

        //---- Обработка ошибок
        protected function error($errInfo = [], $error = []) {

            if(empty($errInfo) && empty($error))
                return true; // если нет ошибок выходим

            $err = ['info' => $errInfo];

            if($error instanceof \PDOException) {
                $message = $error->getMessage();
                $err['message'] = $message;
            } else {

            }

            $err['query'] = $this->query;
            $err['data']  = $this->data;
            $err['error'] = $error;

            $exception = print_r($err, true);

            $header = "<h3 style='color:red;'>Ошибка при работе с базой данных</h3>";
            print $header . "<pre>{$exception}</pre>";
            die();
        }

        protected function queryBuilder(array $data, $where = []) {

            $tableFields = $this->getTableFields($this->tableName);
            $primaryKey  = $this->primaryKey;
            $action      = $this->action;
            $state  = $itemId = false;

            if(isset($data[$primaryKey])) {
                $this->itemId = $itemId = $data[$primaryKey];
                $where = array_merge ([$primaryKey => $itemId], $where);
                unset($data[$primaryKey]);
            }

            switch ($action) {
                case 'insert':
                    $state = $this->insertBuilder($data);
                    break;

                case 'update':
                    $state = $this->updateBuilder($data, $where);
                    break;

                case 'delete':
                    $state = $this->deleteBuilder($where);
                    break;
            }

            return $state;
        }

        protected function insertBuilder(array $data) {

            $values = $fields = $prepare = [];
            $tableFields = $this->tableFields;
            $tableName   = $this->tableName;

            foreach ($tableFields as $fieldName => $fieldParam) {
                if(!isset($data[$fieldName]))
                    continue;

                $value = trim($data[$fieldName]);
                $fields[]  = $fieldName;
                $prepare[] = '? ';
                $values[]  = $value;
            }

            if(!empty($fields) && !empty($values)) {

                $fieldsLine = implode(',', $fields);
                $valuesLine = implode(',', $prepare);

                $this->query = "INSERT INTO {$tableName} ({$fieldsLine}) VALUES ({$valuesLine})";
                $this->data  = $values;
                return true;
            }

            return false;
        }

        protected function updateBuilder(array $data, $where = []) {

            $whereLine = '';
            $values = $fields = [];
            $tableFields = $this->tableFields;
            $tableName   = $this->tableName;
            // $primareKey  = $this->primaryKey;

            foreach ($tableFields as $fieldName => $fieldParam) {
                if(!isset($data[$fieldName]))
                    continue;
                $value = trim($data[$fieldName]);

                $fields[]  = $fieldName . " = ?";
                $values[]  = $value;
            }

            if(!empty($where)) {

                $whereAnd = [];
                foreach ($where as $fname => $value) {
                    $whereAnd[] = $fname . '= ?';
                    $values[]   = $value;
                }

                if(!empty($whereAnd)) {
                    $whereLine = implode(' AND ', $whereAnd);
                }
            }

            if(!empty($fields) && !empty($values)) {
                $fieldsLine = implode(', ', $fields);
                $this->query = "UPDATE {$tableName} SET {$fieldsLine} WHERE {$whereLine}";
                $this->data  = $values;
                return true;
            }

            return false;
        }

    protected function deleteBuilder($where = []) {

        $whereLine   = '';
        $tableFields = $this->tableFields;
        $tableName   = $this->tableName;

        if(!empty($where)) {

            $whereAnd = $values = [];

            foreach ($where as $fname => $value) {
                $whereAnd[] = $fname . '= ?';
                $values[]   = $value;
            }

            if(!empty($whereAnd)) {
                $whereLine = implode(' AND ', $whereAnd);
                $this->query = "DELETE FROM {$tableName} WHERE {$whereLine}";
                $this->data  = $values;
                return true;
            }
        }

        return false;
    }

        protected function getTableFields(string $tableName) : array {

            $driver = $this->driver;

            $query  = "SHOW COLUMNS FROM {$tableName}";
            $tableFields = $this->fetchData($query);

            $autoIdName = '';
            $result     = [];

            switch ($driver) {
                case 'mysql' :
                    foreach ($tableFields as $key => $row) {
                        $type  = $row['Type'];
                        $name  = $row['Field'];
                        $extra = $row['Extra'];
                        if($extra == 'auto_increment')
                            $autoIdName = $name;

                        $result[$name] = $row;
                    }
                    break;
            }

            $this->tableFields = $result;
            $this->primaryKey  = $autoIdName;

            return $result;
        }


//        //---- Удаление
//        public function delete($table, $field, $value) {
//            $query = "DELETE FROM {$table} WHERE {$field} = '{$value}'";
//            return $this->query($query);
//        }

}
