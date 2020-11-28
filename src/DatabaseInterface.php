<?php


interface DatabaseInterface {

    public function fetchData(string $query, array $param = [], bool $first) : array ;
    public function save(array $data, string $tableName, string $action, array $where = []) : array;
    public function query(string $sql, string $action): array;

}