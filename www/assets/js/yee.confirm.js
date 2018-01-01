(function ($, Yee, layer) {
    Yee.extend(':input,form,a', 'confirm', function (element) {
        var qem = $(element);
        qem.data('confirm_prevent', true);

        function send(opt, callback) {
            var args = Yee.parseUrl(opt.url);
            args.path = args.path || window.location.pathname;
            $.ajax({
                type: opt.method,
                url: args.path,
                data: args.prams,
                cache: false,
                dataType: 'json',
                success: function (ret) {
                    //拉取数据成功
                    if (ret.confirm) {
                        callback(ret.confirm);
                    }
                    else {
                        callback();
                    }
                }
            });
        }

        function redo(that, idx) {
            that.data('confirm_prevent', false);
            if (that.is('a')) {
                var p = $('<p style="display: none"></p>').appendTo(that);
                p.trigger('click');
                p.remove();
            }
            else if (that.is('form')) {
                that.trigger('submit');
            }
            else {
                that.trigger('click');
            }
            that.data('confirm_prevent', true);
            layer.close(idx);
        }

        function confirm(ev, elem) {
            var that = $(elem);
            if (!that.data('confirm_prevent')) {
                return true;
            }
            ev.stopImmediatePropagation();
            var tips = that.data('confirm') || '';
            var method = that.data('confirm-method') || 'get';
            var url = that.data('confirm-url') || '';
            if (tips == '' && url == '') {
                return true;
            }
            if (url == '') {
                layer.confirm(tips, function (idx) {
                    redo(that, idx);
                });
            } else {
                send({url: url, method: method}, function (text) {
                    var text = text || tips;
                    if (text == null || text == '') {
                        redo(that, idx);
                    } else {
                        layer.confirm(text, function (idx) {
                            redo(that, idx);
                        });
                    }
                });
            }
            return false;
        }

        if (qem.is('form')) {
            var currentListener = qem[0].onsubmit;
            if (currentListener) {
                qem.bind('submit', function (e) {
                    return currentListener.call(this, e.originalEvent);
                });
                qem[0].onsubmit = null;
            }
            qem.on('submit', function (ev) {
                if (qem.is('.disabled') || qem.is(':disabled')) {
                    return false;
                }
                if (qem.triggerHandler('before_confirm') === false) {
                    return false;
                }
                return confirm(ev, this);
            });
            var typeEvents = ($._data(qem[0], "events") || qem.data("events")).submit;
            typeEvents.unshift(typeEvents.pop());
        } else {
            var currentListener = qem[0].onclick;
            if (currentListener) {
                qem.bind('click', function (e) {
                    return currentListener.call(this, e.originalEvent);
                });
                qem[0].onclick = null;
            }
            qem.on('click', function (ev) {
                if (qem.is('.disabled') || qem.is(':disabled')) {
                    return false;
                }
                if (qem.triggerHandler('before_confirm') === false) {
                    return false;
                }
                return confirm(ev, this);
            });
            var typeEvents = ($._data(qem[0], "events") || qem.data("events")).click;
            typeEvents.unshift(typeEvents.pop());
        }
    });
})(jQuery, Yee, layer);