<?php if ($pageRequest && $pageRequest == 'upload-video') { ?>
<script type="text/javascript">
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
        uploadFile(0, name, '', $('#videoUploadForm #videoDuration').val());
    }

    $('#uploadVideoButton').on( 'click', startUpload );

    function uploadFile(start, name, videoFileName, duration) {
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
                            uploadFile(nextSlice, name, data.videoFileName, duration);
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
                                    notes: notes
                                },
                                success: function(data)
                                {
                                    if (data === 1) {
                                        $('#result').html('<div class="alert alert-success m-b-0">Video successfully uploaded.</div>');
                                        $("#videoUploadForm").trigger('reset');
                                        $('#videoUploadForm #videoDuration').val(undefined);
                                        $('#videoUploadForm #videoPreviewContent').hide();
                                        $('#videoUploadForm #videoNotes').hide();
                                        $('#videoNotesTableBody').html('');
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
<?php } ?>