import {Popover} from 'bootstrap';
import Choices from "choices.js";

window.addEventListener('load', () => {
    EveSrp.initPopover();
    EveSrp.initMultiselect();
    EveSrp.initCopyText();
    EveSrp.initPageAdminDivisions();
    EveSrp.initPageSubmit();
    EveSrp.initPageRequest();
    EveSrp.initPageAllRequests();
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

    initCopyText: () => {
        document.querySelectorAll('.srp-copy-text').forEach((copyText) => {
            copyText.addEventListener('click', () => {
                if (!navigator.clipboard) {
                    return;
                }
                const icon = copyText.querySelector('.bi');
                navigator.clipboard.writeText(copyText.dataset.text).then(() => {
                    icon.classList.remove('bi-clipboard');
                    icon.classList.add('bi-check2');
                    window.setTimeout(() => {
                        icon.classList.remove('bi-check2');
                        icon.classList.add('bi-clipboard');
                    }, 1000)
                });
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
                    const orgValue = evt.target.value;
                    // noinspection JSUnresolvedVariable
                    const orgPosition = evt.target.selectionStart;

                    let newValue = orgValue.replace(/[^0-9]/g, '');
                    if (newValue !== '') {
                        newValue = parseInt(newValue).toLocaleString('en-US');
                    }

                    // Number of thousand separators of original and newly formatted string
                    const numSep1 = (orgValue.substring(0, orgPosition).match(/,/g) || []).length;
                    const numSep2 = (newValue.substring(0, orgPosition).match(/,/g) || []).length;

                    const newPosition = orgPosition + numSep2 - numSep1;

                    evt.target.value = newValue;
                    evt.target.selectionStart = newPosition;
                    evt.target.selectionEnd = newPosition;
                })
            }
        }
    },

    initPageAllRequests: () => {
        const element = document.getElementById('searchFormReset');
        if (element) {
            element.addEventListener('click', () => {
                document.querySelectorAll('form select, form input').forEach((select) => {
                    select.value = '';
                });
            });
        }
    },

    ping: () => {
        // noinspection JSIgnoredPromiseFromCall
        fetch('/ping');
    },
};
