<?php

require 'DatabaseInterface.php';
require 'DatabaseClass.php';

$config = [
    'host' => 'bolderp5.beget.tech',
    'user' => 'bolderp5_garden',
    'passwd' => '12QW34er',
    'password' => '12QW34er',
    'dbname' => 'bolderp5_garden',
    'driver' => 'mysql',
    'port' => false,
];

$db = new DatabaseClass($config);

$result = $param = $response = [];

$sql = "SELECT * FROM `basicrefbook` LIMIT 5 ";

$response = $db->fetchData($sql, $param, false);

dd($response);

$data = [
    'id' => 82,
    'name' => 'Статья о финансовой грамотrttttt',
    'page' => 'basicrefbook',
    'resourcetype' => 'article',
    'article' => 'Наши преподава6677тели трейдеры с многолетним стажем, rrrrtt
                       которые благодаря своему опыту и знаниям создали 
                       эффективный теоретический курс для обучения успешной торговли vbbbbbb',
    'unique_material_status' => 'null',
];

$table = 'basicrefbook';

lg($response);

function lg()
{

    $out = '';
    $get = false;
    $style = 'margin:10px; padding:10px; border:3px red solid;';
    $args = func_get_args();
    foreach ($args as $key => $value) {
        $itemArr = array();
        $itemStr = '';
        is_array($value) ? $itemArr = $value : $itemStr = $value;
        if ($itemStr == 'get') {
            $get = true;
        }

        $line = print_r($value, true);
        $out .= '<div style="' . $style . '" ><pre>' . $line . '</pre></div>';
    }

    $debugTrace = debug_backtrace();
    $line = print_r($debugTrace, true);
    $out .= '<div style="' . $style . '" ><pre>' . $line . '</pre></div>';

    if ($get)
        return $out;
    print $out;
    exit;
}
