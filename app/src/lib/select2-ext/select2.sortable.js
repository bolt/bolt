(function($){
    $.fn.extend({
        select2Sortable: function(){
            var select = $(this);
            $(select).select2({
                width: '100%',
                createTag: function(params) {
                    return undefined;
                }
            });
            var ul = $(select).next('.select2-container').find('ul.select2-selection__rendered').first();
            ul.sortable({
                items       : 'li:not(.select2-search)',
                tolerance   : 'pointer',
                stop: function() {
                    $($(ul).find('.select2-selection__choice').get().reverse()).each(function() {
                        var id = $(this).attr('title');
                        var option = select.find('option:contains("'+id+'")')[0];
                        $(select).prepend(option);
                    });
                }
            });
        }
    });
}(jQuery));