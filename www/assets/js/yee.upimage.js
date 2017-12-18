(function ($, Yee, layer) {

    var setImgWH = function (img, url, show_maxwidth, show_maxheight) {

        var imgtemp = new Image();
        imgtemp.onload = function () {
            var width = imgtemp.width;
            var height = imgtemp.height;
            if (width > show_maxwidth) {
                var pt = show_maxwidth / width;//高比宽
                width = show_maxwidth;
                height = height * pt;
            }
            if (height > show_maxheight) {
                var pt = show_maxheight / height;//宽比高
                height = show_maxheight;
                width = width * pt;
            }
            img.height(height);
            img.width(width);
        };
        imgtemp.src = url;
    };

    function UpImage(element, options) {

        options = $.extend({
            catSizes: '',
            catType: 0,
            btnWidth: 150,
            btnHeight: 100,
            strictSize: 0,
            button: null
        }, options || {});
        var bindData = {};

        bindData.catSizes = options.catSizes || null;
        bindData.catType = options.catType || null;
        bindData.strictSize = options.strictSize || null;
        options.bindData = bindData;
        var qem = $(element).hide();
        qem.parent().wrapInner('<div style="display: inline-block; vertical-align: bottom;line-height: 50px;"></div>');
        var boxLayout = qem.parent();
        var btnLayout = $('<div class="up_image_layout"></div>').insertBefore(boxLayout);
        var button = $('<a class="up_image_btn" href="javascript:;" style="display: inline-block;"></a>').appendTo(btnLayout);
        button.width(options.btnWidth).height(options.btnHeight);
        options.button = button;
        var table = $('<table  border="0" cellspacing="0" cellpadding="0"><tr><td style="padding:0px; vertical-align:middle; text-align:center; line-height:0px;"></td></tr></table>').appendTo(button);
        table.width(options.btnWidth).height(options.btnHeight);
        var delBtn = $('<a href="javascript:void(0);"></a>').addClass('up_image_delpic').hide().appendTo(btnLayout);
        delBtn.click(function () {
            table.hide();
            delBtn.hide();
            qem.val('');
        });
        var image = $('<img/ title="请选择上传图片">').appendTo(table.find('td'));
        if (qem.val() == '') {
            table.hide();
            delBtn.hide();
        } else {
            var val = qem.val();
            setImgWH(image, val, options.btnWidth, options.btnHeight);
            image.attr('src', val);
            table.show();
            delBtn.show();
        }
        var bindBox = options.input ? $(options.input) : null;
        qem.on('displayError', function (ev, data) {
            button.addClass('error');
        });
        qem.on('displayDefault displayValid', function (ev, data) {
            button.removeClass('error');
        });
        button.on('mouseenter', function () {
            if (typeof(qem.setDefault) == 'function') {
                qem.setDefault();
            }
        });
        qem.on('completeUpload', function (ev, context) {
            if (!context.status) {
                if (context.error !== '') {
                    layer.alert(context.error);
                }
                return;
            }
            setImgWH(image, context.data.url, options.btnWidth, options.btnHeight);
            image.attr('src', context.data.url);
            table.show();
            delBtn.show();
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
        if (typeof FormData == 'function') {
            button.on('click', function () {
                qem.triggerHandler('upload');
            });
            new Yee.Html5Upload(qem, options);
        } else {
            new Yee.FrameUpload(qem, button, options);
        }
    }

    Yee.extend('input,a,img', 'upimage', UpImage);

})(jQuery, Yee, layer);