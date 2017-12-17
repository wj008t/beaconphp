(function ($, Yee, layer) {

    function UpFile(element, options) {
        options = $.extend({
            input: null,
            button: null
        }, options || {});
        var qem = $(element);
        var button = null;
        if (options.button) {
            button = $(options.button);
        } else if (qem.is('input')) {
            button = $('<a  href="javascript:;" class="yee-upfile-btn">选择文件</a>').insertBefore(qem);
            if (qem.is(':visible')) {
                qem.addClass('not-radius-left');
                button.addClass('not-radius-right');
            }
        } else {
            button = qem;
        }
        button.on('click', function () {
            qem.triggerHandler('upload');
        });
        var bindBox = options.input ? $(options.input) : null;
        options.bindData = [];
        qem.on('completeUpload', function (ev, context) {
            if (!context.status) {
                if (context.error !== '') {
                    layer.alert(context.error);
                }
                return;
            }
            if (qem.is('input')) {
                qem.val(context.data.url);
            }
            if (bindBox) {
                bindBox.val(context.data.url);
            }
            if (context.message) {
                layer.msg(context.message);
            }
        });
        new Yee.FrameUpload(qem, button, options);
    }

    Yee.extend('input,a,img', 'upfile', UpFile);

})(jQuery, Yee, layer);