<?php
define('AJAX', TRUE);

require_once 'class.user.php';

sessionStart();

if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
	exit("Security");
}

if (empty($_SESSION['videoNotesToken'])) {
    $_SESSION['videoNotesToken'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['videoNotesToken'];

$headers = apache_request_headers();

if (isset($headers['csrftoken']))
{
	if(!hash_equals($csrfToken, $headers['csrftoken']))
	{
		exit("Security");
	}
} 
else 
{
	exit("Security");
}

if(loginCheck($DB_con) == true)
{
    header('Location: /');
    exit();
}

$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

if(empty($username) || empty($password))
{
	echo 4;
	exit();
}

$_user->login($username,$password);
