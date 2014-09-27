/**
 * Bind the file upload when editing content, so it works and stuff
 *
 * @param {string} key
 */
function bindFileUpload(key) {
    // Since jQuery File Upload's 'paramName' option seems to be ignored,
    // it requires the name of the upload input to be "images[]". Which clashes
    // with the non-fancy fallback, so we hackishly set it here. :-/
    $('#fileupload-' + key)
        .fileupload({
            dataType: 'json',
            dropZone: $('#dropzone-' + key),
            done: function (e, data) {
                $.each(data.result, function (index, file) {
                    if (file.error === undefined) {
                        var filename = decodeURI(file.url).replace("files/", "");
                        $('#field-' + key).val(filename);
                        $('#thumbnail-' + key).html("<img src='" + path + "../thumbs/200x150c/" + encodeURI(filename) + "' width='200' height='150'>");
                        window.setTimeout(function () { $('#progress-' + key).fadeOut('slow'); }, 1500);

                        // Add the uploaded file to our stack.
                        stack.addToStack(filename);

                    } else {
                        var message = "Oops! There was an error uploading the file. Make sure the file is not corrupt, and that the 'files/'-folder is writable."
                            + "\n\n(error was: "
                            + file.error + ")";

                        alert(message);
                        window.setTimeout(function () { $('#progress-' + key).fadeOut('slow'); }, 50);
                    }
                    $('#progress-' + key + ' div.bar').css('width', "100%");
                    $('#progress-' + key).removeClass('progress-striped active');
                });
            }
        })
        .bind('fileuploadprogress', function (e, data) {
            var progress = Math.round(100 * data._bitrateTimer.loaded / data.files[0].size);
            $('#progress-' + key).show().addClass('progress-striped active');
            $('#progress-' + key + ' div.bar').css('width', progress + "%");
        });
}
