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
    $getUser = $DB_con->prepare("SELECT id, username, email, name, type FROM users WHERE id = :id");
    $getUser->execute(array(":id" => loginCheck($DB_con)));
    $user = $getUser->fetch(PDO::FETCH_ASSOC);
    $isAdmin = $user && ($user['type'] === 'SUPER ADMIN' || $user['type'] === 'ADMIN');
    if($pageRequest == "logout") {
		echo $_user->logout();
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
    } else if ($pageRequest == 'add-video') {
        if (!$isAdmin) {
            echo 'Security';
            exit();
        }
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $videoFileName = filter_input(INPUT_POST, 'videoFileName', FILTER_SANITIZE_STRING);
        $videoFileType = filter_input(INPUT_POST, 'fileType', FILTER_SANITIZE_STRING);
        $videoFileDuration = filter_input(INPUT_POST, 'duration', FILTER_SANITIZE_STRING);
        $videoThumbnailSecond = filter_input(INPUT_POST, 'thumbnailSecond', FILTER_SANITIZE_STRING);
        $videoFileFormat = explode('/', $videoFileType)[1];
        if (!isset($name) || empty($name)) {
            @unlink($app['videoDirectory'] . $name . '.' . $videoFileFormat);
            echo 0;
            exit();
        }
        try {
            $addVideo = $DB_con->prepare('INSERT INTO videos(name, fileName, duration, format, thumbnailSecond) VALUES (:name, :fileName, :duration, :format, :thumbnailSecond)');
            if ($addVideo->execute(array(':name' => $name, ':fileName' => $videoFileName . '.' . $videoFileFormat, ':duration' => $videoFileDuration, ':format' => $videoFileType, ':thumbnailSecond' => $videoThumbnailSecond))) {
                $videoId = $DB_con->lastInsertId();
                if (isset($_POST['notes']) && count($_POST['notes']) > 0) {
                    $videoId = $DB_con->lastInsertId();
                    foreach ($_POST['notes'] as $note) {
                        if (isset($note['minute']) && isset($note['second']) && isset($note['note'])) {
                            $addNote = $DB_con->prepare('INSERT INTO video_notes(videoId, minute, second, note) VALUES (:videoId, :minute, :second, :note)');
                            $addNote->execute(array(':videoId' => $videoId, ':minute' => $note['minute'], ':second' => $note['second'], ':note' => $note['note']));
                        }
                    }
                    echo json_encode(['success' => true, 'videoId' => $videoId]);
                    exit();
                } else {
                    echo json_encode(['success' => true, 'videoId' => $videoId]);
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
    } else if ($pageRequest == 'delete-video') {
        if (!$isAdmin) {
            exit(throwError(401));
        }
        $videoId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if($videoId === false)
        {
            exit(throwError(400));
        }
        if(!isset($videoId) || empty($videoId))
        {
            exit(throwError(400));
        }
        $checkVideo = $DB_con->prepare("SELECT id, fileName FROM videos WHERE id = :id");
        $checkVideo->execute(array(":id"=>$videoId));
        if($checkVideo->rowCount() != 1)
        {
            exit(throwError(400));
        }
        $fetchVideo = $checkVideo->fetch(PDO::FETCH_ASSOC);
        $deleteVideo = $DB_con->prepare("DELETE FROM videos WHERE id = :id");
        $deleteVideo->execute(array(":id"=>$videoId));
        $deleteVideoNotes = $DB_con->prepare("DELETE from video_notes WHERE videoId = :videoId");
        $deleteVideoNotes->execute(array(":videoId" => $videoId));
        @unlink($app['videoDirectory'].$fetchVideo['fileName']);
        exit(throwError(200));
    } else if ($pageRequest == 'get-users') {
        if (!$isAdmin) {
            echo 'Security';
            exit();
        }
        $getUsers = $DB_con->prepare('SELECT * FROM users');
        $getUsers->execute();
        ?>
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="card">
                <div class="header">
                    <h2>Users</h2>
                    <ul class="header-dropdown m-r--5">
                        <li class="dropdown">
                            <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                <i class="material-icons">more_vert</i>
                            </a>
                            <ul class="dropdown-menu pull-right">
                                <li><a href="add-user" class=" waves-effect waves-block">Add User</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <div class="body table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>USERNAME</th>
                            <th>E-MAIL</th>
                            <th>NAME</th>
                            <th>TYPE</th>
                            <th>ACTION</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        while ($fetchUsers = $getUsers->fetch(PDO::FETCH_ASSOC)) {
                        ?>
                        <tr>
                            <th><?=$fetchUsers['username']?></th>
                            <td><?=$fetchUsers['email']?></td>
                            <td><?=$fetchUsers['name']?></td>
                            <td><?=$fetchUsers['type']?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if ($user['type'] == 'SUPER ADMIN' || ($user['type'] == 'ADMIN' && $fetchUsers['type'] == 'USER')) { ?>
                                    <a href="edit-user-<?=$fetchUsers['id']?>" class="btn btn-info btn-xs waves-effect"><i class="material-icons">edit</i></a>
                                    <?php } ?>
                                    <?php if (($user['type'] == 'SUPER ADMIN' && $fetchUsers['type'] != 'SUPER ADMIN') || ($user['type'] == 'ADMIN' && $fetchUsers['type'] == 'USER')) { ?>
                                    <button type="button" class="btn btn-danger btn-xs waves-effect deleteButton" data-user-id="<?=$fetchUsers['id']?>"><i class="material-icons">delete</i></button>
                                    <?php } ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    } else if ($pageRequest == 'delete-user') {
        if (!$isAdmin) {
            exit(throwError(401));
        }
        $userId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if($userId === false)
        {
            exit(throwError(400));
        }
        if(!isset($userId) || empty($userId))
        {
            exit(throwError(400));
        }
        $checkUser = $DB_con->prepare("SELECT id, type FROM users WHERE id = :id");
        $checkUser->execute(array(":id"=>$userId));
        if($checkUser->rowCount() != 1)
        {
            exit(throwError(400));
        }
        $fetchUser = $checkUser->fetch(PDO::FETCH_ASSOC);
        if ($fetchUser['type'] === 'SUPER ADMIN') {
            exit(throwError(400));
        }
        if ($user['type'] === 'ADMIN' && $fetchUser['type'] !== 'USER') {
            exit(throwError(401));
        }
        $deleteUser = $DB_con->prepare("DELETE FROM users WHERE id = :id");
        $deleteUser->execute(array(":id"=>$userId));
        exit(throwError(200));
    } else if ($pageRequest == 'edit-user') {
        if (!$isAdmin) {
            exit(throwError(401));
        }
        $userId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if($userId === false)
        {
            exit(throwError(400));
        }
        if(!isset($userId) || empty($userId))
        {
            exit(throwError(400));
        }
        $checkUser = $DB_con->prepare("SELECT id, username, type FROM users WHERE id = :id");
        $checkUser->execute(array(":id"=>$userId));
        if($checkUser->rowCount() != 1)
        {
            exit(throwError(400));
        }
        $userName = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $userMail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $userType = '';
        $fetchUser = $checkUser->fetch(PDO::FETCH_ASSOC);
        if ($user['type'] === 'ADMIN' && $fetchUser['type'] !== 'USER') {
            exit(throwError(401));
        }
        if ($user['type'] === 'SUPER ADMIN' && $fetchUser['id'] !== $user['id']) {
            $userType = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        }
        if(isset($userMail) && !empty($userMail) && !filter_var($userMail, FILTER_VALIDATE_EMAIL))
        {
            exit(throwError(400, 'Please enter a valid e-mail address.'));
        }
        $additionalSQL = '';
        if(!empty($_POST["password"]) && !empty($_POST["passwordVerify"]))
        {
            $newPassword = $_POST['passwordVerify'];
            $newHashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $additionalSQL = ', password = :password';
        }
        $updateUser = $DB_con->prepare('UPDATE users SET name = :name, email = :email, type = :type ' . $additionalSQL . ' WHERE id = :id');
        if ($updateUser->execute(empty($additionalSQL) ? array(':name' => $userName, ':email' => $userMail, ':type' => (empty($userType) ? $fetchUser['type'] : $userType), ':id' => $userId) : array(':name' => $userName, ':email' => $userMail, ':type' => (empty($userType) ? $fetchUser['type'] : $userType), ':password' => $newHashedPassword, ':id' => $userId))) {
            if (!empty($additionalSQL) && !empty($userMail)) {
                $subject = "VideoNotes User Credentials";
                $mainBodyHTML = '<table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88% !important; min-width: 88%; max-width: 88%;">
                                     <tr>
                                        <td align="left" valign="top">
                                           <font face="Source Sans Pro, sans-serif" color="#1a1a1a" style="font-size: 52px; line-height: 60px; font-weight: 300; letter-spacing: -1.5px;">
                                              <span style="font-family: Source Sans Pro, Arial, Tahoma, Geneva, sans-serif; color: #1a1a1a; font-size: 52px; line-height: 60px; font-weight: 300; letter-spacing: -1.5px;">Hello, '.$userName.'</span>
                                           </font>
                                           <div style="height: 33px; line-height: 33px; font-size: 31px;">&nbsp;</div>
                                           <font face="Source Sans Pro, sans-serif" color="#585858" style="font-size: 24px; line-height: 32px;">
                                              <span style="font-family: Source Sans Pro, Arial, Tahoma, Geneva, sans-serif; color: #585858; font-size: 24px; line-height: 32px;">Below are your user credentials.<br><strong>Username: </strong> '.$fetchUser['username'].'<br><strong>Password: </strong> '.$newPassword.'</span>
                                           </font>
                                           <div style="height: 33px; line-height: 33px; font-size: 31px;">&nbsp;</div>
                                           <table class="mob_btn" cellpadding="0" cellspacing="0" border="0" style="background: #27cbcc; border-radius: 4px;">
                                              <tr>
                                                 <td align="center" valign="top"> 
                                                    <a href="'.$app['url'].'" target="_blank" style="display: block; border: 1px solid #27cbcc; border-radius: 4px; padding: 12px 23px; font-family: Source Sans Pro, Arial, Verdana, Tahoma, Geneva, sans-serif; color: #ffffff; font-size: 20px; line-height: 30px; text-decoration: none; white-space: nowrap; font-weight: 600;">
                                                       <font face="Source Sans Pro, sans-serif" color="#ffffff" style="font-size: 20px; line-height: 30px; text-decoration: none; white-space: nowrap; font-weight: 600;">
                                                          <span style="font-family: Source Sans Pro, Arial, Verdana, Tahoma, Geneva, sans-serif; color: #ffffff; font-size: 20px; line-height: 30px; text-decoration: none; white-space: nowrap; font-weight: 600;">Go to the website</span>
                                                       </font>
                                                    </a>
                                                 </td>
                                              </tr>
                                           </table>
                                           <div style="height: 75px; line-height: 75px; font-size: 73px;">&nbsp;</div>
                                        </td>
                                     </tr>
                                  </table>';
                $message = createEmailTemplate($subject, $mainBodyHTML, $app);
                sendMail($userMail,$userName,$message,$subject,$app);
            }
            exit(throwError(200));
        } else {
            exit(throwError(500));
        }
    } else if ($pageRequest == 'add-user') {
        if (!$isAdmin) {
            exit(throwError(401));
        }
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $passwordVerify = filter_input(INPUT_POST, 'passwordVerify', FILTER_SANITIZE_STRING);
        $type = 'USER';
        if ($user['type'] === 'SUPER ADMIN') {
            $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        }
        if (!isset($username) || empty($username)) {
            exit(throwError(400, 'Enter a username for the user.'));
        }
        if (!isset($name) || empty($name)) {
            exit(throwError(400, 'Enter a name for the user.'));
        }
        if (!isset($password) || empty($password) || !isset($passwordVerify) || empty($passwordVerify)) {
            exit(throwError(400, 'Enter a password for the user.'));
        }
        if(isset($email) && !empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            exit(throwError(400, 'Please enter a valid e-mail address.'));
        }
        $checkUsernameExist = $DB_con->prepare('SELECT id FROM users WHERE username = :username');
        $checkUsernameExist->execute(array(':username' => $username));
        if ($checkUsernameExist->rowCount() != 0) {
            exit(throwError(400, 'The username you entered is already taken.'));
        }
        $hashedPassword = password_hash($passwordVerify, PASSWORD_BCRYPT);
        $addUser = $DB_con->prepare('INSERT INTO users(username, password, email, name, type) VALUES (:username, :password, :email, :name, :type)');
        if ($addUser->execute(array(':username' => $username, ':password' => $hashedPassword, ':email' => $email, ':name' => $name, ':type' => $type))) {
            if (!empty($email)) {
                $subject = "VideoNotes User Credentials";
                $mainBodyHTML = '<table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88% !important; min-width: 88%; max-width: 88%;">
                                     <tr>
                                        <td align="left" valign="top">
                                           <font face="Source Sans Pro, sans-serif" color="#1a1a1a" style="font-size: 52px; line-height: 60px; font-weight: 300; letter-spacing: -1.5px;">
                                              <span style="font-family: Source Sans Pro, Arial, Tahoma, Geneva, sans-serif; color: #1a1a1a; font-size: 52px; line-height: 60px; font-weight: 300; letter-spacing: -1.5px;">Hello, '.$name.'</span>
                                           </font>
                                           <div style="height: 33px; line-height: 33px; font-size: 31px;">&nbsp;</div>
                                           <font face="Source Sans Pro, sans-serif" color="#585858" style="font-size: 24px; line-height: 32px;">
                                              <span style="font-family: Source Sans Pro, Arial, Tahoma, Geneva, sans-serif; color: #585858; font-size: 24px; line-height: 32px;">Below are your user credentials.<br><strong>Username: </strong> '.$username.'<br><strong>Password: </strong> '.$passwordVerify.'</span>
                                           </font>
                                           <div style="height: 33px; line-height: 33px; font-size: 31px;">&nbsp;</div>
                                           <table class="mob_btn" cellpadding="0" cellspacing="0" border="0" style="background: #27cbcc; border-radius: 4px;">
                                              <tr>
                                                 <td align="center" valign="top"> 
                                                    <a href="'.$app['url'].'" target="_blank" style="display: block; border: 1px solid #27cbcc; border-radius: 4px; padding: 12px 23px; font-family: Source Sans Pro, Arial, Verdana, Tahoma, Geneva, sans-serif; color: #ffffff; font-size: 20px; line-height: 30px; text-decoration: none; white-space: nowrap; font-weight: 600;">
                                                       <font face="Source Sans Pro, sans-serif" color="#ffffff" style="font-size: 20px; line-height: 30px; text-decoration: none; white-space: nowrap; font-weight: 600;">
                                                          <span style="font-family: Source Sans Pro, Arial, Verdana, Tahoma, Geneva, sans-serif; color: #ffffff; font-size: 20px; line-height: 30px; text-decoration: none; white-space: nowrap; font-weight: 600;">Go to the website</span>
                                                       </font>
                                                    </a>
                                                 </td>
                                              </tr>
                                           </table>
                                           <div style="height: 75px; line-height: 75px; font-size: 73px;">&nbsp;</div>
                                        </td>
                                     </tr>
                                  </table>';
                $message = createEmailTemplate($subject, $mainBodyHTML, $app);
                sendMail($email,$name,$message,$subject,$app);
            }
            exit(throwError(200));
        } else {
            exit(throwError(500));
        }
    } else if ($pageRequest == 'edit-video') {
        if (!$isAdmin) {
            echo 'Security';
            exit();
        }
        $videoId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if($videoId === false)
        {
            exit(throwError(400));
        }
        if(!isset($videoId) || empty($videoId))
        {
            exit(throwError(400));
        }
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $thumbnailSecond = filter_input(INPUT_POST, 'thumbnailSecond', FILTER_SANITIZE_STRING);
        if (!isset($name) || empty($name)) {
            exit(throwError(400, 'Enter a name for the video.'));
        }
        $checkVideo = $DB_con->prepare("SELECT id FROM videos WHERE id = :id");
        $checkVideo->execute(array(":id"=>$videoId));
        if($checkVideo->rowCount() != 1)
        {
            exit(throwError(400));
        }
        try {
            $updateVideo = $DB_con->prepare('UPDATE videos SET name = :name, thumbnailSecond = :thumbnailSecond WHERE id = :id');
            if ($updateVideo->execute(array(':name' => $name, ':thumbnailSecond' => $thumbnailSecond, ':id' => $videoId))) {
                if (isset($_POST['notes']) && count($_POST['notes']) > 0) {
                    foreach ($_POST['notes'] as $note) {
                        if (isset($note['minute']) && isset($note['second']) && isset($note['note']) && isset($note['id'])) {
                            if ($note['id'] < 0) {
                                $deleteVideoNote = $DB_con->prepare('DELETE FROM video_notes WHERE id = :id AND videoId = :videoId');
                                $deleteVideoNote->execute(array(':id' => abs($note['id']), ':videoId' => $videoId));
                            } else if ($note['id'] > 0) {
                                $updateVideoNote = $DB_con->prepare('UPDATE video_notes SET minute = :minute , second = :second, note = :note WHERE id = :id AND videoId = :videoId');
                                $updateVideoNote->execute(array(':minute' => $note['minute'], ':second' => $note['second'], ':note' => $note['note'], ':id' => $note['id'], ':videoId' => $videoId));
                            } else if ($note['id'] == 0) {
                                $addVideoNote = $DB_con->prepare('INSERT INTO video_notes(videoId, minute, second, note) VALUES (:videoId, :minute, :second, :note)');
                                $addVideoNote->execute(array(':videoId' => $videoId, ':minute' => $note['minute'], ':second' => $note['second'], ':note' => $note['note']));
                            }
                        }
                    }
                }
                exit(throwError(200));
            } else {
                exit(throwError(500));
            }
        }
        catch(PDOException $ex)
        {
            @unlink($app['videoDirectory'] . $name . '.' . $videoFileFormat);
            echo $ex->getMessage();
            exit();
        }
    } else if ($pageRequest == 'get-videos') {
        $getVideosQuery = $DB_con->prepare("SELECT * FROM videos");
        $getVideosQuery->execute();
        $index = 0;
        while ($fetchVideos = $getVideosQuery->fetch(PDO::FETCH_ASSOC)) {
            $getVideoNotes = $DB_con->prepare('SELECT * FROM video_notes WHERE videoId = :videoId');
            $getVideoNotes->execute(array(':videoId' => $fetchVideos['id']));
            ?>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="header">
                        <h2><?=$fetchVideos['name']?></h2>
                        <?php if ($isAdmin) { ?>
                        <ul class="header-dropdown m-r--5">
                            <li class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="true">
                                    <i class="material-icons">more_vert</i>
                                </a>
                                <ul class="dropdown-menu pull-right">
                                    <li><a href="edit-video-<?=$fetchVideos['id']?>" class=" waves-effect waves-block">Edit Video</a></li>
                                    <li><a href="javascript:void(0);" class=" waves-effect waves-block deleteButton" data-video-id="<?=$fetchVideos['id']?>">Delete Video</a></li>
                                </ul>
                            </li>
                        </ul>
                        <?php } ?>
                    </div>
                    <div class="body">
                        <video id="video_<?=$index?>" class="video-js vjs-default-skin vjs-16-9" width="100%" height="480"></video>
                    </div>
                    <div id="overlay_<?=$index?>" style="display:none">
                        <div class="overlay-content">
                            <p class="text"></p>
                            <button type="button" class="btn bg-<?=$app['themeColor']?> btn-block btn-lg waves-effect continue-button" style="max-width: 120px;">Continue</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                function setActiveVideo(videoIndex) {
                    localStorage.setItem('activeVideo', videoIndex);
                }
                function setActiveVideoLastMarkerDuration(duration) {
                    localStorage.setItem('activeVideoLastMarkerDuration', duration);
                }
                var player_<?=$index?> = videojs('video_<?=$index?>', {
                    controls: true,
                    controlBar: {
                        'pictureInPictureToggle': false
                    }
                });
                player_<?=$index?>.one('play', function () {
                    this.currentTime(0);
                    setActiveVideo(<?=$index?>);
                    setActiveVideoLastMarkerDuration(0);
                });
                player_<?=$index?>.src({src: '<?=$app['videoDirectory'].$fetchVideos['fileName']?>#t=<?=$fetchVideos['thumbnailSecond']?>', type: '<?=$fetchVideos['format']?>'});
                player_<?=$index?>.markers({
                    markerTip: {
                        display: false
                    },
                    markers: [
                        <?php while ($fetchVideoNotes = $getVideoNotes->fetch(PDO::FETCH_ASSOC)) { ?>
                        {
                            time: <?=(($fetchVideoNotes['minute'] * 60) + $fetchVideoNotes['second'])?>,
                            text: '<?=escapeJavaScriptText($fetchVideoNotes['note'])?>',
                        },
                        <?php } ?>
                    ],
                    onMarkerClick: function(marker) {
                        player_<?=$index?>.pause();
                        $('#overlay_<?=$index?> .text').text(marker.text);
                        $('#overlay_<?=$index?>').show();
                        setActiveVideo(<?=$index?>);
                        setActiveVideoLastMarkerDuration(marker.duration);
                    },
                    onMarkerReached: function(marker) {
                        if (player_<?=$index?>.hasStarted_) {
                            player_<?=$index?>.pause();
                            setActiveVideo(<?=$index?>);
                            setActiveVideoLastMarkerDuration(marker.time);
                            let currentTime = player_<?=$index?>.currentTime();
                            setTimeout(function () {
                                if (currentTime === player_<?=$index?>.currentTime()) {
                                    $('#overlay_<?=$index?> .text').text(marker.text);
                                    $('#overlay_<?=$index?>').show();
                                }
                            }, 200);
                        }

                    },
                });
                $('#overlay_<?=$index?>').appendTo($('#video_<?=$index?>'));
                $('#overlay_<?=$index?> .overlay-content .continue-button').click(function () {
                    $('#overlay_<?=$index?>').hide();
                    player_<?=$index?>.play();
                });
            </script>
            <?php
            $index++;
        }
    }
} else {
    echo 'Not found';
    exit();
}
