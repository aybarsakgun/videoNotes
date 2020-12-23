<?php
if(!defined('AJAX') && !defined('VAR4')) {
    die('Security');
}

use PHPMailer\PHPMailer\PHPMailer;

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

function throwError($code, $message = '') {
    $response = [];
    switch ($code) {
        case 400:
            $response = [
                'success' => false,
                'message' => $message ? $message : 'Bad request',
                'code' => $code
            ];
            break;
        case 401:
            $response = [
                'success' => false,
                'message' => $message ? $message : 'Unauthorized',
                'code' => $code
            ];
            break;
        case 500:
            $response = [
                'success' => false,
                'message' => $message ? $message : 'Internal server error',
                'code' => $code
            ];
            break;
        case 200:
            $response = [
                'success' => true,
                'message' => $message ? $message : 'Success',
                'code' => $code
            ];
            break;
        default:
            $response = [
                'success' => false,
                'message' => $message ? $message : 'Unknown error',
                'code' => $code
            ];
            break;
    }
    return json_encode($response);
}

function sendMail($uMail,$uName,$Message,$Subject, $app)
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->SMTPDebug  = 0;
    $mail->SMTPAuth   = true;
    $mail->SMTPSecure = "ssl";
    $mail->Host       = "bree.guzelhosting.com";
    $mail->Port       = 465;
    $mail->addAddress($uMail, $uName);
    $mail->Username = "sbm@aybarsakgun.com";
    $mail->Password = "student123bm.";
    $mail->setFrom('sbm@aybarsakgun.com', $app['name']);
    $mail->addReplyTo("sbm@aybarsakgun.com", $app['name']);
    $mail->Subject = $Subject;
    $mail->CharSet = "UTF-8";
    $mail->msgHTML($Message);
    if(!$mail->send())
    {
        return false;
    }
    else
    {
        return true;
    }
}

function createEmailTemplate($title, $mainBody, $app) {
    return '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> <html> <head> <meta http-equiv="Content-Type" content="text/html; charset=utf-8" > <title>'.$title.' - '.$app['name'].'</title> <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700" rel="stylesheet"> <style type="text/css"> html{-webkit-text-size-adjust: none; -ms-text-size-adjust: none;}@media only screen and (min-device-width: 750px){.table750{width: 750px !important;}}@media only screen and (max-device-width: 750px), only screen and (max-width: 750px){table[class="table750"]{width: 100% !important;}.mob_b{width: 93% !important; max-width: 93% !important; min-width: 93% !important;}.mob_b1{width: 100% !important; max-width: 100% !important; min-width: 100% !important;}.mob_left{text-align: left !important;}.mob_soc{width: 50% !important; max-width: 50% !important; min-width: 50% !important;}.mob_menu{width: 50% !important; max-width: 50% !important; min-width: 50% !important; box-shadow: inset -1px -1px 0 0 rgba(255, 255, 255, 0.2);}.mob_center{text-align: center !important;}.top_pad{height: 15px !important; max-height: 15px !important; min-height: 15px !important;}.mob_pad{width: 15px !important; max-width: 15px !important; min-width: 15px !important;}.mob_div{display: block !important;}}@media only screen and (max-device-width: 550px), only screen and (max-width: 550px){.mod_div{display: block !important;}}.table750{width: 750px;}</style> </head> <body style="margin: 0; padding: 0;"> <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background: #f3f3f3; min-width: 350px; font-size: 1px; line-height: normal;"> <tr> <td align="center" valign="top"><!--[if (gte mso 9)|(IE)]> <table border="0" cellspacing="0" cellpadding="0"> <tr><td align="center" valign="top" width="750"><![endif]--> <table cellpadding="0" cellspacing="0" border="0" width="750" class="table750" style="width: 100%; max-width: 750px; min-width: 350px; background: #f3f3f3;"> <tr> <td class="mob_pad" width="25" style="width: 25px; max-width: 25px; min-width: 25px;">&nbsp;</td><td align="center" valign="top" style="background: #ffffff;"> <table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100% !important; min-width: 100%; max-width: 100%; background: #f3f3f3;"> <tr> <td align="right" valign="top"> <div class="top_pad" style="height: 25px; line-height: 25px; font-size: 23px;">&nbsp;</div></td></tr></table> <table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88% !important; min-width: 88%; max-width: 88%;"> <tr> <td align="left" valign="top"> <div style="height: 39px; line-height: 39px; font-size: 37px;">&nbsp;</div><div style="height: 73px; line-height: 73px; font-size: 71px;">&nbsp;</div></td></tr></table> '.$mainBody.' <table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100% !important; min-width: 100%; max-width: 100%; background: #f3f3f3;"> <tr> <td align="center" valign="top"> <table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88% !important; min-width: 88%; max-width: 88%;"> <tr> <td align="center" valign="top"> <div style="height: 34px; line-height: 34px; font-size: 32px;">&nbsp;</div><font face="Source Sans Pro, sans-serif" color="#868686" style="font-size: 17px; line-height: 20px;"> <span style="font-family: Source Sans Pro, Arial, Tahoma, Geneva, sans-serif; color: #868686; font-size: 17px; line-height: 20px;">Copyright &copy; '.$app['year'].' '.$app['name'].'</span> </font> <div style="height: 3px; line-height: 3px; font-size: 1px;">&nbsp;</div><font face="Source Sans Pro, sans-serif" color="#1a1a1a" style="font-size: 17px; line-height: 20px;"> <span style="font-family: Source Sans Pro, Arial, Tahoma, Geneva, sans-serif; color: #1a1a1a; font-size: 17px; line-height: 20px;"><a href="mailto:'.$app['mail'].'" style="font-family: Source Sans Pro, Arial, Tahoma, Geneva, sans-serif; color: #1a1a1a; font-size: 17px; line-height: 20px; text-decoration: none;">'.$app['mail'].'</a> &nbsp;&nbsp;|&nbsp;&nbsp; <a href="tel:'.$app['phone'].'" style="font-family: Source Sans Pro, Arial, Tahoma, Geneva, sans-serif; color: #1a1a1a; font-size: 17px; line-height: 20px; text-decoration: none;">'.$app['phone'].'</a></span> </font> <div style="height: 35px; line-height: 35px; font-size: 33px;">&nbsp;</div></td></tr></table> </td></tr></table> </td><td class="mob_pad" width="25" style="width: 25px; max-width: 25px; min-width: 25px;">&nbsp;</td></tr></table><!--[if (gte mso 9)|(IE)]> </td></tr></table><![endif]--> </td></tr></table> </body> </html>';
}