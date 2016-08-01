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
            var ul = $(select).next('.select2-container').first('ul.select2-selection__rendered');
            ul.sortable({
                items       : 'li:not(.select2-search)',
                tolerance   : 'pointer',
                stop: function() {
                    $($(ul).find('.select2-selection__choice').get().reverse()).each(function() {
                        var id = $(this).data('data').id;
                        var option = select.find('option[value="' + id + '"]')[0];
                        $(select).prepend(option);
                    });
                }
            });
        }
    });
}(jQuery));
