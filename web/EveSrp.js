
window.$(function () {
    EveSrp.ready();
});

const EveSrp = (function($) {
    
    return {
        ready: function () {
            $('[data-toggle="popover"]').popover()
        }
    }
})(window.$);
