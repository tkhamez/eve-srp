
import $ from 'jquery';

$(function () {
    initDeleteDivision();
    initSemanticComponents();
    initMenu();
});

function initDeleteDivision() {
    $('body').on('click', '.delete-division', function (evt) {
        const id = $(evt.target).data('id');
        $('.confirm-delete-division input[name="id"]').val(id);
    });
    $('.confirm-delete-division')
        .modal('attach events', '.delete-division', 'show')
    ;
}

function initSemanticComponents() {
    $('select.ui.dropdown')
        .dropdown({
        })
    ;
}

function initMenu() {
    $('.ui.menu .ui.dropdown').dropdown({
        on: 'hover'
    });
    $('.ui.stackable.menu').on('click', function (evt) {
        $.Event(evt).stopPropagation();
    });
    $('.ui.stackable.menu .toggle-button').on('click', function (evt) {
        $.Event(evt).stopPropagation();
        const $items = $('.ui.stackable.menu .toggle');
        if ($items.hasClass('mobile-hide')) {
            $items.removeClass('mobile-hide');
        } else {
            $items.addClass('mobile-hide');
        }
    });
    $('body').on('click', function () {
        const $items = $('.ui.stackable.menu .toggle');
        if (! $items.hasClass('mobile-hide')) {
            $items.addClass('mobile-hide');
        }
    });
}
