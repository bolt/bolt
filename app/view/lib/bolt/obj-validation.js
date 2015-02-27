/**
 * Form validation
 */
bolt.validation = (function () {

    /**
     * Basic legacy validation checking
     * Adapted from  http://www.sitepoint.com/html5-forms-javascript-constraint-validation-api/
     *
     * @param {Object} field - Field element
     * @returns {boolean}
     */
    function legacyValidation(field) {
        var
            valid = true,
            val = field.value,
            type = field.getAttribute('type'),
            chkbox = type === 'checkbox' || type === 'radio',
            required = field.getAttribute('required'),
            minlength = field.getAttribute('minlength'),
            maxlength = field.getAttribute('maxlength'),
            pattern = field.getAttribute('pattern');

        // Disabled fields should not be validated
        if (field.disabled) {
            return valid;
        }

        /* jshint -W126 */

        // value required?
        valid = valid && (!required ||
            (chkbox && field.checked) ||
            (!chkbox && val !== "")
        );

        // minlength or maxlength set?
        valid = valid && (chkbox || (
            (!minlength || val.length >= minlength) &&
            (!maxlength || val.length <= maxlength)
        ));

        /* jshint +W126 */

        // Test pattern
        if (valid && pattern) {
            pattern = new RegExp('^(?:' + pattern + ')$');
            valid = pattern.test(val);
        }

        return valid;
    }

    /**
     * Adds error classes to field
     *
     * @param {Object} field - Field element
     * @param {boolean} isCkeditor - Is a isCkeditor field
     * @returns {boolean}
     */
    function addErrorClass(field, isCkeditor) {
        $(field).addClass('error');

        if (isCkeditor) {
            $('#cke_' + field.id).addClass('cke_error');
        }
    }

    /**
     * Removes error classes from field
     *
     * @param {Object} field - Field element
     * @param {boolean} isCkeditor - Is a isCkeditor field
     * @returns {boolean}
     */
    function removeErrorClass(field, isCkeditor) {
        $(field).removeClass('error');

        if (isCkeditor) {
            $('#cke_' + field.id).removeClass('cke_error');
        }
    }

    /**
     * Validates a field
     *
     * @param {Object} field - Field element
     * @returns {boolean}
     */
    function validate(field) {
        var hasNativeValidation,
            isCkeditor;

        // Is native browser validation available?
        hasNativeValidation = typeof field.willValidate !== 'undefined';
        if (hasNativeValidation) {
            // Native validation available
            if (field.nodeName === 'INPUT' && field.type !== field.getAttribute('type')) {
                // Input type not supported! Use legacy JavaScript validation
                field.setCustomValidity(legacyValidation(field) ? '' : 'error');
            }
            // Native browser check
            field.checkValidity();
        } else {
            // Native validation not available
            field.validity = field.validity || {};
            // Set to result of validation function
            field.validity.valid = legacyValidation(field);

            // If "invalid" events are required, trigger it here
        }

        // Special validation for CKEdito fields
        isCkeditor = field.nodeName === 'TEXTAREA' && $(field).hasClass('ckeditor');
        if (isCkeditor) {
            var editor = CKEDITOR.instances[field.id],
                error;

            if (editor) {
                error = editor._.required === true && editor.getData().trim() === '';
                if (hasNativeValidation) {
                    field.setCustomValidity(error ? 'Required' : '');
                } else {
                    field.validity.valid = error;
                }
            }
        }

        var noticeID = 'notice--' + field.id;

        // First, remove any existing old notices
        $('#' + noticeID).remove();

        if (field.validity.valid) {
            removeErrorClass(field, isCkeditor);

            // Field is valid
            return true;
        } else {
            addErrorClass(field, isCkeditor);

            var msg = $(field).data('errortext');
            if (!msg) {
                msg = bolt.data.editcontent.error.msg.subst({'%FIELDNAME%': field.name});
            }

            var alertbox = bolt.data.editcontent.error.alertbox.subst({
                '%NOTICE_ID%': noticeID,
                '%MESSAGE%': msg
            });
            $(alertbox)
                .hide()
                .insertAfter('.page-header')
                .slideDown('fast');

            // Field is invalid
            return false;
        }
    }

    return {
        /**
         * Validates all inputs of a form
         *
         * @param {Object} form - Form element
         * @returns {boolean}
         */
        run: function (form) {
            var formLength = form.elements.length,
                f,
                field,
                formvalid = true;

            // Loop all fields
            for (f = 0; f < formLength; f++) {
                field = form.elements[f];

                if (field.nodeName !== 'INPUT' && field.nodeName !== 'TEXTAREA' && field.nodeName !== 'SELECT') {
                    continue;
                }

                if (field.nodeName === 'INPUT') {
                    // Trim input values
                    field.value = field.value.trim();
                }

                if (validate(field) === false) {
                    formvalid = false;
                }
            }

            return formvalid;
        }
    };
} ());
