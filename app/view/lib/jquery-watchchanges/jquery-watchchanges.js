/*!
 * Small jQuery plugin to detect whether or not a form's values have been changed.
 * @see: https://gist.github.com/DrPheltRight/4131266
 * Written by Luke Morton, licensed under MIT. Adapted for Bolt by Bob.
 */
(function ($) {

    $.fn.watchChanges = function () {
        var val;

        // First, make sure the underlying textareas are updated with the content in the CKEditor fields.
        if (typeof CKEDITOR !== 'undefined') {
            for (var instanceName in CKEDITOR.instances) {
                CKEDITOR.instances[instanceName].updateElement();
            }
        }

        $('form#editcontent').find('input, textarea, select').each(function () {
            if (this.name) {
                val = this.type === 'select-multiple' ? JSON.stringify($(this).val()) : $(this).val();
                val = val.replace(/\s/g, '');
                $(this).data('watch', val);
            }
        });

    };

    $.fn.hasChanged = function () {
        var changes = 0,
            val;

        // First, make sure the underlying textareas are updated with the content in the CKEditor fields.
        if (typeof CKEDITOR !== 'undefined') {
            for (var instanceName in CKEDITOR.instances) {
                CKEDITOR.instances[instanceName].updateElement();
            }
        }

        $('form#editcontent').find('input, textarea, select').each(function () {
            if (this.name) {
                val = this.type === 'select-multiple' ? JSON.stringify($(this).val()) : $(this).val();
                val = val.replace(/\s/g, '');
                if ($(this).data('watch') !== val) {
                    changes++;
                }
            }
        });

        return changes > 0;
    };

}).call(this, jQuery);
