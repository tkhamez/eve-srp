import {Popover} from 'bootstrap';

window.addEventListener('load', () => {
    EveSrp.initPopover();
    EveSrp.initDeleteDivision();
    window.setInterval(EveSrp.ping, 300000); // 5 minutes
});

window.EveSrp = {
    initPopover: function () {
        [...document.querySelectorAll('[data-bs-toggle="popover"]')]
            .map(popoverTriggerEl => new Popover(popoverTriggerEl, null))
    },

    initDeleteDivision: function () {
        [...document.querySelectorAll('.delete-division')].map(button => {
            button.addEventListener('click', function (evt) {
                document.querySelector('#deleteModal input[name="id"]').value = evt.target.dataset.srpId;
            })
        })
    },

    ping: function () {
        // noinspection JSIgnoredPromiseFromCall
        fetch('/ping');
    },
}
