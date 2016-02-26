/**
 * Initialise CKEditor instances if available.
 *
 * @mixin
 * @namespace Bolt.ckeditor
 *
 * @param {Object} bolt - The Bolt module.
 * @param {Object} $ - jQuery.
 * @param {Object|undefined} cke - CKEDITOR global or undefined.
 */
(function (bolt, $, cke) {
    'use strict';

    /*jshint latedef: nofunc */

    /**
     * Bolt.ckeditor mixin container.
     *
     * @private
     * @type {Object}
     */
    var ckeditor = {};

    /**
     * Initialise all CKEditor instances, if available.
     *
     * @static
     * @function init
     * @memberof Bolt.ckeditor
     */
    ckeditor.init = function () {
        if (cke) {
            cke = ckeditor.initcke(cke);
        }
    };

    /**
     * Update the underlying textareas with the content in the CKEditor fields.
     *
     * @private
     *
     * @static
     * @function update
     * @memberof Bolt.ckeditor
     */
    ckeditor.update = function () {
        // First, make sure
        if (cke) {
            for (var instance in cke.instances) {
                if (cke.instances.hasOwnProperty(instance)) {
                    cke.instances[instance].updateElement();
                }
            }
        }
    };

    /**
     * Initialise a new ckeditor element.
     *
     * @private
     *
     * @static
     * @function add
     * @memberof Bolt.ckeditor
     */
    ckeditor.add = function (element) {
        cke.replace(element);
    };

    /**
     * Initialise CKEditor instances
     *
     * @public
     *
     * @static
     * @function initcke
     * @memberof Bolt.ckeditor
     *
     * @param {Object} cke - Global CKEditor object
     */
    ckeditor.initcke = function(cke) {
        cke.editorConfig = function (config) {
            var key,
                param = $(this.element.$).data('param') || {},
                set = bolt.conf('ckeditor');

            config.language = bolt.conf('ckeditor.lang');
            config.skin = 'boltcke';
            config.resize_enabled = true;
            config.entities = false;
            config.fillEmptyBlocks = false;
            config.extraPlugins = 'codemirror';

            config.toolbar = list(
                [                 { name: 'styles',      items: list( [                 'Format'        ],
                                                                      [set.styles,      'Styles'        ] )}],

                [                 { name: 'basicstyles', items: list( [                 'Bold'          ],
                                                                      [                 'Italic'        ],
                                                                      [set.underline,   'Underline'     ],
                                                                      [set.strike,      'Strike'        ] )}],

                [                 { name: 'paragraph',   items: list( [                 'NumberedList'  ],
                                                                      [                 'BulletedList'  ],
                                                                      [                 'Indent'        ],
                                                                      [                 'Outdent'       ],
                                                                      [set.blockquote,  '|Blockquote'   ] )}],

                [                 { name: 'links',       items: list( [                 'Link'          ],
                                                                      [                 'Unlink'        ],
                                                                      [set.anchor,      '|Anchor'       ] )}],

                [set.subsuper,    { name: 'subsuper',    items: list( [                 'Subscript'     ],
                                                                      [                 'Superscript'   ] )}],

                [set.images,      { name: 'image',       items: list( [                 'Image'         ] )}],

                [set.embed,       { name: 'embed',       items: list( [                 'oembed'        ] )}],

                [set.tables,      { name: 'table',       items: list( [                 'Table'         ] )}],

                [set.ruler,       { name: 'ruler',       items: list( [                 'HorizontalRule'] )}],

                [set.align,       { name: 'align',       items: list( [                 'JustifyLeft'   ],
                                                                      [                 'JustifyCenter' ],
                                                                      [                 'JustifyRight'  ],
                                                                      [                 'JustifyBlock'  ] )}],

                [set.fontcolor,   { name: 'colors',      items: list( [                 'TextColor'     ],
                                                                      [                 'BGColor'       ] )}],

                [set.codesnippet, { name: 'code',        items: list( [                 '|CodeSnippet'  ] )}],

                [                 { name: 'tools',       items: list( [                 'RemoveFormat'  ],
                                                                      [                 'Maximize'      ],
                                                                      [                 '|Source'       ],
                                                                      [set.specialchar, '|SpecialChar'  ] )}]
            );

            if (set.embed) {
                config.extraPlugins += ',oembed,widget';
                config.oembed_maxWidth = '853';
                config.oembed_maxHeight = '480';
            }

            config.height = 250;
            config.autoGrow_onStartup = true;
            config.autoGrow_minHeight = 150;
            config.autoGrow_maxHeight = 400;
            config.autoGrow_bottomSpace = 24;
            config.removePlugins = 'elementspath';
            config.resize_dir = 'vertical';

            if (set.filebrowser) {
                if (set.filebrowser.browseUrl) {
                    config.filebrowserBrowseUrl = set.filebrowser.browseUrl;
                }
                if (set.filebrowser.imageBrowseUrl) {
                    config.filebrowserImageBrowseUrl = set.filebrowser.imageBrowseUrl;
                }
                if (set.filebrowser.uploadUrl) {
                    config.filebrowserUploadUrl = set.filebrowser.uploadUrl;
                }
                if (set.filebrowser.imageUploadUrl) {
                    config.filebrowserImageUploadUrl = set.filebrowser.imageUploadUrl;
                }
            } else {
                config.filebrowserBrowseUrl = '';
                config.filebrowserImageBrowseUrl = '';
                config.filebrowserUploadUrl = '';
                config.filebrowserImageUploadUrl = '';
            }

            config.codemirror = {
                theme: 'default',
                lineNumbers: true,
                lineWrapping: true,
                matchBrackets: true,
                autoCloseTags: true,
                autoCloseBrackets: true,
                enableSearchTools: true,
                enableCodeFolding: true,
                enableCodeFormatting: false,
                autoFormatOnStart: false,
                autoFormatOnUncomment: false,
                autoFormatOnModeChange: false,
                highlightActiveLine: true,
                highlightMatches: true,
                showFormatButton: false,
                showCommentButton: false,
                showUncommentButton: false
            };

            // Parse override settings from config.yml
            for (key in set.ck) {
                if (set.ck.hasOwnProperty(key)) {
                     config[key] = set.ck[key];
                }
            }

            // Set height
            if (param.height) {
                config.height = parseInt(param.height);
                // Adjust autogrow values if heigth is out of range
                config.autoGrow_minHeight = Math.max(config.autoGrow_minHeight, config.height);
                config.autoGrow_maxHeight = Math.max(config.autoGrow_maxHeight, config.height);
            }

            // Parse override settings from field in contenttypes.yml
            for (key in param.ckeditor) {
                if (param.ckeditor.hasOwnProperty(key)) {
                    config[key] = param.ckeditor[key];
                }
            }
        };

        var set = bolt.conf('ckeditor');

        // When 'pasting' from Word (or perhaps other editors too), you'll often
        // get extra `&nbsp;&nbsp;` or `<p>&nbsp;</p>`. Strip these out on paste,
        // if configured to do so with the `allowNbsp` setting.
        if (set.ck.allowNbsp) {
            cke.on('instanceReady', function (ev) {
                ev.editor.on('paste', function (evt) {
                    evt.data.dataValue = evt.data.dataValue.replace(/&nbsp;/g, ' ');
                    evt.data.dataValue = evt.data.dataValue.replace(/<p> <\/p>/g, '');
                }, null, null, 9);
            });
        }

        return cke;
    };

    /**
     * Helper function to create conditional lists for toolbars
     *
     * @private
     *
     * @static
     * @function list
     * @memberof Bolt.ckeditor
     *
     * @param {...Array} items - Either an array with one element or two, first a boolean tells if to add
     */
    function list() {
        var ret = [];

        for (var n in arguments) {
            if (arguments[n].length === 1 || arguments[n][0]) {
                var val = arguments[n][arguments[n].length - 1];
                if (typeof val === 'string' && val.substr(0, 1) === '|') {
                    val = val.substr(1);
                    ret = ret.concat('-');
                }
                ret = ret.concat(val);
            }
        }

        return ret;
    }

    // Apply mixin container
    bolt.ckeditor = ckeditor;

})(Bolt || {}, jQuery, typeof CKEDITOR !== 'undefined' ? CKEDITOR : undefined);
