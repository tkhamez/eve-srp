import {Popover} from 'bootstrap';
import Choices from "choices.js";

window.addEventListener('load', () => {
    EveSrp.initPopover();
    EveSrp.initDeleteDivision();
    EveSrp.initMultiselect();
    EveSrp.initSubmitPage();
    window.setInterval(EveSrp.ping, 300000); // 5 minutes
});

window.EveSrp = {
    initPopover: function () {
        [...document.querySelectorAll('[data-bs-toggle="popover"]')]
            .map(popoverTriggerEl => new Popover(popoverTriggerEl, null));
    },

    initMultiselect: function () {
        document.querySelectorAll('.srp-multiselect').forEach((select) => {
            new Choices(select, {
                removeItemButton: true,
                allowHTML: false,
            });
        });
    },

    initDeleteDivision: function () {
        document.querySelectorAll('.delete-division').forEach((button) => {
            button.addEventListener('click', function (evt) {
                document.querySelector('#deleteModal input[name="id"]').value = evt.target.dataset.srpId;
            })
        });
    },

    initSubmitPage: function () {
        const form = document.getElementById('requestForm');
        if (!form) {
            return;
        }
        form.addEventListener('submit', function () {
            document.getElementById('requestFormSubmit').disabled = true;
        });
    },

    ping: function () {
        // noinspection JSIgnoredPromiseFromCall
        fetch('/ping');
    },
}
