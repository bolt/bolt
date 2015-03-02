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
     */
    function removeErrorClass(field, isCkeditor) {
        $(field).removeClass('error');

        if (isCkeditor) {
            $('#cke_' + field.id).removeClass('cke_error');
        }
    }

    /**
     * Show alertbox
     *
     * @param {string} id - Id of the alertbox
     * @param {string} msg - Message text
     */
    function showAlertbox(id, msg) {
        var alertbox = bolt.data.editcontent.error.alertbox.subst({
            '%NOTICE_ID%': id,
            '%MESSAGE%': msg
        });

        $(alertbox)
            .hide()
            .insertAfter('.page-header')
            .slideDown('fast');
    }

    /**
     * Checks if value is a float
     *
     * @param {string} value - Value of field
     * @returns {string}
     */
    function checkFloat(value) {
        if (value !== '' && !value.match(/^[-+]?[0-9]*[,.]?[0-9]+([eE][-+]?[0-9]+)?$/)) {
            return bolt.data.validation.float;
        } else {
            return '';
        }
    }

    /**
     * Validates a field
     *
     * @param {Object} field - Field element
     * @returns {boolean}
     */
    function validate(field) {
        var hasNativeValidation = typeof field.willValidate !== 'undefined',
            isCkeditor,
            task,
            param,
            value = field.value,
            error,
            label;

        var validates = $(field).data('validate');
        if (validates) {
            for (task in validates) {
                param = validates[task];

                switch (task) {
                    case 'float':
                        error = checkFloat(value);
                        break;

                    case 'required':
                        if (param === true && value === '') {
                            error = bolt.data.validation.required;
                        }
                        break;

                    case 'min':
                        if (value !== '' && Number(value.replace(',', '.')) < param) {
                            error = bolt.data.validation.min.subst({'%MINVAL%': param});
                        }
                        break;

                    case 'max':
                        if (value !== '' && Number(value.replace(',', '.')) > param) {
                            error = bolt.data.validation.max.subst({'%MAXVAL%': param});
                        }
                        break;

                    default:
                        console.log('UNKNOWN VALIDATION' + task + " -> " + param);
                }
                // Stop on first error
                if (error) {
                    // Insert label
                    label = $('label[for="' + field.id + '"]').contents().first().text().trim();
                    error = error.subst({'%FIELDNAME%': label ? label : field.name});

                    if (hasNativeValidation) {
                        field.setCustomValidity(error);
                    } else {
                        field.validity.valid = false;
                    }
                    break;
                } else {
                    if (hasNativeValidation) {
                        field.setCustomValidity('');
                    } else {
                        field.validity.valid = true;
                    }
                }
            }
        } else {
            // Is native browser validation available?
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

            // Special validation for CKEditor fields
            isCkeditor = field.nodeName === 'TEXTAREA' && $(field).hasClass('ckeditor');
            if (isCkeditor) {
                var editor = CKEDITOR.instances[field.id],
                    invalid;

                if (editor) {
                    invalid = editor._.required === true && editor.getData().trim() === '';
                    if (hasNativeValidation) {
                        field.setCustomValidity(invalid ? 'Required' : '');
                    } else {
                        field.validity.valid = !invalid;
                    }
                }
            }
        }

        var noticeID = 'notice--' + field.id;

        // First, remove any existing old notices
        $('#' + noticeID).remove();

        if (field.validity.valid) {
            removeErrorClass(field, isCkeditor);

            return true;
        } else {
            addErrorClass(field, isCkeditor);

            var msg = error || $(field).data('errortext');
            if (!msg) {
                msg = bolt.data.editcontent.error.msg.subst({'%FIELDNAME%': field.name});
            }
            showAlertbox(noticeID, msg);

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
