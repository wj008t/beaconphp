(function ($, Yee, layer) {
    window.openYeeDialog = function (url, title, option, callwin, assign) {
        callwin = callwin || window;
        if (window.top != window) {
            if (window.top.openYeeDialog) {
                window.top.openYeeDialog(url, title, option, callwin, assign);
            }
            return;
        }
        title = title || '网页对话框';
        option = option || {};
        option.width = option.width || 1060;
        option.height = option.height || 720;
        var winW = $(window).width() - 20;
        var winH = $(window).height() - 20;
        option.width = option.width > winW ? winW : option.width;
        option.height = option.height > winH ? winH : option.height;
        var iframe = null;
        var layIndex = layer.open({
            type: 2,
            title: title,
            area: [option.width + 'px', option.height + 'px'],
            maxmin: option.maxmin === void 0 ? true : option.maxmin,
            content: url,
            end: function () {
                if (callwin.jQuery) {
                    callwin.jQuery(callwin).triggerHandler('closeYDialog', [option, assign]);
                }
                if (iframe != null) {
                    iframe.remove();
                    iframe = null;
                }
            },
            success: function (layero, index) {
                var dialogWindow = null;
                iframe = layero.find('iframe');
                if (iframe.length > 0) {
                    var winName = iframe[0].name;
                    dialogWindow = window[winName];
                }
                if (dialogWindow) {
                    dialogWindow.emit = function (event, data) {
                        if (callwin.jQuery) {
                            callwin.jQuery(callwin).triggerHandler(event, [data]);
                        }
                    }
                    dialogWindow.trigger = function (event, data) {
                        if (option.elem) {
                            option.elem.triggerHandler(event, [data]);
                        }
                    }
                    dialogWindow.closeYeeDialog = function () {
                        layer.close(layIndex);
                    };
                    if (!(dialogWindow.document.title === null || dialogWindow.document.title === '')) {
                        layer.title(dialogWindow.document.title, index);
                    }
                    //准备好了
                    var readyFunc = function () {
                        if (typeof dialogWindow.readyYeeDialog == 'function') {
                            if (assign !== void 0) {
                                dialogWindow.readyYeeDialog(assign, callwin, option.elem || null);
                            } else {
                                dialogWindow.readyYeeDialog(null, callwin, option.elem || null);
                            }
                        } else {
                            setTimeout(readyFunc, 100);
                        }
                    }
                    readyFunc();
                }
            }
        });
    };
    Yee.extend('a', 'dialog', function (elem) {
        $(elem).on('click', function (ev) {
            var that = $(this);
            if (that.is('.disabled') || that.is(':disabled')) {
                return false;
            }
            var url = that.data('href') || that.attr('href');
            var title = that.attr('title') || '';
            var option = $.extend({
                height: 720,
                width: 1060
            }, that.data() || {});
            option.elem = that;
            window.openYeeDialog(url, title, option, window, option.assign || null);
            ev.preventDefault();
            return false;
        });
    });

})(jQuery, Yee, layer);