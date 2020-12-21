<?php
if(!defined('AJAX') && !defined('VAR3')) {
    die('Security');
}

define('VAR4', TRUE);

$DB_host = $databaseSettings['host'];
$DB_user = $databaseSettings['user'];
$DB_pass = $databaseSettings['password'];
$DB_name = $databaseSettings['databaseName'];

try
{
    $DB_con = new PDO("mysql:host={$DB_host};dbname={$DB_name};charset=utf8",$DB_user,$DB_pass);
	$DB_con->exec("SET NAMES utf8mb4");
	$DB_con->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $DB_con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
    echo $e->getMessage();
}

date_default_timezone_set($app['timeZone']);

$_user = new USER($DB_con);
