<?php if ($pageRequest && $pageRequest == 'upload-video') { ?>
<script src="plugins/bootstrap-notify/bootstrap-notify.min.js"></script>
<script type="text/javascript">
function showNotification(text) {
    $.notify({
            message: text
        },
        {
            type: 'bg-green',
            allow_dismiss: true,
            newest_on_top: true,
            timer: 1000,
            placement: {
                from: 'bottom',
                align: 'right'
            },
            animate: {
                enter: 'animated bounceInUp',
                exit: 'animated bounceOutUp'
            },
            template: '<div data-notify="container" class="bootstrap-notify-container alert alert-dismissible bg-green p-r-35" role="alert">' +
                '<button type="button" aria-hidden="true" class="close" data-notify="dismiss">×</button>' +
                '<span data-notify="message">'+text+'</span>' +
                '</div>'
        });
}
$(function () {
    var reader = {};
    var file = {};
    var sliceSize = 1000 * 1024;
    var greatestMinute = 0;

    $('#videoUploadForm #video').on('change', function() {
        var videoSource = $('#videoUploadForm #videoPreview');
        if (this.files.length) {
            videoSource[0].src = URL.createObjectURL(this.files[0]);
            videoSource.parent()[0].load();
            document.getElementById('videoPreviewElement').addEventListener('loadedmetadata', function(e) {
                $('#videoUploadForm #videoDuration').val(Math.floor(e.target.duration));
            });
            $('#videoUploadForm #videoPreviewContent').show();
            $('#videoUploadForm #videoNotes').show();
        } else {
            $('#videoUploadForm #videoDuration').val(undefined);
            $('#videoUploadForm #videoPreviewContent').hide();
            $('#videoUploadForm #videoNotes').hide();
        }
        $('#videoNotesTableBody').html('');
    });
    $('#videoUploadForm #addNewNoteButton').on('click', function() {
        var noteIndex = $('#videoNotesTableBody tr').length;
        var duration = parseInt($('#videoUploadForm #videoDuration').val());
        var minutes = `<option value="0">0</option>`;
        var minutesArray = [0];
        greatestMinute = 0;
        for (var i = 1; i <= Math.floor(duration / 60); i++) {
            minutes += '<option value="' + i + '">' + i + '</option>';
            minutesArray.push(i);
        }
        greatestMinute = Math.max(...minutesArray);
        var remainingSeconds = duration - (greatestMinute * 60);
        var seconds = '';
        for (var i = 0; i <= (greatestMinute > 0 ? 59 : remainingSeconds); i++) {
            seconds += '<option value="' + i + '">' + i + '</option>';
        }
        var trContent = `
        <tr id="row${noteIndex}">
            <td>
                <select class="form-control minute">
                    ${minutes}
                </select>
            </td>
            <td>
                <select class="form-control second">
                    ${seconds}
                </select>
            </td>
            <td>
                <textarea class="form-control note" rows="3"></textarea>
            </td>
            <td>
                <button type="button" id="${noteIndex}" class="btn btn-danger btn-xs waves-effect deleteNoteButton">
                    <i class="material-icons">delete</i>
                </button>
            </td>
        </tr>
        `;
        $('#videoUploadForm #videoNotesTableBody').append(trContent);
        if ($(this).attr('data-set-time') === 'true') {
            var lastNote = $('#videoUploadForm #videoNotesTableBody tr:last');
            lastNote.fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100);
            var previewVideo = document.getElementById("videoPreviewElement");
            previewVideo.pause();
            var currentTime = new Date(previewVideo.currentTime * 1000).toISOString().substr(14, 5).split(':');
            lastNote.find('select.minute').val(currentTime[0].charAt(0) === '0' ? currentTime[0].charAt(1) : currentTime[0]);
            lastNote.find('select.second').val(currentTime[1].charAt(0) === '0' ? currentTime[1].charAt(1) : currentTime[1]);
            lastNote.find('textarea.note').focus();
        }
    });

    $('#videoUploadForm #setThumbnail').on('click', function() {
        var previewVideo = document.getElementById("videoPreviewElement");
        var currentTime = previewVideo.currentTime;
        $('#videoUploadForm #thumbnailSecond').val(currentTime);
        showNotification('Video thumbnail time changed to: ' + currentTime);
    });

    $('body').on('change', '#videoNotesTableBody .minute', function () {
        var seconds = '';
        var duration = parseInt($('#videoUploadForm #videoDuration').val());
        var remainingSeconds = duration - (greatestMinute * 60);
        for (var i = 0; i <= ($(this).val() == greatestMinute ? remainingSeconds : greatestMinute > 0 ? 59 : remainingSeconds); i++) {
            seconds += '<option value="' + i + '">' + i + '</option>';
        }
        $(this).closest('tr').find('.second').html(seconds);
    });

    $('body').on('click', '.deleteNoteButton', function () {
        var buttonId = $(this).attr("id");
        $('#row'+buttonId+'').remove();
    });

    function startUpload(event) {
        event.preventDefault();
        reader = new FileReader();
        file = document.querySelector( '#video' ).files[0];
        var name = $('#videoUploadForm #name').val();
        if (!file) {
            $('#result').html('<div class="alert alert-danger m-b-0">Choose a video.</div>');
            return false;
        }
        if (file && file.type !== 'video/mp4') {
            $('#result').html('<div class="alert alert-danger m-b-0">Only mp4 video can be uploaded.</div>');
            return false;
        }
        if (!name) {
            $('#result').html('<div class="alert alert-danger m-b-0">A name must be specified for the video.</div>');
            return false;
        }
        var noteDurationsValid = true;
        var noteNoteValid = true;
        var durations = [];
        $.each($('#videoNotesTableBody tr'), function(_, value) {
            var row = $(value);
            var duration = row.find('select.minute').val() + ':' + row.find('select.second').val();
            if ($.inArray(duration, durations) !== -1) {
                noteDurationsValid = false;
            }
            durations.push(duration);
            if (!row.find('textarea.note').val()) {
                noteNoteValid = false;
            }
        });
        if (!noteDurationsValid) {
            $('#result').html('<div class="alert alert-danger m-b-0">There are repetitive periods.</div>');
            return false;
        }
        if (!noteNoteValid) {
            $('#result').html('<div class="alert alert-danger m-b-0">There is a blank note.</div>');
            return false;
        }
        uploadFile(0, name, '', $('#videoUploadForm #videoDuration').val(), $('#videoUploadForm #thumbnailSecond').val());
    }

    $('#uploadVideoButton').on( 'click', startUpload );

    function uploadFile(start, name, videoFileName, duration, thumbnailSecond) {
        var nextSlice = start + sliceSize + 1;
        var blob = file.slice(start, nextSlice);

        reader.onloadend = function(event) {
            if (event.target.readyState !== FileReader.DONE) {
                return;
            }
            $("#videoUploadForm :input").attr("disabled", true);
            $.ajax( {
                url: 'upload-video-a',
                type: 'POST',
                dataType: 'json',
                cache: false,
                data: {
                    fileData: event.target.result,
                    file: file.name,
                    fileType: file.type,
                    isFirstChunk: start === 0,
                    name: name,
                    videoFileName: videoFileName,
                    duration: duration
                },
                headers : {
                    'csrftoken': $('meta[name="csrf-token"]').attr('content')
                },
                error: function() {
                    $('#result').html('<div class="alert alert-danger m-b-0">An error occurred.</div>');
                    $("#videoUploadForm :input").attr("disabled", false);
                },
                success: function(data) {
                    if (data.success) {
                        var sizeDone = start + sliceSize;
                        var percentDone = Math.floor((sizeDone / file.size) * 100);
                        if (nextSlice < file.size) {
                            $('#result').html('<div class="progress"><div class="progress-bar bg-<?=$app["themeColor"]?> progress-bar-striped active" role="progressbar" aria-valuenow="'+percentDone+'" aria-valuemin="0" aria-valuemax="100" style="width: '+percentDone+'%">Uploading '+percentDone+'%</div></div>');
                            uploadFile(nextSlice, name, data.videoFileName, duration, thumbnailSecond);
                        } else {
                            var notes = $.map($('#videoNotesTableBody tr'), function(value) {
                                var row = $(value);
                                var minute = row.find('select.minute').val();
                                var second = row.find('select.second').val();
                                var note = row.find('textarea.note').val();
                                return {minute: minute, second: second, note: note};
                            });
                            $.ajax({
                                url: "add-video-a",
                                type: "POST",
                                dataType: 'json',
                                cache: false,
                                headers : {
                                    'csrftoken': $('meta[name="csrf-token"]').attr('content')
                                },
                                data: {
                                    name: name,
                                    videoFileName: data.videoFileName,
                                    fileType: file.type,
                                    duration: duration,
                                    notes: notes,
                                    thumbnailSecond: thumbnailSecond,
                                },
                                success: function(data)
                                {
                                    if (data && data.success) {
                                        $('#result').html('<div class="alert alert-success m-b-0">Video successfully uploaded.</div>');
                                        $("#videoUploadForm").trigger('reset');
                                        $('#videoUploadForm #videoDuration').val(undefined);
                                        $('#videoUploadForm #videoPreviewContent').hide();
                                        $('#videoUploadForm #videoNotes').hide();
                                        $('#videoNotesTableBody').html('');
                                        setTimeout(function() {
                                            window.location.href = 'edit-video-' + data.videoId;
                                        }, 1000);
                                    } else {
                                        $('#result').html('<div class="alert alert-danger m-b-0">An error occurred.</div>');
                                    }
                                    $("#videoUploadForm :input").attr("disabled", false);
                                }
                            });
                        }
                    } else {
                        $('#result').html('<div class="alert alert-danger m-b-0">' + data.message + '</div>');
                        $("#videoUploadForm :input").attr("disabled", false);
                    }
                }
            } );
        };
        reader.readAsDataURL(blob);
    }
});
</script>
<?php } else if ($pageRequest && $pageRequest == 'home') { ?>
<link rel="stylesheet" type="text/css" href="plugins/sweetalert/css/sweetalert.css">
<script src="plugins/sweetalert/js/sweetalert.min.js"></script>
<link href="https://vjs.zencdn.net/7.10.2/video-js.css" rel="stylesheet"/>
<script src="https://vjs.zencdn.net/7.10.2/video.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/videojs-markers@1.0.1/dist/videojs-markers.min.js"></script>
<script>
    getVideos();
    function getVideos() {
        $('.page-loader-wrapper').show();
        $.ajax({
            url: "get-videos-a",
            type: "GET",
            contentType: false,
            cache: false,
            processData: false,
            headers : {
                'csrftoken': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (data) {
                $("#videosContainer").html(data);
                $('.page-loader-wrapper').fadeOut();
            }
        });
    }
    $(document).on('click','.deleteButton',function(e) {
        var videoId = $(this).data('video-id');
        e.preventDefault();
        swal({
            title: "Warning!",
            text: "Are you sure you want to delete the video?",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it.",
            cancelButtonText: "Cancel",
            closeOnConfirm: false,
            closeOnCancel: false,
            showLoaderOnConfirm: true,
        },
        function(isConfirm)
        {
            if (isConfirm)
            {
                setTimeout(function()
                {
                    $.ajax({
                        type:'POST',
                        url:'delete-video-a',
                        data:'id=' + videoId,
                        dataType: 'json',
                        headers : {
                            'csrftoken': $('meta[name="csrf-token"]').attr('content')
                        },
                        success:function(data)
                        {
                            if(data.success)
                            {
                                swal({
                                    title: "Successful!",
                                    text: "The video was deleted successfully.",
                                    type: "success",
                                    confirmButtonText: "OK",
                                    closeOnConfirm: true
                                });
                                getVideos();
                            }
                            else
                            {
                                swal({
                                    title: "Error!",
                                    text: "Something went wrong. Please try again.",
                                    type: "error",
                                    confirmButtonText: "OK",
                                    closeOnConfirm: true
                                });
                            }
                        }
                    });
                }, 1000);
            }
            else
            {
                swal({
                    title: "Canceled!",
                    text: "Your request to delete the video has been canceled.",
                    type: "error",
                    confirmButtonText: "OK",
                    closeOnConfirm: true
                });
            }
        });
    });
</script>
<?php } else if ($pageRequest && $pageRequest == 'users') { ?>
<link rel="stylesheet" type="text/css" href="plugins/sweetalert/css/sweetalert.css">
<script src="plugins/sweetalert/js/sweetalert.min.js"></script>
<script>
    getUsers();
    function getUsers() {
        $('.page-loader-wrapper').show();
        $.ajax({
            url: "get-users-a",
            type: "GET",
            contentType: false,
            cache: false,
            processData: false,
            headers : {
                'csrftoken': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (data) {
                $("#usersContainer").html(data);
                $('.page-loader-wrapper').fadeOut();
            }
        });
    }
    $(document).on('click','.deleteButton',function(e) {
        var userId = $(this).data('user-id');
        e.preventDefault();
        swal({
            title: "Warning!",
            text: "Are you sure you want to delete the user?",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it.",
            cancelButtonText: "Cancel",
            closeOnConfirm: false,
            closeOnCancel: false,
            showLoaderOnConfirm: true,
        },
        function(isConfirm)
        {
            if (isConfirm)
            {
                setTimeout(function()
                {
                    $.ajax({
                        type:'POST',
                        url:'delete-user-a',
                        data:'id=' + userId,
                        dataType: 'json',
                        headers : {
                            'csrftoken': $('meta[name="csrf-token"]').attr('content')
                        },
                        success:function(data)
                        {
                            if(data.success)
                            {
                                swal({
                                    title: "Successful!",
                                    text: "The user was deleted successfully.",
                                    type: "success",
                                    confirmButtonText: "OK",
                                    closeOnConfirm: true
                                });
                                getUsers();
                            }
                            else
                            {
                                swal({
                                    title: "Error!",
                                    text: "Something went wrong. Please try again.",
                                    type: "error",
                                    confirmButtonText: "OK",
                                    closeOnConfirm: true
                                });
                            }
                        }
                    });
                }, 1000);
            }
            else
            {
                swal({
                    title: "Canceled!",
                    text: "Your request to delete the user has been canceled.",
                    type: "error",
                    confirmButtonText: "OK",
                    closeOnConfirm: true
                });
            }
        });
    });
</script>
<?php } else if ($pageRequest && $pageRequest == 'edit-user') { ?>
<script src="plugins/bootstrap-notify/bootstrap-notify.min.js"></script>
<script>
    function toggleVisibility(_this, input) {
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            $(_this).text('visibility_off');
        } else {
            input.attr('type', 'password');
            $(_this).text('visibility');
        }
    }
    function showNotification(text) {
        $.notify({
                message: text
            },
            {
                type: 'bg-green',
                allow_dismiss: true,
                newest_on_top: true,
                timer: 1000,
                placement: {
                    from: 'bottom',
                    align: 'right'
                },
                animate: {
                    enter: 'animated bounceInUp',
                    exit: 'animated bounceOutUp'
                },
                template: '<div data-notify="container" class="bootstrap-notify-container alert alert-dismissible bg-green p-r-35" role="alert">' +
                    '<button type="button" aria-hidden="true" class="close" data-notify="dismiss">×</button>' +
                    '<span data-notify="message">'+text+'</span>' +
                    '</div>'
            });
    }
    function generatePassword() {
        var length = 8,
            charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
            retVal = "";
        for (var i = 0, n = charset.length; i < length; ++i) {
            retVal += charset.charAt(Math.floor(Math.random() * n));
        }
        return retVal;
    }
    $("#generatePassword").on('click', function(e) {
        var generatedPassword = generatePassword();
        $('#password').val(generatedPassword);
        $('#passwordVerify').val(generatedPassword);
        // var password = document.getElementById("password");
        // var inputTypeChanged = false;
        // if (password.type === 'password') {
        //     password.type = 'text';
        //     inputTypeChanged = true;
        // }
        // password.select();
        // password.setSelectionRange(0, 99999);
        // document.execCommand("copy");
        // if (inputTypeChanged) {
        //     password.type = 'password';
        // }
        showNotification('Password generated.');
    });
    $("#passwordVisibilityChanger").on('click', function(e) {
        toggleVisibility(this, $('#password'));
    });
    $("#passwordVerifyVisibilityChanger").on('click', function(e) {
        toggleVisibility(this, $('#passwordVerify'));
    });
    $("#editUserForm").on('submit',(function(e)
    {
        e.preventDefault();

        $("#result").empty();

        $('#editUserButton').prop('disabled', true);
        $('#editUserButton').html("Editing...");


        var password = $("#password").val();
        var passwordVerify = $("#passwordVerify").val();

        if(password.length > 0)
        {
            if(password.length < 6 || password.length > 29)
            {
                $("#result").html("<div class='alert alert-danger'>User new password can consist of a minimum of 6 characters and a maximum of 30 characters. Please try again.</div>");
                $('#editUserButton').prop('disabled', false);
                $('#editUserButton').html("Edit User");
                $("#password").val("");
                $("#passwordVerify").val("");
                return false;
            }
        }
        if(password.length > 0 && passwordVerify.length > 0)
        {
            if (password != passwordVerify)
            {
                $("#result").html("<div class='alert alert-danger'>Passwords do not match.</div>");
                $('#editUserButton').prop('disabled', false);
                $('#editUserButton').html("Edit User");
                $("#password").val("");
                $("#passwordVerify").val("");
                return false;
            }
        }

        $.ajax({
            url: "edit-user-a",
            type: "POST",
            data:  new FormData(this),
            dataType: 'json',
            contentType: false,
            cache: false,
            processData:false,
            headers : {
                'csrftoken': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data)
            {
                if(data.success)
                {
                    $("#result").html("<div class='alert alert-success'>User successfully edited.</div>");
                    $("#password").val("");
                    $("#passwordVerify").val("");
                } else {
                    $("#result").html("<div class='alert alert-danger'>" + data.message + "</div>");
                }
                $('#editUserButton').prop('disabled', false);
                $('#editUserButton').html("Edit User");
            }
        });
    }));
</script>
<?php } else if ($pageRequest && $pageRequest == 'add-user') { ?>
<script src="plugins/bootstrap-notify/bootstrap-notify.min.js"></script>
<script>
    function toggleVisibility(_this, input) {
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            $(_this).text('visibility_off');
        } else {
            input.attr('type', 'password');
            $(_this).text('visibility');
        }
    }
    function showNotification(text) {
        $.notify({
                message: text
            },
            {
                type: 'bg-green',
                allow_dismiss: true,
                newest_on_top: true,
                timer: 1000,
                placement: {
                    from: 'bottom',
                    align: 'right'
                },
                animate: {
                    enter: 'animated bounceInUp',
                    exit: 'animated bounceOutUp'
                },
                template: '<div data-notify="container" class="bootstrap-notify-container alert alert-dismissible bg-green p-r-35" role="alert">' +
                    '<button type="button" aria-hidden="true" class="close" data-notify="dismiss">×</button>' +
                    '<span data-notify="message">'+text+'</span>' +
                    '</div>'
            });
    }
    function generatePassword() {
        var length = 8,
            charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
            retVal = "";
        for (var i = 0, n = charset.length; i < length; ++i) {
            retVal += charset.charAt(Math.floor(Math.random() * n));
        }
        return retVal;
    }
    $("#generatePassword").on('click', function(e) {
        var generatedPassword = generatePassword();
        $('#password').val(generatedPassword);
        $('#passwordVerify').val(generatedPassword);
        // var password = document.getElementById("password");
        // var inputTypeChanged = false;
        // if (password.type === 'password') {
        //     password.type = 'text';
        //     inputTypeChanged = true;
        // }
        // password.select();
        // password.setSelectionRange(0, 99999);
        // document.execCommand("copy");
        // if (inputTypeChanged) {
        //     password.type = 'password';
        // }
        showNotification('Password generated.');
    });
    $("#passwordVisibilityChanger").on('click', function(e) {
        toggleVisibility(this, $('#password'));
    });
    $("#passwordVerifyVisibilityChanger").on('click', function(e) {
        toggleVisibility(this, $('#passwordVerify'));
    });
    $("#addUserForm").on('submit',(function(e)
    {
        e.preventDefault();

        $("#result").empty();

        $('#addUserButton').prop('disabled', true);
        $('#addUserButton').html("Adding...");


        var password = $("#password").val();
        var passwordVerify = $("#passwordVerify").val();

        if(password.length > 0)
        {
            if(password.length < 6 || password.length > 29)
            {
                $("#result").html("<div class='alert alert-danger'>User password can consist of a minimum of 6 characters and a maximum of 30 characters. Please try again.</div>");
                $('#addUserButton').prop('disabled', false);
                $('#addUserButton').html("Add User");
                $("#password").val("");
                $("#passwordVerify").val("");
                return false;
            }
        }
        if(password.length > 0 && passwordVerify.length > 0)
        {
            if (password != passwordVerify)
            {
                $("#result").html("<div class='alert alert-danger'>Passwords do not match.</div>");
                $('#addUserButton').prop('disabled', false);
                $('#addUserButton').html("Add User");
                $("#password").val("");
                $("#passwordVerify").val("");
                return false;
            }
        }

        $.ajax({
            url: "add-user-a",
            type: "POST",
            data:  new FormData(this),
            dataType: 'json',
            contentType: false,
            cache: false,
            processData:false,
            headers : {
                'csrftoken': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data)
            {
                if(data.success)
                {
                    $("#result").html("<div class='alert alert-success'>User successfully added.</div>");
                    $("#addUserForm").trigger('reset');
                } else {
                    $("#result").html("<div class='alert alert-danger'>" + data.message + "</div>");
                }
                $('#addUserButton').prop('disabled', false);
                $('#addUserButton').html("Add User");
            }
        });
    }));
</script>
<?php } else if ($pageRequest && $pageRequest == 'edit-video') { ?>
<script src="plugins/bootstrap-notify/bootstrap-notify.min.js"></script>
<script type="text/javascript">
    function showNotification(text) {
        $.notify({
                message: text
            },
            {
                type: 'bg-green',
                allow_dismiss: true,
                newest_on_top: true,
                timer: 1000,
                placement: {
                    from: 'bottom',
                    align: 'right'
                },
                animate: {
                    enter: 'animated bounceInUp',
                    exit: 'animated bounceOutUp'
                },
                template: '<div data-notify="container" class="bootstrap-notify-container alert alert-dismissible bg-green p-r-35" role="alert">' +
                    '<button type="button" aria-hidden="true" class="close" data-notify="dismiss">×</button>' +
                    '<span data-notify="message">'+text+'</span>' +
                    '</div>'
            });
    }
    $('#editVideoForm #addNewNoteButton').on('click', function() {
        var noteIndex = $('#videoNotesTableBody tr').length + 1;
        var duration = parseInt($('#editVideoForm #videoDuration').val());
        var minutes = `<option value="0">0</option>`;
        var minutesArray = [0];
        greatestMinute = 0;
        for (var i = 1; i <= Math.floor(duration / 60); i++) {
            minutes += '<option value="' + i + '">' + i + '</option>';
            minutesArray.push(i);
        }
        greatestMinute = Math.max(...minutesArray);
        var remainingSeconds = duration - (greatestMinute * 60);
        var seconds = '';
        for (var i = 0; i <= (greatestMinute > 0 ? 59 : remainingSeconds); i++) {
            seconds += '<option value="' + i + '">' + i + '</option>';
        }
        var trContent = `
        <tr id="row${noteIndex}">
            <td>
                <select class="form-control minute">
                    ${minutes}
                </select>
            </td>
            <td>
                <select class="form-control second">
                    ${seconds}
                </select>
            </td>
            <td>
                <textarea class="form-control note" rows="3"></textarea>
            </td>
            <td>
                <button type="button" id="${noteIndex}" class="btn btn-danger btn-xs waves-effect deleteNoteButton">
                    <i class="material-icons">delete</i>
                </button>
            </td>
        </tr>
        `;
        $('#editVideoForm #videoNotesTableBody').append(trContent);
        if ($(this).attr('data-set-time') === 'true') {
            var lastNote = $('#editVideoForm #videoNotesTableBody tr:last');
            lastNote.fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100);
            var previewVideo = document.getElementById("videoPreviewElement");
            previewVideo.pause();
            var currentTime = new Date(previewVideo.currentTime * 1000).toISOString().substr(14, 5).split(':');
            lastNote.find('select.minute').val(currentTime[0].charAt(0) === '0' ? currentTime[0].charAt(1) : currentTime[0]);
            lastNote.find('select.second').val(currentTime[1].charAt(0) === '0' ? currentTime[1].charAt(1) : currentTime[1]);
            lastNote.find('textarea.note').focus();
        }
    });

    $('#editVideoForm #setThumbnail').on('click', function() {
        var previewVideo = document.getElementById("videoPreviewElement");
        var currentTime = previewVideo.currentTime;
        $('#editVideoForm #thumbnailSecond').val(currentTime);
        showNotification('Video thumbnail time changed to: ' + currentTime);
    });

    $('body').on('change', '#videoNotesTableBody .minute', function () {
        var seconds = '';
        var duration = parseInt($('#editVideoForm #videoDuration').val());
        var remainingSeconds = duration - (greatestMinute * 60);
        for (var i = 0; i <= ($(this).val() == greatestMinute ? remainingSeconds : greatestMinute > 0 ? 59 : remainingSeconds); i++) {
            seconds += '<option value="' + i + '">' + i + '</option>';
        }
        $(this).closest('tr').find('.second').html(seconds);
    });

    $('body').on('click', '.deleteNoteButton', function () {
        var parentTr = $(this).closest('tr');
        var rowId = parentTr.attr('id');
        var id = parentTr.attr('data-id');
        if (id) {
            parentTr.attr('data-id', parseInt(id) * -1);
            $('#' + rowId).hide();
        } else {
            $('#' + rowId).remove();
        }
    });

    $("#editVideoForm").on('submit',(function(e)
    {
        e.preventDefault();

        var name = $('#editVideoForm #name').val();

        if (!name) {
            $('#result').html('<div class="alert alert-danger m-b-0">A name must be specified for the video.</div>');
            return false;
        }
        var noteDurationsValid = true;
        var noteNoteValid = true;
        var durations = [];
        $.each($('#videoNotesTableBody tr:visible'), function(_, value) {
            var row = $(value);
            var duration = row.find('select.minute').val() + ':' + row.find('select.second').val();
            if ($.inArray(duration, durations) !== -1) {
                noteDurationsValid = false;
            }
            durations.push(duration);
            if (!row.find('textarea.note').val()) {
                noteNoteValid = false;
            }
        });
        if (!noteDurationsValid) {
            $('#result').html('<div class="alert alert-danger m-b-0">There are repetitive periods.</div>');
            return false;
        }
        if (!noteNoteValid) {
            $('#result').html('<div class="alert alert-danger m-b-0">There is a blank note.</div>');
            return false;
        }

        var formData = {
            id: $('#editVideoForm #videoId').val(),
            name: name,
            thumbnailSecond: $('#editVideoForm #thumbnailSecond').val(),
            notes: $.map($('#videoNotesTableBody tr'), function(value) {
                var row = $(value);
                var minute = row.find('select.minute').val();
                var second = row.find('select.second').val();
                var note = row.find('textarea.note').val();
                var id = row.attr('data-id');
                return {
                    minute: minute,
                    second: second,
                    note: note,
                    id: id ? id : 0
                };
            })
        };

        $("#result").empty();

        $("#editVideoForm :input").attr("disabled", true);
        $('#editVideoButton').html("Editing...");

        $.ajax({
            url: "edit-video-a",
            type: "POST",
            data:  formData,
            dataType: 'json',
            cache: false,
            headers : {
                'csrftoken': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data)
            {
                if(data.success)
                {
                    $("#result").html("<div class='alert alert-success'>Video successfully edited.</div>");
                } else {
                    $("#result").html("<div class='alert alert-danger'>" + data.message + "</div>");
                }
                $("#editVideoForm :input").attr("disabled", false);
                $('#editVideoButton').html("Edit Video");
            }
        });
    }));
</script>
<?php } ?>