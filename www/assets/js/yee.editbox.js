(function ($, Yee, layer) {

    //分页插件
    Yee.extend('input,textarea', 'editbox', function (elem) {
        var qem = $(elem);
        var oldval = qem.val();

        var send = function () {
            var option = $.extend({
                method: 'get',
                href: '',
            }, qem.data() || {});
            var val = qem.val();
            var args = Yee.parseUrl(option.href);
            for (var key in args.prams) {
                if (args.prams[key] == '#value#') {
                    args.prams[key] = val;
                }
            }
            $.ajax({
                type: option.method,
                url: args.path,
                data: args.prams,
                cache: false,
                dataType: 'json',
                success: function (ret) {
                    var rt = qem.triggerHandler('back', [ret]);
                    if (rt === false) {
                        return;
                    }
                    //拉取数据成功
                    if (ret.status === true) {
                        if (ret.message && typeof (ret.message) === 'string') {
                            layer.msg(ret.message, {icon: 1, time: 1000});
                        }
                    }
                    //拉取数据错误
                    if (ret.status === false) {
                        if (ret.message && typeof (ret.message) === 'string') {
                            layer.msg(ret.message, {icon: 0, time: 2000});
                        }

                    }
                }
            });
        };

        qem.on('send', function (ev) {
            send();
        });

        qem.on('focus', function () {
            oldval = $(this).val();
        });

        qem.on('blur', function (ev) {
            var that = $(this);
            if (ev.result === false) {
                return false;
            }
            //如果被确认框阻止
            if (that.data('confirm_prevent')) {
                return false;
            }
            var val = that.val();
            if (val == '') {
                that.val(oldval);
                return;
            }
            if (val == oldval) {
                return;
            }
            send();
            return false;
        });
    });

})(jQuery, Yee, layer);
