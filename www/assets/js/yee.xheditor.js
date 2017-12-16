(function ($, Yee) {
    Yee.extend(':input', 'xheditor', function (elem, options) {
        if (!elem.style.width) {
            elem.style.width = '100%';
        }
        if (!elem.style.height) {
            elem.style.height = '180px';
        }
        $(elem).xheditor(options);
    });
})(jQuery, Yee);