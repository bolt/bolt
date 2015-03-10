/**
 * Backbone object for all file actions functionality.
 */
var Moments = Backbone.Model.extend({

    defaults: {
        timeout: undefined,
        wait: 16 * 1000 // 16 seconds
    },

    initialize: function () {
        // Set locale
        moment.locale(bolt.locale.long);

        // Something to update?
        if ($('time.moment').length) {
            this.update();
        }
    },

    update: function () {
        var that = this,
            next;

        // Update all moment fields
        $('time.moment').each(function () {
            $(this).html(moment($(this).attr('datetime')).fromNow());
        });

        // Clear pending timeout
        clearTimeout(this.get('timeout'));

        // Set next call to update
        next = setTimeout(function () {
            that.update();
        }, this.get('wait'));

        // Remember timeout
        this.set('timeout', next);
    }
});
