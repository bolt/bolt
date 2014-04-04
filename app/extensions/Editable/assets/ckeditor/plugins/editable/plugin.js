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
                    alert('Block saved successfully');
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('Error saving block');
                });
            }
        });

        editor.ui.addButton('EditableSave', {
            label: 'Save',
            command: 'inlinesave'
        });
    }
});
