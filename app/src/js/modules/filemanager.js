/**
 * Enhances file manager with search, multiselect and drag-and-drop magic
 *
 * @mixin
 * @namespace Bolt.filemanager
 *
 * @param {Object} bolt - The Bolt module
 * @param {Object} $ - jQuery
 * @param {Object} Dropzone
 *
 * @author Len van Essen
 */
(function (bolt, $, Dropzone) {
    'use strict';

    /**
     * Bolt.filemanager mixin container.
     *
     * @private
     * @type {Object}
     */
    var filemanager = {};

    /**
     * Pre-processes and regex string to escape characters
     *
     * @function escapeRegExp
     * @param str
     */
    function escapeRegExp(str) {
        return str.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
    }

    /**
     * Returns matches wrapped in <mark> tags
     * @param str
     * @param find
     */
    function markAll(str, find) {
        var replace = '<mark>' + find + '</mark>';
        return str.replace(new RegExp(escapeRegExp(find), 'g'), replace);
    }

    /**
     * Searches the filemanagers list and marks matching words
     *
     * TODO since the filenames are capped at 80chars, this possibly does not match when you search for a full filename longer than 80chars
     * @function searchFiles
     * @memberof Bolt.filemanager
     */
    function searchFiles() {
        var term = $(this).val();

        $('.dashboardlisting').find('[data-bolt-browse-name]').each(function () {
            var name = $(this).data('bolt-browse-name');
            var hide = name.search(term) < 0;

            $(this).toggleClass('hidden', hide);

            if (!hide) {
                $(this).find('.name').each(function () {

                    $(this).html( markAll(name, term) );

                    // Removes marks if search term is empty
                    if(term.length === 0) {
                        $(this).html(name);
                    }
                });
            }

        });
    }

    /**
     * Lazy loads images to increase performance
     *
     * @function lazyLoadImages
     */
    function lazyLoadImages() {
        var images = document.querySelectorAll('img[data-src]');

        for (var i = 0; i < images.length; i++) {
            if (images[i].getAttribute('data-src')) {
                images[i].setAttribute('src', images[i].getAttribute('data-src'));
                images[i].removeAttribute('data-src');
            }
        }
    }

    /**
     * Binds internal functions to eventListeners
     *
     * @function setupEventHandlers
     */
    function addEventListeners() {

        // Input event
        document.getElementById('file-manager-search').addEventListener('keyup', searchFiles);

        // Select file
        $('#file_upload_select').addClass('btn-secondary').bootstrapFileInput();
        document.getElementById('file_upload_select').addEventListener('change', function () {
            document.getElementById('file_upload_upload').removeAttribute('disabled');
        });

        // Lazy load images
        window.addEventListener('load', lazyLoadImages);
    }

    /**
     * Native dragleave event gets fired every mousemove
     * This function prevents the flickering of the hint
     * and also binds this to the drop event
     *
     * @function handleDropHint
     * @param target Dropzone
     */
    function dragOver(target) {
        var elm = $('body');
        var counter = 0;

        elm.bind({
            dragenter: function (ev) {
                ev.preventDefault(); // needed for IE
                counter++;
                $(this).addClass('dropzone-active');
            },

            dragleave: function () {
                counter--;
                if (counter === 0) {
                    $(this).removeClass('dropzone-active');
                }
            }
        });

        // Bind to drop-event
        target.on('drop', function () {
            elm.removeClass('dropzone-active');
        });
    }

    /**
     * Initializes dropzone and handles the request
     *
     * @function bindDropzone
     */
    function bindDropzone() {
        var csrf = $('form[name="file_upload"]').find('#file_upload__token').val();

        var DropzoneTarget = new Dropzone(document.body, {
            url: bolt.conf('uploadConfig.url'),
            previewsContainer: "#dropzone-preview",
            acceptedFiles: '.' + bolt.conf('uploadConfig.acceptFileTypes').join(',.'),
            uploadMultiple: 'true',
            enqueueForUpload: 'true',
            hiddenInputContainer: '.form-horizontal',
            paramName: 'file_upload[select]',
            clickable: false,
            autoProcessQueue: true,
            params: {
                'file_upload[_token]': csrf
            },
            init: function () {
                dragOver(this);
            }
        });


        // Show the progress
        DropzoneTarget.on('sending', function () {
            $('.panel-uploadprogress').removeClass('hidden');
        });

        // Removes the file the clear the queue box
        DropzoneTarget.on('complete', function (file) {
            DropzoneTarget.removeFile(file);
        });

        // After we're done uploading, we refresh the page. With a slight delay, or we might miss the latest file.
        DropzoneTarget.on('totaluploadprogress', function (progress) {
            if(progress === 100) {
                window.setTimeout(function () { location.reload(); }, 200);
            }
        });

    }


    /**
     * Initializes the mixin.
     *
     * @static
     * @function run
     * @memberof Bolt.filemanager
     *
     */
    filemanager.init = function () {

        // Always disable autoDiscover
        Dropzone.autoDiscover = false;

        // Bail out if this isn't the filemanager page
        if(document.querySelector('div.file-manager') !== null) {

            // Dropzone events
            bindDropzone();

            // Hook up listeners for custom functions
            addEventListeners();
        }
    };

    // Apply mixin container.
    bolt.filemanager = filemanager;

})(Bolt || {}, jQuery, Dropzone);
