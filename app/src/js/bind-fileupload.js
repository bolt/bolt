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
                    var filename, message;

                    if (file.error === undefined) {
                        filename = decodeURI(file.url).replace('files/', '');
                        $('#field-' + key).val(filename);
                        $('#thumbnail-' + key).html('<img src="' + Bolt.conf('paths.root') + 'thumbs/200x150c/' +
                            encodeURI(filename) + '" width="200" height="150">');
                        window.setTimeout(function () { $('#progress-' + key).fadeOut('slow'); }, 1500);

                        // Add the uploaded file to our stack.
                        Bolt.stack.addToStack(filename);

                    } else {
                        message = "Oops! There was an error uploading the file. Make sure the file is not " +
                            "corrupt, and that the 'files/'-folder is writable." +
                            "\n\n(error was: " + file.error + ")";

                        alert(message);
                        window.setTimeout(function () { $('#progress-' + key).fadeOut('slow'); }, 50);
                    }
                    $('#progress-' + key + ' div.bar').css('width', "100%");
                    $('#progress-' + key).removeClass('progress-striped active');
                });
            },
            add: bindFileUpload.checkFileSize
        })
        .bind('fileuploadprogress', function (e, data) {
            var progress = Math.round(100 * data.loaded / data.total);

            $('#progress-' + key).show().addClass('progress-striped active');
            $('#progress-' + key + ' div.progress-bar').css('width', progress + "%");
        });
}

bindFileUpload.checkFileSize = function checkFileSize (event, data) {
    // The jQuery upload doesn't expose an API to cover an entire upload set. So we keep "bad" files
    // in the data.originalFiles, which is the same between multiple files in one upload set.
    var badFiles = [];

    if (typeof data.originalFiles.bad === 'undefined') {
        data.originalFiles.bad = [];
    }

    _.each(data.files, function (file, index) {
        if ((file.size || 0) > Bolt.conf('uploadConfig.maxSize') && Bolt.conf('uploadConfig.maxSize') > 0) {
            badFiles.push(file.name);
            data.originalFiles.bad.push(file.name);
        }
    });

    if (data.originalFiles.bad.length > 0) {
        var filename1 = data.files[data.files.length - 1].name;
        var filename2 = data.originalFiles[data.originalFiles.length - 1].name;

        if (filename1 === filename2) {
            // We're at the end of this upload cycle
            var message = 'One or more of the files that you selected was larger than the max size of ' +
                Bolt.conf('uploadConfig.maxSizeNice') + ":\n\n" +
                data.originalFiles.bad.join("\n");

            alert(message);
        }
    }

    if (badFiles.length === 0) {
        data.submit();
    }
};
