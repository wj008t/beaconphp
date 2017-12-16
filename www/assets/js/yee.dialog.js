(function ($, Yee, layer) {
    var dialogIndex = 0;
    window.openTDialog = function (url, title, options, callwin) {
        callwin = callwin || window;
        if (window.top != window) {
            if (window.top.openTDialog) {
                window.top.openTDialog(url, title, options, callwin);
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
                    callwin.jQuery(callwin).triggerHandler('closeTDialog', options);
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
                    dialogWindow.getTDialogOptions = function () {
                        return options;
                    }
                    dialogWindow.getCallWindow = function () {
                        return callwin;
                    }
                    dialogWindow.emit = function (event, data) {
                        if (callwin.jQuery) {
                            callwin.jQuery(callwin).triggerHandler(event, [data]);
                        }
                    }
                    dialogWindow.closeTDialog = function () {
                        layer.close(layIndex);
                    };
                    if (!(dialogWindow.document.title === null || dialogWindow.document.title === '')) {
                        layer.title(dialogWindow.document.title, index);
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
            window.openTDialog(url, title, option, window);
            ev.preventDefault();
            return false;
        });
    });

})(jQuery, Yee, layer);