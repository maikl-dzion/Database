# Database

use App\Database;

$config = [
    'host'     => 'localhost',
    'database' => 'faximile',
    'user'     => 'w1user',
    'password' => 'w1password',
    'driver'   => 'pgsql',
    'port'     => 5432,
];


$db = Database::getInstance($config);

$table = 'desktop_list';

///////////////////////////
//  получить список
$results = $db->select($table, [], '*', ' LIMIT 2')->result();

///////////////////////////
//  получить одну запись
$where = ['id = ?', [16] ];
$user  = $db->select($table, $where)->one();
// var_export($results); die;

///////////////////////////
//  добавить новую запись
$data = [
    'title'   => 'Рабочий стол 8',
    'vidgets' => 'rrr',
    'note'    => 'rrrr',
    'name'    => 'rrr',
];

$r = $db->insert($table, $data);
var_export($r);


///////////////////////////
//  изменить запись
$data = [
    'name'    => 'ertyuuuuuu',
];
$where = ['id', 16];
$r = $db->update($table, $data, $where);
var_export($db);


///////////////////////////
//  удалить запись
$where = ['id = ?', [17] ];
$r = $db->delete($table, $where);
var_export($r);


