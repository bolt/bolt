/*!
 * Small jQuery plugin to detect whether or not a form's values have been changed.
 * @see: https://gist.github.com/DrPheltRight/4131266
 * Written by Luke Morton, licensed under MIT. Adapted for Bolt by Bob.
 */
(function ($) {

    $.fn.watchChanges = function () {

        // First, make sure the underlying textareas are updated with the content in the CKEditor fields.
        for (var instanceName in CKEDITOR.instances) {
            CKEDITOR.instances[instanceName].updateElement();
        }

        return this.each(function () {
            $.data(this, 'formHash', $(this).serialize());
        });
    };

    $.fn.hasChanged = function () {
        var hasChanged = false;

        // First, make sure the underlying textareas are updated with the content in the CKEditor fields.
        for(var instanceName in CKEDITOR.instances) {
            CKEDITOR.instances[instanceName].updateElement();
        }

        this.each(function () {
            var formHash = $.data(this, 'formHash');

            if (formHash != null && formHash !== $(this).serialize()) {
                hasChanged = true;
                return false;
            }
        });

        return hasChanged;
    };

}).call(this, jQuery);
