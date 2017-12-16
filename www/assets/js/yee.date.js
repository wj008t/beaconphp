(function ($, Yee) {
    Yee.extend(':input', 'date', function (element, option) {
        laydate.render($.extend({elem: element}, option || {}));
    });
})(jQuery, Yee);