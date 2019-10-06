
import $ from 'jquery';

$(function () {
    initPopover();
    initDeleteDivision();
});

function initPopover() {
    $(function() {
        $('[data-toggle="popover"]').popover();
    });
}

function initDeleteDivision() {
    $('body').on('click', '.delete-division', function (evt) {
        const id = $(evt.target).data('id');
        $('.confirm-delete-division input[name="id"]').val(id);
    });
}
