<?php
if(!defined('AJAX') && !defined('VAR1')) {
    die('Security');
}

define('VAR2', true);

$request_uri = $_SERVER['REQUEST_URI'];
$query_string = $_SERVER['QUERY_STRING'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

if (
	stripos($request_uri, 'eval(') || 
	stripos($request_uri, 'CONCAT') || 
	stripos($request_uri, 'UNION+SELECT') || 
	stripos($request_uri, '(null)') || 
	stripos($request_uri, 'base64_') || 
	stripos($request_uri, '/localhost') || 
	stripos($request_uri, '/pingserver') || 
	stripos($request_uri, '/config.') || 
	stripos($request_uri, '/wwwroot') || 
	stripos($request_uri, '/makefile') || 
	stripos($request_uri, 'crossdomain.') || 
	stripos($request_uri, 'proc/self/environ') || 
	stripos($request_uri, 'etc/passwd') || 
	stripos($request_uri, '/https/') || 
	stripos($request_uri, '/http/') || 
	stripos($request_uri, '/ftp/') || 
	stripos($request_uri, '/cgi/') || 
	stripos($request_uri, '.cgi') || 
	stripos($request_uri, '.exe') || 
	stripos($request_uri, '.sql') || 
	stripos($request_uri, '.ini') || 
	stripos($request_uri, '.dll') || 
	stripos($request_uri, '.asp') || 
	stripos($request_uri, '.jsp') || 
	stripos($request_uri, '/.bash') || 
	stripos($request_uri, '/.git') || 
	stripos($request_uri, '/.svn') || 
	stripos($request_uri, '/.tar') || 
	stripos($request_uri, ' ') || 
	stripos($request_uri, '<') || 
	stripos($request_uri, '>') || 
	stripos($request_uri, '/=') || 
	stripos($request_uri, '...') || 
	stripos($request_uri, '+++') || 
	stripos($request_uri, '://') || 
	stripos($request_uri, '/&&') || 
	stripos($query_string, '?') || 
	stripos($query_string, ':') || 
	stripos($query_string, '[') || 
	stripos($query_string, ']') || 
	stripos($query_string, '../') || 
	stripos($query_string, '127.0.0.1') || 
	stripos($query_string, 'loopback') || 
	stripos($query_string, '%0A') || 
	stripos($query_string, '%0D') || 
	stripos($query_string, '%22') || 
	stripos($query_string, '%27') || 
	stripos($query_string, '%3C') || 
	stripos($query_string, '%3E') || 
	stripos($query_string, '%00') || 
	stripos($query_string, '%2e%2e') || 
	stripos($query_string, 'union') || 
	stripos($query_string, 'input_file') || 
	stripos($query_string, 'execute') || 
	stripos($query_string, 'mosconfig') || 
	stripos($query_string, 'environ') || 
	stripos($query_string, 'path=.') || 
	stripos($query_string, 'mod=.') || 
	stripos($user_agent, 'binlar') || 
	stripos($user_agent, 'casper') || 
	stripos($user_agent, 'cmswor') || 
	stripos($user_agent, 'diavol') || 
	stripos($user_agent, 'dotbot') || 
	stripos($user_agent, 'finder') || 
	stripos($user_agent, 'flicky') || 
	stripos($user_agent, 'libwww') || 
	stripos($user_agent, 'nutch') || 
	stripos($user_agent, 'planet') || 
	stripos($user_agent, 'purebot') || 
	stripos($user_agent, 'pycurl') || 
	stripos($user_agent, 'skygrid') || 
	stripos($user_agent, 'sucker') || 
	stripos($user_agent, 'turnit') || 
	stripos($user_agent, 'vikspi') || 
	stripos($user_agent, 'zmeu')
) {
	@header('HTTP/1.1 403 Forbidden');
	@header('Status: 403 Forbidden');
	@header('Connection: Close');
	@exit;
}

require_once 'settings.php';
require_once 'database.php';
require_once 'functions.php';

class USER
{	
	private $db;

	function __construct($DB_con)
	{
		$this->db = $DB_con;
	}
	
	public function login($username, $password)
	{
		try
		{
			$stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
			$stmt->execute(array(":username" => $username));
			$userRow=$stmt->fetch(PDO::FETCH_ASSOC);
			if($stmt->rowCount() == 1)
			{
				$userId = $userRow['id'];
				if(checkBruteForce($userId, $this->db) == true)
				{
					echo 3;
					exit();
				}
				else
				{
					$db_pass = $userRow['password'];
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $user_browser = $_SERVER['HTTP_USER_AGENT'];
                    $now = date('Y-m-d H:i:s');
                    $positive = 1;
                    $negative = 0;
					if(password_verify($password, $db_pass))
					{
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['username'] = $userRow['username'];
                        $_SESSION['login_string'] = hash('sha512', $db_pass.$user_browser.$ip_address);
                        $stmt = $this->db->prepare("INSERT INTO login_attempts(userId, date, ip_address, browser, status, verify) VALUES (:userId, :date, :ip_address, :browser, :status, :verify)");
                        $stmt->bindparam(":userId",$userId);
                        $stmt->bindparam(":date",$now);
                        $stmt->bindparam(":ip_address",$ip_address);
                        $stmt->bindparam(":browser",$user_browser);
                        $stmt->bindparam(":status",$positive);
                        $stmt->bindparam(":verify",$positive);
                        $stmt->execute();
                        $fiveMinutesAgo = date("Y-m-d H:i:s", strtotime(" -5 minutes"));
                        $stmt = $this->db->prepare("UPDATE login_attempts SET verify=:verify WHERE userId=:userId AND status=:status AND date > :fiveMinutesAgo");
                        $stmt->bindparam(":userId",$userId);
                        $stmt->bindparam(":verify",$positive);
                        $stmt->bindparam(":status",$negative);
                        $stmt->bindparam(":fiveMinutesAgo",$fiveMinutesAgo);
                        $stmt->execute();
                        echo 1;
                        exit();
					}
					else
					{
						$stmt = $this->db->prepare("INSERT INTO login_attempts(userId, date, ip_address, browser, status, verify) VALUES (:userId, :date, :ip_address, :browser, :status, :verify)");
						$stmt->bindparam(":userId",$userId);
						$stmt->bindparam(":date",$now);
						$stmt->bindparam(":ip_address",$ip_address);
						$stmt->bindparam(":browser",$user_browser);
						$stmt->bindparam(":status",$negative);
						$stmt->bindparam(":verify",$negative);
						$stmt->execute();
						echo 2;
						exit;
					}	
				}
			}
			else
			{
				echo 2;
				exit;
			}		
		}
		catch(PDOException $ex)
		{
			echo $ex->getMessage();
		}
	}

	public function logout()
	{
		$_SESSION = array();
		$params = session_get_cookie_params();
		setcookie(session_name(),
				'', time() - 42000, 
				$params["path"], 
				$params["domain"], 
				$params["secure"], 
				$params["httponly"]);
		session_destroy();
		return true;
	}
}