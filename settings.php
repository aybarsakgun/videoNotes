<?php
if(!defined('AJAX') && !defined('VAR2')) {
    die('Security');
}

define('VAR3', TRUE);

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
    'users',
    'add-user',
    'edit-user',
    'upload-video',
    'edit-video'
];
