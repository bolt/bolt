/**
 * Helper to get all selected Items and return Array
 */
function getSelectedItems() {
    var aItems = [];

    $('.dashboardlisting input:checked').each(function () {
        if ($(this).parents('tr').attr('id')) {
            aItems.push($(this).parents('tr').attr('id').substr(5));
        }
    });

    return aItems;
}

/**
 * Basic form validation before submit, adapted from
 * http://www.sitepoint.com/html5-forms-javascript-constraint-validation-api/
*/

// Basic legacy validation checking
function LegacyValidation(field) {
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

function validateContent(form) {

    var formLength = form.elements.length,
        f,
        field,
        formvalid = true,
        hasNativeValidation,
        isCkeditor;

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

        // Is native browser validation available?
        hasNativeValidation = typeof field.willValidate !== 'undefined';
        if (hasNativeValidation) {
            // Native validation available
            if (field.nodeName === 'INPUT' && field.type !== field.getAttribute('type')) {
                // Input type not supported! Use legacy JavaScript validation
                field.setCustomValidity(LegacyValidation(field) ? '' : 'error');
            }
            // Native browser check
            field.checkValidity();
        } else {
            // Native validation not available
            field.validity = field.validity || {};
            // Set to result of validation function
            field.validity.valid = LegacyValidation(field);

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

        var noticeID = field.id + '-notice';

        // First, remove any existing old notices
        $('#' + noticeID).remove();

        if (field.validity.valid) {
            // Remove error styles and messages
            $(field).removeClass('error');

            if (isCkeditor) {
                $('#cke_' + field.id).removeClass('cke_error');
            }
        } else {
            // Style field, show error, etc.
            $(field).addClass('error');

            if (isCkeditor) {
                $('#cke_' + field.id).addClass('cke_error');
            }

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

            // form is invalid
            formvalid = false;
        }
    }

    return formvalid;
}
