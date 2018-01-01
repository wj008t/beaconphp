(function ($, Yee, layer) {

    var fromTimeout = true;
    //AJAX提交连接
    Yee.extend('a', 'ajaxlink', function (elem) {
        var qem = $(elem);
        var send = function (url) {
            //防止误触双击
            if (!fromTimeout) {
                return false;
            }
            fromTimeout = false;
            setTimeout(function () {
                fromTimeout = true;
            }, 1000);
            var option = $.extend({
                method: 'get',
            }, qem.data() || {});
            option.url = url;
            var args = Yee.parseUrl(url);
            args.path = args.path || window.location.pathname;
            option.path = args.path;
            option.prams = args.prams;
            option.cache = false;
            if (qem.triggerHandler('before', [option]) === false) {
                return;
            }
            $.ajax({
                type: option.method,
                url: option.path,
                data: option.prams,
                cache: option.cache,
                dataType: 'json',
                success: function (ret) {
                    if (qem.triggerHandler('back', [ret]) === false) {
                        return;
                    }
                    //拉取数据成功
                    if (ret.status === true) {
                        if (qem.triggerHandler('success', [ret]) === false) {
                            return;
                        }
                        if (ret.message && typeof (ret.message) === 'string') {
                            layer.msg(ret.message, {icon: 1, time: 1000});
                        }
                    }
                    //拉取数据错误
                    if (ret.status === false) {
                        if (qem.triggerHandler('error', [ret]) === false) {
                            return;
                        }
                        if (ret.error && typeof (ret.error) === 'string') {
                            layer.msg(ret.error, {icon: 0, time: 2000});
                        }
                    }
                }
            });
        };
        qem.on('send', function (ev, url) {
            send(url);
        });
        qem.on('click', function (ev) {
            var that = $(this);
            if (that.is('.disabled') || that.is(':disabled')) {
                return false;
            }
            if (ev.result === false) {
                return false;
            }
            var url = $(this).data('href') || $(this).attr('href');
            send(url);
            return false;
        });

    });

})(jQuery, Yee, layer);