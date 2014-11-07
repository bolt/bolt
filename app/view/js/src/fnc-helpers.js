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
    console.log('getSelectedItems: ' + aItems);
    return aItems;
}


// basic form validation before submit, adapted from
// http://www.sitepoint.com/html5-forms-javascript-constraint-validation-api/
// =========================================================

function validateContent(form) {

    var formLength = form.elements.length,
        f, field, formvalid = true;

    // loop all fields
    for (f = 0; f < formLength; f++) {
        field = form.elements[f];

        if (field.nodeName !== "INPUT" && field.nodeName !== "TEXTAREA" && field.nodeName !== "SELECT") continue;

		if (field.nodeName === "INPUT"){
			// trim input values
			field.value = field.value.trim();
		}

        // is native browser validation available?
        if (typeof field.willValidate !== "undefined") {
            // native validation available
            if (field.nodeName === "INPUT" && field.type !== field.getAttribute("type")) {
                // input type not supported! Use legacy JavaScript validation
                field.setCustomValidity(LegacyValidation(field) ? "" : "error");
            }
            // native browser check
            field.checkValidity();
        }
        else {
            // native validation not available
            field.validity = field.validity || {};
            // set to result of validation function
            field.validity.valid = LegacyValidation(field);

            // if "invalid" events are required, trigger it here

        }

        var noticeID = field.id + '-notice';

        // first, remove any existing old notices
        $('#'+noticeID).remove();

        if (field.validity.valid) {

            // remove error styles and messages
            $(field).removeClass('error');
        }
        else {
            // style field, show error, etc.
            $(field).addClass('error');

            var msg = $(field).data('errortext') || 'The '+field.name+' field is required or needs to match a pattern';

            $('.page-header').after('<div id='+noticeID+' class="alert alert-danger"><button class="close" data-dismiss="alert">×</button>'+msg+'</div>');

            // form is invalid
            formvalid = false;
        }
    }

    return formvalid;
}


// basic legacy validation checking
function LegacyValidation(field) {
    var
        valid = true,
        val = field.value,
        type = field.getAttribute("type"),
        chkbox = (type === "checkbox" || type === "radio"),
        required = field.getAttribute("required"),
        minlength = field.getAttribute("minlength"),
        maxlength = field.getAttribute("maxlength"),
        pattern = field.getAttribute("pattern");

    // disabled fields should not be validated
    if (field.disabled) return valid;

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

    // test pattern
    if (valid && pattern) {
        pattern = new RegExp('^(?:'+pattern+')$');
        valid = pattern.test(val);
    }

    return valid;
}


// =========================================================
