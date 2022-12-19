import {Popover} from 'bootstrap';
import Choices from "choices.js";

window.addEventListener('load', () => {
    EveSrp.initPopover();
    EveSrp.initMultiselect();
    EveSrp.initPageAdminDivisions();
    EveSrp.initPageSubmit();
    window.setInterval(EveSrp.ping, 300000); // 5 minutes
});

window.EveSrp = {
    initPopover: () => {
        [...document.querySelectorAll('[data-bs-toggle="popover"]')]
            .map(popoverTriggerEl => new Popover(popoverTriggerEl, null));
    },

    initMultiselect: () => {
        document.querySelectorAll('.srp-multiselect').forEach((select) => {
            new Choices(select, {
                removeItemButton: true,
                allowHTML: false,
            });
        });
    },

    initPageAdminDivisions: () => {
        document.querySelectorAll('.delete-division').forEach((button) => {
            button.addEventListener('click', evt => {
                // noinspection JSUnresolvedVariable
                document.querySelector('#deleteModal input[name="id"]').value = evt.target.dataset.srpId;
            })
        });
    },

    initPageSubmit: () => {
        const form = document.getElementById('requestForm');
        if (!form) {
            return;
        }
        form.addEventListener('submit', () => {
            document.getElementById('requestFormSubmit').disabled = true;
        });
    },

    ping: () => {
        // noinspection JSIgnoredPromiseFromCall
        fetch('/ping');
    },
}
