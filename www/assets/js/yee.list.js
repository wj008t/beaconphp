(function ($, Yee, layer) {

    Yee.extend('*', 'list', function (elem, option) {
        var qem = $(elem);
        option = $.extend({method: 'get', showMsg: false, autoUrl: 0, autoLoad: 0}, option);
        if (option.autoUrl == 1) {
            if (/\/$/.test(String(window.location.pathname))) {
                option.url = window.location.pathname + 'index.json' + window.location.search;
            } else {
                option.url = window.location.pathname + '.json' + window.location.search;
            }
        }
        var last_opts = null;
        var bind = option.bind || null;
        var send = function (opts) {
            //防止误触双击
            opts = $.extend(option, opts || {});
            last_opts = opts;
            var query = opts.url;
            var args = Yee.parseUrl(opts.url);
            if (option.autoUrl == 1 && typeof(window.history.replaceState) != 'undefined') {
                var thisUrl = Yee.toUrl({path: args.path.replace(/\.json$/i, ''), prams: args.prams});
                window.history.replaceState(null, document.title, thisUrl);
            }
            args.path = args.path || window.location.pathname;
            if (qem.triggerHandler('before', [opts]) === false) {
                return;
            }
            $.ajax({
                type: opts.method,
                url: args.path,
                data: args.prams,
                cache: false,
                dataType: 'json',
                success: function (ret) {
                    if (qem.triggerHandler('back', [ret]) === false) {
                        return;
                    }
                    //拉取数据成功
                    if (ret.status === true) {
                        if (opts.showMsg && layer && ret.message && typeof (ret.message) === 'string') {
                            layer.msg(ret.message, {icon: 1, time: 1000});
                        }
                        if (ret.data) {
                            qem.triggerHandler('source', [ret.data, query || ""]);
                            if (bind != null) {
                                $(bind).triggerHandler('source', [ret.data, query || ""]);
                            }
                        }
                        qem.triggerHandler('success', [ret]);
                    }
                    //拉取数据错误
                    if (ret.status === false) {
                        if (opts.showMsg && layer && ret.error && typeof (ret.error) === 'string') {
                            layer.msg(ret.error, {icon: 0, time: 2000});
                        }
                        qem.triggerHandler('error', [ret]);
                    }
                }
            });
        };
        qem.on('source', function (ev, source) {
            var data = qem.triggerHandler('filter', [source]);
            if (data !== void 0) {
                source = data;
            }
            if (source.html !== void 0) {
                qem.html(source.html);
            }
            Yee.update(qem);
            qem.triggerHandler('change', [source]);
        });
        qem.on('load', function (ev, opts) {
            if (opts == null) {
                opts = option;
            }
            else if (typeof(opts) == 'string') {
                opts = {url: opts};
            }
            send(opts);
        });
        qem.on('reload', function (ev, showMsg) {
            var opts = $.extend(last_opts, {showMsg: showMsg});
            send(opts);
        });
        qem.on('reset', function (ev, showMsg) {
            var opts = $.extend(option, {showMsg: showMsg});
            send(opts);
        });
        if (option.url && option.autoLoad) {
            qem.triggerHandler('load');
        }
    });


})(jQuery, Yee, typeof(layer) == 'undefined' ? null : layer);