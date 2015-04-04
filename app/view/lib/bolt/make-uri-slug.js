/**
 * Functions for working with the automagic URI/Slug generation with multipleslug support.
 */

var makeuritimeout;

function makeUriAjax(text, contenttypeslug, id, slugfield, fulluri) {
    $.ajax({
        url: bolt.paths.async + 'makeuri',
        type: 'GET',
        data: {
            title: text,
            contenttypeslug: contenttypeslug,
            id: id,
            slugfield: slugfield,
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

    function makeUri(contenttypeSlug, id, usesFields, slugFieldId, fullUri) {
        $.each(usesFields, function (i, bindField) {
            $('#' + bindField).on('propertychange.bolt input.bolt change.bolt', function () {
                var usesvalue = '';

                $.each(usesFields, function (i, useField) {
                    var field = $('#' + useField);

                    if (field.is('select') && field.hasClass('slug-text')) {
                        usesvalue += field.val() ? field.find('option[value=' + field.val() + ']').text() : '';
                    } else {
                        usesvalue += field.val() || '';
                    }
                    usesvalue += ' ';
                });

                clearTimeout(timeout);
                timeout = setTimeout(
                    function () {
                        makeUriAjax(usesvalue, contenttypeSlug, id, slugFieldId, fullUri);
                    },
                    200
                );
            }).trigger('change.bolt');
        });
    }

function stopMakeUri(usesfields) {
    $(usesfields).each(function () {
        $('#' + this).unbind('propertychange.bolt input.bolt change.bolt');
    });
    clearTimeout(makeuritimeout);
}
