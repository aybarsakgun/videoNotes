<?php
if(!defined('AJAX') && !defined('VAR4')) {
    die('Security');
}

function sessionStart() {
    $session_name = 'videoNotes';
    $secure = false;
    $httponly = true;
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly);
    session_name($session_name);
    session_start();
    session_regenerate_id();
}

function checkBruteForce($user_id, $DB_con) {
    $fiveMinutesAgo = date("Y-m-d H:i:s", strtotime(" -5 minutes"));
    if ($stmt = $DB_con->prepare("SELECT date FROM login_attempts WHERE userId=:userId AND status=:status AND verify=:verify AND date > :date"))
    {
        $stmt->execute(array(":userId"=>$user_id,":status"=>"0",":verify"=>"0",":date"=>$fiveMinutesAgo));
        if($stmt->rowCount() > 3) {
            return true;
        } else {
            return false;
        }
    }
}

function loginCheck($DB_con) {
    if (isset($_SESSION['user_id'],
                        $_SESSION['username'],
                        $_SESSION['login_string'])) {

        $user_id = $_SESSION['user_id'];
        $login_string = $_SESSION['login_string'];
        $username = $_SESSION['username'];
        $user_browser = $_SERVER['HTTP_USER_AGENT'];
        $ip_address = $_SERVER['REMOTE_ADDR'];

        if ($stmt = $DB_con->prepare("SELECT password
                                      FROM users 
                                      WHERE id=:userId LIMIT 1")) {
            $stmt->bindparam(":userId",$user_id);
            $stmt->execute();
            $userRow=$stmt->fetch(PDO::FETCH_ASSOC);

            if($stmt->rowCount() == 1) {
                $login_check = hash('sha512', $userRow['password'] . $user_browser.$ip_address);
                if (hash_equals($login_check, $login_string) ){
                    return $user_id;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function seo($url)
{
    $url = trim($url);
    $find = array('<b>', '</b>');
    $url = str_replace ($find, '', $url);
    $url = preg_replace('/<(\/{0,1})img(.*?)(\/{0,1})\>/', 'image', $url);
    $find = array(' ', '&amp;amp;amp;quot;', '&amp;amp;amp;amp;', '&amp;amp;amp;', '\r\n', '\n', '/', '\\', '+', '<', '>');
    $url = str_replace ($find, '-', $url);
    $find = array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ë', 'Ê');
    $url = str_replace ($find, 'e', $url);
    $find = array('í', 'ý', 'ì', 'î', 'ï', 'I', 'Ý', 'Í', 'Ì', 'Î', 'Ï','İ','ı');
    $url = str_replace ($find, 'i', $url);
    $find = array('ó', 'ö', 'Ö', 'ò', 'ô', 'Ó', 'Ò', 'Ô');
    $url = str_replace ($find, 'o', $url);
    $find = array('á', 'ä', 'â', 'à', 'â', 'Ä', 'Â', 'Á', 'À', 'Â');
    $url = str_replace ($find, 'a', $url);
    $find = array('ú', 'ü', 'Ü', 'ù', 'û', 'Ú', 'Ù', 'Û');
    $url = str_replace ($find, 'u', $url);
    $find = array('ç', 'Ç');
    $url = str_replace ($find, 'c', $url);
    $find = array('þ', 'Þ','ş','Ş');
    $url = str_replace ($find, 's', $url);
    $find = array('ð', 'Ð','ğ','Ğ');
    $url = str_replace ($find, 'g', $url);
    $find = array('/[^A-Za-z0-9\-<>]/', '/[\-]+/', '/<&#91;^>]*>/');
    $repl = array('', '-', '');
    $url = preg_replace ($find, $repl, $url);
    $url = str_replace ('--', '-', $url);
    $url = strtolower($url);
    return $url;
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function decodeChunk($data) {
    $data = explode(';base64,', $data);
    if (!is_array($data) || !isset($data[1])) {
        return false;
    }
    $data = base64_decode($data[1]);
    if (!$data) {
        return false;
    }
    return $data;
}

function escapeJavaScriptText($string)
{
    return str_replace("\n", '\n', str_replace('"', '\"', addcslashes(str_replace("\r", '', (string)$string), "\0..\37'\\")));
}
