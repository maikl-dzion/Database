<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Database;

$config = ['host' => '185.63.191.96',
    'database' => 'faximile',
    'user' => 'w1user',
    'password' => 'w1password',
    'driver' => 'pgsql',
    'port' => 5432,
];

$pdo = Database::getInstance($config);

$table = 'desktop_list';

$results = $pdo->select($table, [], '*', ' LIMIT 2')->result();

$where = ['id = ?', [16]];
var_export($results);

$data = array(
    'title' => 'Рабочий стол 8',
    'vidgets' => 'rrr',
    'note' => 'rrrr',
    'name' => 'rrr',
);

$data = array(
    'name' => 'ertyuuuuuu',
);
$where = ['id', 16];
$r = $pdo->update($table, $data, $where);
var_export($pdo);
$where = ['id = ?', [17]];
