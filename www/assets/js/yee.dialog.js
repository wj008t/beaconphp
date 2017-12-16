(function ($, Yee) {
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
        dialogIndex++;
        var name = 'yee_dialog_' + dialogIndex;
        var Dialog = $('<div style="overflow:hidden;"></div>').attr('title', title).appendTo(window.document.body);
        var iframe = $('<iframe name="' + name + '" src="javascript:false;" frameborder="false" scrolling="auto" style="overflow-x:hidden;border:none;" width="100%" height="100%" id="' + name + '" ></iframe>').appendTo(Dialog);
        var doFix = function (ev, ui) {
            $('iframe', this).each(function () {
                $('<div class="ui-draggable-iframeFix" style="background: #FFF;"></div>').css({
                    width: '100%', height: '100%',
                    position: 'absolute', opacity: '0.7', overflowX: 'hidden'
                }).css($(this).position()).appendTo($(this).offsetParent());
            });
        };
        var removeFix = function (ev, ui) {
            $("div.ui-draggable-iframeFix").each(function () {
                this.parentNode.removeChild(this);
            });
        };
        options = options || {};
        options.closeText = "";
        options.width = options.width || 1060;
        options.height = options.height || 720;
        var winW = $(window).width() - 20;
        var winH = $(window).height() - 20;
        options.width = options.width > winW ? winW : options.width;
        options.height = options.height > winH ? winH : options.height;
        options.modal = typeof (options.modal) === 'undefined' ? true : options.modal;

        if (typeof(options.modal) == 'string') {
            if (options.modal == 'true' || options.modal == '1') {
                options.modal = true;
            } else {
                options.modal = false;
            }
        }

        options.close = function () {
            if (callwin.jQuery) {
                callwin.jQuery(callwin).triggerHandler('closeTDialog', [options]);
            }
            iframe.remove();
            Dialog.remove();
            Dialog = null;
        };

        options.autoOpen = true;
        options.dragStart = doFix;
        options.dragStop = removeFix;
        options.resizeStart = doFix;
        options.resizeStop = removeFix;
        Dialog.dialog(options);
        Dialog.dialog('moveToTop');
        iframe.load(function () {
            var dialogWindow = this.contentWindow;
            Dialog.window = dialogWindow;
            try {
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
                    Dialog.dialog('close');
                };
                if (!(dialogWindow.document.title === null || dialogWindow.document.title === '')) {
                    Dialog.dialog({title: dialogWindow.document.title});
                }
            } catch (e) {
            }
        });
        iframe.attr('src', url);
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

})(jQuery, Yee);