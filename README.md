# Database


$pdo = Database::getInstance($config);

$table = 'desktop_list';

$results = $pdo->select($table, [], '*', ' LIMIT 2')->result();

$where = ['id = ?', [16] ];
// $user    = $pdo->select($table, $where)->one();
var_export($results); die;

$data = array (
    'title'   => 'Рабочий стол 8',
    'vidgets' => 'rrr',
    'note'    => 'rrrr',
    'name'    => 'rrr',
);

//$r = $pdo->insert($table, $data);
//var_dump($r);

$data = array (
    'name'    => 'ertyuuuuuu',
);

$where = ['id', 16];

$r = $pdo->update($table, $data, $where);
var_export($pdo);

$where = ['id = ?', [17] ];
//$r = $pdo->delete($table, $where);
//var_export($r);