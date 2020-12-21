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
    if (!hash_equals($csrfToken, $headers['csrftoken'])) {
        exit("Security");
    }
} else {
    exit("Security");
}

if(loginCheck($DB_con) == false)
{
	header('Location: login');
	exit();
}

$pageRequest = filter_input(INPUT_GET, 'pr', FILTER_SANITIZE_STRING);

if(isset($pageRequest))
{
    $getUser = $DB_con->prepare("SELECT username, email, name, type FROM users WHERE id = :id");
    $getUser->execute(array(":id" => loginCheck($DB_con)));
    $user = $getUser->fetch(PDO::FETCH_ASSOC);
    $isAdmin = $user && $user['type'] === 'ADMIN';
    if($pageRequest == "logout") {
		$_user->logout();
	} else if ($pageRequest == 'upload-video') {
        if (!$isAdmin) {
            echo 'Security';
            exit();
        }
        $isFirstChunk = isset($_POST['isFirstChunk']) && $_POST['isFirstChunk'] == true;
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $videoFileName = filter_input(INPUT_POST, 'videoFileName', FILTER_SANITIZE_STRING);
        if (!isset($videoFileName) || empty($videoFileName)) {
            $videoFileName = generateRandomString(18);
        }
        if ($isFirstChunk) {
            if (!isset($name) || empty($name)) {
                echo json_encode(['success' => false, 'message' => 'A name must be specified for the video.']);
                exit();
            }
            if ($_POST['fileType'] !== 'video/mp4') {
                echo json_encode(['success' => false, 'message' => 'Only mp4 video can be uploaded.']);
                exit();
            }
        }
        $videoFileType = explode('/', $_POST['fileType'])[1];
        $filePath = $app['videoDirectory'] . $videoFileName . '.' . $videoFileType;
        $fileData = decodeChunk($_POST['fileData']);
        if ($fileData === false) {
            @unlink($filePath);
            echo json_encode(['success' => false, 'message' => 'An error occurred.']);
            exit();
        }
        if (file_put_contents($filePath, $fileData, FILE_APPEND)) {
            echo json_encode(['success' => true, 'videoFileName' => $videoFileName]);
            exit();
        } else {
            @unlink($filePath);
            echo json_encode(['success' => false, 'message' => 'An error occurred.']);
            exit();
        }
    } else if ($pageRequest === 'add-video') {
        if (!$isAdmin) {
            echo 'Security';
            exit();
        }
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $videoFileName = filter_input(INPUT_POST, 'videoFileName', FILTER_SANITIZE_STRING);
        $videoFileType = filter_input(INPUT_POST, 'fileType', FILTER_SANITIZE_STRING);
        $videoFileDuration = filter_input(INPUT_POST, 'duration', FILTER_SANITIZE_STRING);
        $videoFileFormat = explode('/', $videoFileType)[1];
        if (!isset($name) || empty($name)) {
            @unlink($app['videoDirectory'] . $name . '.' . $videoFileFormat);
            echo 0;
            exit();
        }
        try {
            $addVideo = $DB_con->prepare('INSERT INTO videos(name, fileName, duration, format) VALUES (:name, :fileName, :duration, :format)');
            if ($addVideo->execute(array(':name' => $name, ':fileName' => $videoFileName . '.' . $videoFileFormat, ':duration' => $videoFileDuration, ':format' => $videoFileType))) {
                if (isset($_POST['notes']) && count($_POST['notes']) > 0) {
                    $videoId = $DB_con->lastInsertId();
                    foreach ($_POST['notes'] as $note) {
                        if (isset($note['minute']) && isset($note['second']) && isset($note['note'])) {
                            $addNote = $DB_con->prepare('INSERT INTO video_notes(videoId, minute, second, note) VALUES (:videoId, :minute, :second, :note)');
                            $addNote->execute(array(':videoId' => $videoId, ':minute' => $note['minute'], ':second' => $note['second'], ':note' => $note['note']));
                        }
                    }
                    echo 1;
                    exit();
                } else {
                    echo 1;
                    exit();
                }
            }
        }
        catch(PDOException $ex)
        {
            @unlink($app['videoDirectory'] . $name . '.' . $videoFileFormat);
            echo $ex->getMessage();
            exit();
        }
    }
} else {
    echo 'Not found';
    exit();
}
