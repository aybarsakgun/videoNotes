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

$getUser = $DB_con->prepare("SELECT username, email, name, type FROM users WHERE id = :id");
$getUser->execute(array(":id" => loginCheck($DB_con)));
$user = $getUser->fetch(PDO::FETCH_ASSOC);
$isAdmin = $user && $user['type'] === 'ADMIN';

$pageRequest = filter_input(INPUT_GET, 'pr', FILTER_SANITIZE_STRING);
if (in_array($pageRequest, $onlyAdminAccessiblePages) && !$isAdmin) {
    $pageRequest = null;
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
                        <li>
                            <a href="video-gallery">
                                <i class="material-icons">video_library</i>
                                <span>Video Gallery</span>
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
                    <div class="row clearfix">

                    </div>
                </div>
            </div>
        </section>
        <?php } else if ($pageRequest == 'videos') { ?>
        <section class="content">
            <div class="container-fluid">
                <div class="row clearfix">
                    <div class="row clearfix">
                        <?php
                        $getVideosQuery = $DB_con->prepare("SELECT * FROM videos");
                        $getVideosQuery->execute();
                        $index = 0;
                        while ($fetchVideos = $getVideosQuery->fetch(PDO::FETCH_ASSOC)) {
                        ?>
                        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                            <div class="card">
                                <div class="header">
                                    <h2><?=$fetchVideos['name']?></h2>
                                </div>
                                <div class="body">
                                    <video id="video_<?=$index?>" class="video-js vjs-default-skin vjs-16-9" width="100%" height="480"></video>
                                </div>
                                <div id="overlay_<?=$index?>" style="display:none">
                                    <div class="overlay-content">
                                        <p class="text"></p>
                                        <a class="continue-button">Continue</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        $index++;
                        }
                        ?>
                    </div>
                </div>
            </div>
        </section>
        <?php } else if ($pageRequest == 'add-user') { ?>
        <section class="content">
            <div class="container-fluid">
                <div class="row clearfix">
                    <div class="row clearfix">

                    </div>
                </div>
            </div>
        </section>
        <?php } else if ($pageRequest == 'upload-video') { ?>
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
        <?php } ?>
    <?php } ?>
        <script src="plugins/jquery/jquery.min.js"></script>
        <script src="plugins/bootstrap/js/bootstrap.min.js"></script>
        <script src="plugins/jquery-slimscroll/jquery.slimscroll.js"></script>
        <script src="plugins/node-waves/waves.min.js"></script>
        <script src="plugins/jquery-inputmask/jquery.inputmask.bundle.min.js"></script>
        <script src="js/main.js"></script>
        <?php if ($pageRequest == 'videos') { ?>
        <link href="https://vjs.zencdn.net/7.10.2/video-js.css" rel="stylesheet"/>
        <script src="https://vjs.zencdn.net/7.10.2/video.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/videojs-markers@1.0.1/dist/videojs-markers.min.js"></script>
            <?php
            $getVideosQuery = $DB_con->prepare("SELECT * FROM videos");
            $getVideosQuery->execute();
            $index = 0;
            while ($fetchVideos = $getVideosQuery->fetch(PDO::FETCH_ASSOC)) {
                $getVideoNotes = $DB_con->prepare('SELECT * FROM video_notes WHERE videoId = :videoId');
                $getVideoNotes->execute(array(':videoId' => $fetchVideos['id']));
            ?>
            <script>
                var player_<?=$index?> = videojs('video_<?=$index?>', {
                    controls: true,
                    controlBar: {
                        'pictureInPictureToggle': false
                    }
                });
                player_<?=$index?>.src({src: '<?=$app['videoDirectory'].$fetchVideos['fileName']?>', type: '<?=$fetchVideos['format']?>'});
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
                    },
                    onMarkerReached: function(marker) {
                        player_<?=$index?>.pause();
                        let currentTime = player_<?=$index?>.currentTime();
                        setTimeout(function () {
                            if (currentTime === player_<?=$index?>.currentTime()) {
                                $('#overlay_<?=$index?> .text').text(marker.text);
                                $('#overlay_<?=$index?>').show();
                            }
                        }, 200);
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
        ?>
        <?php
        include_once 'pageJS.php';
        ?>
    </body>
</html>
