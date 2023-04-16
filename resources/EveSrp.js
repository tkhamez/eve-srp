import {Popover} from 'bootstrap';
import Choices from "choices.js";

window.addEventListener('load', () => {
    EveSrp.initPopover();
    EveSrp.initMultiselect();
    EveSrp.initPageAdminDivisions();
    EveSrp.initPageSubmit();
    EveSrp.initPageRequest();
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

    initPageRequest: () => {
        for (const elementId of ['editPayout', 'modifierAmount']) {
            const element = document.getElementById(elementId);
            if (element) {
                element.addEventListener('input', evt => {
                    // noinspection JSUnresolvedVariable
                    let value = evt.target.value.replace(/[^0-9]/g, '');
                    if (value !== '') {
                        value = parseInt(value).toLocaleString('en-US');
                    }
                    evt.target.value = value;
                })
            }
        }
    },

    ping: () => {
        // noinspection JSIgnoredPromiseFromCall
        fetch('/ping');
    },
};
