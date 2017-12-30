(function ($, Yee) {

    Yee.extend('form', 'searchform', function (elem, option) {
        var qem = $(elem);
        option = $.extend({method: 'get', autoUrl: 1, bind: '#list', url: qem.attr('action')}, option);
        var list = $(option.bind);
        if (option.autoUrl == 1) {
            option.url = window.location.pathname + '.json';
        }
        var initform = function () {
            var args = Yee.parseUrl(window.location.href);
            for (var name in args.prams) {
                var box = qem.find(':input[name="' + name + '"]');
                if (box.length > 0) {
                    if (box.is(':radio') || box.is(':checkbox')) {
                        if (box.val() == args.prams[name]) {
                            box.prop("checked", true);
                        }
                    } else {
                        box.val(args.prams[name]);
                    }
                }
            }
        };
        initform();
        if (list.length > 0) {
            list.on('reset', function () {
                qem.get(0).reset();
                initform();
            });
        }
        qem.on('submit', function (ev) {
            if (ev.result === false) {
                return false;
            }
            var sendData = qem.serialize();
            if (list.length > 0) {
                list.each(function () {
                    $(this).triggerHandler('load', [{url: option.url + '?' + sendData}]);
                });
            }
            return false;
        });
    });
})(jQuery, Yee);
