/**
 * Functions for working with the automagic URI/Slug generation.
 */

var makeuritimeout;

function makeUriAjax(text, contenttypeslug, id, slugfield, fulluri) {
    $.ajax({
        url: bolt.asyncPath + 'makeuri',
        type: 'GET',
        data: {
            title: text,
            contenttypeslug: contenttypeslug,
            id: id,
            fulluri: fulluri
        },
        success: function (uri) {
            $('#' + slugfield).val(uri);
            $('#show-' + slugfield).html(uri);
        },
        error: function () {
            console.log('failed to get an URI');
        }
    });
}

function makeUri(contenttypeslug, id, usesfields, slugfield, fulluri) {
    $(usesfields).each(function () {
        $('#' + this).on('propertychange.bolt input.bolt change.bolt', function () {
            var usesvalue = "";
            $(usesfields).each(function () {
                if ($("#" + this).is("select") && $("#" + this).hasClass("slug-text")) {
                    usesvalue += $("#" + this).val() ?
                        $("#" + this).find("option[value=" + $("#" + this).val() + "]").text() : "";
                }
                else {
                    usesvalue += $("#" + this).val() || "";
                }
                usesvalue += " ";
            });
            clearTimeout(makeuritimeout);
            makeuritimeout = setTimeout(function () {
                makeUriAjax(usesvalue, contenttypeslug, id, slugfield, fulluri);
            }, 200);
        }).trigger('change.bolt');
    });
}

function stopMakeUri(usesfields) {
    $(usesfields).each(function () {
        $('#' + this).unbind('propertychange.bolt input.bolt change.bolt');
    });
    clearTimeout(makeuritimeout);
}
