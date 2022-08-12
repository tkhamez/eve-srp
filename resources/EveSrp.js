
import $ from 'jquery';

$(function () {
    EveSrp.initPopover();
    EveSrp.initDeleteDivision();
    window.setInterval(EveSrp.ping, 300000); // 5 minutes
});

window.EveSrp = {
    initPopover: function () {
        $('[data-toggle="popover"]').popover();
    },

    initDeleteDivision: function () {
        $('body').on('click', '.delete-division', function (evt) {
            const id = $(evt.target).data('id');
            $('#deleteModal input[name="id"]').val(id);
        });
    },

    ping: function () {
        $.get('/ping');
    },
}
