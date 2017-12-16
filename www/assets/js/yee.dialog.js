(function ($, Yee, layer) {
    window.openYeeDialog = function (url, title, options, callwin) {
        callwin = callwin || window;
        if (window.top != window) {
            if (window.top.openYeeDialog) {
                window.top.openYeeDialog(url, title, options, callwin);
            }
            return;
        }
        title = title || '网页对话框';
        options = options || {};
        options.width = options.width || 1060;
        options.height = options.height || 720;
        var winW = $(window).width() - 20;
        var winH = $(window).height() - 20;
        options.width = options.width > winW ? winW : options.width;
        options.height = options.height > winH ? winH : options.height;
        var layIndex = layer.open({
            type: 2,
            title: title,
            area: [options.width + 'px', options.height + 'px'],
            fixed: false, //不固定
            maxmin: true,
            content: url,
            end: function () {
                if (callwin.jQuery) {
                    if (options.data !== void 0) {
                        callwin.jQuery(callwin).triggerHandler('closeYDialog', options);
                    } else {
                        callwin.jQuery(callwin).triggerHandler('closeYDialog', options);
                    }
                }
            },
            success: function (layero, index) {
                var dialogWindow = null;
                var iframe = layero.find('iframe');
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
                    dialogWindow.closeYeeDialog = function () {
                        layer.close(layIndex);
                    };
                    if (!(dialogWindow.document.title === null || dialogWindow.document.title === '')) {
                        layer.title(dialogWindow.document.title, index);
                    }
                    //准备好了
                    if (typeof dialogWindow.readyYeeDialog == 'function') {
                        if (options.data !== void 0) {
                            dialogWindow.readyYeeDialog(options.data, callwin);
                        } else {
                            dialogWindow.readyYeeDialog(null, callwin);
                        }
                    }
                }
            }
        });
    };
    Yee.extend('a', 'dialog', function (elem) {
        $(elem).on('click', function (ev) {
            var that = $(this);
            var url = that.data('href') || that.attr('href');
            var title = that.attr('title') || '';
            var option = $.extend({
                height: 720,
                width: 1060
            }, that.data() || {});
            window.openYeeDialog(url, title, option, window);
            ev.preventDefault();
            return false;
        });
    });

})(jQuery, Yee, layer);