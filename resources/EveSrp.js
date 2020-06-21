
import $ from 'jquery';

$(function () {
    initPopover();
    initDeleteDivision();
    window.setInterval(ping, 300000); // 5 minutes
});

function initPopover() {
    $('[data-toggle="popover"]').popover();
}

function initDeleteDivision() {
    $('body').on('click', '.delete-division', function (evt) {
        const id = $(evt.target).data('id');
        $('.confirm-delete-division input[name="id"]').val(id);
    });
}

function ping() {
    $.get('/ping');
}
