CKEDITOR.plugins.add('editable', {
    init: function(editor) {
        editor.addCommand('inlinesave', {
            exec: function(editor) {
                var data = {};
                var $element = $(editor.element.$);
                data.parameters = $element.data('parameters');
                data[editor.element.$.dataset['content_id']] = editor.getData();
                jQuery.ajax({
                    type: "POST",
                    url: '/editable/save',
                    data: {
                        editcontent: JSON.stringify(data)
                    }
                }).done(function(data, textStatus, jqXHR) {
                    message('Block saved successfully!', 'save-ok', 2000);
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    message('Error saving block!', 'save-error', 3000);
                });
            }
        });

        editor.ui.addButton('EditableSave', {
            label: 'Save',
            command: 'inlinesave'
        });

        function message(msg, cls, duration) {
            $('#ext-editable-popup').addClass(cls).html(msg).fadeIn(200, function() {
                setTimeout(function() {
                    $('#ext-editable-popup').fadeOut(500, function() {
                        $(this).removeClass(cls);
                    })
                }, duration);
            });
        }
    }
});
