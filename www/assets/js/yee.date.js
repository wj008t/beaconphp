(function ($, Yee) {
    Yee.extend(':input', 'date', function (element, option) {
        try {
            laydate.render($.extend({elem: element}, option || {}));
        } catch (e) {
        }
    });
})(jQuery, Yee);