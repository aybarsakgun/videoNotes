<?php
if(!defined('AJAX') && !defined('VAR2')) {
    die('Security');
}

define('VAR3', TRUE);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$app = [
    'url' => 'http://127.0.0.1:8080/VideoNotes/',
    'name' => 'VideoNotes',
    'mail' => 'example@example.com',
    'phone' => '5555555555',
    'year' => '2020',
    'timeZone' => 'America/New_York',
    'themeColor' => 'cyan',
    'videoDirectory' => 'videos/'
];

$databaseSettings = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'databaseName' => 'videonotes'
];

$onlyAdminAccessiblePages = [
    'add-user',
    'upload-video'
];

function SendMail($uMail,$uName,$Message,$Subject)
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->SMTPDebug  = 0;
    $mail->SMTPAuth   = true;
    $mail->SMTPSecure = "ssl";
    $mail->Host       = "example@example.com";
    $mail->Port       = 465;
    $mail->addAddress($uMail, $uName);
    $mail->Username = "example@example.com";
    $mail->Password = "example";
    $mail->setFrom('example@example.com','Video Notes');
    $mail->addReplyTo("example@example.com","Video Notes");
    $mail->Subject = $Subject;
    $mail->CharSet = "UTF-8";
//    $mail->AddEmbeddedImage('img/logo.png', 'logo');
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
    return '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> <html> <head> <meta http-equiv="Content-Type" content="text/html; charset=utf-8" > <title>'.$title.' - '.$app['name'].'</title> <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700" rel="stylesheet"> <style type="text/css"> html{-webkit-text-size-adjust: none; -ms-text-size-adjust: none;}@media only screen and (min-device-width: 750px){.table750{width: 750px !important;}}@media only screen and (max-device-width: 750px), only screen and (max-width: 750px){table[class="table750"]{width: 100% !important;}.mob_b{width: 93% !important; max-width: 93% !important; min-width: 93% !important;}.mob_b1{width: 100% !important; max-width: 100% !important; min-width: 100% !important;}.mob_left{text-align: left !important;}.mob_soc{width: 50% !important; max-width: 50% !important; min-width: 50% !important;}.mob_menu{width: 50% !important; max-width: 50% !important; min-width: 50% !important; box-shadow: inset -1px -1px 0 0 rgba(255, 255, 255, 0.2);}.mob_center{text-align: center !important;}.top_pad{height: 15px !important; max-height: 15px !important; min-height: 15px !important;}.mob_pad{width: 15px !important; max-width: 15px !important; min-width: 15px !important;}.mob_div{display: block !important;}}@media only screen and (max-device-width: 550px), only screen and (max-width: 550px){.mod_div{display: block !important;}}.table750{width: 750px;}</style> </head> <body style="margin: 0; padding: 0;"> <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background: #f3f3f3; min-width: 350px; font-size: 1px; line-height: normal;"> <tr> <td align="center" valign="top"><!--[if (gte mso 9)|(IE)]> <table border="0" cellspacing="0" cellpadding="0"> <tr><td align="center" valign="top" width="750"><![endif]--> <table cellpadding="0" cellspacing="0" border="0" width="750" class="table750" style="width: 100%; max-width: 750px; min-width: 350px; background: #f3f3f3;"> <tr> <td class="mob_pad" width="25" style="width: 25px; max-width: 25px; min-width: 25px;">&nbsp;</td><td align="center" valign="top" style="background: #ffffff;"> <table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100% !important; min-width: 100%; max-width: 100%; background: #f3f3f3;"> <tr> <td align="right" valign="top"> <div class="top_pad" style="height: 25px; line-height: 25px; font-size: 23px;">&nbsp;</div></td></tr></table> <table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88% !important; min-width: 88%; max-width: 88%;"> <tr> <td align="left" valign="top"> <div style="height: 39px; line-height: 39px; font-size: 37px;">&nbsp;</div><a href="#" target="_blank" style="display: block; max-width: 128px;"> <img src="cid:logo" alt="img" width="160" border="0" style="display: block; width: 160px;"/> </a> <div style="height: 73px; line-height: 73px; font-size: 71px;">&nbsp;</div></td></tr></table> '.$mainBody.' <table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100% !important; min-width: 100%; max-width: 100%; background: #f3f3f3;"> <tr> <td align="center" valign="top"> <table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88% !important; min-width: 88%; max-width: 88%;"> <tr> <td align="center" valign="top"> <div style="height: 34px; line-height: 34px; font-size: 32px;">&nbsp;</div><font face="Source Sans Pro, sans-serif" color="#868686" style="font-size: 17px; line-height: 20px;"> <span style="font-family: Source Sans Pro, Arial, Tahoma, Geneva, sans-serif; color: #868686; font-size: 17px; line-height: 20px;">Copyright &copy; '.$companyInformations['companyYear'].' '.$companyInformations['companyName'].'</span> </font> <div style="height: 3px; line-height: 3px; font-size: 1px;">&nbsp;</div><font face="Source Sans Pro, sans-serif" color="#1a1a1a" style="font-size: 17px; line-height: 20px;"> <span style="font-family: Source Sans Pro, Arial, Tahoma, Geneva, sans-serif; color: #1a1a1a; font-size: 17px; line-height: 20px;"><a href="mailto:'.$companyInformations['companyMail'].'" style="font-family: Source Sans Pro, Arial, Tahoma, Geneva, sans-serif; color: #1a1a1a; font-size: 17px; line-height: 20px; text-decoration: none;">'.$companyInformations['companyMail'].'</a> &nbsp;&nbsp;|&nbsp;&nbsp; <a href="tel:'.$companyInformations['companyPhone'].'" style="font-family: Source Sans Pro, Arial, Tahoma, Geneva, sans-serif; color: #1a1a1a; font-size: 17px; line-height: 20px; text-decoration: none;">'.$companyInformations['companyPhone'].'</a></span> </font> <div style="height: 35px; line-height: 35px; font-size: 33px;">&nbsp;</div></td></tr></table> </td></tr></table> </td><td class="mob_pad" width="25" style="width: 25px; max-width: 25px; min-width: 25px;">&nbsp;</td></tr></table><!--[if (gte mso 9)|(IE)]> </td></tr></table><![endif]--> </td></tr></table> </body> </html>';
}