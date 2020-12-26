<?php
define('VAR1', true);

require_once 'class.user.php';

sessionStart();

if (empty($_SESSION['videoNotesToken'])) {
    $_SESSION['videoNotesToken'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['videoNotesToken'];

if(loginCheck($DB_con) == false)
{
    header("Location: login");
    exit();
}

$getUser = $DB_con->prepare("SELECT id, username, email, name, type FROM users WHERE id = :id");
$getUser->execute(array(":id" => loginCheck($DB_con)));
$user = $getUser->fetch(PDO::FETCH_ASSOC);
$isAdmin = $user && ($user['type'] === 'SUPER ADMIN' || $user['type'] === 'ADMIN');

$pageRequest = filter_input(INPUT_GET, 'pr', FILTER_SANITIZE_STRING);
if (in_array($pageRequest, $onlyAdminAccessiblePages) && !$isAdmin) {
    $pageRequest = null;
} else {
    if (empty($pageRequest)) {
        $pageRequest = 'home';
    }
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=Edge">
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <meta name="csrf-token" content="<?=$csrfToken?>">
        <title><?=$app['name']?></title>
        <link rel="icon" href="img/favicon.png" type="image/x-icon">
        <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&subset=latin,cyrillic-ext" rel="stylesheet" type="text/css">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" type="text/css">
        <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="plugins/node-waves/waves.min.css" rel="stylesheet" />
        <link href="plugins/animate-css/animate.min.css" rel="stylesheet" />
        <link href="css/style.css" rel="stylesheet">
        <link href="css/all-themes.min.css" rel="stylesheet" />
    </head>
    <?php if (!isset($pageRequest)) {?>
    <body class="four-zero-four">
        <div class="four-zero-four-container">
            <div class="error-code">404</div>
            <div class="error-message">This page doesn't exist</div>
            <div class="button-place">
                <a href="home" class="btn btn-default btn-lg waves-effect">GO TO HOMEPAGE</a>
            </div>
        </div>
    <?php
    } else {
    ?>
    <body class="theme-<?=$app['themeColor']?>">
        <div class="page-loader-wrapper">
            <div class="loader">
                <div class="preloader">
                    <div class="spinner-layer pl-<?=$app['themeColor']?>">
                        <div class="circle-clipper left">
                            <div class="circle"></div>
                        </div>
                        <div class="circle-clipper right">
                            <div class="circle"></div>
                        </div>
                    </div>
                </div>
                <p>Please wait...</p>
            </div>
        </div>
        <div class="overlay"></div>
        <nav class="navbar">
            <div class="container-fluid">
                <div class="navbar-header">
                    <a href="javascript:void(0);" class="bars"></a>
                    <a class="navbar-brand" href="home">Video<strong>Notes</strong></a>
                </div>
            </div>
        </nav>
        <section>
            <aside id="leftsidebar" class="sidebar">
                <div class="user-info">
                    <div class="info-container">
                        <div class="name" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?=$user['name']?></div>
                        <div class="email"><?php if (!empty($user['email'])) { echo $user['email']; } else { echo $user['username']; }?></div>
                        <div class="btn-group user-helper-dropdown">
                            <i class="material-icons logoutButton">exit_to_app</i>
                        </div>
                    </div>
                </div>
                <div class="menu">
                    <ul class="list">
                        <?php if ($isAdmin) { ?>
                        <li class="header">Admin Controls</li>
                        <li>
                            <a href="users">
                                <i class="material-icons">people</i>
                                <span>Users</span>
                            </a>
                        </li>
                        <li>
                            <a href="add-user">
                                <i class="material-icons">person_add</i>
                                <span>Add User</span>
                            </a>
                        </li>
                        <li>
                            <a href="upload-video">
                                <i class="material-icons">file_upload</i>
                                <span>Upload Video</span>
                            </a>
                        </li>
                        <?php } ?>
                        <li class="header">Navigation</li>
                        <li>
                            <a href="home">
                                <i class="material-icons">home</i>
                                <span>Home</span>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="legal">
                    <div class="copyright">
                        &copy; 2020 <?=$app['name']?>
                    </div>
                </div>
            </aside>
        </section>
        <?php if ($pageRequest == 'home') { ?>
        <section class="content">
            <div class="container-fluid">
                <div class="row clearfix">
                    <div class="row clearfix" id="videosContainer"></div>
                </div>
            </div>
        </section>
        <?php } else if ($pageRequest == 'add-user') {
            if (!$isAdmin) {
                echo 401;
                exit();
            }
        ?>
            <section class="content">
                <div class="container-fluid">
                    <div class="row clearfix">
                        <div class="row clearfix">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="card">
                                    <div class="header">
                                        <h2>
                                            Add User
                                        </h2>
                                    </div>
                                    <div class="body">
                                        <form id="addUserForm">
                                            <div class="row clearfix">
                                                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                                    <label for="username">* Username</label>
                                                    <div class="form-group">
                                                        <div class="form-line">
                                                            <input type="text" id="username" name="username" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                                    <label for="email">E-Mail Address</label>
                                                    <div class="form-group">
                                                        <div class="form-line">
                                                            <input type="text" id="email" name="email" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                                    <label for="name">* Name</label>
                                                    <div class="form-group">
                                                        <div class="form-line">
                                                            <input type="text" id="name" name="name" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                                    <label for="password">* Password</label>
                                                    <div class="input-group">
                                                        <span class="input-group-addon">
                                                            <i class="material-icons" style="cursor:pointer;" id="generatePassword" title="Generate password">flash_on</i>
                                                        </span>
                                                        <div class="form-line">
                                                            <input type="password" id="password" name="password" class="form-control">
                                                        </div>
                                                        <span class="input-group-addon">
                                                            <i class="material-icons" style="cursor:pointer;" id="passwordVisibilityChanger">visibility</i>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                                    <label for="passwordVerify">* Password Verify</label>
                                                    <div class="input-group">
                                                        <div class="form-line">
                                                            <input type="password" id="passwordVerify" name="passwordVerify" class="form-control">
                                                        </div>
                                                        <span class="input-group-addon">
                                                            <i class="material-icons" style="cursor:pointer;" id="passwordVerifyVisibilityChanger">visibility</i>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php
                                            if ($user['type'] === 'SUPER ADMIN') {
                                                ?>
                                                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                                    <label for="video">Type</label>
                                                    <div class="form-group">
                                                        <input name="type" type="radio" id="typeUser" checked=""
                                                               value="USER">
                                                        <label for="typeUser">USER</label>
                                                        <input name="type" type="radio" id="typeAdmin"
                                                               value="ADMIN">
                                                        <label for="typeAdmin">ADMIN</label>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                            </div>
                                            <div id="result"></div>
                                            <button type="submit" class="btn bg-<?=$app['themeColor']?> m-t-15 waves-effect" id="addUserButton">Add User</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php } else if ($pageRequest == 'edit-user') {
            $userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if ($userId === false) {
                echo 400;
                exit();
            }
            if (!$isAdmin) {
                echo 401;
                exit();
            }
            $getUser = $DB_con->prepare("SELECT id, username, email, name, type FROM users WHERE id = :id");
            $getUser->execute(array(':id' => $userId));
            $fetchUser = $getUser->fetch(PDO::FETCH_ASSOC);
            if ($user['type'] === 'ADMIN' && $fetchUser['type'] !== 'USER') {
                echo 401;
                exit();
            }
        ?>
        <section class="content">
            <div class="container-fluid">
                <div class="row clearfix">
                    <div class="row clearfix">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="card">
                                <div class="header">
                                    <h2>
                                        Edit User
                                    </h2>
                                </div>
                                <div class="body">
                                    <form id="editUserForm">
                                        <div class="row clearfix">
                                            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                                <input type="hidden" name="id" value="<?=$fetchUser['id']?>">
                                                <label for="username">Username</label>
                                                <div class="form-group">
                                                    <div class="form-line">
                                                        <input type="text" id="username" name="username" class="form-control" value="<?=$fetchUser['username']?>" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                                <label for="name">Name</label>
                                                <div class="form-group">
                                                    <div class="form-line">
                                                        <input type="text" id="name" name="name" class="form-control" value="<?=$fetchUser['name']?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                                <label for="email">E-Mail Address</label>
                                                <div class="form-group">
                                                    <div class="form-line">
                                                        <input type="text" id="email" name="email" class="form-control" value="<?=$fetchUser['email']?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                                <label for="password">Password <small>If you do not want to change it, leave it blank.</small></label>
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="material-icons" style="cursor:pointer;" id="generatePassword" title="Generate password">flash_on</i>
                                                    </span>
                                                    <div class="form-line">
                                                        <input type="password" id="password" name="password" class="form-control">
                                                    </div>
                                                    <span class="input-group-addon">
                                                        <i class="material-icons" style="cursor:pointer;" id="passwordVisibilityChanger">visibility</i>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                                <label for="passwordVerify">Password Verify</label>
                                                <div class="input-group">
                                                    <div class="form-line">
                                                        <input type="password" id="passwordVerify" name="passwordVerify" class="form-control">
                                                    </div>
                                                    <span class="input-group-addon">
                                                        <i class="material-icons" style="cursor:pointer;" id="passwordVerifyVisibilityChanger">visibility</i>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php
                                        if ($user['type'] === 'SUPER ADMIN' && $fetchUser['id'] !== $user['id']) {
                                            ?>
                                            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                                <label for="video">Type</label>
                                                <div class="form-group">
                                                    <input name="type" type="radio" id="typeUser"
                                                           value="USER" <?php if ($fetchUser['type'] === 'USER') { ?> checked="" <?php } ?>>
                                                    <label for="typeUser">USER</label>
                                                    <input name="type" type="radio" id="typeAdmin"
                                                           value="ADMIN" <?php if ($fetchUser['type'] === 'ADMIN') { ?> checked="" <?php } ?>>
                                                    <label for="typeAdmin">ADMIN</label>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                        </div>
                                        <div id="result"></div>
                                        <button type="submit" class="btn bg-<?=$app['themeColor']?> m-t-15 waves-effect" id="editUserButton">Edit User</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php } else if ($pageRequest == 'users') {
            if (!$isAdmin) {
                echo 401;
                exit();
            }
        ?>
        <section class="content">
            <div class="container-fluid">
                <div class="row clearfix">
                    <div class="row clearfix" id="usersContainer">

                    </div>
                </div>
            </div>
        </section>
        <?php } else if ($pageRequest == 'upload-video') {
            if (!$isAdmin) {
                echo 401;
                exit();
            }
        ?>
        <section class="content">
            <div class="container-fluid">
                <div class="row clearfix">
                    <div class="row clearfix">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="card">
                                <div class="header">
                                    <h2>
                                        Upload Video
                                    </h2>
                                </div>
                                <div class="body">
                                    <form id="videoUploadForm">
                                        <div class="row clearfix">
                                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                                <label for="name">Name</label>
                                                <div class="form-group">
                                                    <div class="form-line">
                                                        <input type="text" id="name" name="name" class="form-control">
                                                    </div>
                                                </div>
                                                <label for="video">Video</label>
                                                <div class="form-group">
                                                    <div class="form-line">
                                                        <input type="file" id="video" name="video" class="form-control" accept="video/*">
                                                        <input type="hidden" id="videoDuration">
                                                    </div>
                                                </div>

                                                <div id="videoPreviewContent" style="display:none">
                                                    <label for="videoPreview">Preview</label>
                                                    <video controls disablepictureinpicture controlslist="nodownload" width="100%" id="videoPreviewElement">
                                                        <source id="videoPreview">
                                                        Your browser does not support HTML5 video.
                                                    </video>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                                <div id="videoNotes" style="display:none">
                                                    <div class="table-responsive">
                                                        <table class="table">
                                                            <thead>
                                                            <tr>
                                                                <th>Minute</th>
                                                                <th>Second</th>
                                                                <th>Note</th>
                                                                <th>
                                                                    <button type="button" class="btn btn-success btn-xs waves-effect" id="addNewNoteButton">
                                                                        <i class="material-icons">note_add</i>
                                                                    </button>
                                                                </th>
                                                            </tr>
                                                            </thead>
                                                            <tbody id="videoNotesTableBody"></tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="result"></div>
                                        <button type="button" class="btn bg-<?=$app['themeColor']?> m-t-15 waves-effect" id="uploadVideoButton">Upload Video</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
        } else if ($pageRequest == 'edit-video') {
            $videoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if ($videoId === false) {
                echo 400;
                exit();
            }
            if (!$isAdmin) {
                echo 401;
                exit();
            }
            $getVideo = $DB_con->prepare("SELECT * FROM videos WHERE id = :id");
            $getVideo->execute(array(':id' => $videoId));
            $fetchVideo = $getVideo->fetch(PDO::FETCH_ASSOC);
        ?>
        <section class="content">
            <div class="container-fluid">
                <div class="row clearfix">
                    <div class="row clearfix">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="card">
                                <div class="header">
                                    <h2>
                                        Edit Video
                                    </h2>
                                </div>
                                <div class="body">
                                    <form id="editVideoForm">
                                        <div class="row clearfix">
                                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                                <label for="name">Name</label>
                                                <div class="form-group">
                                                    <div class="form-line">
                                                        <input type="text" id="name" name="name" value="<?=$fetchVideo['name']?>" class="form-control">
                                                        <input type="hidden" id="videoId" value="<?=$fetchVideo['id']?>">
                                                    </div>
                                                </div>
                                                <div id="videoPreviewContent">
                                                    <label for="videoPreview">Preview</label>
                                                    <video controls disablepictureinpicture controlslist="nodownload" width="100%" id="videoPreviewElement">
                                                        <source id="videoPreview" src="<?=$app['videoDirectory'].$fetchVideo['fileName']?>">
                                                        Your browser does not support HTML5 video.
                                                    </video>
                                                    <input type="hidden" id="videoDuration" value="<?=$fetchVideo['duration']?>">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                                <div id="videoNotes">
                                                    <div class="table-responsive">
                                                        <table class="table">
                                                            <thead>
                                                            <tr>
                                                                <th>Minute</th>
                                                                <th>Second</th>
                                                                <th>Note</th>
                                                                <th>
                                                                    <button type="button" class="btn btn-success btn-xs waves-effect" id="addNewNoteButton">
                                                                        <i class="material-icons">note_add</i>
                                                                    </button>
                                                                </th>
                                                            </tr>
                                                            </thead>
                                                            <tbody id="videoNotesTableBody">
                                                            <?php
                                                            $duration = (int)$fetchVideo['duration'];
                                                            $minutes = [];
                                                            for ($i = 0; $i <= floor($duration / 60); $i++) {
                                                                $minutes[] = $i;
                                                            }
                                                            $greatestMinute = max($minutes);
                                                            $remainingSeconds = $duration - ($greatestMinute * 60);
                                                            $seconds = [];
                                                            for ($i = 0; $i <= ($greatestMinute > 0 ? 59 : $remainingSeconds); $i++) {
                                                                $seconds[] = $i;
                                                            }
                                                            $getVideoNotes = $DB_con->prepare('SELECT * FROM video_notes WHERE videoId = :videoId');
                                                            $getVideoNotes->execute(array(':videoId' => $videoId));
                                                            $i = 0;
                                                            while ($fetchVideoNotes = $getVideoNotes->fetch(PDO::FETCH_ASSOC)) {
                                                                $i++;
                                                            ?>
                                                                <tr id="row<?=$i?>" data-id="<?=$fetchVideoNotes['id']?>">
                                                                    <td>
                                                                        <select class="form-control minute">
                                                                            <?php
                                                                            foreach ($minutes as $minute) {
                                                                                ?>
                                                                                <option value="<?=$minute?>" <?php if ($minute == $fetchVideoNotes['minute']) { ?>selected<?php } ?>><?=$minute?></option>
                                                                                <?php
                                                                            }
                                                                            ?>
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <select class="form-control second">
                                                                            <?php
                                                                            foreach ($seconds as $second) {
                                                                                ?>
                                                                                <option value="<?=$second?>" <?php if ($second == $fetchVideoNotes['second']) { ?>selected<?php } ?>><?=$second?></option>
                                                                                <?php
                                                                            }
                                                                            ?>
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <textarea class="form-control note" rows="3"><?=$fetchVideoNotes['note']?></textarea>
                                                                    </td>
                                                                    <td>
                                                                        <button type="button" id="<?=$fetchVideoNotes['id']?>" class="btn btn-danger btn-xs waves-effect deleteNoteButton">
                                                                            <i class="material-icons">delete</i>
                                                                        </button>
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
                                        </div>
                                        <div id="result"></div>
                                        <button type="submit" class="btn bg-<?=$app['themeColor']?> m-t-15 waves-effect" id="editVideoButton">Edit Video</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php } ?>
    <?php } ?>
        <script src="plugins/jquery/jquery.min.js"></script>
        <script src="plugins/bootstrap/js/bootstrap.min.js"></script>
        <script src="plugins/jquery-slimscroll/jquery.slimscroll.js"></script>
        <script src="plugins/node-waves/waves.min.js"></script>
        <script src="plugins/jquery-inputmask/jquery.inputmask.bundle.min.js"></script>
        <script src="js/main.js"></script>
        <?php include_once 'pageJS.php'; ?>
    </body>
</html>
